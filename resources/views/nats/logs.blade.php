@extends('layouts.app')

@section('title', 'NATS logs')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">NATS activity logs</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">Task and email activity from the NATS queue (queued, completed, failed, emails sent).</p>
    </div>

    <div class="max-w-5xl space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Filter:</span>
            <a href="{{ route('logs', ['filter' => 'all']) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium {{ ($filter ?? 'all') === 'all' ? 'bg-slate-200 dark:bg-slate-600 text-slate-900 dark:text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                All
            </a>
            <a href="{{ route('logs', ['filter' => 'tasks']) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium {{ ($filter ?? '') === 'tasks' ? 'bg-slate-200 dark:bg-slate-600 text-slate-900 dark:text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                Tasks
            </a>
            <a href="{{ route('logs', ['filter' => 'emails']) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium {{ ($filter ?? '') === 'emails' ? 'bg-slate-200 dark:bg-slate-600 text-slate-900 dark:text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                Emails
            </a>
        </div>

        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
            <div class="px-4 py-2 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-sm font-medium text-slate-700 dark:text-slate-300">
                Recent activity (last 200)
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700 max-h-[70vh] overflow-y-auto">
                @forelse ($entries as $entry)
                    <div class="px-4 py-2.5 flex flex-wrap items-baseline gap-x-3 gap-y-1 text-sm">
                        <span class="shrink-0 text-xs font-mono text-slate-500 dark:text-slate-400 whitespace-nowrap" title="{{ $entry->created_at->toIso8601String() }}">
                            {{ $entry->created_at->format('Y-m-d H:i:s') }}
                        </span>
                        @php
                            $typeClass = match ($entry->type) {
                                'task_queued' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200',
                                'task_completed' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200',
                                'task_failed' => 'bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-200',
                                'email_sent' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200',
                                default => 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300',
                            };
                        @endphp
                        <span class="shrink-0 px-2 py-0.5 rounded text-xs font-medium {{ $typeClass }}">{{ $entry->type }}</span>
                        <span class="text-slate-800 dark:text-slate-200">{{ $entry->message }}</span>
                        @if (!empty($entry->meta))
                            <span class="w-full sm:w-auto text-xs text-slate-500 dark:text-slate-400 font-mono">{{ json_encode($entry->meta) }}</span>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                        No activity yet. Create a task or send an email to see entries here.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
