<?php

namespace App\Http\Controllers;

use App\Events\ProjectUpdated;
use App\Models\Project;
use App\Models\User;
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

    public function create(): View
    {
        return view('projects.create');
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

        $ownerId = $request->user()?->id ?? User::query()->value('id');
        if ($ownerId === null) {
            return redirect()->back()
                ->withInput($request->only('name', 'description'))
                ->withErrors(['owner' => 'Cannot create a project: no authenticated user and no users in the system.']);
        }

        $project = Project::create([
            ...$validated,
            'owner_id' => $ownerId,
        ]);

        event(new ProjectUpdated($project));

        return redirect()->route('projects.show', $project)->with('success', 'Project created.');
    }
}
