# Vectora Studio — endpoint checks & form testing guide

This document lists every **Vectora Studio** route, how we verify them, and **example values** you can paste into the UI to exercise Pinecone end-to-end.

## Endpoint map

| Method | Path | Purpose |
|--------|------|---------|
| `GET` | `/vectora/studio` | HTML UI (`vectora.studio`) |
| `GET` | `/vectora/api/stats` | `describe_index_stats` → dimension, counts, metric, namespaces |
| `POST` | `/vectora/api/upsert` | Upsert one vector (text → embed, or raw JSON array) |
| `POST` | `/vectora/api/query` | Semantic search (query text → embed → `query`) |
| `POST` | `/vectora/api/delete` | Delete vectors by id list |

All JSON endpoints live on the **web** middleware group (session + **CSRF**). The Blade UI sends `X-CSRF-TOKEN` automatically; for `curl` you must pass a session cookie and token (see below).

## Automated verification (CI-safe)

PHPUnit exercises routing, validation, and “Pinecone not configured” behaviour **without** requiring a live index for most cases:

```bash
composer test:vectora-studio-api
# same as:
php artisan test --group=vectora-studio-api
```

What these tests assert:

| Check | Expected |
|--------|----------|
| `GET /vectora/studio` | `200` |
| `GET /vectora/api/stats` with `PINECONE_API_KEY` cleared | `503`, `ok: false` |
| `POST` upsert / query / delete with key cleared | `503`, `ok: false` |
| `GET /vectora/api/stats?connection=invalid` (with gate config) | `422` |
| `POST /vectora/api/upsert` missing id / vector source / both text+vector | `422` |
| `POST /vectora/api/upsert` with invalid `vector` JSON | `422` (parsed **before** calling Pinecone) |
| `POST /vectora/api/query` without `text` | `422` |
| `POST /vectora/api/delete` with empty ids | `422` |

Live Pinecone round-trips are covered separately:

```bash
composer test:poc
```

## Manual checklist (real Pinecone)

Prerequisites: `PINECONE_API_KEY`, `PINECONE_HOST`, and embedding settings so **`Pinecone::embed()` returns a vector whose length equals your index dimension** (see `VECTORA_USAGE.md`).

1. Open `/vectora/studio` — page loads; **Index pulse** updates every ~5s (or shows an error message from the API).
2. **Upsert** — submit once; **Total vectors** in Index pulse should increase (may lag a second; use **Refresh stats**).
3. **Semantic search** — use related text; your new id should appear in results with a strong score.
4. **Delete** — remove the test ids; vector count should drop.

If any step fails, open the browser **Network** tab and inspect the JSON body of `/vectora/api/*` responses (`message`, `errors`, `pinecone_status`).

## Form examples (copy-paste)

Use a **unique vector id** per run so you do not collide with old tests (e.g. `studio-demo-{date}-{random}`).

### Index scope (applies to all actions)

| Field | Example | Notes |
|--------|---------|--------|
| Connection | `default` | Must match a key under `config('pinecone.indexes')`. |
| Namespace | *(empty)* | Uses `.env` / connection default. |
| Pinecone default namespace | ☐ unchecked | Check only if you intentionally want the unscoped default namespace. |

### Upsert / update — mode: Text → embedding

| Field | Example value |
|--------|----------------|
| Vector id | `studio-demo-001` |
| Content | `Vectora connects Laravel to Pinecone for upserts and semantic search.` |
| Metadata (JSON) | `{"title":"Studio demo","tags":["demo","vectora"],"env":"local"}` |

**Update the same record:** change **Content** and/or **Metadata**, keep the same **Vector id**, submit again — Pinecone overwrites that id.

### Upsert / update — mode: Raw vector JSON

Only use this if you can paste **exactly** `dimension` floats (see **Index pulse → Dimension** after stats load).

| Field | Example |
|--------|---------|
| Vector id | `studio-raw-001` |
| Vector JSON | `[0.01, -0.02, 0.03, ...]` ← must have length = index dimension |

### Semantic search

| Field | Example |
|--------|---------|
| Query | `How does Laravel talk to Pinecone?` |
| Top K | `10` |
| Metadata filter | `{"tags":{"$in":["demo"]}}` *(optional; must be valid Pinecone filter JSON)* |

### Delete by id

| Field | Example |
|--------|---------|
| Ids | `studio-demo-001` or multiple lines / commas: `studio-demo-001, studio-raw-001` |

## Example `curl` (session + CSRF)

Replace `APP_URL`, cookie, and token with values from your browser after logging into the same app (or any session that can access these routes).

```bash
BASE="http://127.0.0.1:8000"
COOKIE="laravel_session=YOUR_SESSION_VALUE"
CSRF="YOUR_CSRF_TOKEN_FROM_META_OR_COOKIE"

curl -sS "${BASE}/vectora/api/stats" \
  -H "Cookie: ${COOKIE}" \
  -H "Accept: application/json"

curl -sS -X POST "${BASE}/vectora/api/upsert" \
  -H "Cookie: ${COOKIE}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-XSRF-TOKEN: ${CSRF}" \
  -d '{"id":"curl-demo-1","text":"Hello from curl","metadata":{"via":"curl"}}'

curl -sS -X POST "${BASE}/vectora/api/query" \
  -H "Cookie: ${COOKIE}" \
  -H "Content-Type: application/json" \
  -H "X-XSRF-TOKEN: ${CSRF}" \
  -d '{"text":"Hello","top_k":5}'

curl -sS -X POST "${BASE}/vectora/api/delete" \
  -H "Cookie: ${COOKIE}" \
  -H "Content-Type: application/json" \
  -H "X-XSRF-TOKEN: ${CSRF}" \
  -d '{"ids":"curl-demo-1"}'
```

Laravel often expects the **decrypted** `XSRF-TOKEN` cookie value in `X-XSRF-TOKEN` for JSON posts; the Studio page uses `X-CSRF-TOKEN` from the meta tag — either works when aligned with your app’s `VerifyCsrfToken` setup.

## UI reference

Open **`/vectora/studio`** in the browser for the live layout: **Index scope**, **Index pulse**, **Upsert / update**, **Semantic search**, **Delete by id**, and **Activity**. Field names match the JSON keys in the tables above (`id`, `text`, `vector`, `metadata`, `connection`, `namespace`, etc.).

## Related docs

- [VECTORA_USAGE.md](VECTORA_USAGE.md) — install, env, Vite vs CDN fallback, package links.
