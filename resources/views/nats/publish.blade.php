@extends('layouts.app')

@section('title', 'Broadcast')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Broadcast</h1>
            <p class="mt-1 text-slate-600 dark:text-slate-400">Publish to a NATS subject. Run <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">php artisan nats:subscribe</code> to log messages and enable request/reply.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">← Tasks</a>
    </div>

    <div class="space-y-6">
        <div class="max-w-xl">
            <form action="{{ route('nats.publish.store') }}" method="POST" class="space-y-4 p-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                @csrf
                @if(count($connections ?? []) > 1)
                    <div>
                        <label for="connection" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Connection</label>
                        <select name="connection" id="connection" class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm">
                            @foreach($connections as $c)
                                <option value="{{ $c }}" {{ old('connection') === $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label for="subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Subject</label>
                    <input type="text" name="subject" id="subject" value="{{ old('subject', 'app.events') }}" required
                        class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-slate-900 dark:text-slate-100 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        placeholder="e.g. app.events or orders.created">
                    @error('subject')
                        <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="payload" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Payload (optional JSON)</label>
                    <textarea name="payload" id="payload" rows="3"
                        class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-slate-900 dark:text-slate-100 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-mono text-sm"
                        placeholder='{"key": "value"}'>{{ old('payload') }}</textarea>
                    @error('payload')
                        <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="inline-flex justify-center items-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800">
                        Publish
                    </button>
                    <form action="{{ route('nats.ping') }}" method="POST" class="inline">
                        @csrf
                        @if(count($connections ?? []) > 1)
                            <input type="hidden" name="connection" value="{{ old('connection', $connections[0] ?? '') }}">
                        @endif
                        <button type="submit" class="inline-flex justify-center items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700">
                            Ping (request/reply)
                        </button>
                    </form>
                </div>
            </form>
        </div>

        @if(isset($broadcastLogs) && $broadcastLogs->isNotEmpty())
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <h2 class="px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700">Recent received (from nats:subscribe)</h2>
                <div class="overflow-x-auto max-h-64 overflow-y-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Subject</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Payload</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Received</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($broadcastLogs as $log)
                                <tr class="bg-white dark:bg-slate-800">
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-white">{{ $log->subject }}</td>
                                    <td class="px-4 py-2 text-sm font-mono text-slate-600 dark:text-slate-400 max-w-xs truncate" title="{{ $log->payload }}">{{ Str::limit($log->payload, 80) }}</td>
                                    <td class="px-4 py-2 text-sm text-slate-500 dark:text-slate-400">{{ $log->received_at->format('M j, H:i:s') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <p class="text-sm text-slate-500 dark:text-slate-400">Run <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">php artisan nats:subscribe</code> (or <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">docker compose exec app php artisan nats:subscribe</code>) to see received messages here.</p>
        @endif
    </div>
@endsection
