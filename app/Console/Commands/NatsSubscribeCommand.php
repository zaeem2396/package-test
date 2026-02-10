<?php

namespace App\Console\Commands;

use App\Models\BroadcastLog;
use Illuminate\Console\Command;
use LaravelNats\Laravel\Facades\Nats;

class NatsSubscribeCommand extends Command
{
    protected $signature = 'nats:subscribe
                            {--subject=app.events : Subject to subscribe to (broadcast log)}
                            {--queue= : Queue group name (optional)}
                            {--connection= : NATS connection name}
                            {--no-ping : Do not subscribe to demo.ping for request/reply}';

    protected $description = 'Subscribe to NATS: log messages to broadcast_log and reply to demo.ping.';

    public function handle(): int
    {
        $subject = $this->option('subject');
        $queue = $this->option('queue');
        $connectionName = $this->option('connection');
        $withPing = ! $this->option('no-ping');

        $nats = $connectionName ? Nats::connection($connectionName) : Nats::connection();
        $nats->connect();

        $this->info("Subscribing to: {$subject}" . ($queue ? " (queue: {$queue})" : ''));
        if ($withPing) {
            $this->info('Subscribing to demo.ping for request/reply.');
        }

        $logCallback = function ($message): void {
            $subj = $message->getSubject();
            $payload = $message->getPayload();
            BroadcastLog::create([
                'subject' => $subj,
                'payload' => is_string($payload) ? $payload : json_encode($payload),
                'reply_to' => $message->getReplyTo(),
                'received_at' => now(),
            ]);
            $this->line("  [{$subj}] logged");
        };

        $pingCallback = function ($message) use ($nats): void {
            $replyTo = $message->getReplyTo();
            if ($replyTo !== null) {
                $nats->publishRaw($replyTo, json_encode(['pong' => true, 'at' => now()->toIso8601String()]));
                $this->line('  [demo.ping] replied with pong');
            }
        };

        if ($queue) {
            $nats->queueSubscribe($subject, $queue, $logCallback);
            if ($withPing) {
                $nats->queueSubscribe('demo.ping', $queue, $pingCallback);
            }
        } else {
            $nats->subscribe($subject, $logCallback);
            if ($withPing) {
                $nats->subscribe('demo.ping', $pingCallback);
            }
        }

        $this->info('Listening (Ctrl+C to stop)...');

        while (true) {
            $nats->process(1.0);
        }
    }
}
