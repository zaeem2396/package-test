# Vectora (`vectora/laravel-pinecone`) in this app

This Laravel app includes **[Vectora](https://github.com/zaeem2396/vectora)** — a community-maintained Laravel SDK for Pinecone’s REST API (upsert, query, delete, stats, embeddings helpers, optional Eloquent sync). Pinecone does not ship an official PHP client; Vectora is intended as a practical Laravel integration.

## Install (already done here)

`composer.json` uses a VCS repository and requires `vectora/laravel-pinecone`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/zaeem2396/vectora"
    }
],
"require": {
    "vectora/laravel-pinecone": "dev-main"
},
"minimum-stability": "dev",
"prefer-stable": true
```

The package **auto-discovers** its service provider and registers the **`Pinecone`** facade.

## Configuration

1. **Publish config** (already published in this repo as `config/pinecone.php`):

   ```bash
   php artisan vendor:publish --tag=pinecone-config
   ```

2. **Environment variables** — set at minimum:

   | Variable | Purpose |
   |----------|---------|
   | `PINECONE_API_KEY` | Pinecone API key |
   | `PINECONE_HOST` | Data-plane host for your index (from Pinecone console; not the control-plane URL) |

   Optional / common:

   | Variable | Purpose |
   |----------|---------|
   | `PINECONE_INDEX` | Default **connection name** under `config('pinecone.indexes')` (default: `default`) |
   | `PINECONE_NAMESPACE` | Default namespace for upsert/query/delete when not specified |
   | `PINECONE_EMBEDDING_DRIVER` | `deterministic` (no network) or `openai` for `Pinecone::embed()` |
   | `PINECONE_EMBEDDING_DETERMINISTIC_DIMENSIONS` | Must match index dimension if using `deterministic` embeddings |
   | `OPENAI_API_KEY` / `PINECONE_OPENAI_API_KEY` | For OpenAI embedding driver |

   See `config/pinecone.php` and the upstream docs: [installation](https://github.com/zaeem2396/vectora/blob/main/doc/installation.md), [Laravel](https://github.com/zaeem2396/vectora/blob/main/doc/laravel.md), [embeddings](https://github.com/zaeem2396/vectora/blob/main/doc/embeddings.md).

3. **Index dimension** — vectors you upsert must match the index dimension. The **`vectora:test-pinecone`** command uses **`Pinecone::embed()`** on real text, so your embedding driver must output vectors with the same dimension as the index (see **Queues and embeddings** below).

## Vectora Studio (real-time web PoC)

Open **`/vectora/studio`** (named route `vectora.studio`). The welcome page also links to it as **Vectora Studio**.

**Testing guide:** [VECTORA_STUDIO_TESTING.md](VECTORA_STUDIO_TESTING.md) — endpoint map, automated checks (`composer test:vectora-studio-api`), sample form values, and `curl` hints.

**Assets:** If `public/build/manifest.json` exists (after `npm run build`) or `public/hot` exists (`npm run dev`), the page uses **Vite** (`resources/css/app.css` + `resources/js/vectora-studio.js`). Otherwise it **still loads the full UI** using the Tailwind **Play CDN**, **Axios** from jsDelivr, and **`public/js/vectora-studio.js`** — no Node 20 or `npm` required for local demos (a slim banner notes this mode; production should use a proper Vite build).

The UI includes:

- **Live index stats** — polls `GET /vectora/api/stats` every five seconds (dimension, total vector count, metric, namespace breakdown).
- **Upsert / update** — send **text** (embedded via `Pinecone::embed()`) or a **raw vector JSON array**; optional metadata JSON. In Pinecone, upserting an existing id overwrites that vector.
- **Semantic search** — embeds your query and calls `POST /vectora/api/query` with optional metadata filter JSON.
- **Delete** — `POST /vectora/api/delete` with comma- or newline-separated ids for the selected connection/namespace scope.
- **Activity feed** — append-only log of operations in the browser session.

This route is **not** authenticated; use only on trusted networks or behind your own access controls.

## Smoke test: real text embeddings + semantic query

The **`vectora:test-pinecone`** command:

1. Calls **`describe_index_stats`** (dimension, counts, metric).
2. **Upserts four short text passages** as real embeddings via **`Pinecone::embed()`** (ids like `vectora-seed-laravel-queues`, `vectora-seed-pinecone-vectors`, …). Metadata includes `source=vectora:test-pinecone-seed` for filtering.
3. Runs a **semantic query** with a default paraphrase aimed at the Laravel queues doc, **`filter`**’d to those seed records only, and prints ranked matches (use **`--query="..."`** to try your own phrase).
4. Optionally **deletes** the four seed ids (`--cleanup`).

```bash
php artisan vectora:test-pinecone
```

Leave the vectors in the index (default), or remove them after a successful run:

```bash
php artisan vectora:test-pinecone --cleanup
```

Custom query or connection:

```bash
php artisan vectora:test-pinecone --query="What is a vector database for embeddings?"
php artisan vectora:test-pinecone --connection=my_other_index
```

**Package-built diagnostics:**

```bash
php artisan pinecone:sync
```

prints index stats (similar to step 1 above). **`pinecone:flush`** can delete an entire namespace — use with care (requires `--force` in production).

## Automated tests (live API)

Integration tests are tagged **`pinecone`** and **`pinecone-poc`**. They are **skipped** unless `PINECONE_API_KEY` and a resolvable **host** for the default connection are set (typically via `.env`).

```bash
composer test:poc
# equivalent:
php artisan test --group=pinecone-poc
```

Wider Pinecone group (if you add more tests under `@Group('pinecone')`):

```bash
composer test:pinecone
```

CI without Pinecone credentials should keep skipping these tests; no mocks are required for the default pipeline.

## Application code pointers

| Item | Role |
|------|------|
| `app/Http/Controllers/VectoraStudioController.php` | Web UI + JSON API for `/vectora/studio` |
| `resources/views/vectora/studio.blade.php` | Studio layout (Tailwind) |
| `resources/js/vectora-studio.js` | Live polling, forms, activity feed (Vite entry) |
| `public/js/vectora-studio.js` | Same logic when Vite is not used (CDN fallback) |
| `app/Console/Commands/PineconeTestCommand.php` | `vectora:test-pinecone` — embed real texts, semantic query (filtered), optional delete |
| `tests/Feature/PineconeVectoraIntegrationTest.php` | PHPUnit round-trip against real Pinecone when configured |
| `config/pinecone.php` | Published Vectora configuration |

In your own code, prefer the **`Pinecone`** facade or type-hint **`Vectora\Pinecone\Contracts\VectorStoreContract`** and DTOs such as `UpsertVectorsRequest`, `QueryVectorsRequest`, `VectorRecord`, `DeleteVectorsRequest` (see `vendor/vectora/laravel-pinecone/doc/core.md`).

## Queues and embeddings

- If you use **queued** upserts/deletes or Eloquent **`HasEmbeddings`** with `PINECONE_ELOQUENT_SYNC=queued`, run a worker: `php artisan queue:work`.
- For **semantic** search with real text, configure the **OpenAI** (or compatible) embedding driver and ensure the **embedding dimension matches your Pinecone index** (e.g. `text-embedding-3-small` with default size 1536).

## Troubleshooting

- **Query `matches` order** — Pinecone may not guarantee sort order by score in the JSON payload. Production code should sort by `score` if you need a deterministic ranking (the smoke command and integration test do this).

- **`Pinecone api_key is not configured`** — set `PINECONE_API_KEY` and clear config cache: `php artisan config:clear`.
- **Dimension mismatch** from Pinecone — your vectors must match `describe_index_stats.dimension`. The smoke command avoids this by reading stats first; custom code must match the index.
- **Wrong host** — use the index-specific **data plane** host from the Pinecone dashboard, not `https://api.pinecone.io` (control plane).
- **401 / 403** — rotate or fix the API key; see Vectora `ApiException` helpers in [dx.md](https://github.com/zaeem2396/vectora/blob/main/doc/dx.md).

For full package behavior, see the **[vectora repository](https://github.com/zaeem2396/vectora)** and its `doc/` directory.
