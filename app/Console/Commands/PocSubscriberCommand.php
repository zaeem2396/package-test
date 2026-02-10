<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use LaravelNats\Laravel\Facades\Nats;

/**
 * Subscribes to poc.demo.> (wildcard) and poc.demo.workers (queue group).
 * Stores last message in cache for PoC UI. Run in background: php artisan poc:subscriber
 */
class PocSubscriberCommand extends Command
{
    protected $signature = 'poc:subscriber';

    protected $description = 'Subscribe to poc.demo.> and store last message for PoC (run in background).';

    public function handle(): int
    {
        $this->info('Subscribing to poc.demo.> (wildcard) and poc.demo.workers (queue group). Ctrl+C to stop.');

        Nats::subscribe('poc.demo.>', function ($message): void {
            $subject = $message->getSubject();
            $payload = $message->getDecodedPayload();
            Cache::put('poc_last_pubsub', [
                'subject' => $subject,
                'payload' => $payload,
                'at' => now()->toIso8601String(),
            ], now()->addHours(1));
            $this->line("[poc.demo.>] {$subject}: " . json_encode($payload));
        });

        Nats::subscribe('poc.demo.workers', function ($message): void {
            $payload = $message->getDecodedPayload();
            $this->line('[queue group] poc.demo.workers: ' . json_encode($payload));
        }, 'poc-workers');

        while (true) {
            Nats::process(1.0);
        }
    }
}
