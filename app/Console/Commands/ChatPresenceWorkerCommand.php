<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use LaravelNats\Laravel\Facades\Nats;

/**
 * Subscribes to presence.request and replies with current room presence (request/reply pattern).
 * Run in a separate process: php artisan chat:presence-worker
 */
class ChatPresenceWorkerCommand extends Command
{
    protected $signature = 'chat:presence-worker';

    protected $description = 'Run presence request/reply responder (NATS subject: presence.request).';

    public function handle(): int
    {
        $this->info('Subscribing to presence.request (queue: presence). Reply with users from cache.');
        Nats::subscribe('presence.request', function ($message): void {
            $replyTo = $message->getReplyTo();
            if ($replyTo === null) {
                return;
            }
            $payload = $message->getDecodedPayload();
            $roomId = (int) ($payload['room_id'] ?? 0);
            $key = 'chat_presence_room_' . $roomId;
            $users = Cache::get($key, []);
            $names = array_keys($users);
            Nats::publish($replyTo, ['users' => $names]);
        }, 'presence');

        while (true) {
            Nats::process(1.0);
        }
    }
}
