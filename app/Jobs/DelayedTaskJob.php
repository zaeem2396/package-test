<?php

namespace App\Jobs;

use App\Mail\TaskCompletedMail;
use App\Models\NatsActivityLog;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class DelayedTaskJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $taskId
    ) {}

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $task = Task::find($this->taskId);
        if (! $task || ! $task->isPending()) {
            return;
        }

        $task->update(['status' => 'processing']);

        try {
            $result = 'Ran at ' . now()->toIso8601String() . ' (scheduled)';
            $task->update([
                'status' => 'completed',
                'processed_at' => now(),
                'result' => $result,
            ]);
            NatsActivityLog::logTaskCompleted($task->id, $result);
            $this->sendCompletionEmail($task);
        } catch (\Throwable $e) {
            $task->update([
                'status' => 'failed',
                'processed_at' => now(),
                'result' => $e->getMessage(),
            ]);
            NatsActivityLog::logTaskFailed($task->id, $e->getMessage());
            throw $e;
        }
    }

    private function sendCompletionEmail(Task $task): void
    {
        $to = env('NOTIFY_EMAIL');
        if (empty($to) || ! $task->isCompleted()) {
            return;
        }
        try {
            Mail::to($to)->send(new TaskCompletedMail($task));
            NatsActivityLog::logEmailSent($to, 'task_completed', ['task_id' => $task->id]);
        } catch (\Throwable) {
            // Don't fail the job if email fails
        }
    }
}
