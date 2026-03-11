@extends('layouts.app')

@section('title', 'Export')

@section('content')
<h1 class="text-2xl font-bold mb-6">Task Export</h1>
<p class="text-gray-500 mb-4">This page simulates a slow export (800ms delay).</p>
<table class="w-full border-collapse border border-gray-300 dark:border-gray-600">
    <thead>
        <tr class="bg-gray-100 dark:bg-gray-800">
            <th class="border border-gray-300 dark:border-gray-600 p-2 text-left">ID</th>
            <th class="border border-gray-300 dark:border-gray-600 p-2 text-left">Title</th>
            <th class="border border-gray-300 dark:border-gray-600 p-2 text-left">Project</th>
            <th class="border border-gray-300 dark:border-gray-600 p-2 text-left">Assignee</th>
            <th class="border border-gray-300 dark:border-gray-600 p-2 text-left">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tasks as $task)
            <tr>
                <td class="border border-gray-300 dark:border-gray-600 p-2">{{ $task->id }}</td>
                <td class="border border-gray-300 dark:border-gray-600 p-2">{{ $task->title }}</td>
                <td class="border border-gray-300 dark:border-gray-600 p-2">{{ $task->project->name }}</td>
                <td class="border border-gray-300 dark:border-gray-600 p-2">{{ $task->assignee?->name ?? '-' }}</td>
                <td class="border border-gray-300 dark:border-gray-600 p-2">{{ $task->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<p class="mt-4 text-sm text-gray-500">Total: {{ $tasks->count() }} tasks</p>
@endsection
