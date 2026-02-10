@extends('layouts.app')

@section('title', 'Multiple connections — NATS PoC')

@section('content')
    <div class="mb-6">
        <a href="{{ route('nats-poc.index') }}" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">&larr; PoC Home</a>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white mt-2">Multiple connections</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">Publish using <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">Nats::connection('default')</code> or <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">Nats::connection('secondary')</code>. Both typically point to same server in config.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 max-w-xl">
        <form action="{{ route('nats-poc.multiple-connections.publish') }}" method="post" class="space-y-4">
            @csrf
            <div>
                <label for="connection" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Connection</label>
                <select name="connection" id="connection" class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm">
                    <option value="default">default</option>
                    <option value="secondary">secondary</option>
                </select>
            </div>
            <div>
                <label for="subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Subject</label>
                <input type="text" name="subject" id="subject" value="poc.multi.connection" class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm">
            </div>
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Publish</button>
        </form>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
        <h2 class="px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">Logs</h2>
        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
            @forelse($logs as $log)
                <li class="px-4 py-2 flex items-center gap-4">
                    <span class="{{ $log->success ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} font-medium">{{ $log->success ? 'Pass' : 'Fail' }}</span>
                    <span class="text-slate-600 dark:text-slate-300">{{ $log->subject_or_connection }}</span>
                    <span class="text-slate-500 dark:text-slate-400 text-sm">{{ $log->message }}</span>
                    <span class="text-slate-400 text-xs ml-auto">{{ $log->created_at->format('H:i:s') }}</span>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-slate-500 dark:text-slate-400">No logs yet.</li>
            @endforelse
        </ul>
    </div>
@endsection
