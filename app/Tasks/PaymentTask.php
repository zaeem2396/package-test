<?php

declare(strict_types=1);

namespace App\Tasks;

use App\Services\OrderService;
use Conductor\Laravel\Workers\TaskHandler;

/**
 * Simulated payment — 30% transient failure; Conductor retries via workflow retryCount.
 */
final class PaymentTask implements TaskHandler
{
    public function __construct(
        private readonly OrderService $orders,
    ) {
    }

    public function taskType(): string
    {
        return 'payment_process';
    }

    public function handle(array $task): array
    {
        $order = $this->orders->orderFromTask($task);
        if ($order === null) {
            return [
                'status' => 'FAILED',
                'reasonForIncompletion' => 'order not found',
                'outputData' => [],
                'terminal' => true,
            ];
        }

        $this->orders->updateStep($order, 'payment_process');

        if (random_int(1, 100) <= 30) {
            $this->orders->incrementRetryCount($order);
            $attempt = $order->fresh()->retry_count;
            $this->orders->recordEvent($order, 'payment_process', 'Payment FAILED (simulated; retrying via Conductor)', 'warning', [
                'attempt' => $attempt,
            ]);
            $this->orders->auditLog($order, 'Payment FAILED (retrying)');

            return [
                'status' => 'FAILED',
                'reasonForIncompletion' => 'Payment declined (simulated)',
                'outputData' => ['recoverable' => true],
                'terminal' => false,
            ];
        }

        $this->orders->recordEvent($order, 'payment_process', 'Payment captured successfully', 'success', [
            'transaction_ref' => 'txn_' . bin2hex(random_bytes(6)),
        ]);
        $this->orders->auditLog($order, 'Payment SUCCESS');

        return [
            'step' => 'payment_process',
            'paid' => true,
        ];
    }
}
