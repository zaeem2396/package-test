@extends('layouts.app')

@section('title', 'New Task')

@section('content')
<h1 class="text-2xl font-bold mb-4">New Task</h1>
<form action="{{ route('tasks.store') }}" method="POST" class="max-w-md space-y-4">
    @csrf
    <div>
        <label for="project_id" class="block text-sm font-medium mb-1">Project</label>
        <select name="project_id" id="project_id" required class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2">
            @foreach($projects as $p)
                <option value="{{ $p->id }}" {{ (old('project_id', $selectedProjectId) == $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        @error('project_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="title" class="block text-sm font-medium mb-1">Title</label>
        <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2">
        @error('title')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="description" class="block text-sm font-medium mb-1">Description</label>
        <textarea name="description" id="description" rows="3" class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2">{{ old('description') }}</textarea>
    </div>
    <div>
        <label for="assignee_id" class="block text-sm font-medium mb-1">Assignee</label>
        <select name="assignee_id" id="assignee_id" class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2">
            <option value="">Unassigned</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ old('assignee_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Create</button>
</form>
@endsection
