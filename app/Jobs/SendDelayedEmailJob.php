<?php

namespace App\Jobs;

use App\Mail\TestEmailMail;
use App\Models\NatsActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDelayedEmailJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Delay in seconds to wait before sending (NATS driver does not support later(), so we sleep in-job).
     */
    public function __construct(
        public string $email,
        public string $body,
        public int $delaySeconds = 0
    ) {}

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        if ($this->delaySeconds > 0) {
            sleep(min($this->delaySeconds, 3600)); // cap at 1 hour to avoid blocking worker too long
        }

        Mail::to($this->email)->send(new TestEmailMail($this->body));
        NatsActivityLog::logEmailSent($this->email, 'delayed', ['delay_seconds' => $this->delaySeconds]);
    }
}
