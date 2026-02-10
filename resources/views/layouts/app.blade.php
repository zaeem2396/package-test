<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tasks') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Instrument Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'] } } } }</script>
    @endif
</head>
<body class="font-sans antialiased bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 min-h-screen">
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4">
                <div class="flex items-center justify-between">
                    <a href="{{ route('dashboard') }}" class="text-lg font-semibold text-slate-900 dark:text-white">
                        Tasks
                    </a>
                    <nav class="flex items-center gap-1 sm:gap-4">
                        <a href="{{ route('chat.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('chat.*') ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">Chat</a>
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">Tasks</a>
                        <a href="{{ route('tasks.create') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('tasks.create') ? 'bg-slate-100 dark:bg-slate-700' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">New task</a>
                        <a href="{{ route('nats.publish') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('nats.publish') ? 'bg-slate-100 dark:bg-slate-700' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">Broadcast</a>
                        <a href="{{ route('email.delayed') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('email.delayed*') ? 'bg-slate-100 dark:bg-slate-700' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">Delayed email</a>
                        <a href="{{ route('nats.status') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('nats.status') ? 'bg-slate-100 dark:bg-slate-700' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">Status</a>
                        <a href="{{ route('logs') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('logs') ? 'bg-slate-100 dark:bg-slate-700' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">Logs</a>
                        <a href="{{ route('nats-poc.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('nats-poc.*') ? 'bg-slate-100 dark:bg-slate-700' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">NATS PoC</a>
                        <a href="{{ route('insight-test.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('insight-test.*') ? 'bg-slate-100 dark:bg-slate-700' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">Insight test</a>
                    </nav>
                </div>
            </div>
        </header>

        @if (session('success'))
            <div class="max-w-5xl mx-auto px-4 sm:px-6 mt-4 w-full">
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="max-w-5xl mx-auto px-4 sm:px-6 mt-4 w-full space-y-2">
                <div class="rounded-lg bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 px-4 py-3 text-sm">
                    <strong>Error:</strong> {{ session('error') }}
                </div>
                @if (session('error_detail'))
                    @php $detail = session('error_detail'); @endphp
                    <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-4 py-3 text-xs font-mono overflow-x-auto">
                        <p><strong>Exception:</strong> {{ $detail['class'] ?? '' }}</p>
                        <p><strong>File:</strong> {{ $detail['file'] ?? '' }} (line {{ $detail['line'] ?? '' }})</p>
                        @if (!empty($detail['trace']))
                            <p class="mt-2 font-semibold">Stack trace:</p>
                            <pre class="mt-1 whitespace-pre-wrap break-all text-slate-600 dark:text-slate-400">{{ $detail['trace'] }}</pre>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-8">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 dark:border-slate-700 py-4 text-center text-sm text-slate-500 dark:text-slate-400">
            Run worker to process tasks: <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">docker compose exec app php artisan queue:work nats</code> (or <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">php artisan queue:work nats</code> locally)
        </footer>
    </div>
</body>
</html>
