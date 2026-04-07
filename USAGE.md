# Usage reference (PoC)

**First-time setup:** follow **[README.md](README.md)** — this file adds **Tinker examples**, **pass/fail scenarios**, and extra detail.

This PoC expects **MySQL** by default (see Docker Compose or local MySQL in the README). **SQLite** is optional if you prefer a file database.

---

## Tinker (manual checks)

```bash
php artisan tinker
```

```php
use App\Models\Post;

// RAG
$payload = \Knowledge::ask('What topics do the posts cover?')
    ->from(Post::class)
    ->topK(5)
    ->withSources()
    ->answer();

$payload->toArray();

// Semantic search
Post::semanticSearch('refund', topK: 5);
```

---

## Pass / fail scenarios

### Missing Pinecone API key

- **Command:** `php artisan vector:ingest`
- **Fail:** `PINECONE_API_KEY is not set. Configure Pinecone before ingesting.`
- **Fix:** Set `PINECONE_API_KEY` in `.env`.

### Embedding dimension mismatch

- **Symptom:** `Vector dimension … does not match the dimension of the index …`
- **Fix:** Use an embedding output size that matches the index, or create an index with the correct dimension (e.g. 1536 for typical OpenAI `text-embedding-3-small`).

### Missing OpenAI key with `VECTORA_LLM_DRIVER=openai`

- **Symptom:** API errors when calling `Knowledge::ask()->answer()`.
- **Fix:** Set `OPENAI_API_KEY`, or use `VECTORA_LLM_DRIVER=stub` for stub answers.

### No vectors in the index

- **Symptom:** Empty sources / zero semantic matches.
- **Fix:** Run `php artisan vector:ingest` (and correct namespace if you use one).

### Queue ingest stuck

- **Symptom:** Jobs never processed.
- **Fix:** Run `php artisan queue:work` or keep the Docker `queue-worker` service up.

---

## JSON demo endpoints

Same session cookie as the browser UI (CSRF required for POST from browser; API clients must send `X-CSRF-TOKEN` or use Sanctum/API routes if you add them later).

- `POST /knowledge/ask` — `{ "question": "…", "top_k": 5, "with_sources": true }`
- `POST /knowledge/search` — `{ "query": "…", "top_k": 8 }`
