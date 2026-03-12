<?php

namespace App\Jobs;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTaskNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Task $task,
        public string $type = 'completed'
    ) {}

    public function handle(): void
    {
        Log::info('Task notification', [
            'task_id' => $this->task->id,
            'type' => $this->type,
            'assignee_id' => $this->task->assignee_id,
        ]);
    }
}
