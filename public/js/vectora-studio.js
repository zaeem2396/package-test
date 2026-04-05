/**
 * Vectora Studio (no-Vite fallback). Keep behavior aligned with resources/js/vectora-studio.js.
 * Loaded when public/build/manifest.json and public/hot are absent.
 */
(function () {
    'use strict';

    if (typeof axios === 'undefined') {
        console.error('vectora-studio: axios not loaded');
        return;
    }

    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = meta.getAttribute('content');
    }
    axios.defaults.headers.common['Accept'] = 'application/json';
    axios.defaults.headers.common['Content-Type'] = 'application/json';

    function cfg() {
        var el = document.getElementById('vectora-config');
        if (!el || !el.textContent) {
            return { configured: false, connections: ['default'] };
        }
        try {
            return JSON.parse(el.textContent);
        } catch (e) {
            return { configured: false, connections: ['default'] };
        }
    }

    function log(type, title, detail) {
        var feed = document.getElementById('activity-feed');
        if (!feed) return;
        var row = document.createElement('div');
        row.className =
            'studio-log border border-white/10 rounded-lg px-3 py-2 text-sm bg-white/5 animate-studio-in';
        var t = new Date().toLocaleTimeString();
        var colors = { ok: 'text-emerald-400', err: 'text-rose-400', info: 'text-sky-400' };
        var colorClass = colors[type] || 'text-zinc-200';
        row.innerHTML =
            '<div class="flex justify-between gap-2 text-xs text-zinc-500"><span>' +
            type.toUpperCase() +
            '</span><span>' +
            t +
            '</span></div>' +
            '<div class="font-medium ' +
            colorClass +
            '">' +
            escapeHtml(title) +
            '</div>' +
            (detail
                ? '<pre class="mt-1 text-xs text-zinc-400 whitespace-pre-wrap break-all max-h-32 overflow-y-auto">' +
                  escapeHtml(detail) +
                  '</pre>'
                : '');
        feed.prepend(row);
        while (feed.children.length > 40) {
            feed.removeChild(feed.lastChild);
        }
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function connectionPayload() {
        var sel = document.getElementById('field-connection');
        var v = sel && sel.value;
        return v && v !== '' ? { connection: v } : {};
    }

    function namespacePayload() {
        var nsEl = document.getElementById('field-namespace');
        var ns = nsEl && nsEl.value ? nsEl.value.trim() : '';
        var pineEl = document.getElementById('field-pinecone-default-ns');
        var pineDefault = pineEl && pineEl.checked;
        var out = {};
        if (pineDefault) {
            out.pinecone_default_namespace = true;
        } else if (ns) {
            out.namespace = ns;
        }
        return out;
    }

    async function refreshStats() {
        var dot = document.getElementById('live-dot');
        var banner = document.getElementById('config-banner');
        var c = cfg();
        if (!c.configured) {
            if (banner) banner.classList.remove('hidden');
            if (dot) dot.classList.add('hidden');
            return;
        }
        if (banner) banner.classList.add('hidden');
        if (dot) dot.classList.remove('hidden');
        try {
            var res = await axios.get('/vectora/api/stats', { params: connectionPayload() });
            var data = res.data;
            if (!data.ok) {
                document.getElementById('stat-dim').textContent = '—';
                document.getElementById('stat-count').textContent = '—';
                document.getElementById('stat-metric').textContent = '—';
                document.getElementById('stat-time').textContent = data.message || 'Error';
                return;
            }
            document.getElementById('stat-dim').textContent = String(data.dimension);
            document.getElementById('stat-count').textContent = String(data.total_vector_count);
            document.getElementById('stat-metric').textContent = data.metric || '—';
            document.getElementById('stat-time').textContent = data.fetched_at
                ? new Date(data.fetched_at).toLocaleTimeString()
                : '—';
            var nsEl = document.getElementById('stat-namespaces');
            if (nsEl) {
                if (!data.namespaces || !data.namespaces.length) {
                    nsEl.textContent = 'No namespace breakdown';
                } else {
                    nsEl.textContent = data.namespaces
                        .map(function (n) {
                            return n.name + ': ' + n.vector_count;
                        })
                        .join(' · ');
                }
            }
        } catch (e) {
            var msg = e.response && e.response.data && e.response.data.message ? e.response.data.message : e.message;
            document.getElementById('stat-time').textContent = msg;
        }
    }

    function readMetadataObject() {
        var rawEl = document.getElementById('upsert-metadata');
        var raw = rawEl && rawEl.value ? rawEl.value.trim() : '';
        if (!raw) return {};
        try {
            var o = JSON.parse(raw);
            return typeof o === 'object' && o !== null && !Array.isArray(o) ? o : {};
        } catch (e) {
            throw new Error('Metadata must be valid JSON object, e.g. {"category":"docs"}');
        }
    }

    function readFilterObject() {
        var rawEl = document.getElementById('query-filter');
        var raw = rawEl && rawEl.value ? rawEl.value.trim() : '';
        if (!raw) return null;
        try {
            var o = JSON.parse(raw);
            return typeof o === 'object' && o !== null ? o : null;
        } catch (e) {
            throw new Error('Filter must be valid JSON object or empty');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var c = cfg();
        var conn = document.getElementById('field-connection');
        if (conn && c.connections && c.connections.length) {
            conn.textContent = '';
            for (var i = 0; i < c.connections.length; i++) {
                var name = c.connections[i];
                var o = document.createElement('option');
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

        var btnRefresh = document.getElementById('btn-refresh-stats');
        if (btnRefresh) {
            btnRefresh.addEventListener('click', function () {
                refreshStats();
                log('info', 'Stats refresh', 'Manual refresh');
            });
        }

        var formUpsert = document.getElementById('form-upsert');
        if (formUpsert) {
            formUpsert.addEventListener('submit', async function (ev) {
                ev.preventDefault();
                var modeEl = document.querySelector('input[name="upsert-mode"]:checked');
                var mode = modeEl ? modeEl.value : 'text';
                var idEl = document.getElementById('upsert-id');
                var id = idEl && idEl.value ? idEl.value.trim() : '';
                if (!id) {
                    log('err', 'Upsert', 'Vector id is required');
                    return;
                }
                var metadata = {};
                try {
                    metadata = readMetadataObject();
                } catch (e) {
                    log('err', 'Upsert', e.message);
                    return;
                }
                var payload = Object.assign({ id: id, metadata: metadata }, connectionPayload(), namespacePayload());
                if (mode === 'text') {
                    var textEl = document.getElementById('upsert-text');
                    var text = textEl ? textEl.value : '';
                    if (!text.trim()) {
                        log('err', 'Upsert', 'Enter text to embed, or switch to raw vector mode.');
                        return;
                    }
                    payload.text = text;
                } else {
                    var vecEl = document.getElementById('upsert-vector');
                    var vector = vecEl && vecEl.value ? vecEl.value.trim() : '';
                    if (!vector) {
                        log('err', 'Upsert', 'Paste a JSON array of floats matching your index dimension.');
                        return;
                    }
                    payload.vector = vector;
                }
                try {
                    var res = await axios.post('/vectora/api/upsert', payload);
                    var data = res.data;
                    if (!data.ok) {
                        log('err', 'Upsert failed', JSON.stringify(data, null, 2));
                        return;
                    }
                    log('ok', 'Upserted “' + id + '”', 'count=' + data.upserted_count + ' dim=' + data.dimension);
                    refreshStats();
                } catch (e) {
                    var body = e.response && e.response.data;
                    log('err', 'Upsert error', body ? JSON.stringify(body, null, 2) : e.message);
                }
            });
        }

        var formQuery = document.getElementById('form-query');
        if (formQuery) {
            formQuery.addEventListener('submit', async function (ev) {
                ev.preventDefault();
                var qEl = document.getElementById('query-text');
                var text = qEl ? qEl.value : '';
                if (!text.trim()) {
                    log('err', 'Search', 'Query text is required');
                    return;
                }
                var filter = null;
                try {
                    filter = readFilterObject();
                } catch (e) {
                    log('err', 'Search', e.message);
                    return;
                }
                var topkEl = document.getElementById('query-topk');
                var topK = parseInt(topkEl && topkEl.value ? topkEl.value : '10', 10) || 10;
                var payload = Object.assign({ text: text, top_k: topK }, connectionPayload(), namespacePayload());
                if (filter) payload.filter = filter;
                try {
                    var res = await axios.post('/vectora/api/query', payload);
                    var data = res.data;
                    if (!data.ok) {
                        log('err', 'Search failed', JSON.stringify(data, null, 2));
                        return;
                    }
                    var tbody = document.querySelector('#query-results tbody');
                    if (tbody) {
                        tbody.innerHTML = '';
                        var matches = data.matches || [];
                        for (var j = 0; j < matches.length; j++) {
                            var m = matches[j];
                            var tr = document.createElement('tr');
                            tr.className = 'border-t border-white/10';
                            tr.innerHTML =
                                '<td class="py-2 pr-3 font-mono text-xs">' +
                                escapeHtml(m.id) +
                                '</td><td class="py-2 pr-3 text-emerald-400">' +
                                m.score +
                                '</td><td class="py-2 text-xs text-zinc-400">' +
                                escapeHtml(JSON.stringify(m.metadata || {})) +
                                '</td>';
                            tbody.appendChild(tr);
                        }
                    }
                    log('ok', 'Search: ' + (data.matches ? data.matches.length : 0) + ' matches', text.slice(0, 120));
                } catch (e) {
                    var body2 = e.response && e.response.data;
                    log('err', 'Search error', body2 ? JSON.stringify(body2, null, 2) : e.message);
                }
            });
        }

        var formDelete = document.getElementById('form-delete');
        if (formDelete) {
            formDelete.addEventListener('submit', async function (ev) {
                ev.preventDefault();
                var idsEl = document.getElementById('delete-ids');
                var ids = idsEl ? idsEl.value : '';
                if (!ids.trim()) {
                    log('err', 'Delete', 'Enter at least one id');
                    return;
                }
                var payload = Object.assign({ ids: ids }, connectionPayload(), namespacePayload());
                try {
                    var res = await axios.post('/vectora/api/delete', payload);
                    var data = res.data;
                    if (!data.ok) {
                        log('err', 'Delete failed', JSON.stringify(data, null, 2));
                        return;
                    }
                    log('ok', 'Deleted vectors', (data.deleted_ids || []).join(', '));
                    refreshStats();
                } catch (e) {
                    var body3 = e.response && e.response.data;
                    log('err', 'Delete error', body3 ? JSON.stringify(body3, null, 2) : e.message);
                }
            });
        }

        document.querySelectorAll('input[name="upsert-mode"]').forEach(function (r) {
            r.addEventListener('change', function () {
                var checked = document.querySelector('input[name="upsert-mode"]:checked');
                var mode = checked ? checked.value : 'text';
                var tw = document.getElementById('upsert-text-wrap');
                var vw = document.getElementById('upsert-vector-wrap');
                if (tw) tw.classList.toggle('hidden', mode !== 'text');
                if (vw) vw.classList.toggle('hidden', mode !== 'vector');
            });
        });
    });
})();
