<?php

declare(strict_types=1);

namespace App\Tasks;

use App\Services\OrderService;
use Conductor\Laravel\Workers\TaskHandler;

/**
 * Simulated fraud screening — ~12% fraud (terminal stop).
 */
final class FraudCheckTask implements TaskHandler
{
    public function __construct(
        private readonly OrderService $orders,
    ) {
    }

    public function taskType(): string
    {
        return 'fraud_check';
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

        $this->orders->updateStep($order, 'fraud_check');

        // 10–15% → use 12%
        if (random_int(1, 100) <= 12) {
            $this->orders->recordEvent($order, 'fraud_check', 'Fraud suspected — workflow stopped', 'error', [
                'score' => random_int(85, 99),
            ]);
            $this->orders->auditLog($order, 'Fraud check FAILED — stopping workflow');
            $this->orders->markFailed($order, 'fraud');

            return [
                'status' => 'FAILED',
                'reasonForIncompletion' => 'Fraud check failed',
                'outputData' => ['fraud' => true],
                'terminal' => true,
            ];
        }

        $this->orders->recordEvent($order, 'fraud_check', 'Fraud check passed', 'success', [
            'score' => random_int(5, 25),
        ]);
        $this->orders->auditLog($order, 'Fraud check PASSED');

        return [
            'step' => 'fraud_check',
            'cleared' => true,
        ];
    }
}
