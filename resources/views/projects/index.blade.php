@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Projects</h1>
    <a href="{{ route('projects.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">New Project</a>
</div>
<ul class="space-y-3">
    @foreach($projects as $project)
        <li class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex justify-between items-center">
            <div>
                <a href="{{ route('projects.show', $project) }}" class="font-medium hover:underline">{{ $project->name }}</a>
                <p class="text-sm text-gray-500">{{ $project->owner->name }} · {{ $project->tasks_count }} tasks</p>
            </div>
        </li>
    @endforeach
</ul>
<div class="mt-4">{{ $projects->links() }}</div>
@endsection
