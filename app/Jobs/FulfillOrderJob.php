<?php

namespace App\Jobs;

use App\Models\NatsActivity;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use LaravelNats\Laravel\Facades\Nats;

class FulfillOrderJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function handle(): void
    {
        $this->order->markShipped();

        $payload = [
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'customer_email' => $this->order->customer_email,
            'amount' => (float) $this->order->amount,
            'shipped_at' => $this->order->shipped_at->toIso8601String(),
        ];

        // 4. Publish orders.shipped (event chaining)
        Nats::publish('orders.shipped', $payload);
        $this->log('orders.shipped', $payload);

        // 5. Analytics connection: publish metrics.orders
        Nats::connection('analytics')->publish('metrics.orders', [
            'event' => 'order.shipped',
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'amount' => (float) $this->order->amount,
            'at' => now()->toIso8601String(),
        ]);
        $this->log('metrics.orders (analytics)', ['order_id' => $this->order->id]);
    }

    private function log(string $summary, array $payload): void
    {
        try {
            NatsActivity::log('job_processed', "FulfillOrderJob: {$summary}", $payload);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
