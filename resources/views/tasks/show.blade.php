@extends('layouts.app')

@section('title', $task->title)

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">{{ $task->title }}</h1>
    <p class="text-gray-500">Project: <a href="{{ route('projects.show', $task->project) }}" class="hover:underline">{{ $task->project->name }}</a></p>
    <p class="text-gray-500">Status: {{ $task->status }} · Assignee: {{ $task->assignee?->name ?? 'Unassigned' }}</p>
    @if($task->description)<p class="mt-2">{{ $task->description }}</p>@endif
</div>
@if($task->status !== 'completed')
    <form action="{{ route('tasks.complete', $task) }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Mark complete</button>
    </form>
@endif

<div class="mt-6 p-4 rounded border border-rose-300 bg-rose-50 dark:bg-rose-900/20 dark:border-rose-700 max-w-2xl">
    <h2 class="font-semibold text-rose-900 dark:text-rose-200">APM demo: trigger update error</h2>
    <p class="text-sm mt-1 text-rose-800 dark:text-rose-300">
        Sends a simulated workflow-lock conflict while updating this task so the error appears in Datadog APM.
    </p>
    <form action="{{ route('tasks.demo.fail-update', $task) }}" method="POST" class="mt-3 flex flex-wrap items-end gap-3">
        @csrf
        @method('PUT')
        <div>
            <label for="demo_status" class="block text-xs font-medium mb-1">Status to attempt</label>
            <input
                id="demo_status"
                type="text"
                name="status"
                value="in_progress"
                class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2"
            >
        </div>
        <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded hover:bg-rose-700">
            Trigger update failure
        </button>
    </form>
</div>
@endsection
