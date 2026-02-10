@extends('layouts.app')

@section('title', 'NATS Package PoC')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">NATS Package PoC</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">Full-feature demo of <a href="https://github.com/zaeem2396/laravel-nats" class="text-blue-600 dark:text-blue-400 hover:underline">zaeem2396/laravel-nats</a>: pass and fail scenarios.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        <a href="{{ route('nats-poc.pubsub') }}" class="block p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
            <h2 class="font-semibold text-slate-900 dark:text-white">Pub/Sub</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Publish to subject; subscribe with wildcard (<code>poc.demo.></code>) and queue group.</p>
        </a>
        <a href="{{ route('nats-poc.request-reply') }}" class="block p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
            <h2 class="font-semibold text-slate-900 dark:text-white">Request/Reply</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Send request; get reply (pass). Timeout when no responder (fail).</p>
        </a>
        <a href="{{ route('nats-poc.multiple-connections') }}" class="block p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
            <h2 class="font-semibold text-slate-900 dark:text-white">Multiple connections</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Publish via default or secondary connection.</p>
        </a>
        <a href="{{ route('nats-poc.queue') }}" class="block p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
            <h2 class="font-semibold text-slate-900 dark:text-white">Queue driver</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Dispatch, retries/backoff, failed jobs, DLQ, delayed (JetStream <code>later()</code>).</p>
        </a>
        <a href="{{ route('nats-poc.jetstream') }}" class="block p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
            <h2 class="font-semibold text-slate-900 dark:text-white">JetStream</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Streams (create, info, get message, purge), consumers (create, fetch, ack/nak/term).</p>
        </a>
        <a href="{{ route('nats-poc.failures') }}" class="block p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
            <h2 class="font-semibold text-slate-900 dark:text-white">Failure scenarios</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Request timeout, connection errors, job failures.</p>
        </a>
    </div>

    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
        <h2 class="px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">Recent PoC logs</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Scenario</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Subject/Connection</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Pass/Fail</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Message</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($recent as $log)
                        <tr class="bg-white dark:bg-slate-800">
                            <td class="px-4 py-2 text-sm text-slate-900 dark:text-white">{{ $log->scenario }}</td>
                            <td class="px-4 py-2 text-sm text-slate-600 dark:text-slate-300">{{ $log->subject_or_connection }}</td>
                            <td class="px-4 py-2">
                                @if($log->success)
                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">Pass</span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-400 font-medium">Fail</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400 max-w-xs truncate">{{ $log->message }}</td>
                            <td class="px-4 py-2 text-sm text-slate-500 dark:text-slate-400">{{ $log->created_at->format('H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500 dark:text-slate-400">No logs yet. Run scenarios from the sections above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 p-4 rounded-xl bg-slate-100 dark:bg-slate-800/50 text-sm text-slate-600 dark:text-slate-400">
        <p class="font-medium text-slate-700 dark:text-slate-300">Background processes (run in separate terminals):</p>
        <ul class="mt-2 list-disc list-inside space-y-1">
            <li><code class="bg-slate-200 dark:bg-slate-700 px-1 rounded">php artisan poc:subscriber</code> — receive pub/sub messages (wildcard <code>poc.demo.></code>)</li>
            <li><code class="bg-slate-200 dark:bg-slate-700 px-1 rounded">php artisan poc:responder</code> — reply to <code>poc.demo.request</code> (request/reply pass)</li>
            <li><code class="bg-slate-200 dark:bg-slate-700 px-1 rounded">php artisan queue:work nats</code> — process queue jobs (dispatch, retry, failed, delayed)</li>
        </ul>
    </div>
@endsection
