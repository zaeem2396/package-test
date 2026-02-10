<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class FailingTestJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public function __construct(
        public string $reason = 'Intentional failure'
    ) {}

    public function handle(): void
    {
        throw new RuntimeException($this->reason);
    }

    public function failed(?\Throwable $exception): void
    {
        // Optional: called when job fails finally
    }
}
