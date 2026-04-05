@php
    $useVite = file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'));
    $vectoraStudioJsPath = public_path('js/vectora-studio.js');
    $vectoraStudioJsVersion = is_file($vectoraStudioJsPath) ? filemtime($vectoraStudioJsPath) : 0;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vectora Studio — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @if ($useVite)
        @vite(['resources/css/app.css', 'resources/js/vectora-studio.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: { sans: ['Instrument Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    },
                },
            };
        </script>
        <style>
            @keyframes studio-in {
                from { opacity: 0; transform: translateY(6px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-studio-in { animation: studio-in 0.35s ease-out; }
        </style>
    @endif
    <script type="application/json" id="vectora-config">@json(['configured' => $configured, 'connections' => $connections])</script>
</head>
<body class="min-h-full bg-zinc-950 text-zinc-100 antialiased font-sans selection:bg-violet-500/30">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-40 left-1/2 h-[480px] w-[900px] -translate-x-1/2 rounded-full bg-gradient-to-b from-violet-600/25 via-fuchsia-600/10 to-transparent blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-64 w-64 rounded-full bg-cyan-500/10 blur-3xl"></div>
    </div>

    <header class="border-b border-white/10 bg-zinc-950/80 backdrop-blur-md sticky top-0 z-20">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-lg font-bold text-white shadow-lg shadow-violet-500/25">V</span>
                <div>
                    <h1 class="text-lg font-semibold tracking-tight text-white">Vectora Studio</h1>
                    <p class="text-xs text-zinc-500">Live Pinecone data plane · upsert, semantic search, delete</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <span id="live-dot" class="{{ $configured ? 'inline-flex' : 'hidden' }} items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-emerald-400">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                    </span>
                    Live
                </span>
                <button type="button" id="btn-refresh-stats" class="rounded-lg border border-white/15 bg-white/5 px-3 py-1.5 text-zinc-300 transition hover:border-white/25 hover:bg-white/10">
                    Refresh stats
                </button>
                <a href="{{ url('/') }}" class="rounded-lg border border-white/15 px-3 py-1.5 text-zinc-400 transition hover:text-white">Home</a>
            </div>
        </div>
    </header>

    @unless ($useVite)
        <div class="border-b border-cyan-500/20 bg-cyan-950/40 px-4 py-2 text-center text-xs text-cyan-200/90">
            Running without Vite: Tailwind + Axios load from CDN; logic from <code class="rounded bg-black/30 px-1">public/js/vectora-studio.js</code>. For production, prefer <code class="rounded bg-black/30 px-1">npm run build</code> (Node 20+).
        </div>
    @endunless

    <div id="config-banner" class="{{ $configured ? 'hidden' : '' }} border-b border-amber-500/30 bg-amber-500/10 px-4 py-3 text-center text-sm text-amber-200">
        Pinecone is not configured for this environment. Set <code class="rounded bg-black/30 px-1">PINECONE_API_KEY</code> and <code class="rounded bg-black/30 px-1">PINECONE_HOST</code>, then <code class="rounded bg-black/30 px-1">php artisan config:clear</code>. See <code class="rounded bg-black/30 px-1">VECTORA_USAGE.md</code> in the project root.
    </div>

    <main class="mx-auto max-w-7xl px-4 py-8">
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 shadow-xl shadow-black/20">
                    <h2 class="text-sm font-medium text-zinc-400 uppercase tracking-wider">Index scope</h2>
                    <p class="mt-1 text-xs text-zinc-500">Applies to upsert, search, and delete below.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm">
                            <span class="text-zinc-400">Connection</span>
                            <select id="field-connection" class="mt-1 w-full rounded-lg border border-white/10 bg-zinc-900/80 px-3 py-2 text-sm text-white outline-none ring-violet-500/40 focus:ring-2"></select>
                        </label>
                        <label class="block text-sm">
                            <span class="text-zinc-400">Namespace (optional)</span>
                            <input id="field-namespace" type="text" placeholder="Leave empty for .env default" class="mt-1 w-full rounded-lg border border-white/10 bg-zinc-900/80 px-3 py-2 text-sm text-white outline-none ring-violet-500/40 focus:ring-2" autocomplete="off" />
                        </label>
                    </div>
                    <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-zinc-400">
                        <input id="field-pinecone-default-ns" type="checkbox" class="rounded border-white/20 bg-zinc-900" />
                        Use Pinecone’s unscoped default namespace (omit namespace in API)
                    </label>
                </section>

                <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-white">Index pulse</h2>
                            <p class="text-xs text-zinc-500">Auto-refresh every 5s when configured</p>
                        </div>
                        <div class="text-right text-xs text-zinc-500">Updated <span id="stat-time">—</span></div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <div class="rounded-xl border border-white/10 bg-zinc-900/50 p-4">
                            <dt class="text-xs text-zinc-500">Dimension</dt>
                            <dd id="stat-dim" class="mt-1 text-2xl font-semibold text-white">—</dd>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-zinc-900/50 p-4">
                            <dt class="text-xs text-zinc-500">Total vectors</dt>
                            <dd id="stat-count" class="mt-1 text-2xl font-semibold text-violet-300">—</dd>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-zinc-900/50 p-4 sm:col-span-1 col-span-2">
                            <dt class="text-xs text-zinc-500">Metric</dt>
                            <dd id="stat-metric" class="mt-1 text-2xl font-semibold text-fuchsia-300">—</dd>
                        </div>
                    </dl>
                    <p id="stat-namespaces" class="mt-3 text-xs text-zinc-500"></p>
                </section>

                <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <h2 class="text-base font-semibold text-white">Upsert / update</h2>
                    <p class="mt-1 text-sm text-zinc-400">Same operation: new id inserts; existing id overwrites (metadata + vector).</p>
                    <form id="form-upsert" class="mt-4 space-y-4">
                        <label class="block text-sm">
                            <span class="text-zinc-400">Vector id</span>
                            <input id="upsert-id" required class="mt-1 w-full rounded-lg border border-white/10 bg-zinc-900/80 px-3 py-2 font-mono text-sm outline-none ring-violet-500/40 focus:ring-2" placeholder="doc-001" autocomplete="off" />
                        </label>
                        <div class="flex flex-wrap gap-4 text-sm">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="upsert-mode" value="text" checked class="border-white/20 bg-zinc-900" />
                                <span>Text → embedding</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="upsert-mode" value="vector" class="border-white/20 bg-zinc-900" />
                                <span>Raw vector JSON</span>
                            </label>
                        </div>
                        <div id="upsert-text-wrap">
                            <label class="block text-sm">
                                <span class="text-zinc-400">Content</span>
                                <textarea id="upsert-text" rows="3" class="mt-1 w-full rounded-lg border border-white/10 bg-zinc-900/80 px-3 py-2 text-sm outline-none ring-violet-500/40 focus:ring-2" placeholder="Sentence or paragraph to embed…"></textarea>
                            </label>
                        </div>
                        <div id="upsert-vector-wrap" class="hidden">
                            <label class="block text-sm">
                                <span class="text-zinc-400">Vector JSON array</span>
                                <textarea id="upsert-vector" rows="4" class="mt-1 w-full rounded-lg border border-white/10 bg-zinc-900/80 px-3 py-2 font-mono text-xs outline-none ring-violet-500/40 focus:ring-2" placeholder="[0.01, -0.02, …]"></textarea>
                            </label>
                        </div>
                        <label class="block text-sm">
                            <span class="text-zinc-400">Metadata (JSON object)</span>
                            <textarea id="upsert-metadata" rows="2" class="mt-1 w-full rounded-lg border border-white/10 bg-zinc-900/80 px-3 py-2 font-mono text-xs outline-none ring-violet-500/40 focus:ring-2" placeholder='{"title":"Note","tags":["poc"]}'></textarea>
                        </label>
                        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 py-2.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/20 transition hover:opacity-95 sm:w-auto sm:px-8">
                            Upsert to Pinecone
                        </button>
                    </form>
                </section>

                <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <h2 class="text-base font-semibold text-white">Semantic search</h2>
                    <p class="mt-1 text-sm text-zinc-400">Embeds your query and runs <code class="rounded bg-black/40 px-1 text-xs">query</code> against the index.</p>
                    <form id="form-query" class="mt-4 space-y-4">
                        <label class="block text-sm">
                            <span class="text-zinc-400">Query</span>
                            <textarea id="query-text" rows="2" required class="mt-1 w-full rounded-lg border border-white/10 bg-zinc-900/80 px-3 py-2 text-sm outline-none ring-cyan-500/40 focus:ring-2" placeholder="What are you looking for?"></textarea>
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm">
                                <span class="text-zinc-400">Top K</span>
                                <input id="query-topk" type="number" min="1" max="100" value="10" class="mt-1 w-full rounded-lg border border-white/10 bg-zinc-900/80 px-3 py-2 text-sm outline-none ring-cyan-500/40 focus:ring-2" />
                            </label>
                            <label class="block text-sm sm:col-span-2">
                                <span class="text-zinc-400">Metadata filter (optional JSON)</span>
                                <input id="query-filter" type="text" class="mt-1 w-full rounded-lg border border-white/10 bg-zinc-900/80 px-3 py-2 font-mono text-xs outline-none ring-cyan-500/40 focus:ring-2" placeholder='{"tags":{"$in":["poc"]}}' autocomplete="off" />
                            </label>
                        </div>
                        <button type="submit" class="w-full rounded-xl border border-cyan-500/40 bg-cyan-500/10 py-2.5 text-sm font-semibold text-cyan-200 transition hover:bg-cyan-500/20 sm:w-auto sm:px-8">
                            Run search
                        </button>
                    </form>
                    <div class="mt-6 overflow-x-auto rounded-xl border border-white/10">
                        <table id="query-results" class="w-full text-left text-sm">
                            <thead class="bg-zinc-900/80 text-xs uppercase text-zinc-500">
                                <tr>
                                    <th class="px-3 py-2">Id</th>
                                    <th class="px-3 py-2">Score</th>
                                    <th class="px-3 py-2">Metadata</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-2xl border border-rose-500/20 bg-rose-500/[0.04] p-5">
                    <h2 class="text-base font-semibold text-rose-200">Delete by id</h2>
                    <p class="mt-1 text-sm text-rose-200/70">Removes vectors for the given ids in the current scope. This cannot be undone.</p>
                    <form id="form-delete" class="mt-4 space-y-4">
                        <label class="block text-sm">
                            <span class="text-zinc-400">Ids (comma or newline separated)</span>
                            <textarea id="delete-ids" rows="3" class="mt-1 w-full rounded-lg border border-white/10 bg-zinc-900/80 px-3 py-2 font-mono text-sm outline-none ring-rose-500/40 focus:ring-2" placeholder="doc-001&#10;doc-002"></textarea>
                        </label>
                        <button type="submit" class="w-full rounded-xl border border-rose-500/50 bg-rose-600/20 py-2.5 text-sm font-semibold text-rose-100 transition hover:bg-rose-600/30 sm:w-auto sm:px-8">
                            Delete vectors
                        </button>
                    </form>
                </section>
            </div>

            <aside class="lg:col-span-1">
                <div class="sticky top-24 rounded-2xl border border-white/10 bg-zinc-900/40 p-4 backdrop-blur-sm">
                    <h2 class="text-sm font-semibold text-white">Activity</h2>
                    <p class="mt-0.5 text-xs text-zinc-500">Operations from this session</p>
                    <div id="activity-feed" class="mt-4 flex max-h-[calc(100vh-8rem)] flex-col gap-2 overflow-y-auto pr-1"></div>
                </div>
            </aside>
        </div>

        <p class="mt-10 text-center text-xs text-zinc-600">
            Dev PoC — do not expose publicly without authentication. Package:
            <a href="https://github.com/zaeem2396/vectora" class="text-violet-400 underline hover:text-violet-300">vectora/laravel-pinecone</a>
        </p>
    </main>

    @unless ($useVite)
        <script defer src="https://cdn.jsdelivr.net/npm/axios@1.7.9/dist/axios.min.js"></script>
        <script defer src="{{ asset('js/vectora-studio.js') }}?v={{ $vectoraStudioJsVersion }}"></script>
    @endunless
</body>
</html>
