<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use LaravelNats\Core\JetStream\StreamConfig;
use LaravelNats\Laravel\Facades\Nats;

/**
 * Ensures the ORDERS JetStream stream exists for event persistence (orders.created, orders.shipped, etc.).
 * Run: php artisan nats:setup-orders-stream
 */
class SetupOrdersStreamCommand extends Command
{
    protected $signature = 'nats:setup-orders-stream';

    protected $description = 'Create ORDERS JetStream stream for orders.* event persistence';

    public function handle(): int
    {
        $js = Nats::jetstream();

        try {
            $info = $js->getStreamInfo('ORDERS');
            $this->info('JetStream stream ORDERS already exists.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            // stream does not exist, create it
        }

        $config = (new StreamConfig('ORDERS', ['orders.>']))
            ->withDescription('Order events for PoC: orders.created, orders.shipped, etc.')
            ->withMaxMessages(50_000)
            ->withMaxBytes(10 * 1024 * 1024) // 10MB
            ->withStorage(StreamConfig::STORAGE_FILE);

        try {
            $js->createStream($config);
            $this->info('Created JetStream stream ORDERS with subjects orders.>.');
        } catch (\Throwable $e) {
            $this->error('Failed to create stream: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
