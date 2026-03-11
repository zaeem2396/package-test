<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Intentionally slow analytics route: multiple heavy queries + sleep.
     */
    public function analytics(Request $request): View
    {
        usleep(1_200_000); // 1.2s

        $projectStats = Project::query()
            ->select('id', 'name')
            ->withCount('tasks')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'tasks_count' => $p->tasks_count]);

        $statusCounts = Task::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $userCount = User::count();
        $activityCount = Activity::count();

        return view('reports.analytics', compact('projectStats', 'statusCounts', 'userCount', 'activityCount'));
    }

    /**
     * Slow export-style route: simulates heavy data load.
     */
    public function export(Request $request): View
    {
        usleep(800_000); // 800ms

        $tasks = Task::with(['project', 'assignee'])->latest()->take(200)->get();

        return view('reports.export', compact('tasks'));
    }
}
