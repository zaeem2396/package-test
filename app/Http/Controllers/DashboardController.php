<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Dashboard with intentionally slow N+1-style load for demo.
     */
    public function index(): View
    {
        usleep(400_000); // 400ms delay

        $projects = Project::withCount('tasks')->with('owner')->latest()->take(50)->get();
        foreach ($projects as $project) {
            $project->load('tasks.assignee');
        }

        $recentTasks = Task::with(['project', 'assignee'])->latest()->take(30)->get();
        $userCount = User::count();

        return view('dashboard', compact('projects', 'recentTasks', 'userCount'));
    }
}
