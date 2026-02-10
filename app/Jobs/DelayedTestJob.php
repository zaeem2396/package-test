<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DelayedTestJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $scheduledFor
    ) {}

    public function handle(): void
    {
        Log::info('DelayedTestJob ran', ['scheduled_for' => $this->scheduledFor]);
    }
}
