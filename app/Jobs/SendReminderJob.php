<?php

namespace App\Jobs;

use App\Models\NatsActivity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class SendReminderJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public function __construct(
        public string $message = 'Reminder'
    ) {}

    public function handle(): void
    {
        NatsActivity::log('job_processed', "SendReminderJob completed: \"{$this->message}\"", [
            'job' => self::class,
            'message' => $this->message,
        ]);
    }
}
