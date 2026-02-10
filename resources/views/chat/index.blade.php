@extends('layouts.app')

@section('title', 'Chat rooms')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Chat rooms</h1>
            <p class="mt-1 text-slate-600 dark:text-slate-400">Real-time collaboration PoC - pub/sub via NATS, messages persisted in JetStream.</p>
        </div>
        <a href="{{ route('chat.create') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
            New room
        </a>
    </div>

    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
            @forelse($rooms as $room)
                <li>
                    <a href="{{ route('chat.room.show', $room) }}" class="flex items-center justify-between px-4 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <span class="font-medium text-slate-900 dark:text-white">{{ $room->name }}</span>
                        <span class="text-slate-500 dark:text-slate-400 text-sm">{{ $room->messages_count }} messages</span>
                    </a>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                    No rooms yet. <a href="{{ route('chat.create') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Create one</a>.
                </li>
            @endforelse
        </ul>
    </div>
@endsection
