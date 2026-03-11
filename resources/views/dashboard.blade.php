@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Users</p>
            <p class="text-2xl font-semibold">{{ $userCount }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Projects</p>
            <p class="text-2xl font-semibold">{{ $projects->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Recent Tasks</p>
            <p class="text-2xl font-semibold">{{ $recentTasks->count() }}</p>
        </div>
    </div>
    <section>
        <h2 class="text-lg font-semibold mb-2">Recent Projects</h2>
        <ul class="space-y-2">
            @foreach($projects->take(10) as $project)
                <li class="flex justify-between items-center bg-white dark:bg-gray-800 rounded p-3">
                    <a href="{{ route('projects.show', $project) }}" class="hover:underline">{{ $project->name }}</a>
                    <span class="text-sm text-gray-500">{{ $project->tasks_count }} tasks</span>
                </li>
            @endforeach
        </ul>
    </section>
    <section>
        <h2 class="text-lg font-semibold mb-2">Recent Tasks</h2>
        <ul class="space-y-2">
            @foreach($recentTasks->take(10) as $task)
                <li class="flex justify-between items-center bg-white dark:bg-gray-800 rounded p-3">
                    <a href="{{ route('tasks.show', $task) }}" class="hover:underline">{{ $task->title }}</a>
                    <span class="text-sm text-gray-500">{{ $task->status }}</span>
                </li>
            @endforeach
        </ul>
    </section>
</div>
@endsection
