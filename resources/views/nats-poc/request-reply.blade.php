@extends('layouts.app')

@section('title', 'Request/Reply — NATS PoC')

@section('content')
    <div class="mb-6">
        <a href="{{ route('nats-poc.index') }}" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">&larr; PoC Home</a>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white mt-2">Request/Reply</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">Pass: run <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">php artisan poc:responder</code> then send request. Fail: send request with responder stopped to see timeout.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm"><pre class="whitespace-pre-wrap">{{ session('success') }}</pre></div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 max-w-xl">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Send request</h2>
        <form action="{{ route('nats-poc.request-reply.send') }}" method="post" class="space-y-4">
            @csrf
            <div>
                <label for="subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Subject</label>
                <input type="text" name="subject" id="subject" value="{{ old('subject', 'poc.demo.request') }}" class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm">
            </div>
            <div>
                <label for="payload" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Payload (JSON)</label>
                <textarea name="payload" id="payload" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm">{"ping": true, "at": "{{ now()->toIso8601String() }}"}</textarea>
            </div>
            <div>
                <label for="timeout" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Timeout (seconds)</label>
                <input type="number" name="timeout" id="timeout" value="{{ old('timeout', 3) }}" step="0.5" min="1" max="30" class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm">
            </div>
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Send request</button>
        </form>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
        <h2 class="px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">Request/Reply logs</h2>
        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
            @forelse($logs as $log)
                <li class="px-4 py-2 flex items-center gap-4">
                    <span class="{{ $log->success ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} font-medium">{{ $log->success ? 'Pass' : 'Fail' }}</span>
                    <span class="text-slate-600 dark:text-slate-300">{{ $log->scenario }}</span>
                    <span class="text-slate-500 dark:text-slate-400 text-sm truncate max-w-md">{{ $log->message }}</span>
                    <span class="text-slate-400 text-xs ml-auto">{{ $log->created_at->format('H:i:s') }}</span>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-slate-500 dark:text-slate-400">No logs yet.</li>
            @endforelse
        </ul>
    </div>
@endsection
