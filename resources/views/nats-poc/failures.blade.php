@extends('layouts.app')

@section('title', 'Failure scenarios — NATS PoC')

@section('content')
    <div class="mb-6">
        <a href="{{ route('nats-poc.index') }}" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">&larr; PoC Home</a>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white mt-2">Failure scenarios</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">Demonstrate expected failures: request timeout (no responder), job failures (failed_jobs + DLQ), connection errors.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6">
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
            <h3 class="font-semibold text-slate-900 dark:text-white">Request timeout (fail)</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Send request to subject with no responder. Expect timeout exception.</p>
            <form action="{{ route('nats-poc.failures.request-timeout') }}" method="post" class="mt-3">
                @csrf
                <button type="submit" class="w-full px-3 py-2 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700">Trigger timeout</button>
            </form>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
            <h3 class="font-semibold text-slate-900 dark:text-white">Failed job</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Dispatch from <a href="{{ route('nats-poc.queue') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Queue</a> → “Dispatch failing job”. Job goes to failed_jobs + DLQ.</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
        <h2 class="px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">Failure logs</h2>
        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
            @forelse($logs as $log)
                <li class="px-4 py-2 flex items-center gap-4">
                    <span class="text-rose-600 dark:text-rose-400 font-medium">Fail</span>
                    <span class="text-slate-600 dark:text-slate-300">{{ $log->scenario }}</span>
                    <span class="text-slate-500 dark:text-slate-400 text-sm truncate flex-1">{{ $log->message }}</span>
                    <span class="text-slate-400 text-xs">{{ $log->created_at->format('H:i:s') }}</span>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-slate-500 dark:text-slate-400">No failure logs yet.</li>
            @endforelse
        </ul>
    </div>
@endsection
