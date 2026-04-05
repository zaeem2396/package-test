/** Vite entry — keep behavior in sync with `public/js/vectora-studio.js` (no-build / CDN fallback). */
import './bootstrap';

const meta = document.querySelector('meta[name="csrf-token"]');
if (meta) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = meta.getAttribute('content');
}
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.headers.common['Content-Type'] = 'application/json';

function cfg() {
    const el = document.getElementById('vectora-config');
    if (!el?.textContent) {
        return { configured: false, connections: ['default'] };
    }
    try {
        return JSON.parse(el.textContent);
    } catch {
        return { configured: false, connections: ['default'] };
    }
}

function log(type, title, detail) {
    const feed = document.getElementById('activity-feed');
    if (!feed) return;
    const row = document.createElement('div');
    row.className =
        'studio-log border border-white/10 rounded-lg px-3 py-2 text-sm bg-white/5 animate-studio-in';
    const t = new Date().toLocaleTimeString();
    const colors = {
        ok: 'text-emerald-400',
        err: 'text-rose-400',
        info: 'text-sky-400',
    };
    row.innerHTML = `
        <div class="flex justify-between gap-2 text-xs text-zinc-500"><span>${type.toUpperCase()}</span><span>${t}</span></div>
        <div class="font-medium ${colors[type] ?? 'text-zinc-200'}">${escapeHtml(title)}</div>
        ${detail ? `<pre class="mt-1 text-xs text-zinc-400 whitespace-pre-wrap break-all max-h-32 overflow-y-auto">${escapeHtml(detail)}</pre>` : ''}
    `;
    feed.prepend(row);
    while (feed.children.length > 40) {
        feed.removeChild(feed.lastChild);
    }
}

