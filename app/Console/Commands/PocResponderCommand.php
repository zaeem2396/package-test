<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use LaravelNats\Laravel\Facades\Nats;

/**
 * Request/Reply responder for PoC. Subscribes to poc.demo.request and replies.
 * Run in background: php artisan poc:responder
 */
class PocResponderCommand extends Command
{
    protected $signature = 'poc:responder';

    protected $description = 'Respond to poc.demo.request (request/reply). Run in background for PoC pass scenario.';

    public function handle(): int
    {
        $this->info('Subscribing to poc.demo.request. Reply with echo payload. Ctrl+C to stop.');

        Nats::subscribe('poc.demo.request', function ($message): void {
            $replyTo = $message->getReplyTo();
            if ($replyTo === null) {
                return;
            }
            $payload = $message->getDecodedPayload();
            $reply = array_merge($payload, ['replied_at' => now()->toIso8601String(), 'echo' => true]);
            Nats::publish($replyTo, $reply);
            $this->line('Replied to: ' . json_encode($reply));
        });

        while (true) {
            Nats::process(1.0);
        }
    }
}
