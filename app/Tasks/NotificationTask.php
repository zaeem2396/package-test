<?php

declare(strict_types=1);

namespace App\Tasks;

use App\Services\OrderService;
use Conductor\Laravel\Workers\TaskHandler;

/**
 * Final step: log completion and mark order completed in DB.
 */
final class NotificationTask implements TaskHandler
{
    public function __construct(
        private readonly OrderService $orders,
    ) {
    }

    public function taskType(): string
    {
        return 'send_notification';
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

        $this->orders->updateStep($order, 'send_notification');
        $this->orders->recordEvent($order, 'send_notification', 'Customer notified (simulated log)', 'success', [
            'channel' => 'email',
            'to' => $order->email,
        ]);
        $this->orders->markCompleted($order);
        $this->orders->auditLog($order, 'Notification sent — order completed');

        return [
            'step' => 'send_notification',
            'notified' => true,
        ];
    }
}
