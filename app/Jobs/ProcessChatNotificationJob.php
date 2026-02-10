<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Demo: NATS queue for chat notifications (e.g. on @mention).
 * Dispatched to NATS connection to show queue + worker pattern.
 */
class ProcessChatNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $roomId,
        public string $roomName,
        public string $authorName,
        public string $body,
        public array $mentionedNames = []
    ) {}

    public function handle(): void
    {
        if (empty($this->mentionedNames)) {
            return;
        }
        Log::info('Chat notification (NATS queue)', [
            'room' => $this->roomName,
            'author' => $this->authorName,
            'mentioned' => $this->mentionedNames,
            'snippet' => \Illuminate\Support\Str::limit($this->body, 80),
        ]);
        // In a real app: send push/email to mentioned users
    }
}
