<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Workflows\OrderWorkflow;
use PHPUnit\Framework\TestCase;

final class OrderWorkflowDefinitionTest extends TestCase
{
    public function test_definition_name_and_task_sequence(): void
    {
        $def = OrderWorkflow::definition();

        $this->assertSame(OrderWorkflow::NAME, $def['name'] ?? null);
        $tasks = $def['tasks'] ?? [];
        $names = array_map(static fn (array $t): string => (string) ($t['name'] ?? ''), $tasks);

        $this->assertSame(
            ['inventory_check', 'payment_process', 'fraud_check', 'create_shipping', 'send_notification'],
            $names,
        );
    }

    public function test_payment_process_has_retry_count(): void
    {
        $def = OrderWorkflow::definition();
        $tasks = $def['tasks'] ?? [];
        $payment = null;
        foreach ($tasks as $t) {
            if (($t['name'] ?? '') === 'payment_process') {
                $payment = $t;
                break;
            }
        }

        $this->assertNotNull($payment);
        $this->assertSame(5, $payment['retryCount'] ?? null);
    }

    public function test_each_simple_task_maps_workflow_input(): void
    {
        $def = OrderWorkflow::definition();
        foreach ($def['tasks'] ?? [] as $t) {
            $in = $t['inputParameters'] ?? [];
            $this->assertSame('${workflow.input.order_id}', $in['order_id'] ?? null);
            $this->assertSame('${workflow.input.amount}', $in['amount'] ?? null);
            $this->assertSame('${workflow.input.user_email}', $in['user_email'] ?? null);
        }
    }
}
