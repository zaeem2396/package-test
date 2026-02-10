@extends('layouts.app')

@section('title', 'Runtime Insight tests')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Runtime Insight — deliberate errors</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">These routes trigger runtime errors on purpose so <a href="https://github.com/clarityphp/runtime-insight" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">clarityphp/runtime-insight</a> can detect and explain them. Explanations are logged; run <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">php artisan insight:explain</code> (or <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">php artisan runtime:explain --log=storage/logs/laravel.log</code>) to see the last one.</p>
    </div>

    <div class="max-w-2xl space-y-4">
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
            <p class="text-sm text-amber-800 dark:text-amber-200"><strong>Warning:</strong> Each link will throw an exception and show an error page. Use only in local/staging to test Runtime Insight.</p>
        </div>

        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
            <div class="px-4 py-2 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-sm font-medium text-slate-700 dark:text-slate-300">
                Trigger these errors
            </div>
            <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                <li>
                    <a href="{{ route('insight-test.null-pointer') }}" class="flex items-center justify-between px-4 py-3 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <span><strong>Null pointer</strong> — call method on null</span>
                        <span class="text-slate-400 text-sm">/insight-test/null-pointer</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('insight-test.undefined-index') }}" class="flex items-center justify-between px-4 py-3 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <span><strong>Undefined index</strong> — access missing array key</span>
                        <span class="text-slate-400 text-sm">/insight-test/undefined-index</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('insight-test.type-error') }}" class="flex items-center justify-between px-4 py-3 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <span><strong>Type error</strong> — wrong argument type (int instead of string)</span>
                        <span class="text-slate-400 text-sm">/insight-test/type-error</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('insight-test.argument-count') }}" class="flex items-center justify-between px-4 py-3 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <span><strong>Argument count</strong> — too few arguments</span>
                        <span class="text-slate-400 text-sm">/insight-test/argument-count</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('insight-test.class-not-found') }}" class="flex items-center justify-between px-4 py-3 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <span><strong>Class not found</strong> — instantiate non-existent class</span>
                        <span class="text-slate-400 text-sm">/insight-test/class-not-found</span>
                    </a>
                </li>
            </ul>
        </div>

        <p class="text-sm text-slate-500 dark:text-slate-400">
            After triggering an error, check <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">storage/logs/laravel.log</code> for the Runtime Insight explanation (debug level), or run <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">php artisan insight:explain</code> to print the last explanation.
        </p>
    </div>
@endsection
