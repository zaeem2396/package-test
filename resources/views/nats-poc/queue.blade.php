@extends('layouts.app')

@section('title', 'Queue driver — NATS PoC')

@section('content')
    <div class="mb-6">
        <a href="{{ route('nats-poc.index') }}" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">&larr; PoC Home</a>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white mt-2">Queue driver</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">Dispatch jobs to NATS, retries/backoff, failed jobs + <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">failed()</code> callback, DLQ, delayed (<code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">later()</code>). Run <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">php artisan queue:work nats</code>.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="mb-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 flex items-center justify-between">
        <span class="text-slate-700 dark:text-slate-300">Failed jobs in DB:</span>
        <span class="font-mono font-semibold">{{ $failedCount }}</span>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
            <h3 class="font-semibold text-slate-900 dark:text-white">Dispatch (pass)</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Simple job on NATS queue.</p>
            <form action="{{ route('nats-poc.queue.dispatch') }}" method="post" class="mt-3">
                @csrf
                <input type="text" name="message" value="PoC at {{ now()->format('H:i:s') }}" class="mb-2 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                <button type="submit" class="w-full px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Dispatch</button>
            </form>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
            <h3 class="font-semibold text-slate-900 dark:text-white">Retries + backoff (pass)</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Job fails 3x then succeeds. Backoff: 2s, 5s, 10s.</p>
            <form action="{{ route('nats-poc.queue.retry') }}" method="post" class="mt-3">
                @csrf
                <button type="submit" class="w-full px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Dispatch retryable job</button>
            </form>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
            <h3 class="font-semibold text-slate-900 dark:text-white">Failed job + DLQ (fail scenario)</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Always fails → failed_jobs + DLQ + <code>failed()</code>.</p>
            <form action="{{ route('nats-poc.queue.failed') }}" method="post" class="mt-3">
                @csrf
                <input type="text" name="reason" value="PoC failed job demo" class="mb-2 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                <button type="submit" class="w-full px-3 py-2 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700">Dispatch failing job</button>
            </form>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
            <h3 class="font-semibold text-slate-900 dark:text-white">Delayed job (JetStream)</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Queue::connection('nats')->later(seconds, job). Requires delayed enabled.</p>
            <form action="{{ route('nats-poc.queue.delayed') }}" method="post" class="mt-3">
                @csrf
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Delay (seconds, 5–120)</label>
                <input type="number" name="delay_seconds" value="10" min="5" max="120" class="mb-2 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                <button type="submit" class="w-full px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Schedule delayed job</button>
            </form>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
        <h2 class="px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">Queue logs</h2>
        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
            @forelse($logs as $log)
                <li class="px-4 py-2 flex items-center gap-4">
                    <span class="{{ $log->success ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} font-medium w-10">{{ $log->success ? 'Pass' : 'Fail' }}</span>
                    <span class="text-slate-600 dark:text-slate-300">{{ $log->scenario }}</span>
                    <span class="text-slate-500 dark:text-slate-400 text-sm truncate flex-1">{{ $log->message }}</span>
                    <span class="text-slate-400 text-xs">{{ $log->created_at->format('H:i:s') }}</span>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-slate-500 dark:text-slate-400">No logs yet.</li>
            @endforelse
        </ul>
    </div>
@endsection
