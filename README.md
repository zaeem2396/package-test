<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Laravel + Conductor (Orkes / Conductor OSS) — e-commerce workflow demo

Sample app using **[conductor/orkes-laravel](https://github.com/zaeem2396/orkes-laravel)** (`dev-main` via Composer VCS) to run an **`order_processing`** workflow with a small **orders UI** (`/orders`).

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/)
- [Git](https://git-scm.com/) (to clone and switch branches)

### Getting started (step by step)

This workflow demo lives on a **feature branch**, not `main`. Clone the repo, then **check out the branch** before installing dependencies or running Docker.

1. **Clone and enter the project**

   ```bash
   git clone git@github.com:zaeem2396/package-test.git
   cd package-test
   ```

   (Use HTTPS if you prefer: `https://github.com/zaeem2396/package-test.git`.)

2. **Switch to the Conductor / Orkes demo branch**

   ```bash
   git fetch origin
   git checkout feature/conductor-orkes-poc
   ```

   If your remote is not named `origin`, use the name shown by `git remote -v` (for example `git fetch package-test` and `git checkout feature/conductor-orkes-poc`).

3. **Environment file**

   ```bash
   cp .env.example .env
   ```

   Edit `.env` for your setup:

   - **Docker Compose (default in this branch):** keep MySQL/redis settings aligned with `docker-compose.yml` (the stack provides `mysql`, `redis`, etc.). The first `docker compose up` will need a valid `APP_KEY`; generate one after containers are up with `docker compose exec app php artisan key:generate --force` if needed.
   - **Orkes Cloud:** merge values from [docs/env-orkes-snippet.env](docs/env-orkes-snippet.env) and set `CONDUCTOR_SERVER_URL`, `CONDUCTOR_AUTH_KEY`, and `CONDUCTOR_AUTH_SECRET`. See [docs/ORKEES_CLOUD_SETUP.md](docs/ORKEES_CLOUD_SETUP.md).
   - **Local Conductor OSS in Compose:** you can point `CONDUCTOR_SERVER` at `http://conductor-server:8080/api` once the `conductor-server` service is healthy (see [CONDUCTOR_ECOMMERCE_TESTING.md](CONDUCTOR_ECOMMERCE_TESTING.md)).

   Never commit real secrets; `.env` is gitignored.

4. **Optional: local SDK clone for live `orkes-laravel`**

   If you have `../orkes-laravel` next to this repo, `docker-compose.yml` bind-mounts it over `vendor/conductor/orkes-laravel`. Skip this if you only use the package from Composer.

5. **Build and start the stack**

   From the **repository root** (`package-test/`):

   ```bash
   docker compose up -d --build
   ```

   Wait until app/database services are healthy (`docker compose ps`).

6. **Application key and database (first run)**

   ```bash
   docker compose exec app php artisan key:generate --force
   docker compose exec app php artisan migrate --force
   ```

7. **Run workers (required for workflow tasks)**

   In another terminal, either start the dedicated worker service (if defined in your compose file) or:

   ```bash
   docker compose exec app php artisan conductor:work
   ```

   For **Orkes**, workers must use the same `CONDUCTOR_SERVER_URL` and credentials as the app. Tasks stay **Scheduled** until a worker polls.

8. **Try the demo**

   - Open the **orders UI:** [http://localhost:8000/orders](http://localhost:8000/orders)
   - Seed demo orders and workflows:

     ```bash
     docker compose exec app php artisan demo:orders
     ```

   If you start a workflow manually (`conductor:start`), ensure the `order_id` exists in the `orders` table or inventory tasks will fail—see [CONDUCTOR_ECOMMERCE_TESTING.md](CONDUCTOR_ECOMMERCE_TESTING.md).

**Orkes-only shortcut:** import [docs/orkes/order_processing_workflow.json](docs/orkes/order_processing_workflow.json) into your Orkes cluster, configure `.env` as in [docs/ORKEES_CLOUD_SETUP.md](docs/ORKEES_CLOUD_SETUP.md), then run steps 5–8 above.

### Run with Docker (summary)

`docker-compose.yml` bind-mounts `../orkes-laravel` over `vendor/conductor/orkes-laravel` when that path exists (live SDK during development). Remove or adjust that mount if you rely only on Composer.

| Service | URL / command |
|--------|----------------|
| **Orders UI** | [http://localhost:8000/orders](http://localhost:8000/orders) |
| **Conductor API** | `http://localhost:8090/api` (UI often at [http://localhost:8090](http://localhost:8090)) |
| **Workers** | `docker compose exec app php artisan conductor:work` — for **Orkes**, ensure `.env` has `CONDUCTOR_SERVER_URL` + auth; `conductor-worker` uses the same `env_file` (see [docs/ORKEES_CLOUD_SETUP.md](docs/ORKEES_CLOUD_SETUP.md)) |
| **Demo orders** | `docker compose exec app php artisan demo:orders` |
| **Start workflow (CLI)** | `conductor:start` — [section below](#start-workflow-cli-conductorstart) |

### Start workflow (CLI): conductor:start

After the stack is up and workers are polling, you can start **`order_processing`** from Artisan (same as in [docs/ORKEES_CLOUD_SETUP.md](docs/ORKEES_CLOUD_SETUP.md)). Use `docker compose exec app` so the command runs inside the app container with the correct `.env`.

```bash
docker compose exec app php artisan conductor:start order_processing \
  --input='{"order_id":1,"amount":99.99,"user_email":"you@example.com"}'
```

For this demo app, **`order_id` must refer to an existing row** in the `orders` table (create one via the UI or `demo:orders`), or later tasks can fail with “order not found.” Optional flags from the package: `--correlation-id=…`, `--wf-version=…` (see `php artisan conductor:start --help`).

### Documentation (this repo)

| Doc | Description |
|-----|-------------|
| [CONDUCTOR_ECOMMERCE_TESTING.md](CONDUCTOR_ECOMMERCE_TESTING.md) | Full runbook: stack, env, workflow, handlers, troubleshooting |
| [docs/ORKEES_CLOUD_SETUP.md](docs/ORKEES_CLOUD_SETUP.md) | **Orkes Cloud:** credentials, JSON to import, `.env`, `conductor:start` |
| [docs/orkes/order_processing_workflow.json](docs/orkes/order_processing_workflow.json) | Workflow definition to paste/import into Orkes |
| [docs/ECOMMERCE_WORKFLOW_DEMO.md](docs/ECOMMERCE_WORKFLOW_DEMO.md) | Short overview of the workflow |
| [docs/ORKEES_LARAVEL_PACKAGE_BUGS.md](docs/ORKEES_LARAVEL_PACKAGE_BUGS.md) | Integration notes / upstream tracking |

### Automated tests

```bash
docker compose exec app php artisan test
```

Uses SQLite in-memory in `phpunit.xml`; the app image includes `pdo_sqlite`. Tests use `Conductor::fake()` (no live Conductor required).

### License

This demo app follows the same **[MIT license](https://opensource.org/licenses/MIT)** as the default Laravel application template.
