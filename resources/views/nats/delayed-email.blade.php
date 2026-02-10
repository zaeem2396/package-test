@extends('layouts.app')

@section('title', 'Delayed email (NATS)')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Send email after delay</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">Queue an email to be sent after a delay using the NATS driver. The queue worker must be running.</p>
    </div>

    <div class="max-w-xl space-y-4">
        <div class="p-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white mb-3">Delayed email via NATS</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">The job is queued on NATS; when the worker picks it up, it waits for the chosen delay then sends the email. View messages in <a href="http://localhost:8025" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">Mailhog</a>. Delay is capped at 1 hour.</p>
            <form action="{{ route('email.delayed.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">To</label>
                    <input type="email" name="email" id="email" value="{{ old('email', 'test@example.com') }}" required
                           class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm">
                </div>
                <div>
                    <label for="message" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Message (optional)</label>
                    <input type="text" name="message" id="message" value="{{ old('message') }}" maxlength="500"
                           class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm"
                           placeholder="Custom message">
                </div>
                <div>
                    <label for="delay_seconds" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Delay (seconds)</label>
                    <input type="number" name="delay_seconds" id="delay_seconds" value="{{ old('delay_seconds', 30) }}" min="1" max="3600" required
                           class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm"
                           placeholder="e.g. 30">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">1–3600 seconds (in-job wait; worker is busy for this time)</p>
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Queue delayed email (NATS)</button>
            </form>
        </div>
    </div>
@endsection
