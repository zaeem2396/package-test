<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Knowledge base — RAG PoC</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
    <style>
        :root {
            --bg: #0c0f14;
            --surface: #141a22;
            --border: #2a3441;
            --text: #e8edf4;
            --muted: #8b9aad;
            --accent: #3d9cf0;
            --accent-dim: #2563a8;
            --ok: #34d399;
            --err: #f87171;
        }
        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            background: radial-gradient(ellipse 120% 80% at 50% -20%, #1a2840 0%, var(--bg) 55%);
            color: var(--text);
            min-height: 100vh;
            margin: 0;
            line-height: 1.5;
        }
        .wrap { max-width: 56rem; margin: 0 auto; padding: 2rem 1.25rem 4rem; }
        h1 { font-size: 1.5rem; font-weight: 600; letter-spacing: -0.02em; margin: 0 0 0.25rem; }
        .sub { color: var(--muted); font-size: 0.875rem; margin-bottom: 2rem; }
        .badge {
            display: inline-block; font-size: 0.75rem; font-weight: 500;
            padding: 0.2rem 0.55rem; border-radius: 0.35rem;
            background: rgba(61, 156, 240, 0.12); color: var(--accent); border: 1px solid rgba(61, 156, 240, 0.25);
        }
        .grid { display: grid; gap: 1.25rem; }
        @media (min-width: 768px) { .grid-2 { grid-template-columns: 1fr 1fr; } }
        .card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 0.75rem; padding: 1.25rem 1.35rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.25);
        }
        .card h2 { font-size: 0.95rem; font-weight: 600; margin: 0 0 1rem; color: var(--text); }
        label { display: block; font-size: 0.75rem; font-weight: 500; color: var(--muted); margin-bottom: 0.35rem; }
        textarea, input[type="text"], select {
            width: 100%; box-sizing: border-box;
            background: #0a0d12; border: 1px solid var(--border); border-radius: 0.5rem;
            color: var(--text); padding: 0.65rem 0.75rem; font-size: 0.875rem; font-family: inherit;
        }
        textarea { min-height: 5.5rem; resize: vertical; }
        textarea:focus, input:focus, select:focus { outline: 2px solid var(--accent-dim); outline-offset: 1px; }
        .row { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-top: 0.75rem; }
        .chk { display: flex; align-items: center; gap: 0.4rem; font-size: 0.8125rem; color: var(--muted); }
        .chk input { accent-color: var(--accent); }
        button[type="submit"] {
            background: linear-gradient(180deg, #4aa8ff 0%, var(--accent-dim) 100%);
            color: #fff; border: none; border-radius: 0.5rem; padding: 0.55rem 1.1rem;
            font-size: 0.875rem; font-weight: 600; font-family: inherit; cursor: pointer;
        }
        button[type="submit"]:disabled { opacity: 0.5; cursor: not-allowed; }
        button[type="submit"]:not(:disabled):hover { filter: brightness(1.06); }
        .out { margin-top: 1rem; font-size: 0.8125rem; }
        .out pre {
            background: #0a0d12; border: 1px solid var(--border); border-radius: 0.5rem;
            padding: 0.85rem; overflow-x: auto; white-space: pre-wrap; word-break: break-word;
            margin: 0.5rem 0 0; color: #c4d4e8;
        }
        .answer {
            background: #0a0d12; border: 1px solid var(--border); border-radius: 0.5rem;
            padding: 1rem; margin-top: 0.5rem; white-space: pre-wrap;
        }
        .meta {
            display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; margin-top: 0.75rem;
            font-size: 0.75rem; color: var(--muted);
        }
        .meta span { background: rgba(255,255,255,0.04); padding: 0.2rem 0.5rem; border-radius: 0.35rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.75rem; margin-top: 0.5rem; }
        th, td { text-align: left; padding: 0.45rem 0.5rem; border-bottom: 1px solid var(--border); vertical-align: top; }
        th { color: var(--muted); font-weight: 500; }
        .err { color: var(--err); margin-top: 0.5rem; font-size: 0.8125rem; }
        .ok { color: var(--ok); font-size: 0.8125rem; margin-top: 0.5rem; }
        .loading::after { content: ' …'; animation: pulse 1s ease infinite; }
        @keyframes pulse { 50% { opacity: 0.4; } }
    </style>
</head>
<body>
    <div class="wrap">
        <p class="badge">Vectora · Pinecone · RAG</p>
        <h1>Knowledge base demo</h1>
        <p class="sub">
            Ask questions over ingested <strong>Post</strong> chunks, or run semantic search.
            <strong>{{ number_format($postCount) }}</strong> posts in the database — run
            <code style="background:#0a0d12;padding:0.1rem 0.35rem;border-radius:0.25rem;font-size:0.8em;">php artisan vector:ingest</code>
            if the index is empty.
        </p>

        <div class="grid grid-2">
            <section class="card">
                <h2>RAG answer</h2>
                <form id="form-ask">
                    <label for="q-question">Question</label>
                    <textarea id="q-question" name="question" required placeholder="e.g. What is the refund policy?" maxlength="4000"></textarea>
                    <div class="row">
                        <div style="flex:1;min-width:8rem">
                            <label for="q-topk">Top K</label>
                            <select id="q-topk" name="top_k">
                                @foreach ([3, 5, 8, 12] as $k)
                                    <option value="{{ $k }}" @selected($k === (int) config('knowledge.default_top_k', 5))>{{ $k }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="chk" style="margin-top:1.5rem">
                            <input type="checkbox" id="q-sources" name="with_sources" checked>
                            Include sources
                        </label>
                    </div>
                    <div class="row">
                        <button type="submit" id="btn-ask">Get answer</button>
                    </div>
                </form>
                <div id="out-ask" class="out" hidden></div>
            </section>

            <section class="card">
                <h2>Semantic search</h2>
                <form id="form-search">
                    <label for="s-query">Query</label>
                    <input type="text" id="s-query" name="query" required placeholder="e.g. shipping API rate limit" maxlength="4000">
                    <div class="row">
                        <div style="flex:1;min-width:8rem">
                            <label for="s-topk">Top K</label>
                            <select id="s-topk" name="top_k">
                                @foreach ([5, 8, 12, 20] as $k)
                                    <option value="{{ $k }}" @selected($k === 8)>{{ $k }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" id="btn-search" style="margin-top:1.5rem">Search</button>
                    </div>
                </form>
                <div id="out-search" class="out" hidden></div>
            </section>
        </div>
    </div>

    <script>
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        };

        function showError(el, data, status) {
            el.hidden = false;
            const msg = data && data.message ? data.message : ('HTTP ' + status);
            el.innerHTML = '<p class="err">' + escapeHtml(msg) + '</p>';
        }

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        document.getElementById('form-ask').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-ask');
            const out = document.getElementById('out-ask');
            out.hidden = false;
            out.innerHTML = '<p class="loading ok">Retrieving context and generating answer</p>';
            btn.disabled = true;
            try {
                const body = {
                    question: document.getElementById('q-question').value.trim(),
                    top_k: parseInt(document.getElementById('q-topk').value, 10),
                    with_sources: document.getElementById('q-sources').checked,
                };
                const res = await fetch(@json(route('knowledge.ask')), { method: 'POST', headers, body: JSON.stringify(body) });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    showError(out, data, res.status);
                    return;
                }
                let html = '<div class="answer">' + escapeHtml(data.answer || '') + '</div>';
                if (data.meta) {
                    html += '<div class="meta">';
                    for (const [k, v] of Object.entries(data.meta)) {
                        html += '<span>' + escapeHtml(k) + ': <strong>' + escapeHtml(String(v)) + '</strong> ms</span>';
                    }
                    html += '</div>';
                }
                if (data.sources && data.sources.length) {
                    html += '<table><thead><tr><th>ID</th><th>Score</th><th>Post</th><th>Chunk</th></tr></thead><tbody>';
                    for (const s of data.sources) {
                        html += '<tr><td>' + escapeHtml(String(s.id)) + '</td><td>' + escapeHtml(String(s.score)) + '</td>';
                        html += '<td>' + (s.model_id != null ? escapeHtml(String(s.model_id)) : '—') + '</td>';
                        html += '<td>' + (s.chunk_index != null ? escapeHtml(String(s.chunk_index)) : '—') + '</td></tr>';
                    }
                    html += '</tbody></table>';
                }
                out.innerHTML = html;
            } catch (err) {
                out.innerHTML = '<p class="err">' + escapeHtml(err.message) + '</p>';
            } finally {
                btn.disabled = false;
            }
        });

        document.getElementById('form-search').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-search');
            const out = document.getElementById('out-search');
            out.hidden = false;
            out.innerHTML = '<p class="loading ok">Querying vector index</p>';
            btn.disabled = true;
            try {
                const body = {
                    query: document.getElementById('s-query').value.trim(),
                    top_k: parseInt(document.getElementById('s-topk').value, 10),
                };
                const res = await fetch(@json(route('knowledge.search')), { method: 'POST', headers, body: JSON.stringify(body) });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    showError(out, data, res.status);
                    return;
                }
                if (!data.matches || !data.matches.length) {
                    out.innerHTML = '<p class="ok">No matches (ingest posts first).</p>';
                    return;
                }
                let html = '<p class="ok">' + data.count + ' match(es)</p><table><thead><tr><th>Score</th><th>ID</th><th>Post</th><th>Snippet</th></tr></thead><tbody>';
                for (const m of data.matches) {
                    html += '<tr><td>' + escapeHtml(String(m.score)) + '</td><td>' + escapeHtml(String(m.id)) + '</td>';
                    html += '<td>' + (m.model_id != null ? escapeHtml(String(m.model_id)) : '—') + '</td>';
                    html += '<td>' + (m.snippet ? escapeHtml(m.snippet) + (m.snippet.length >= 320 ? '…' : '') : '—') + '</td></tr>';
                }
                html += '</tbody></table>';
                out.innerHTML = html;
            } catch (err) {
                out.innerHTML = '<p class="err">' + escapeHtml(err.message) + '</p>';
            } finally {
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
