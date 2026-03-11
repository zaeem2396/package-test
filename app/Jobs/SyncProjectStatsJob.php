<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncProjectStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Project $project
    ) {}

    public function handle(): void
    {
        $count = $this->project->tasks()->count();
        $completed = $this->project->tasks()->where('status', 'completed')->count();
        Cache::put("project:{$this->project->id}:stats", [
            'total_tasks' => $count,
            'completed_tasks' => $completed,
        ], now()->addHours(1));
        Log::info('Project stats synced', ['project_id' => $this->project->id]);
    }
}
