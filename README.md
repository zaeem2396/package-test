# TaskBoard - Laravel Demo with Datadog APM

TaskBoard is a Laravel 12 demo app that includes projects/tasks CRUD, events, listeners, queued jobs, seeders, and intentionally slow routes so you can test observability tooling (especially Datadog APM).

## What this project includes

- Laravel 12 app with MySQL
- Queue worker container for background jobs
- phpMyAdmin for DB inspection
- Datadog Agent container for APM intake
- Datadog PHP tracer installed in the app image

## Architecture (Docker)

- `app` - Laravel web app (`http://localhost:8000`)
- `queue-worker` - `php artisan queue:work database --sleep=3 --tries=3`
- `mysql` - MySQL 8
- `phpmyadmin` - `http://localhost:8080`
- `datadog-agent` - receives traces on port `8126` inside Docker network

---

## Prerequisites

1. Docker Engine + Docker Compose plugin installed.
2. Ports available:
   - `8000` (Laravel app)
   - `8080` (phpMyAdmin)
   - `3306` (MySQL, optional external access)
   - `8126` only if you expose Datadog APM to host
3. Datadog account and API key (for APM data ingestion).

---

## Step-by-step: run the project from scratch

### 1) Clone and enter the project

```bash
git clone <your-repo-url> package-test
cd package-test
```

### 2) Switch to the working branch

```bash
git checkout feature/laravel-full-stack-datadog
```

If the branch does not exist locally yet:

```bash
git fetch origin
git checkout -b feature/laravel-full-stack-datadog origin/feature/laravel-full-stack-datadog
```

### 3) Create runtime env file

If `.env` does not exist, create it from `.env.example`:

```bash
cp .env.example .env
```

Set at least:

```dotenv
DD_API_KEY=<YOUR_DATADOG_API_KEY>
DD_SITE=us5.datadoghq.com
```

### 4) Build and start containers

```bash
docker compose up -d --build
```

### 5) Run migrations + seeders

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

If you want a clean reset every time:

```bash
docker compose exec app php artisan migrate:fresh --seed --force
```

### 6) Open the app

- App: `http://localhost:8000`
- phpMyAdmin: `http://localhost:8080` (user `laravel`, password `secret`)

### 7) Generate app traffic (important for APM)

APM services only appear after traces are sent. Visit:

- `/dashboard`
- `/projects`
- `/tasks`
- `/reports/analytics`
- `/reports/export`

Hit them multiple times so traces are generated.

---

## Step-by-step: Datadog APM setup for this project

### 1) Ensure Datadog Agent is running

```bash
docker compose ps datadog-agent
```

Expected: container is `Up`.

### 2) Ensure app is configured to send traces to agent

The `app` service should have these variables (already in compose):

- `DD_AGENT_HOST=datadog-agent`
- `DD_TRACE_AGENT_PORT=8126`
- `DD_TRACE_AGENT_URL=http://datadog-agent:8126`
- `DD_SERVICE=package-test`
- `DD_ENV=local` (or your environment)
- `DD_VERSION=1.0`

### 3) Ensure agent APM is enabled

The `datadog-agent` service should include:

- `DD_APM_ENABLED=true`
- `DD_APM_NON_LOCAL_TRAFFIC=true`

### 4) Check Datadog UI in the right place

Use:

- **Datadog -> APM -> Services**

Do not rely on Software Catalog for first trace visibility.

### 5) Filter in APM correctly

Use filters:

- `service:package-test`
- `env:local` (or your configured env)

### 6) Wait briefly

After traffic generation, wait 1-3 minutes for ingestion/indexing.

---

## Fresh reset workflow (project + DB)

```bash
docker compose down
docker compose up -d --build
docker compose exec app php artisan migrate:fresh --seed --force
```

Full reset including volumes:

```bash
docker compose down -v
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

---

## Common issues and fixes

### 1) Port `8126` already allocated

Cause: another Datadog container is binding host `8126`.

Fix:

```bash
docker ps --format '{{.Names}}\t{{.Ports}}' | grep 8126
docker rm -f <conflicting-container-name>
```

Then restart the intended agent/container.

### 2) Service not visible in Datadog APM

Checklist:

1. Agent is running.
2. App container is running with Datadog env vars.
3. You generated HTTP traffic after startup.
4. You are looking in `APM -> Services`.
5. Datadog API key/site are valid.

### 3) Queue jobs not processing

```bash
docker compose logs -f queue-worker
docker compose ps queue-worker
```

---

## Helpful commands

```bash
# Start / stop
docker compose up -d
docker compose down

# Rebuild app image
docker compose up -d --build app queue-worker

# Logs
docker compose logs -f app
docker compose logs -f queue-worker
docker compose logs -f datadog-agent

# Artisan inside app container
docker compose exec app php artisan about
docker compose exec app php artisan route:list
docker compose exec app php artisan migrate:status
```

---

## Routes in this demo

- `/` - Welcome
- `/dashboard` - Slow dashboard (~400ms)
- `/projects` - Projects list
- `/projects/create` - Create project
- `/projects/{id}` - Project detail
- `/tasks` - Tasks list (filter supported)
- `/tasks/create` - Create task
- `/tasks/{id}` - Task detail / mark complete
- `/reports/analytics` - Slow analytics (~1.2s)
- `/reports/export` - Slow export (~800ms)

---

## License

MIT
