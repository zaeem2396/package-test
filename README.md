<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Laravel + Conductor (Orkes / Conductor OSS) — e-commerce workflow demo

Sample app using **[conductor/orkes-laravel](https://github.com/zaeem2396/orkes-laravel)** (`dev-main` via Composer VCS) to run an **`order_processing`** workflow with a small **orders UI** (`/orders`).

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/)

### Run with Docker (recommended)

From the **repository root** (`package-test/`):

```bash
docker compose up -d --build
```

`docker-compose.yml` bind-mounts `../orkes-laravel` over `vendor/conductor/orkes-laravel` when that path exists (live SDK during development). Remove or adjust that mount if you rely only on Composer.

First-time database:

```bash
docker compose exec app php artisan migrate --force
```

**Orkes Cloud quick path:** copy [docs/env-orkes-snippet.env](docs/env-orkes-snippet.env) into your `.env`, set `CONDUCTOR_SERVER_URL`, `CONDUCTOR_AUTH_KEY`, and `CONDUCTOR_AUTH_SECRET`, import [docs/orkes/order_processing_workflow.json](docs/orkes/order_processing_workflow.json) into Orkes, then run `docker compose up -d` and `php artisan conductor:work` (or the `conductor-worker` service). Tasks stay **Scheduled** until a worker polls with matching config.

**Demo orders:** `demo:orders` creates DB rows and starts workflows. If you start a workflow manually (`conductor:start`), ensure the `order_id` exists in `orders` or inventory tasks will fail—see [CONDUCTOR_ECOMMERCE_TESTING.md](CONDUCTOR_ECOMMERCE_TESTING.md).

| Service | URL / command |
|--------|----------------|
| **Orders UI** | [http://localhost:8000/orders](http://localhost:8000/orders) |
| **Conductor API** | `http://localhost:8090/api` (UI often at [http://localhost:8090](http://localhost:8090)) |
| **Workers** | `docker compose exec app php artisan conductor:work` — for **Orkes**, ensure `.env` has `CONDUCTOR_SERVER_URL` + auth; `conductor-worker` uses the same `env_file` (see [docs/ORKEES_CLOUD_SETUP.md](docs/ORKEES_CLOUD_SETUP.md)) |
| **Demo orders** | `docker compose exec app php artisan demo:orders` |

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
