<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTaskReminderJob;
use App\Models\Task;
use Illuminate\Console\Command;

class TestQueueCommand extends Command
{
    protected $signature = 'taskboard:test-queue';

    protected $description = 'Dispatch a test job to the queue (for verifying queue worker).';

    public function handle(): int
    {
        $task = Task::query()->first();
        if (! $task) {
            $this->warn('No tasks in database. Run db:seed first.');
            return self::FAILURE;
        }
        ProcessTaskReminderJob::dispatch($task);
        $this->info('Dispatched ProcessTaskReminderJob for task #' . $task->id);
        return self::SUCCESS;
    }
}
