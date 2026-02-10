<?php

namespace App\Console\Commands;

use App\Services\ChatJetStreamService;
use Illuminate\Console\Command;

class ChatSetupJetStreamCommand extends Command
{
    protected $signature = 'chat:setup-jetstream';

    protected $description = 'Create JetStream stream CHAT_MESSAGES for chat (subject chat.room.>). Required for chat PoC.';

    public function handle(ChatJetStreamService $service): int
    {
        if ($service->ensureStream()) {
            $this->info('JetStream stream ' . ChatJetStreamService::STREAM_NAME . ' is ready.');
            return self::SUCCESS;
        }
        $this->warn('JetStream is not available or stream creation failed. Start NATS with --jetstream and run again.');
        return self::FAILURE;
    }
}
