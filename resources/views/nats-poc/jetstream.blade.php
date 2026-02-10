@extends('layouts.app')

@section('title', 'JetStream — NATS PoC')

@section('content')
    <div class="mb-6">
        <a href="{{ route('nats-poc.index') }}" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">&larr; PoC Home</a>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white mt-2">JetStream</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">Streams (create, info, get message, purge), consumers (create, fetch, ack/nak/term). NATS must run with <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">--jetstream</code>.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm"><pre class="whitespace-pre-wrap text-xs overflow-x-auto">{{ session('success') }}</pre></div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="mb-6 flex items-center gap-4">
        @if($available)
            <span class="flex h-3 w-3 rounded-full bg-emerald-500"></span>
            <span class="font-medium text-slate-900 dark:text-white">JetStream available</span>
        @else
            <span class="flex h-3 w-3 rounded-full bg-rose-500"></span>
            <span class="font-medium text-slate-900 dark:text-white">JetStream not available</span>
        @endif
        @if($accountInfo)
            <span class="text-sm text-slate-500 dark:text-slate-400">Account: {{ json_encode($accountInfo) }}</span>
        @endif
    </div>

    @if($available)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6">
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <h3 class="font-semibold text-slate-900 dark:text-white">Create stream</h3>
                <form action="{{ route('nats-poc.jetstream.create-stream') }}" method="post" class="mt-2 space-y-2">
                    @csrf
                    <input type="text" name="stream" value="POC_DEMO" placeholder="Stream name" class="w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                    <input type="text" name="subjects" value="poc.demo.>" placeholder="Subjects (comma)" class="w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                    <button type="submit" class="w-full px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Create</button>
                </form>
            </div>
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <h3 class="font-semibold text-slate-900 dark:text-white">Stream info</h3>
                <form action="{{ route('nats-poc.jetstream.stream-info') }}" method="post" class="mt-2">
                    @csrf
                    <input type="text" name="stream" value="POC_DEMO" class="mb-2 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                    <button type="submit" class="w-full px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Get info</button>
                </form>
            </div>
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <h3 class="font-semibold text-slate-900 dark:text-white">Publish (to stream subject)</h3>
                <form action="{{ route('nats-poc.jetstream.publish') }}" method="post" class="mt-2">
                    @csrf
                    <input type="text" name="subject" value="poc.demo.events" class="mb-2 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                    <button type="submit" class="w-full px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Publish</button>
                </form>
            </div>
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <h3 class="font-semibold text-slate-900 dark:text-white">Get message by sequence</h3>
                <form action="{{ route('nats-poc.jetstream.get-message') }}" method="post" class="mt-2">
                    @csrf
                    <input type="text" name="stream" value="POC_DEMO" class="mb-2 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                    <input type="number" name="sequence" value="1" min="1" class="mb-2 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                    <button type="submit" class="w-full px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Get message</button>
                </form>
            </div>
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <h3 class="font-semibold text-slate-900 dark:text-white">Purge stream</h3>
                <form action="{{ route('nats-poc.jetstream.purge') }}" method="post" class="mt-2" onsubmit="return confirm('Purge all messages?');">
                    @csrf
                    <input type="text" name="stream" value="POC_DEMO" class="mb-2 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                    <button type="submit" class="w-full px-3 py-2 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700">Purge</button>
                </form>
            </div>
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <h3 class="font-semibold text-slate-900 dark:text-white">Create consumer</h3>
                <form action="{{ route('nats-poc.jetstream.create-consumer') }}" method="post" class="mt-2">
                    @csrf
                    <input type="text" name="stream" value="POC_DEMO" class="mb-2 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                    <input type="text" name="consumer" value="poc_demo_consumer" class="mb-2 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                    <button type="submit" class="w-full px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Create consumer</button>
                </form>
            </div>
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 sm:col-span-2">
                <h3 class="font-semibold text-slate-900 dark:text-white">Fetch + ack/nak/term</h3>
                <form action="{{ route('nats-poc.jetstream.fetch-ack') }}" method="post" class="mt-2 flex flex-wrap gap-2 items-end">
                    @csrf
                    <input type="text" name="stream" value="POC_DEMO" placeholder="Stream" class="rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm w-32">
                    <input type="text" name="consumer" value="poc_demo_consumer" placeholder="Consumer" class="rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm w-40">
                    <select name="action" class="rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                        <option value="ack">ack</option>
                        <option value="nak">nak</option>
                        <option value="term">term</option>
                    </select>
                    <button type="submit" class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Fetch & apply</button>
                </form>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden mb-6">
            <h2 class="px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">Streams</h2>
            <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($streams as $s)
                    <li class="px-4 py-2 text-slate-700 dark:text-slate-300">{{ is_object($s) ? $s->getName() : ($s['config']['name'] ?? $s) }}</li>
                @empty
                    <li class="px-4 py-4 text-slate-500 dark:text-slate-400">No streams or unable to list.</li>
                @endforelse
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
        <h2 class="px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">JetStream logs</h2>
        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
            @forelse($logs as $log)
                <li class="px-4 py-2 flex items-center gap-4">
                    <span class="{{ $log->success ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} font-medium">{{ $log->success ? 'Pass' : 'Fail' }}</span>
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
