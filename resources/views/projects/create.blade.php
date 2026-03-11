@extends('layouts.app')

@section('title', 'New Project')

@section('content')
<h1 class="text-2xl font-bold mb-4">New Project</h1>
<form action="{{ route('projects.store') }}" method="POST" class="max-w-md space-y-4">
    @csrf
    <div>
        <label for="name" class="block text-sm font-medium mb-1">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2">
        @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="description" class="block text-sm font-medium mb-1">Description</label>
        <textarea name="description" id="description" rows="3" class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2">{{ old('description') }}</textarea>
    </div>
    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Create</button>
</form>
@endsection
