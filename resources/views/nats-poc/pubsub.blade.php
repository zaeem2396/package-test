@extends('layouts.app')

@section('title', 'Pub/Sub — NATS PoC')

@section('content')
    <div class="mb-6">
        <a href="{{ route('nats-poc.index') }}" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">&larr; PoC Home</a>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white mt-2">Pub/Sub</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">Publish to a subject. Subscriber (wildcard <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">poc.demo.></code> or queue group) receives it. Run <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">php artisan poc:subscriber</code> to see messages.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6">
            <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Publish</h2>
            <form action="{{ route('nats-poc.pubsub.publish') }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label for="subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Subject</label>
                    <input type="text" name="subject" id="subject" value="{{ old('subject', 'poc.demo.events') }}" class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm">
                </div>
                <div>
                    <label for="payload" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Payload (JSON)</label>
                    <textarea name="payload" id="payload" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm">{"message": "Hello from PoC at {{ now()->toIso8601String() }}"}</textarea>
                </div>
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Publish</button>
            </form>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6">
            <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Last received (from subscriber)</h2>
            @if($last)
                <pre class="text-sm text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-900 rounded-lg p-4 overflow-x-auto">{{ json_encode($last, JSON_PRETTY_PRINT) }}</pre>
            @else
                <p class="text-slate-500 dark:text-slate-400">No message yet. Start <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">php artisan poc:subscriber</code> and publish above.</p>
            @endif
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
        <h2 class="px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">Pub/Sub logs</h2>
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
