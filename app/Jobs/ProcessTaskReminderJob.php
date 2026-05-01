<?php

namespace App\Jobs;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTaskReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Task $task
    ) {}

    public function handle(): void
    {
        if ($this->task->status === 'completed') {
            return;
        }
        Log::info('Task reminder processed', [
            'task_id' => $this->task->id,
            'due_at' => $this->task->due_at?->toIso8601String(),
        ]);
    }
}
