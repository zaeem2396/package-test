@extends('layouts.app')

@section('title', 'Tasks')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Tasks</h1>
    <a href="{{ route('tasks.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">New Task</a>
</div>
<form method="GET" class="mb-4 flex gap-2">
    <select name="status" class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2">
        <option value="">All statuses</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In progress</option>
        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
    </select>
    <button type="submit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded">Filter</button>
</form>
<ul class="space-y-3">
    @foreach($tasks as $task)
        <li class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex justify-between items-center">
            <div>
                <a href="{{ route('tasks.show', $task) }}" class="font-medium hover:underline">{{ $task->title }}</a>
                <p class="text-sm text-gray-500">{{ $task->project->name }} · {{ $task->assignee?->name ?? 'Unassigned' }}</p>
            </div>
            <span class="text-sm">{{ $task->status }}</span>
        </li>
    @endforeach
</ul>
<div class="mt-4">{{ $tasks->links() }}</div>
@endsection