function escapeHtml(s) {
    return String(s)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function connectionPayload() {
    const sel = document.getElementById('field-connection');
    const v = sel?.value;
    return v && v !== '' ? { connection: v } : {};
}

function namespacePayload() {
    const ns = document.getElementById('field-namespace')?.value?.trim();
    const pineDefault = document.getElementById('field-pinecone-default-ns')?.checked;
    const out = {};
    if (pineDefault) {
        out.pinecone_default_namespace = true;
    } else if (ns) {
        out.namespace = ns;
    }
    return out;
}

async function refreshStats() {
    const dot = document.getElementById('live-dot');
    const banner = document.getElementById('config-banner');
    const c = cfg();
    if (!c.configured) {
        banner?.classList.remove('hidden');
        dot?.classList.add('hidden');
        return;
    }
    banner?.classList.add('hidden');
    dot?.classList.remove('hidden');
    try {
        const { data } = await window.axios.get('/vectora/api/stats', {
            params: connectionPayload(),
        });
        if (!data.ok) {
            document.getElementById('stat-dim').textContent = '—';
            document.getElementById('stat-count').textContent = '—';
            document.getElementById('stat-metric').textContent = '—';
            document.getElementById('stat-time').textContent = data.message ?? 'Error';
            return;
        }
        document.getElementById('stat-dim').textContent = String(data.dimension);
        document.getElementById('stat-count').textContent = String(data.total_vector_count);
        document.getElementById('stat-metric').textContent = data.metric ?? '—';
        document.getElementById('stat-time').textContent = data.fetched_at
            ? new Date(data.fetched_at).toLocaleTimeString()
            : '—';
        const nsEl = document.getElementById('stat-namespaces');
        if (nsEl) {
            if (!data.namespaces?.length) {
                nsEl.textContent = 'No namespace breakdown';
            } else {
                nsEl.textContent = data.namespaces
                    .map((n) => `${n.name}: ${n.vector_count}`)
                    .join(' · ');
            }
        }
    } catch (e) {
        document.getElementById('stat-time').textContent = e.response?.data?.message ?? e.message;
    }
}

function readMetadataObject() {
    const raw = document.getElementById('upsert-metadata')?.value?.trim();
    if (!raw) return {};
    try {
        const o = JSON.parse(raw);
        return typeof o === 'object' && o !== null && !Array.isArray(o) ? o : {};
    } catch {
        throw new Error('Metadata must be valid JSON object, e.g. {"category":"docs"}');
    }
}

function readFilterObject() {
    const raw = document.getElementById('query-filter')?.value?.trim();
    if (!raw) return null;
    try {
        const o = JSON.parse(raw);
        return typeof o === 'object' && o !== null ? o : null;
    } catch {
        throw new Error('Filter must be valid JSON object or empty');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const c = cfg();
    const conn = document.getElementById('field-connection');
    if (conn && c.connections?.length) {
        conn.textContent = '';
        for (const name of c.connections) {
            const o = document.createElement('option');
            o.value = name;
            o.textContent = name;
            conn.appendChild(o);
        }
    }

    if (c.configured) {
        refreshStats();
        setInterval(refreshStats, 5000);
    } else {
        log('info', 'Studio idle', 'Configure Pinecone in .env to enable live stats and API actions.');
    }

    document.getElementById('btn-refresh-stats')?.addEventListener('click', () => {
        refreshStats();
        log('info', 'Stats refresh', 'Manual refresh');
    });

    document.getElementById('form-upsert')?.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const mode = document.querySelector('input[name="upsert-mode"]:checked')?.value ?? 'text';
        const id = document.getElementById('upsert-id')?.value?.trim();
        if (!id) {
            log('err', 'Upsert', 'Vector id is required');
            return;
        }
        let metadata = {};
        try {
            metadata = readMetadataObject();
        } catch (e) {
            log('err', 'Upsert', e.message);
            return;
        }
        const payload = {
            id,
            metadata,
            ...connectionPayload(),
            ...namespacePayload(),
        };
        if (mode === 'text') {
            const text = document.getElementById('upsert-text')?.value ?? '';
            if (!text.trim()) {
                log('err', 'Upsert', 'Enter text to embed, or switch to raw vector mode.');
                return;
            }
            payload.text = text;
        } else {
            const vector = document.getElementById('upsert-vector')?.value?.trim() ?? '';
            if (!vector) {
                log('err', 'Upsert', 'Paste a JSON array of floats matching your index dimension.');
                return;
            }
            payload.vector = vector;
        }
        try {
            const { data } = await window.axios.post('/vectora/api/upsert', payload);
            if (!data.ok) {
                log('err', 'Upsert failed', JSON.stringify(data, null, 2));
                return;
            }
            log('ok', `Upserted “${id}”`, `count=${data.upserted_count} dim=${data.dimension}`);
            refreshStats();
        } catch (e) {
            const body = e.response?.data;
            log('err', 'Upsert error', body ? JSON.stringify(body, null, 2) : e.message);
        }
    });

    document.getElementById('form-query')?.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const text = document.getElementById('query-text')?.value ?? '';
        if (!text.trim()) {
            log('err', 'Search', 'Query text is required');
            return;
        }
        let filter = null;
        try {
            filter = readFilterObject();
        } catch (e) {
            log('err', 'Search', e.message);
            return;
        }
        const topK = parseInt(document.getElementById('query-topk')?.value ?? '10', 10) || 10;
        const payload = {
            text,
            top_k: topK,
            ...connectionPayload(),
            ...namespacePayload(),
        };
        if (filter) payload.filter = filter;
        try {
            const { data } = await window.axios.post('/vectora/api/query', payload);
            if (!data.ok) {
                log('err', 'Search failed', JSON.stringify(data, null, 2));
                return;
            }
            const tbody = document.querySelector('#query-results tbody');
            if (tbody) {
                tbody.innerHTML = '';
                for (const m of data.matches ?? []) {
                    const tr = document.createElement('tr');
                    tr.className = 'border-t border-white/10';
                    tr.innerHTML = `
                        <td class="py-2 pr-3 font-mono text-xs">${escapeHtml(m.id)}</td>
                        <td class="py-2 pr-3 text-emerald-400">${m.score}</td>
                        <td class="py-2 text-xs text-zinc-400">${escapeHtml(JSON.stringify(m.metadata ?? {}))}</td>`;
                    tbody.appendChild(tr);
                }
            }
            log('ok', `Search: ${data.matches?.length ?? 0} matches`, text.slice(0, 120));
        } catch (e) {
            const body = e.response?.data;
            log('err', 'Search error', body ? JSON.stringify(body, null, 2) : e.message);
        }
    });

    document.getElementById('form-delete')?.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const ids = document.getElementById('delete-ids')?.value ?? '';
        if (!ids.trim()) {
            log('err', 'Delete', 'Enter at least one id');
            return;
        }
        const payload = { ids, ...connectionPayload(), ...namespacePayload() };
        try {
            const { data } = await window.axios.post('/vectora/api/delete', payload);
            if (!data.ok) {
                log('err', 'Delete failed', JSON.stringify(data, null, 2));
                return;
            }
            log('ok', 'Deleted vectors', (data.deleted_ids ?? []).join(', '));
            refreshStats();
        } catch (e) {
            const body = e.response?.data;
            log('err', 'Delete error', body ? JSON.stringify(body, null, 2) : e.message);
        }
    });

    document.querySelectorAll('input[name="upsert-mode"]').forEach((r) => {
        r.addEventListener('change', () => {
            const mode = document.querySelector('input[name="upsert-mode"]:checked')?.value;
            document.getElementById('upsert-text-wrap')?.classList.toggle('hidden', mode !== 'text');
            document.getElementById('upsert-vector-wrap')?.classList.toggle('hidden', mode !== 'vector');
        });
    });
});
