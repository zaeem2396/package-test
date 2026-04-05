# package-test — Pinecone (Vectora) demo

Laravel app demonstrating **[vectora/laravel-pinecone](https://github.com/zaeem2396/vectora)** against [Pinecone](https://www.pinecone.io/): config, **Vectora Studio** UI, smoke command, and tests.

## Switch to this branch

The Pinecone / Vectora work lives on a dedicated branch. After cloning, check it out before running anything:

```bash
git fetch origin
git checkout feat/pinecone-laravel
```

Use your remote name if it is not `origin` (for example `package-test`).

## Run with Docker (recommended)

**This project is intended to be run with Docker Compose.** The stack provides PHP, MySQL, and the Laravel app on port **8000**.

1. **Prerequisites:** [Docker](https://docs.docker.com/get-docker/) and Docker Compose; Pinecone credentials in `.env` (see below).

2. **From the repo root:**

   ```bash
   docker compose up -d --build
   ```

3. **Install dependencies and migrate** (first time or after pulling changes):

   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate --force
   docker compose exec app php artisan migrate --force
   ```

4. **Pinecone environment** — `config/pinecone.php` is already in the repo; tune behavior with `.env`. Edit `.env` on the host (it is mounted into the container). Set at least:

   | Variable | Description |
   |----------|-------------|
   | `PINECONE_API_KEY` | Pinecone API key |
   | `PINECONE_HOST` | Index host URL (data plane, not the control API) |

   For MySQL inside Docker, keep `DB_HOST=mysql` and the DB variables aligned with `docker-compose.yml` (or your overrides). Optional: `PINECONE_NAMESPACE`, `PINECONE_INDEX`, `PINECONE_EMBEDDING_DRIVER`, `PINECONE_EMBEDDING_DETERMINISTIC_DIMENSIONS`, `OPENAI_API_KEY` so **`Pinecone::embed()`** matches your index **dimension**.

   ```bash
   docker compose exec app php artisan config:clear
   ```

5. **Open the app**

   - **Vectora Studio:** [http://localhost:8000/vectora/studio](http://localhost:8000/vectora/studio)
   - **Welcome:** [http://localhost:8000/](http://localhost:8000/)

6. **CLI smoke tests inside the container**

   ```bash
   docker compose exec app php artisan vectora:test-pinecone
   docker compose exec app php artisan pinecone:sync
   ```

If Vite assets are not built in the image, the studio still loads via CDN + `public/js/vectora-studio.js`. To build front-end assets on the host (Node 20+): `npm install && npm run build`.

## Run without Docker (optional)

If you prefer a local PHP install: `composer install`, copy `.env`, `php artisan key:generate`, configure SQLite or MySQL yourself, `php artisan migrate`, then `php artisan serve`. You must still set Pinecone variables and match embedding dimension to your index.

## Smoke test (CLI)

With Docker:

```bash
docker compose exec app php artisan vectora:test-pinecone
docker compose exec app php artisan vectora:test-pinecone --cleanup   # remove seed vectors after
docker compose exec app php artisan pinecone:sync                     # index stats (package command)
```

Without Docker, run the same `php artisan …` commands locally.

## Tests

With Docker:

```bash
docker compose exec app composer test
docker compose exec app composer run test:vectora-studio-api   # API routes, no live Pinecone required
docker compose exec app composer run test:poc                  # live Pinecone when `.env` is configured
```

## Documentation

| Doc | Contents |
|-----|----------|
| [VECTORA_USAGE.md](VECTORA_USAGE.md) | Install, env, Studio, smoke command, package links |
| [VECTORA_STUDIO_TESTING.md](VECTORA_STUDIO_TESTING.md) | Endpoints, sample payloads, `curl` notes |

## License

MIT (see `composer.json`).
