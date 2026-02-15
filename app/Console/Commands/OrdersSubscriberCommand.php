<?php

namespace App\Console\Commands;

use App\Models\NatsActivity;
use Illuminate\Console\Command;
use LaravelNats\Laravel\Facades\Nats;

/**
 * Wildcard subscriber for orders.* – logs all order events (orders.created, orders.paid, orders.shipped).
 * Run: php artisan nats:orders-subscriber
 */
class OrdersSubscriberCommand extends Command
{
    protected $signature = 'nats:orders-subscriber';

    protected $description = 'Subscribe to orders.* (wildcard) and log all order events';

    public function handle(): int
    {
        $this->info('Subscribed to orders.* (wildcard). Logging events... (Ctrl+C to stop)');

        Nats::subscribe('orders.*', function ($message) {
            $subject = $message->getSubject();
            $payload = $message->getDecodedPayload();
            $summary = "[orders.*] {$subject}: " . json_encode($payload);
            $this->line($summary);
            try {
                NatsActivity::log('orders_wildcard', $summary, array_merge(['subject' => $subject], is_array($payload) ? $payload : []));
            } catch (\Throwable $e) {
                // ignore
            }
        });

        while (true) {
            Nats::process(1.0);
        }

        return self::SUCCESS; // unreachable; loop runs until Ctrl+C
    }
}
