<?php

namespace App\Listeners;

use App\Events\ProjectUpdated;
use App\Jobs\SyncProjectStatsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncProjectOnUpdate implements ShouldQueue
{
    public function handle(ProjectUpdated $event): void
    {
        SyncProjectStatsJob::dispatch($event->project);
    }
}
