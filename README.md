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

First-time database:

```bash
docker compose exec app php artisan migrate --force
```

| Service | URL / command |
|--------|----------------|
| **Orders UI** | [http://localhost:8000/orders](http://localhost:8000/orders) |
| **Conductor API** | `http://localhost:8090/api` (UI often at [http://localhost:8090](http://localhost:8090)) |
| **Workers** | `docker compose exec app php artisan conductor:work` (or use the `conductor-worker` service if defined in Compose) |
| **Demo orders** | `docker compose exec app php artisan demo:orders` |

### Documentation (this repo)

| Doc | Description |
|-----|-------------|
| [CONDUCTOR_ECOMMERCE_TESTING.md](CONDUCTOR_ECOMMERCE_TESTING.md) | Full runbook: stack, env, workflow, handlers, troubleshooting |
| [docs/ECOMMERCE_WORKFLOW_DEMO.md](docs/ECOMMERCE_WORKFLOW_DEMO.md) | Short overview of the workflow |
| [docs/ORKEES_LARAVEL_PACKAGE_BUGS.md](docs/ORKEES_LARAVEL_PACKAGE_BUGS.md) | Integration notes / upstream tracking |

### Automated tests

```bash
docker compose exec app php artisan test
```

Uses SQLite in-memory in `phpunit.xml`; the app image includes `pdo_sqlite`. Tests use `Conductor::fake()` (no live Conductor required).

### License

This demo app follows the same **[MIT license](https://opensource.org/licenses/MIT)** as the default Laravel application template.
