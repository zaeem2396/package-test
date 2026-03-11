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
@endsection
