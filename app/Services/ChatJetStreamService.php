<?php

namespace App\Services;

use LaravelNats\Core\JetStream\StreamConfig;
use LaravelNats\Laravel\Facades\Nats;

/**
 * Ensures the JetStream stream for chat messages exists.
 * Subject "chat.room.>" captures all room messages for persistence and replay.
 */
class ChatJetStreamService
{
    public const STREAM_NAME = 'CHAT_MESSAGES';

    public const SUBJECTS = ['chat.room.>'];

    public function ensureStream(): bool
    {
        try {
            $js = Nats::jetstream();
            if (! $js->isAvailable()) {
                return false;
            }
            $js->getStreamInfo(self::STREAM_NAME);
            return true;
        } catch (\Throwable) {
            // Stream doesn't exist or JetStream error - try to create
        }

        try {
            $js = Nats::jetstream();
            $config = (new StreamConfig(self::STREAM_NAME, self::SUBJECTS))
                ->withDescription('Chat messages for persistence and replay (PoC)')
                ->withMaxMessages(50_000)
                ->withStorage(StreamConfig::STORAGE_FILE);
            $js->createStream($config);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
