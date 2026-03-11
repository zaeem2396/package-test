<?php

namespace App\Http\Controllers;

use App\Events\ProjectUpdated;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        usleep(200_000);

        $projects = Project::withCount('tasks')->with('owner')->latest()->paginate(15);

        return view('projects.index', compact('projects'));
    }

    public function show(Project $project): View
    {
        $project->load(['tasks.assignee', 'owner']);
        return view('projects.show', compact('project'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create([
            ...$validated,
            'owner_id' => $request->user()?->id ?? 1,
        ]);

        event(new ProjectUpdated($project));

        return redirect()->route('projects.show', $project)->with('success', 'Project created.');
    }
}
