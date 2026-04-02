<?php

declare(strict_types=1);

namespace App\Tasks;

use App\Services\OrderService;
use Conductor\Laravel\Workers\TaskHandler;

/**
 * Simulated stock check — 20% chance out of stock (terminal workflow stop).
 */
final class InventoryTask implements TaskHandler
{
    public function __construct(
        private readonly OrderService $orders,
    ) {
    }

    public function taskType(): string
    {
        return 'inventory_check';
    }

    public function handle(array $task): array
    {
        $order = $this->orders->orderFromTask($task);
        if ($order === null) {
            return [
                'status' => 'FAILED',
                'reasonForIncompletion' => 'order_id missing or order not found',
                'outputData' => [],
                'terminal' => true,
            ];
        }

        $this->orders->updateStep($order, 'inventory_check');

        if (random_int(1, 100) <= 20) {
            $this->orders->recordEvent($order, 'inventory_check', 'Out of stock (simulated)', 'error', [
                'result' => 'out_of_stock',
            ]);
            $this->orders->auditLog($order, 'Inventory FAILED — out of stock');
            $this->orders->markFailed($order, 'out_of_stock');

            return [
                'status' => 'FAILED',
                'reasonForIncompletion' => 'Out of stock',
                'outputData' => ['reason' => 'out_of_stock'],
                'terminal' => true,
            ];
        }

        $this->orders->recordEvent($order, 'inventory_check', 'Inventory check passed', 'success', [
            'sku' => 'DEMO-SKU-' . $order->id,
        ]);
        $this->orders->auditLog($order, 'Inventory OK');

        return [
            'step' => 'inventory_check',
            'stock_available' => true,
        ];
    }
}
