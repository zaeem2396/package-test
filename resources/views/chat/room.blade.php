@extends('layouts.app')

@section('title', $room->name)

@section('content')
    <div class="flex flex-col h-[calc(100vh-12rem)] max-h-[600px]">
        <div class="flex items-center justify-between mb-4">
            <div>
                <a href="{{ route('chat.index') }}" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">&larr; Rooms</a>
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">{{ $room->name }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Subject: <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">{{ $room->natsSubject() }}</code></p>
            </div>
            <div class="relative">
                <button type="button" id="presence-btn" class="text-sm px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Who's here?</button>
                <div id="presence-list" class="hidden absolute right-0 mt-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-lg py-2 px-3 text-sm z-10 min-w-[140px]">
                    <p class="text-slate-500 dark:text-slate-400 font-medium mb-1">Recently active</p>
                    <ul id="presence-names"></ul>
                </div>
            </div>
        </div>

        <div id="chat-messages" class="flex-1 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 space-y-3 min-h-[200px]">
            @foreach($messages as $m)
                <div class="flex flex-col" data-msg-id="{{ $m->id }}">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $m->author_name }} · {{ $m->created_at->format('H:i') }}</span>
                    <p class="text-slate-800 dark:text-slate-200 break-words">{{ $m->body }}</p>
                </div>
            @endforeach
        </div>

        <form id="chat-form" action="{{ route('chat.messages.store') }}" method="post" class="mt-4 flex gap-2">
            @csrf
            <input type="hidden" name="room_id" value="{{ $room->id }}">
            <input type="text" name="author_name" id="author_name" value="{{ old('author_name', auth()->user()?->name ?? session('chat_author', 'Guest')) }}"
                placeholder="Your name" maxlength="100"
                class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            <input type="text" name="body" id="body" required placeholder="Type a message…" maxlength="2000"
                class="flex-[2] rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                Send
            </button>
        </form>
    </div>

    <script>
        (function () {
            document.getElementById('presence-btn').addEventListener('click', async function () {
                const list = document.getElementById('presence-list');
                const ul = document.getElementById('presence-names');
                const res = await fetch('{{ route("chat.room.presence", $room) }}');
                const data = await res.json();
                ul.innerHTML = (data.users && data.users.length)
                    ? data.users.map(function (n) { return '<li class="text-slate-700 dark:text-slate-200">' + (n || '').replace(/</g, '&lt;') + '</li>'; }).join('')
                    : '<li class="text-slate-500 dark:text-slate-400">No one recently (or presence worker not running)</li>';
                list.classList.toggle('hidden');
            });
            document.getElementById('presence-list').addEventListener('mouseleave', function () { this.classList.add('hidden'); });

            const form = document.getElementById('chat-form');
            const container = document.getElementById('chat-messages');
            const bodyInput = document.getElementById('body');
            const lastId = () => {
                const last = container.querySelector('[data-msg-id]');
                return last ? parseInt(last.getAttribute('data-msg-id'), 10) : 0;
            };

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    const div = document.createElement('div');
                    div.className = 'flex flex-col';
                    div.setAttribute('data-msg-id', data.id);
                    const d = new Date(data.created_at);
                    div.innerHTML = '<span class="text-xs font-medium text-slate-500 dark:text-slate-400">' + (data.author_name || 'Guest') + ' · ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + '</span><p class="text-slate-800 dark:text-slate-200 break-words">' + (data.body || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>';
                    container.appendChild(div);
                    container.scrollTop = container.scrollHeight;
                    bodyInput.value = '';
                }
            });

            setInterval(async () => {
                const after = lastId();
                if (after === 0) return;
                const res = await fetch('{{ route('chat.room.messages', $room) }}?after=' + after, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                data.messages.forEach(function (m) {
                    if (container.querySelector('[data-msg-id="' + m.id + '"]')) return;
                    const div = document.createElement('div');
                    div.className = 'flex flex-col';
                    div.setAttribute('data-msg-id', m.id);
                    const d = new Date(m.created_at);
                    div.innerHTML = '<span class="text-xs font-medium text-slate-500 dark:text-slate-400">' + (m.author_name || 'Guest') + ' · ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + '</span><p class="text-slate-800 dark:text-slate-200 break-words">' + (m.body || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>';
                    container.appendChild(div);
                });
                if (data.messages.length) container.scrollTop = container.scrollHeight;
            }, 2000);
        })();
    </script>
@endsection
