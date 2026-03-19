<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Conductor PoC — Real-time workflow monitor</title>
    <style>
        :root { font-family: system-ui, sans-serif; color: #0f172a; background: #f8fafc; }
        body { margin: 0; padding: 1.25rem; max-width: 1100px; margin-inline: auto; }
        h1 { font-size: 1.35rem; }
        .badge { display: inline-block; padding: .2rem .5rem; border-radius: 6px; font-size: .75rem; font-weight: 600; }
        .live { background: #22c55e; color: #fff; animation: pulse 1.5s ease-in-out infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.65} }
        .row { display: flex; flex-wrap: wrap; gap: .5rem; margin: .75rem 0; align-items: center; }
        button, .btn {
            cursor: pointer; border: 1px solid #cbd5e1; background: #fff; padding: .45rem .75rem; border-radius: 8px; font-size: .875rem;
        }
        button.primary { background: #2563eb; color: #fff; border-color: #1d4ed8; }
        button.danger { background: #dc2626; color: #fff; border-color: #b91c1c; }
        input, textarea { padding: .4rem .55rem; border-radius: 8px; border: 1px solid #cbd5e1; font-size: .875rem; }
        pre {
            background: #0f172a; color: #e2e8f0; padding: 1rem; border-radius: 10px; overflow: auto; font-size: .75rem; max-height: 320px;
        }
        .grid { display: grid; gap: 1rem; }
        @media (min-width: 800px) { .grid-2 { grid-template-columns: 1fr 1fr; } }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; }
        .muted { color: #64748b; font-size: .8rem; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <h1>Conductor / Orkes Laravel — PoC dashboard <span class="badge live">Live poll</span></h1>
    <p class="muted">
        Polls workflow status every 2s when a workflow ID is set. Covers facade/SDK workflow client, DSL preview, search, task poll API, and lifecycle actions.
        <strong>Run a worker</strong> in another terminal: <code>php artisan conductor:work</code> (after Conductor is up and workflow is registered).
    </p>

    <div class="grid grid-2">
        <div class="card">
            <h2>1. DSL (no Conductor call)</h2>
            <div class="row">
                <button type="button" id="btn-preview">GET definition preview</button>
            </div>
        </div>
        <div class="card">
            <h2>2. Register / start</h2>
            <div class="row">
                <label>Version <input type="number" id="wf-version" value="1" min="1" style="width:4rem"></label>
                <button type="button" id="btn-register" class="primary">POST register workflow</button>
                <button type="button" id="btn-update">POST update (bump version)</button>
            </div>
            <div class="row">
                <textarea id="start-input" rows="3" style="width:100%">{"message":"hello from PoC","request_id":"req-1"}</textarea>
            </div>
            <div class="row">
                <button type="button" id="btn-start" class="primary">POST start workflow</button>
                <span class="muted">Uses WorkflowClient::start + optional correlation / version via fields below</span>
            </div>
            <div class="row">
                <input type="text" id="correlation-id" placeholder="correlation id (optional)" style="flex:1">
                <input type="number" id="start-wf-version" placeholder="wf version" style="width:8rem">
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:1rem">
        <h2>3. Real-time status <span class="muted" id="poll-state"></span></h2>
        <div class="row">
            <input type="text" id="workflow-id" placeholder="workflow id" style="flex:1;min-width:240px">
            <button type="button" id="btn-clear-poll">Stop polling</button>
        </div>
        <div class="row">
            <button type="button" id="btn-pause">Pause</button>
            <button type="button" id="btn-resume">Resume</button>
            <button type="button" id="btn-retry">Retry last failed task</button>
            <button type="button" class="danger" id="btn-terminate">Terminate</button>
        </div>
        <pre id="status-out">{}</pre>
    </div>

    <div class="grid grid-2" style="margin-top:1rem">
        <div class="card">
            <h2>4. Search (WorkflowClient::search)</h2>
            <div class="row">
                <button type="button" id="btn-search-run">GET running</button>
                <button type="button" id="btn-search-fail">GET failed / terminated</button>
            </div>
            <pre id="search-out">{}</pre>
        </div>
        <div class="card">
            <h2>5. TaskClient::poll</h2>
            <div class="row">
                <select id="poll-type">
                    <option value="poc_validate">poc_validate</option>
                    <option value="poc_process">poc_process</option>
                    <option value="poc_notify">poc_notify</option>
                </select>
                <button type="button" id="btn-poll">POST poll once</button>
            </div>
            <pre id="poll-out">{}</pre>
        </div>
    </div>

    <p class="muted" style="margin-top:1.5rem">
        Testing steps: repo root <code>CONDUCTOR_POC_TESTING.md</code>.
        Package notes: <code>docs/ORKEES_LARAVEL_PACKAGE_BUGS.md</code>.
    </p>

    <script>
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        };

        async function jfetch(url, opts = {}) {
            const r = await fetch(url, { ...opts, headers: { ...headers, ...opts.headers } });
            const t = await r.text();
            let body;
            try { body = JSON.parse(t); } catch { body = { raw: t }; }
            if (!r.ok) throw new Error(body.error || body.message || t || r.status);
            return body;
        }

        const $ = (id) => document.getElementById(id);

        $('btn-preview').onclick = async () => {
            const v = $('wf-version').value || 1;
            const data = await jfetch(`{{ url('/conductor-poc/definition/preview') }}?version=${encodeURIComponent(v)}`);
            $('status-out').textContent = JSON.stringify(data, null, 2);
        };

        $('btn-register').onclick = async () => {
            const version = parseInt($('wf-version').value, 10) || 1;
            const data = await jfetch(`{{ route('conductor-poc.workflow.register') }}`, {
                method: 'POST',
                body: JSON.stringify({ version }),
            });
            $('status-out').textContent = JSON.stringify(data, null, 2);
        };

        $('btn-update').onclick = async () => {
            const v = (parseInt($('wf-version').value, 10) || 1) + 1;
            $('wf-version').value = String(v);
            const data = await jfetch(`{{ route('conductor-poc.workflow.update') }}`, {
                method: 'POST',
                body: JSON.stringify({ version: v }),
            });
            $('status-out').textContent = JSON.stringify(data, null, 2);
        };

        $('btn-start').onclick = async () => {
            let input = {};
            try { input = JSON.parse($('start-input').value); } catch (e) { alert('Invalid JSON input'); return; }
            const body = { input };
            const cid = $('correlation-id').value.trim();
            if (cid) body.correlation_id = cid;
            const wfv = $('start-wf-version').value.trim();
            if (wfv !== '') body.wf_version = parseInt(wfv, 10);
            const data = await jfetch(`{{ route('conductor-poc.workflow.start') }}`, {
                method: 'POST',
                body: JSON.stringify(body),
            });
            if (data.workflow_id) {
                $('workflow-id').value = data.workflow_id;
                startPolling();
            }
            $('status-out').textContent = JSON.stringify(data, null, 2);
        };

        let pollTimer = null;
        function startPolling() {
            if (pollTimer) clearInterval(pollTimer);
            const tick = async () => {
                const id = $('workflow-id').value.trim();
                if (!id) return;
                $('poll-state').textContent = '(polling…) ' + new Date().toISOString();
                try {
                    const data = await jfetch(`{{ url('/conductor-poc/workflow') }}/${encodeURIComponent(id)}/status`);
                    $('status-out').textContent = JSON.stringify(data, null, 2);
                } catch (e) {
                    $('status-out').textContent = JSON.stringify({ error: String(e) }, null, 2);
                }
            };
            tick();
            pollTimer = setInterval(tick, 2000);
        }

        $('btn-clear-poll').onclick = () => {
            if (pollTimer) clearInterval(pollTimer);
            pollTimer = null;
            $('poll-state').textContent = '(polling stopped)';
        };

        async function wfAction(path) {
            const id = $('workflow-id').value.trim();
            if (!id) { alert('Set workflow id'); return; }
            const data = await jfetch(`{{ url('/conductor-poc/workflow') }}/${encodeURIComponent(id)}${path}`, { method: 'POST', body: '{}' });
            $('status-out').textContent = JSON.stringify(data, null, 2);
        }
        $('btn-pause').onclick = () => wfAction('/pause');
        $('btn-resume').onclick = () => wfAction('/resume');
        $('btn-retry').onclick = () => wfAction('/retry');
        $('btn-terminate').onclick = () => wfAction('/terminate');

        $('btn-search-run').onclick = async () => {
            const data = await jfetch(`{{ route('conductor-poc.search.running') }}?size=15`);
            $('search-out').textContent = JSON.stringify(data, null, 2);
        };
        $('btn-search-fail').onclick = async () => {
            const data = await jfetch(`{{ route('conductor-poc.search.failed') }}?size=15`);
            $('search-out').textContent = JSON.stringify(data, null, 2);
        };

        $('btn-poll').onclick = async () => {
            const task_type = $('poll-type').value;
            const data = await jfetch(`{{ route('conductor-poc.tasks.poll') }}`, {
                method: 'POST',
                body: JSON.stringify({ task_type }),
            });
            $('poll-out').textContent = JSON.stringify(data, null, 2);
        };
    </script>
</body>
</html>
