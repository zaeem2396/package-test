<?php

namespace App\Jobs;

use App\Models\PocDemoLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Always fails. Demonstrates failed_jobs table + DLQ + failed() callback.
 */
class AlwaysFailingDemoJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public string $reason = 'PoC: intentional failure for failed_jobs + DLQ demo'
    ) {}

    public function handle(): void
    {
        PocDemoLog::log('queue_failed', 'nats', ['reason' => $this->reason], false, 'Job threw exception.');
        throw new \RuntimeException($this->reason);
    }

    public function failed(?Throwable $exception): void
    {
        PocDemoLog::log('queue_failed_callback', 'nats', [
            'reason' => $this->reason,
            'exception' => $exception?->getMessage(),
        ], true, 'failed() callback executed; job is in failed_jobs (and DLQ if configured).');
    }
}
