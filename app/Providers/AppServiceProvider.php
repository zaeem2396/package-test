<?php

namespace App\Providers;

use App\Events\ProjectUpdated;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Listeners\LogTaskCreated;
use App\Listeners\NotifyTaskCompleted;
use App\Listeners\SyncProjectOnUpdate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(TaskCreated::class, LogTaskCreated::class);
        Event::listen(TaskCompleted::class, NotifyTaskCompleted::class);
        Event::listen(ProjectUpdated::class, SyncProjectOnUpdate::class);
    }
}
