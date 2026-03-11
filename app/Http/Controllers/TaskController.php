<?php

namespace App\Http\Controllers;

use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Jobs\ProcessTaskReminderJob;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function create(Request $request): View
    {
        usleep(150_000);
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $users = User::orderBy('name')->get(['id', 'name']);
        $selectedProjectId = $request->get('project_id');
        return view('tasks.create', compact('projects', 'users', 'selectedProjectId'));
    }

    public function index(Request $request): View
    {
        usleep(300_000);

        $tasks = Task::with(['project', 'assignee'])
            ->when($request->get('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(20);

        return view('tasks.index', compact('tasks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $task = Task::create([
            ...$validated,
            'status' => 'pending',
        ]);

        event(new TaskCreated($task));

        ProcessTaskReminderJob::dispatch($task)->delay(now()->addMinutes(5));

        return redirect()->route('tasks.show', $task)->with('success', 'Task created.');
    }

    public function show(Task $task): View
    {
        $task->load(['project.owner', 'assignee']);
        return view('tasks.show', compact('task'));
    }

    public function complete(Task $task): RedirectResponse
    {
        $task->update(['status' => 'completed', 'completed_at' => now()]);
        event(new TaskCompleted($task));
        return redirect()->back()->with('success', 'Task marked complete.');
    }
}
