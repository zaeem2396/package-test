<?php

namespace App\Jobs;

use App\Models\PocDemoLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs after delay. Demonstrates Queue::later() with JetStream (when delayed enabled).
 */
class DelayedDemoJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $scheduledFor,
        public int $delaySeconds
    ) {}

    public function handle(): void
    {
        PocDemoLog::log('queue_delayed', 'nats', [
            'scheduled_for' => $this->scheduledFor,
            'delay_seconds' => $this->delaySeconds,
            'processed_at' => now()->toIso8601String(),
        ], true, "Delayed job ran after {$this->delaySeconds}s delay.");
    }
}
