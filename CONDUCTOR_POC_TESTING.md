# Conductor / Orkes Laravel — PoC testing guide

This branch (`feature/conductor-orkes-poc`) integrates **`conductor/orkes-laravel`** from the sibling path repo `../orkes-laravel` and demonstrates **each major feature** via a **real-time polling dashboard** (`/conductor-poc`), Artisan commands, a **standalone PHP script**, and **PHPUnit** using `Conductor::fake()`.

---

## 0. Prerequisites

- PHP 8.2+, Composer 2.x
- A running **Conductor OSS** or **Orkes Conductor** API (REST base URL ending in `/api`, e.g. `http://127.0.0.1:8080/api`)
- If `composer install` fails with “Could not delete … Permission denied” under `vendor/`, the directory is likely **root-owned** (e.g. from Docker). Fix and reinstall, for example:
  - `sudo chown -R "$(whoami):$(whoami)" vendor` (or delete `vendor` as the same user that owns the project), then `composer install`.

```bash
cd /home/zaeem/dev/package-test
composer install
cp .env.example .env   # if needed
php artisan key:generate
```

---

## 1. Environment

Add to `.env` (see `.env.example`):

| Variable | Purpose |
|----------|---------|
| `CONDUCTOR_SERVER` | Base URL, e.g. `http://127.0.0.1:8080/api` |
| `CONDUCTOR_TOKEN` | Optional Bearer token (Orkes / secured installs) |
| `CONDUCTOR_TIMEOUT` | HTTP timeout (seconds) |
| `CONDUCTOR_POLL_INTERVAL` | Worker sleep between cycles (PoC uses `2`) |
| `CONDUCTOR_RETRY_ENABLED` | `true` to enable HTTP retry (exponential backoff) |
| `CONDUCTOR_RETRY_MAX_ATTEMPTS` | Max retries |
| `CONDUCTOR_RETRY_INITIAL_DELAY_MS` | Initial backoff |

Config file: `config/conductor.php` (includes **`task_handlers`** for the three PoC tasks).

---

## 2. Run Conductor (choose one)

### A) Official Docker Compose (recommended)

Clone [conductor-oss/conductor](https://github.com/conductor-oss/conductor) and from its `docker/` directory follow [Running Conductor Using Docker](https://conductor-oss.github.io/conductor/devguide/running/docker.html) (typically Redis + Elasticsearch + server). Map host port **8080** (or set `CONDUCTOR_SERVER` to match your port).

### B) Orkes Cloud / playground

Use the API URL and token from your Orkes account; set `CONDUCTOR_SERVER` and `CONDUCTOR_TOKEN`.

---

## 3. Package features ↔ how this PoC exercises them

| Package area | Feature | Where it’s demonstrated |
|--------------|---------|-------------------------|
| **HttpClient** | JSON, auth, optional retry | Used by the service provider; enable **`CONDUCTOR_RETRY_*`** to exercise retry |
| **ConductorClient** | `workflow()`, `tasks()`, `workers()` | Injected `ConductorClient` in `ConductorPocService`; dashboard + Artisan |
| **ConductorClient::fromArray** | Standalone bootstrap | `scripts/conductor_standalone_example.php` |
| **WorkflowClient** | `start`, `getWorkflow`, `getWorkflowStatus`, `terminate`, `pause`, `resume`, `retry`, `registerWorkflowDefinition`, `updateWorkflowDefinition`, `search` | Dashboard buttons + JSON routes under `/conductor-poc/...` |
| **TaskClient** | `poll` | Dashboard “TaskClient::poll” + route `POST /conductor-poc/tasks/poll` |
| **TaskClient** | `complete` / `fail` / `update` / `ack` | Used internally by **`Worker`** when running `conductor:work` |
| **Worker** | `listen`, `run`, `runOneCycle` | `php artisan conductor:work` (infinite) or `php artisan conductor:local --once` |
| **Workflow DSL** | `Workflow::define`, `task`, `description`, `version`, `ownerEmail`, `inputParameters`, `outputParameters`, `toArray`, `toJson`, `register` | `GET /conductor-poc/definition/preview`, `POST /conductor-poc/workflow/register`, `POST /conductor-poc/workflow/update` |
| **Laravel** | `Conductor` facade, service provider, `config/conductor.php` | All HTTP routes use the bound client; config lists **`task_handlers`** |
| **Artisan** | `conductor:start`, `conductor:work`, `conductor:inspect`, `conductor:local`, `conductor:failures` | CLI section below |
| **Testing** | `Conductor::fake()`, assertions, `recordedStartedWorkflows()` | `tests/Feature/ConductorPocFakeTest.php` |
| **Exceptions** | `ConductorException`, `WorkflowException`, `TaskException`, `AuthenticationException`, etc. | API returns `422` JSON with `exception` + `error` when Conductor calls fail |

---

## 4. Real-time dashboard (primary UX)

1. Start Laravel: `php artisan serve`
2. Open **`http://127.0.0.1:8000/conductor-poc`**
3. **Register workflow** → **Start workflow** → watch **status poll every 2 seconds**
4. In a **second terminal**, run the worker so SIMPLE tasks complete:

```bash
php artisan conductor:work
```

5. Use **Pause / Resume / Retry / Terminate** and **search** panels to hit the remaining workflow APIs.

From the home page, use the link **“Conductor / Orkes Laravel PoC”**.

---

## 5. Artisan commands (manual checks)

Replace `WORKFLOW_ID` after starting a run.

```bash
# Start workflow (same as dashboard “start”; demonstrates CLI)
php artisan conductor:start poc_realtime_demo --input='{"message":"cli","request_id":"r-cli"}'

# Long-running worker (TaskHandler classes from config)
php artisan conductor:work

# Single poll cycle (good for CI / quick checks)
php artisan conductor:local --once

# Inspect running / failed tables
php artisan conductor:inspect --size=20

# List failures; optional retry all listed
php artisan conductor:failures --size=20
php artisan conductor:failures --size=20 --retry
```

---

## 6. Standalone SDK (no Laravel)

```bash
php scripts/conductor_standalone_example.php
```

Uncomment register/start lines in the script to hit a live server.

---

## 7. Automated tests (no live Conductor required)

```bash
php artisan test --filter=ConductorPocFakeTest
```

Covers:

- Dashboard render
- DSL preview (no HTTP to Conductor)
- `Conductor::fake()` + `assertWorkflowStarted*` after `ConductorPocService::startWorkflow()`

---

## 8. Security note

The PoC routes are **unauthenticated** for local demos. **Do not expose** `/conductor-poc` on the public internet without middleware (auth, CSRF is already required for POST from the Blade page).

---

## 9. Suggested end-to-end sequence

1. Conductor API up; `.env` `CONDUCTOR_SERVER` correct.
2. `composer install` + `php artisan serve`
3. Open `/conductor-poc` → **GET definition preview** (DSL only).
4. **POST register workflow**
5. `php artisan conductor:work` in another terminal
6. **POST start workflow** → confirm status becomes **COMPLETED** after three tasks
7. Run **`php artisan test --filter=ConductorPocFakeTest`**
8. Optionally set **`CONDUCTOR_RETRY_ENABLED=true`** and repeat a flaky scenario (if you have one) to validate retries

See **`docs/ORKEES_LARAVEL_PACKAGE_BUGS.md`** for limitations noticed while building this PoC.
