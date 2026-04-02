<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

/**
 * Seeds several orders and starts workflows (random failures occur when workers run).
 */
final class DemoOrdersCommand extends Command
{
    protected $signature = 'demo:orders
                            {--count=8 : Number of demo orders to create}';

    protected $description = 'Create demo orders, register order_processing workflow, start executions (run conductor:work separately)';

    public function handle(OrderService $orders): int
    {
        $count = max(1, min(25, (int) $this->option('count')));
        $this->info('Registering workflow definition…');
        $orders->registerWorkflowDefinition();
        $this->info("Creating {$count} orders and starting workflows…");

        $faker = fake();
        for ($i = 0; $i < $count; $i++) {
            $order = $orders->create([
                'amount' => $faker->randomFloat(2, 9.99, 499.99),
                'email' => $faker->unique()->safeEmail(),
            ]);
            $this->line("  Order #{$order->id} — {$order->email} — \${$order->amount} — workflow {$order->workflow_id}");
        }

        $this->newLine();
        $this->warn('Next: run workers so tasks execute:');
        $this->line('  php artisan conductor:work');
        $this->newLine();
        $this->info('Open UI: /orders');

        return self::SUCCESS;
    }
}
