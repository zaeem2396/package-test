<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTestJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $message,
        public array $payload = []
    ) {}

    public function handle(): void
    {
        Log::info('ProcessTestJob handled', [
            'message' => $this->message,
            'payload' => $this->payload,
        ]);
    }
}
