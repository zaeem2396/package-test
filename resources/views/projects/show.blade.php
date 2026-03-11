@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
    <p class="text-gray-500 dark:text-gray-400">Owner: {{ $project->owner->name }}</p>
    @if($project->description)<p class="mt-2">{{ $project->description }}</p>@endif
</div>
<div class="mb-4">
    <a href="{{ route('tasks.create') }}?project_id={{ $project->id }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Add Task</a>
</div>
<ul class="space-y-2">
    @foreach($project->tasks as $task)
        <li class="bg-white dark:bg-gray-800 rounded p-3 flex justify-between items-center">
            <a href="{{ route('tasks.show', $task) }}" class="hover:underline">{{ $task->title }}</a>
            <span class="text-sm">{{ $task->status }}</span>
            @if($task->status !== 'completed')
                <form action="{{ route('tasks.complete', $task) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-green-600 hover:underline">Complete</button>
                </form>
            @endif
        </li>
    @endforeach
</ul>
@endsection
