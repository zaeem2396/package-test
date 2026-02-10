<?php

namespace App\Jobs;

use App\Models\PocDemoLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Demonstrates basic dispatch to NATS queue (pass scenario). */
class SimpleDispatchDemoJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $message
    ) {}

    public function handle(): void
    {
        PocDemoLog::log('queue_dispatch', 'nats', [
            'message' => $this->message,
            'processed_at' => now()->toIso8601String(),
        ], true, 'Job processed successfully.');
    }
}
