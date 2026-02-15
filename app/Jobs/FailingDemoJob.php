<?php

namespace App\Jobs;

use App\Models\NatsActivity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;
use Throwable;

class FailingDemoJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public $tries = 2;

    public function handle(): void
    {
        throw new RuntimeException('FailingDemoJob: intentional failure for DLQ demo.');
    }

    public function failed(Throwable $exception): void
    {
        NatsActivity::log('job_failed', 'FailingDemoJob failed: ' . $exception->getMessage(), [
            'job' => self::class,
            'exception' => $exception->getMessage(),
        ]);
    }
}
