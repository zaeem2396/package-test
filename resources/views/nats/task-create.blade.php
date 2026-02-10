@extends('layouts.app')

@section('title', 'New task')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">New task</h1>
            <p class="mt-1 text-slate-600 dark:text-slate-400">Create a task to run now (queued) or at a time in the future (delayed).</p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">← Tasks</a>
    </div>

    <form action="{{ route('tasks.store') }}" method="POST" class="max-w-xl space-y-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6">
        @csrf

        <div>
            <label for="message" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Message</label>
            <input type="text" name="message" id="message" value="{{ old('message') }}" required maxlength="500"
                   class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   placeholder="e.g. Send report">
            @error('message')
                <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <span class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">When to run</span>
            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="radio" name="run_type" value="now" {{ old('run_type', 'now') === 'now' ? 'checked' : '' }} class="rounded-full border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-slate-700 dark:text-slate-300">Run now</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="radio" name="run_type" value="later" {{ old('run_type') === 'later' ? 'checked' : '' }} class="rounded-full border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-slate-700 dark:text-slate-300">Run in</span>
                    <input type="number" name="delay_seconds" id="delay_seconds" value="{{ old('delay_seconds', 5) }}" min="1" max="86400"
                           class="w-20 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm">
                    <span class="text-slate-600 dark:text-slate-400">seconds</span>
                </label>
            </div>
            @error('delay_seconds')
                <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                Create task
            </button>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Cancel
            </a>
        </div>
    </form>
@endsection
