<?php

namespace App\Jobs;

use App\Models\PocDemoLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

/**
 * Fails (tries-1) times then succeeds on the last attempt. Demonstrates retries + backoff.
 */
class RetryableDemoJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public array $backoff = [2, 5, 10];

    public function __construct(
        public string $id
    ) {}

    public function handle(): void
    {
        $attempt = $this->attempts();
        if ($attempt < $this->tries) {
            PocDemoLog::log('queue_retry', 'nats', [
                'job_id' => $this->id,
                'attempt' => $attempt,
                'will_retry' => true,
            ], true, "Attempt {$attempt}: failing on purpose, will retry.");
            throw new RuntimeException("Intentional failure attempt {$attempt}/{$this->tries}");
        }
        PocDemoLog::log('queue_retry', 'nats', [
            'job_id' => $this->id,
            'attempt' => $attempt,
        ], true, "Attempt {$attempt}: succeeded.");
    }
}
