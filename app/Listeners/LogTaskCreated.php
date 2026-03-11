<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Models\Activity;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogTaskCreated implements ShouldQueue
{
    public function handle(TaskCreated $event): void
    {
        Activity::create([
            'user_id' => $event->task->assignee_id,
            'subject_type' => $event->task->getMorphClass(),
            'subject_id' => $event->task->id,
            'action' => 'task.created',
            'properties' => ['title' => $event->task->title],
        ]);
    }
}
