<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Workflows\OrderWorkflow;
use Conductor\Exceptions\ConductorException;
use Conductor\Laravel\Facades\Conductor;
use Illuminate\Support\Facades\Log;

/**
 * Creates orders, registers workflow metadata, starts executions, and records orchestration events.
 */
final class OrderService
{
    /**
     * Register or update workflow definition in Conductor (idempotent for demos).
     */
    public function registerWorkflowDefinition(): void
    {
        $def = OrderWorkflow::definition();
        try {
            Conductor::workflow()->registerWorkflowDefinition($def);
        } catch (ConductorException) {
            Conductor::workflow()->updateWorkflowDefinition([$def]);
        }
    }

    /**
     * @param  array{amount: float|int|string, email: string}  $data
     */
    public function create(array $data): Order
    {
        $this->registerWorkflowDefinition();

        $order = Order::query()->create([
            'amount' => $data['amount'],
            'email' => $data['email'],
            'status' => 'processing',
            'current_step' => 'inventory_check',
            'retry_count' => 0,
        ]);

        $this->recordEvent($order, 'order_created', 'Order created; starting workflow', 'info', [
            'amount' => (float) $order->amount,
        ]);

        $workflowId = Conductor::workflow()->start(OrderWorkflow::NAME, [
            'order_id' => $order->id,
            'amount' => (float) $order->amount,
            'user_email' => $order->email,
        ]);

        $order->update(['workflow_id' => $workflowId]);
        $this->recordEvent($order, 'workflow_started', 'Workflow execution started', 'info', [
            'workflow_id' => $workflowId,
        ]);
        $this->logLine($order, 'Order created → workflow started');

        return $order->fresh();
    }

    public function orderFromTask(array $task): ?Order
    {
        $input = $task['inputData'] ?? [];
        $id = (int) ($input['order_id'] ?? 0);

        return $id > 0 ? Order::query()->find($id) : null;
    }

    public function updateStep(Order $order, string $step): void
    {
        $order->update(['current_step' => $step]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function recordEvent(
        Order $order,
        string $stepKey,
        string $message,
        string $level = 'info',
        array $context = [],
    ): void {
        OrderEvent::query()->create([
            'order_id' => $order->id,
            'step_key' => $stepKey,
            'message' => $message,
            'level' => $level,
            'context' => $context === [] ? null : $context,
        ]);
    }

    public function markFailed(Order $order, string $reason): void
    {
        $order->update([
            'status' => 'failed',
            'current_step' => null,
        ]);
        $this->recordEvent($order, 'failed', 'Order failed: ' . $reason, 'error', ['reason' => $reason]);
        $this->logLine($order, 'FAILED — ' . $reason);
    }

    public function markCompleted(Order $order): void
    {
        $order->update([
            'status' => 'completed',
            'current_step' => null,
        ]);
        $this->recordEvent($order, 'completed', 'Order completed successfully', 'success');
        $this->logLine($order, 'Completed');
    }

    public function incrementRetryCount(Order $order): void
    {
        $order->increment('retry_count');
    }

    /** Application log line for demos (demo:orders + workers). */
    public function auditLog(Order $order, string $message): void
    {
        $line = "Order #{$order->id} → {$message}";
        Log::info($line);
    }

    private function logLine(Order $order, string $message): void
    {
        $this->auditLog($order, $message);
    }
}
