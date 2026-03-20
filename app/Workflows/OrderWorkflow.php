<?php

declare(strict_types=1);

namespace App\Workflows;

use Conductor\Laravel\DSL\Workflow;

/**
 * E-commerce order_processing workflow (Conductor schema v2).
 *
 * DSL:
 *   Workflow::define('order_processing')
 *     ->task('inventory_check')
 *     ->task('payment_process')
 *     ->task('fraud_check')
 *     ->task('create_shipping')
 *     ->task('send_notification');
 *
 * payment_process uses Conductor retryCount for simulated payment failures.
 */
final class OrderWorkflow
{
    public const NAME = 'order_processing';

    /**
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        $base = Workflow::define(self::NAME)
            ->description('E-commerce: inventory → payment → fraud → shipping → notification')
            ->inputParameters(['order_id', 'amount', 'user_email'])
            ->ownerEmail('orders@ecommerce-demo.local')
            ->task('inventory_check')
            ->task('payment_process')
            ->task('fraud_check')
            ->task('create_shipping')
            ->task('send_notification')
            ->toArray();

        // Conductor does not auto-pass workflow input to SIMPLE tasks; map explicitly.
        $taskInput = [
            'order_id' => '${workflow.input.order_id}',
            'amount' => '${workflow.input.amount}',
            'user_email' => '${workflow.input.user_email}',
        ];

        foreach ($base['tasks'] as &$t) {
            $t['inputParameters'] = $taskInput;
            if (($t['name'] ?? '') === 'payment_process') {
                $t['retryCount'] = 5;
            }
        }
        unset($t);

        return $base;
    }
}
