<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Jobs\SendTaskNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyTaskCompleted implements ShouldQueue
{
    public function handle(TaskCompleted $event): void
    {
        if ($event->task->assignee_id) {
            SendTaskNotificationJob::dispatch($event->task, 'completed');
        }
    }
}
