@extends('layouts.app')

@section('title', 'New room')

@section('content')
    <div class="max-w-md">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">New chat room</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">Create a public room. Subject <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">chat.room.{id}</code> will be used for NATS pub/sub.</p>

        <form action="{{ route('chat.room.store') }}" method="post" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Room name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="e.g. General">
                @error('name')
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                    Create room
                </button>
                <a href="{{ route('chat.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
