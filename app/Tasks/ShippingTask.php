<?php

declare(strict_types=1);

namespace App\Tasks;

use App\Services\OrderService;
use Conductor\Laravel\Workers\TaskHandler;

/**
 * Creates a simulated shipping label / tracking id.
 */
final class ShippingTask implements TaskHandler
{
    public function __construct(
        private readonly OrderService $orders,
    ) {
    }

    public function taskType(): string
    {
        return 'create_shipping';
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

        $this->orders->updateStep($order, 'create_shipping');
        $tracking = 'TRK-' . strtoupper(bin2hex(random_bytes(4))) . '-' . $order->id;

        $this->orders->recordEvent($order, 'create_shipping', 'Shipping label created', 'success', [
            'tracking_id' => $tracking,
        ]);
        $this->orders->auditLog($order, 'Shipping created — ' . $tracking);

        return [
            'step' => 'create_shipping',
            'tracking_id' => $tracking,
            'carrier' => 'DemoCarrier',
        ];
    }
}
