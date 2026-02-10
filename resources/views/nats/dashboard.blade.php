@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Tasks</h1>
            <p class="mt-1 text-slate-600 dark:text-slate-400">Tasks run via NATS: run now or schedule for later. Worker must be running to process them.</p>
        </div>
        <a href="{{ route('tasks.create') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
            New task
        </a>
    </div>

    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Message</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Scheduled for</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Processed</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($tasks as $t)
                        <tr class="bg-white dark:bg-slate-800">
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">{{ $t->id }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ Str::limit($t->message, 60) }}</td>
                            <td class="px-4 py-3">
                                @if($t->status === 'pending')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200">Pending</span>
                                @elseif($t->status === 'processing')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200">Processing</span>
                                @elseif($t->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200">Completed</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 dark:bg-rose-900/40 text-rose-800 dark:text-rose-200">Failed</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ $t->scheduled_for?->format('M j, H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ $t->processed_at?->format('M j, H:i:s') ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">{{ $t->created_at->format('M j, H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400 text-sm">No tasks yet. <a href="{{ route('tasks.create') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Create one</a>.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 flex flex-wrap gap-4">
        <a href="{{ route('nats.publish') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            Broadcast event
        </a>
        <a href="{{ route('nats.status') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            NATS status
        </a>
    </div>
@endsection
