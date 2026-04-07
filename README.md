# Vectora RAG knowledge base (Laravel PoC)

Sample Laravel app that wires **[vectora/laravel-pinecone](https://github.com/zaeem2396/vectora)** into a small **AI knowledge base**:

- Ingest `Post` records → chunk → embed → **Pinecone**
- **RAG** Q&amp;A and **semantic search** over those chunks
- **Web UI** at `/` plus JSON APIs for demos
- Fluent **`Knowledge`** facade (timings + optional sources)

Configuration: `config/pinecone.php` (Vectora) and `config/knowledge.php` (chunking / RAG defaults).

---

## Getting the code

After cloning, **switch to the branch that contains this PoC** (until it is merged into `main`):

```bash
git clone git@github.com:zaeem2396/package-test.git
cd package-test
git checkout feat/pinecone-laravel
```

If you already cloned on `main`, `git fetch origin && git checkout feat/pinecone-laravel` does the same.

---

## What you need before you start

| Requirement | Notes |
|-------------|--------|
| **PHP** | 8.2+ (Dockerfile uses 8.4) |
| **Composer** | Install PHP dependencies |
| **Node.js** (optional) | Only if you run `npm run build` / `npm run dev` for Vite assets |
| **Pinecone** | Account, an index, **API key**, and index **host** (data plane URL) |
| **OpenAI** (recommended) | API key for chat answers; for embeddings too if your index dimension matches the model (often **1536** for `text-embedding-3-small`) |
| **Database** | **MySQL** for `posts`, sessions, and queues (matches `docker-compose.yml`; SQLite is optional) |

**Important:** Vector dimension from your **embedding driver** must equal your **Pinecone index dimension**. If the index is **1024-D**, use `PINECONE_EMBEDDING_DRIVER=deterministic` and `PINECONE_EMBEDDING_DETERMINISTIC_DIMENSIONS=1024` until you create a **1536-D** index for OpenAI embeddings.

---

## Setup path A — Docker Compose (recommended)

From the project root:

### 1. Environment file

```bash
cp .env.example .env
```

Edit `.env` inside the repo (host bind-mounts it into the container). **Minimum for a working demo:**

- `PINECONE_API_KEY`
- `PINECONE_HOST` (from Pinecone console for your index)
- `OPENAI_API_KEY` (if `VECTORA_LLM_DRIVER=openai`)
- Match **embedding driver + dimensions** to your index (see `.env.example` comments)

The Compose file injects **`DB_HOST=mysql`** (service name) and DB credentials into the `app` container, so they override `DB_HOST` / `DB_*` from `.env` while you run under Docker. The bundled **MySQL 8** service uses database `laravel`, user `laravel`, password `secret` (see `docker-compose.yml`).

### 2. Start stack and install

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate --force
```

### 3. Database and sample data

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

### 4. Index posts in Pinecone

```bash
docker compose exec app php artisan vector:ingest
```

Use `php artisan vector:ingest --id=1` to test one post. For queued ingestion:

```bash
docker compose exec app php artisan vector:ingest --queue
```

Ensure the `queue-worker` service is running (included in `docker-compose.yml`).

### 5. Open the app

The `app` container runs `php artisan serve` on port **8000**.

- **UI:** [http://localhost:8000](http://localhost:8000)
- **phpMyAdmin** (if you use the bundled service): [http://localhost:8080](http://localhost:8080)

---

## Setup path B — Local PHP (no Docker), MySQL

### 1. Install and env

```bash
composer install
cp .env.example .env
php artisan key:generate
```

In `.env`, set **MySQL** (same idea as Docker, but host is your machine):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=your_password
```

Create the database and user in MySQL (example):

```sql
CREATE DATABASE laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'laravel'@'%' IDENTIFIED BY 'your_password';
GRANT ALL ON laravel.* TO 'laravel'@'%';
FLUSH PRIVILEGES;
```

`SESSION_DRIVER=database` and `QUEUE_CONNECTION=database` need the **sessions**, **cache**, and **jobs** tables — run migrations (below).

**Optional — SQLite instead of MySQL:** set `DB_CONNECTION=sqlite`, comment out other `DB_*` lines, run `touch database/database.sqlite`, then migrate. Not the default for this repo.

### 2. Migrate, seed, ingest

```bash
php artisan migrate
php artisan db:seed
php artisan vector:ingest
```

### 3. Run the server

```bash
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

---

## Environment variables (cheat sheet)

Copy from `.env.example` and adjust. Commonly used:

| Variable | Purpose |
|----------|---------|
| `APP_KEY` | `php artisan key:generate` |
| `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | **MySQL** for `posts`, sessions, cache, jobs (`DB_HOST=mysql` in Docker only) |
| `PINECONE_API_KEY` | Pinecone API key |
| `PINECONE_HOST` | Index data-plane URL |
| `PINECONE_INDEX` | Logical connection name (default `default`) |
| `PINECONE_NAMESPACE` | Optional namespace |
| `PINECONE_EMBEDDING_DRIVER` | `openai` or `deterministic` (must match index **dimension**) |
| `PINECONE_EMBEDDING_DETERMINISTIC_DIMENSIONS` | e.g. `1024` when using deterministic + 1024-D index |
| `OPENAI_API_KEY` | Embeddings and/or chat |
| `OPENAI_EMBEDDING_MODEL` | Default `text-embedding-3-small` (typically 1536-D) |
| `VECTORA_LLM_DRIVER` | `openai` for real answers, `stub` for offline stub text |
| `OPENAI_CHAT_MODEL` | e.g. `gpt-4o-mini` |
| `QUEUE_CONNECTION` | `database` or `redis` if you use `vector:ingest --queue` |

---

## Using the web UI

After ingestion:

1. Open `/` — forms for **RAG answer** and **semantic search**.
2. JSON endpoints (POST, `Accept: application/json`, CSRF cookie from the same origin):
   - `/knowledge/ask` — body: `question`, `top_k`, `with_sources`
   - `/knowledge/search` — body: `query`, `top_k`

Controller: `App\Http\Controllers\KnowledgeDemoController`.  
The PoC UI is **not authenticated**; do not expose it publicly without adding auth or network controls.

---

## Artisan commands

| Command | Description |
|---------|-------------|
| `php artisan vector:ingest` | Chunk, embed, upsert all `Post` rows to Pinecone |
| `php artisan vector:ingest --id=N` | Single post |
| `php artisan vector:ingest --queue` | Dispatch `IngestPostJob` per post |

---

## Code API (quick examples)

**RAG (structured payload):**

```php
use App\Models\Post;

$payload = Knowledge::ask('What is the refund policy?')
    ->from(Post::class)
    ->topK(5)
    ->withSources()
    ->answer();

return response()->json($payload);
```

**Semantic search (chunk hits):**

```php
Post::semanticSearch('refund policy', topK: 8);
Post::semanticSearchModels('refund policy', topK: 8);
```

---

## Tests

```bash
php artisan test
```

Live Pinecone round-trip (skipped unless `PINECONE_API_KEY` and `PINECONE_HOST` are set):

```bash
composer run test:pinecone
```

---

## Troubleshooting

| Symptom | Likely cause | What to do |
|--------|----------------|------------|
| `PINECONE_API_KEY is not set` | Missing env | Set key in `.env`, clear config cache |
| `Vector dimension … does not match … index` | Embedding size ≠ index | Align driver/model with index dimension, or recreate index |
| Empty RAG / no search hits | Nothing ingested | Run `vector:ingest`; check namespace |
| OpenAI errors | Bad/missing key | Set `OPENAI_API_KEY`; check `VECTORA_LLM_DRIVER` |
| Queued ingest never runs | No worker | Run `queue:work` or Docker `queue-worker` service |
| `SQLSTATE[HY000]` / connection refused | Wrong `DB_HOST` / MySQL down | Local: `DB_HOST=127.0.0.1` and running MySQL. Docker: rely on Compose `DB_HOST=mysql` |

More scenarios (validation, queue-only, Tinker snippets): see **[USAGE.md](USAGE.md)**.

---

## Project layout (RAG-related)

| Path | Role |
|------|------|
| `app/Services/KnowledgeIngestionService.php` | Ingestion + Vectora `Vector::ingest()` |
| `app/Services/KnowledgeRetrievalService.php` | Embeddings + `VectorStoreContract::query` |
| `app/Services/KnowledgeRagService.php` | RAG pipeline + logging |
| `app/Http/Controllers/KnowledgeDemoController.php` | Web + JSON demo |
| `app/Console/Commands/VectorIngestCommand.php` | `vector:ingest` |
| `config/knowledge.php` | Chunk size, top K, system prompt |
| `config/pinecone.php` | Pinecone / Vectora (merged with package) |

---

## License

MIT (see `composer.json`).
