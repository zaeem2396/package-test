@extends('layouts.app')

@section('title', 'Delayed Job')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Schedule Delayed Job</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">Queue a job to run after a number of seconds. Requires a worker.</p>
    </div>

    <div class="max-w-xl">
        <form action="{{ route('nats.delayed.store') }}" method="POST" class="space-y-4 p-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            @csrf
            <div>
                <label for="message" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Message</label>
                <input type="text" name="message" id="message" value="{{ old('message', 'Delayed task ran') }}" required
                    class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-slate-900 dark:text-slate-100 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                @error('message')
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="delay_seconds" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Delay (seconds)</label>
                <input type="number" name="delay_seconds" id="delay_seconds" value="{{ old('delay_seconds', 30) }}" min="1" max="86400"
                    class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-slate-900 dark:text-slate-100 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                @error('delay_seconds')
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800">
                Schedule Job
            </button>
        </form>
    </div>
@endsection
