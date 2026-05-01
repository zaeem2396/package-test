<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen">
    <nav class="bg-white dark:bg-gray-800 shadow border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" class="font-semibold text-xl">TaskBoard</a>
                    <a href="{{ route('dashboard') }}" class="text-sm hover:underline">Dashboard</a>
                    <a href="{{ route('projects.index') }}" class="text-sm hover:underline">Projects</a>
                    <a href="{{ route('tasks.index') }}" class="text-sm hover:underline">Tasks</a>
                    <a href="{{ route('reports.analytics') }}" class="text-sm hover:underline">Analytics</a>
                    <a href="{{ route('reports.export') }}" class="text-sm hover:underline">Export</a>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
