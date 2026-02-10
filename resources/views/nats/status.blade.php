@extends('layouts.app')

@section('title', 'Status')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Connection Status</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">NATS server and JetStream availability.</p>
    </div>

    <div class="max-w-xl space-y-4">
        @if ($error)
            <div class="p-6 rounded-xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-900/20 space-y-3">
                <p class="font-medium text-rose-800 dark:text-rose-200">Connection failed</p>
                <p class="text-sm text-rose-700 dark:text-rose-300">{{ $error }}</p>
                @if (!empty($errorDetail))
                    <div class="mt-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 px-4 py-3 text-xs font-mono overflow-x-auto">
                        <p><strong>Exception:</strong> {{ $errorDetail['class'] ?? '' }}</p>
                        <p><strong>File:</strong> {{ $errorDetail['file'] ?? '' }} (line {{ $errorDetail['line'] ?? '' }})</p>
                        @if (!empty($errorDetail['trace']))
                            <p class="mt-2 font-semibold text-slate-700 dark:text-slate-300">Stack trace:</p>
                            <pre class="mt-1 whitespace-pre-wrap break-all text-slate-600 dark:text-slate-400">{{ $errorDetail['trace'] }}</pre>
                        @endif
                    </div>
                @endif
                <p class="text-sm text-slate-600 dark:text-slate-400">Ensure NATS is running (e.g. <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">docker compose up -d nats</code>).</p>
            </div>
        @else
            <div class="p-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                <div class="flex items-center gap-3">
                    @if ($connected)
                        <span class="flex h-3 w-3 rounded-full bg-emerald-500"></span>
                        <span class="font-medium text-slate-900 dark:text-white">NATS connected</span>
                    @else
                        <span class="flex h-3 w-3 rounded-full bg-rose-500"></span>
                        <span class="font-medium text-slate-900 dark:text-white">NATS disconnected</span>
                    @endif
                </div>
            </div>
            <div class="p-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                <div class="flex items-center gap-3">
                    @if ($jetstreamAvailable)
                        <span class="flex h-3 w-3 rounded-full bg-emerald-500"></span>
                        <span class="font-medium text-slate-900 dark:text-white">JetStream available</span>
                    @else
                        <span class="flex h-3 w-3 rounded-full bg-amber-500"></span>
                        <span class="font-medium text-slate-900 dark:text-white">JetStream not available</span>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Start NATS with <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">--jetstream</code> for streaming features.</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="p-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white mb-3">Send test email</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Emails are sent to Mailhog in Docker. Open <a href="http://localhost:8025" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">http://localhost:8025</a> to view them.</p>
            <form action="{{ route('send-test-email') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">To</label>
                    <input type="email" name="email" id="email" value="{{ old('email', 'test@example.com') }}" required
                           class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm">
                </div>
                <div>
                    <label for="email_message" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Message (optional)</label>
                    <input type="text" name="message" id="email_message" value="{{ old('message') }}" maxlength="500"
                           class="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm"
                           placeholder="Custom message">
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Send test email</button>
            </form>
            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                Or <a href="{{ route('email.delayed') }}" class="text-blue-600 dark:text-blue-400 hover:underline">send an email after a delay via NATS</a>.
            </p>
        </div>
    </div>
@endsection
