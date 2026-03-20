# Conductor e-commerce demo — testing & how it works

This document replaces the old **`CONDUCTOR_POC_TESTING.md`**. It describes **every layer** of the **`order_processing`** workflow demo: infrastructure, Laravel code paths, worker behavior, UI, and how to run and verify it.

---

## Table of contents

1. [What you are running](#1-what-you-are-running)
2. [Architecture (high level)](#2-architecture-high-level)
3. [Infrastructure step-by-step (Docker Compose)](#3-infrastructure-step-by-step-docker-compose)
4. [Environment & configuration](#4-environment--configuration)
5. [Database model](#5-database-model)
6. [Workflow definition (`OrderWorkflow`)](#6-workflow-definition-orderworkflow)
7. [Starting an order (`OrderService::create`)](#7-starting-an-order-orderservicecreate)
8. [How Conductor runs the workflow](#8-how-conductor-runs-the-workflow)
9. [Task handlers (each step in detail)](#9-task-handlers-each-step-in-detail)
10. [The worker (`php artisan conductor:work`)](#10-the-worker-php-artisan-conductorwork)
11. [HTTP routes & UI](#11-http-routes--ui)
12. [Logging & timeline](#12-logging--timeline)
13. [Demo command (`demo:orders`)](#13-demo-command-demoorders)
14. [End-to-end walkthroughs](#14-end-to-end-walkthroughs)
15. [Troubleshooting](#15-troubleshooting)
16. [Optional: Conductor UI & API](#16-optional-conductor-ui--api)
17. [Related files](#17-related-files)

---

## 1. What you are running

| Piece | Role |
|--------|------|
| **Laravel** | Creates **orders** in MySQL, registers workflow metadata, **starts** workflow executions, serves **Blade UI** at `/orders`. |
| **Conductor OSS** | Orchestrates **SIMPLE** tasks in order: inventory → payment → fraud → shipping → notification. |
| **Workers** | `php artisan conductor:work` polls Conductor for each task type and **completes** or **fails** tasks via the HTTP API. |
| **`conductor/orkes-laravel`** | Client SDK + `TaskHandler` integration + `Workflow::define()` DSL. |

This is **not** a CRUD-only app: the **source of truth for orchestration** is Conductor; the app mirrors **status**, **current step**, and a **timeline** in MySQL for visibility.

---

## 2. Architecture (high level)

```
[Browser]  →  POST /orders  →  OrderService
                              →  MySQL: orders + order_events
                              →  Conductor: POST .../workflow (start)

[conductor:work]  →  GET .../tasks/poll/{taskType}
                 →  POST .../tasks (COMPLETED / FAILED / FAILED_WITH_TERMINAL_ERROR)
                 →  Task handlers update MySQL + Log
```

**Workflow input** (passed to every task’s `inputData` via Conductor) includes at least:

- `order_id` — Laravel primary key (used to load `Order`)
- `amount` — decimal
- `user_email` — string

---

## 3. Infrastructure step-by-step (Docker Compose)

From **`package-test/`**:

```bash
docker compose up -d --build
```

### 3.1 Services that must be healthy

| Service | Purpose |
|---------|---------|
| **`mysql`** | Laravel `orders` / `order_events` / sessions / queue tables. |
| **`conductor-postgres`** | Conductor **archive** DB (Flyway); required by `conductoross/conductor:3.15.x` image. |
| **`rs` (Redis)** | Conductor queue + metadata (per `docker/conductor/config.properties`). |
| **`es` (Elasticsearch 7)** | Conductor indexing / search. |
| **`conductor-server`** | Conductor API + UI (internal port **8080**, mapped to host **8090**). |
| **`app`** | `php artisan serve` on **8000** (after Conductor is healthy). |

### 3.2 Laravel ↔ Conductor networking

Inside Compose, Laravel uses:

`CONDUCTOR_SERVER=http://conductor-server:8080/api`

**Note:** With a **`.env` file** present, Laravel’s `php artisan serve` may strip most environment variables in the child process unless you use **`--no-reload`** (see `Dockerfile` CMD) and/or **`CONDUCTOR_SERVER`** is also written into `.env` (see `docker/entrypoint.sh`). Otherwise HTTP calls from the web container can fall back to the wrong base URL.

### 3.3 URLs (default ports)

| What | URL |
|------|-----|
| Orders UI | http://localhost:8000/orders |
| Conductor API (host) | http://localhost:8090/api |
| Conductor health | http://localhost:8090/health |
| phpMyAdmin (if enabled) | http://localhost:8080 |

---

## 4. Environment & configuration

### 4.1 Important variables

| Variable | Meaning |
|----------|---------|
| `CONDUCTOR_SERVER` | Base URL for REST API, **must** end with `/api` (e.g. `http://conductor-server:8080/api` in Compose). |
| `CONDUCTOR_POLL_INTERVAL` | Seconds between worker poll cycles (`config/conductor.php` → `poll_interval`). |
| `CONDUCTOR_TOKEN` | Optional Bearer token (Orkes Cloud / secured installs). |

See **`.env.example`** for the full list.

### 4.2 Task handler registration

`config/conductor.php` → **`task_handlers`**: array of classes implementing `Conductor\Laravel\Workers\TaskHandler`.

The worker command (`conductor:work`) resolves each class from the container, reads `taskType()`, and registers one **poll loop entry** per type.

---

## 5. Database model

### 5.1 `orders`

| Column | Meaning |
|--------|---------|
| `id` | Primary key; also sent to Conductor as `order_id`. |
| `amount` | Order total (decimal). |
| `email` | Customer email. |
| `status` | `processing` \| `failed` \| `completed` (also `pending` if you extend flows). |
| `current_step` | Last known workflow task name (e.g. `payment_process`) or `null` when finished/failed. |
| `retry_count` | Incremented when **payment** simulates a retriable failure (see §9.2). |
| `workflow_id` | Conductor workflow execution id (UUID string) after `start()`. |

### 5.2 `order_events`

Append-only **timeline** rows:

| Column | Meaning |
|--------|---------|
| `order_id` | FK to `orders`. |
| `step_key` | Logical tag (e.g. `inventory_check`, `failed`, `completed`). |
| `message` | Human-readable line for UI. |
| `level` | `info` \| `success` \| `warning` \| `error` (drives icons on the detail page). |
| `context` | Optional JSON (e.g. `tracking_id`, `workflow_id`). |

---

## 6. Workflow definition (`OrderWorkflow`)

**File:** `app/Workflows/OrderWorkflow.php`  
**Name:** `order_processing` (constant `OrderWorkflow::NAME`).

### 6.1 DSL chain (logical order)

1. `inventory_check`
2. `payment_process`
3. `fraud_check`
4. `create_shipping`
5. `send_notification`

### 6.2 Workflow input → task `inputData`

Conductor **does not** automatically copy workflow start `input` into each SIMPLE task. Every task in this demo sets **`inputParameters`** to map fields from **`${workflow.input.*}`** into the payload workers receive (`order_id`, `amount`, `user_email`). Without this, polled tasks would show **`inputData: {}`** and handlers would fail with “order not found”.

The DSL’s **`->inputParameters([...])`** on the workflow is **documentation** only; **per-task** `inputParameters` in the JSON definition performs the wiring.

- **payment retry:** the `payment_process` task gets **`retryCount: 5`** in the exported array (after `toArray()`), so Conductor will **re-schedule** that task on **retriable** failure up to 5 times.

### 6.3 Registration in Conductor

`OrderService::registerWorkflowDefinition()`:

1. **`POST`** `metadata/workflow` with the full definition.
2. On **failure** (e.g. definition already exists), **`PUT`** `metadata/workflow` with `[definition]` to update.

This runs on **every** `OrderService::create()` so demos stay idempotent.

---

## 7. Starting an order (`OrderService::create`)

**Trigger:** `POST /orders` (form) or `php artisan demo:orders`.

**Steps inside `create()`:**

1. **`registerWorkflowDefinition()`** — ensure Conductor knows `order_processing`.
2. **Insert `orders` row** — `status = processing`, `current_step = inventory_check`, `retry_count = 0`.
3. **`recordEvent`** — `order_created`.
4. **`Conductor::workflow()->start('order_processing', [...])`** with:
   - `order_id` => `$order->id`
   - `amount` => float
   - `user_email` => email  
   Conductor returns a **workflow instance id** (string).
5. **Update order** — set `workflow_id`.
6. **`recordEvent`** — `workflow_started` (includes `workflow_id` in context).
7. **`Log::info`** — `Order #n → Order created → workflow started`.

Until a **worker** runs, the workflow sits in Conductor with tasks **scheduled / waiting to be polled**.

---

## 8. How Conductor runs the workflow

1. **Start** creates a **workflow execution** and schedules the **first SIMPLE task** (`inventory_check`).
2. A worker **polls** `tasks/poll/inventory_check`. Conductor assigns a task instance and returns JSON (includes `taskId`, `workflowInstanceId`, `inputData`, …).
3. The worker **acks** (IN_PROGRESS), runs the handler, then **POSTs** `tasks` with **COMPLETED** or **FAILED**.
4. On **COMPLETED**, Conductor schedules the **next** task in the definition.
5. On **FAILED**:
   - **Retriable** (default FAILED): Conductor applies **task retry policy** (`retryCount` on `payment_process`).
   - **Terminal** (`FAILED_WITH_TERMINAL_ERROR`): **no** further retries for that task; workflow goes to a **failed** state.

Our handlers set **terminal** for **inventory** (out of stock) and **fraud**; **payment** uses **non-terminal** FAILED for simulated declines so Conductor retries.

---

## 9. Task handlers (each step in detail)

**Namespace:** `App\Tasks\*`  
**Contract:** `TaskHandler::taskType(): string` and `handle(array $task): array`.

Handlers use **`OrderService::orderFromTask($task)`**, which reads **`$task['inputData']['order_id']`**.

### 9.1 `InventoryTask` (`inventory_check`)

| Item | Detail |
|------|--------|
| **Task type** | `inventory_check` |
| **Randomness** | **20%** chance “out of stock”. |
| **Success** | Records event `inventory_check` (success), logs `Inventory OK`, returns normal output (wrapped as COMPLETED). |
| **Failure** | Records error event, **`markFailed($order, 'out_of_stock')`**, returns `status: FAILED`, `terminal: true` → **stops workflow**. |

### 9.2 `PaymentTask` (`payment_process`)

| Item | Detail |
|------|--------|
| **Task type** | `payment_process` |
| **Randomness** | **30%** chance simulated decline. |
| **Retriable failure** | Increments **`orders.retry_count`**, records **warning** event (“retrying via Conductor”), logs `Payment FAILED (retrying)`, returns `status: FAILED`, **`terminal: false`**. Conductor retries up to **`retryCount: 5`**. |
| **Success** | Records success + fake `transaction_ref`, logs `Payment SUCCESS`. |

### 9.3 `FraudCheckTask` (`fraud_check`)

| Item | Detail |
|------|--------|
| **Task type** | `fraud_check` |
| **Randomness** | **~12%** (targets 10–15% band). |
| **Failure** | **`markFailed($order, 'fraud')`**, `terminal: true`. |
| **Success** | Event + log `Fraud check PASSED`. |

### 9.4 `ShippingTask` (`create_shipping`)

| Item | Detail |
|------|--------|
| **Task type** | `create_shipping` |
| **Behavior** | Generates a **`tracking_id`** (e.g. `TRK-…`), records success event, logs line with tracking. |

### 9.5 `NotificationTask` (`send_notification`)

| Item | Detail |
|------|--------|
| **Task type** | `send_notification` |
| **Behavior** | Records notification event, **`markCompleted($order)`** (sets `completed`, clears `current_step`), logs completion. |

### 9.6 Return shapes & Laravel wrapper

`WorkerCommand` / `LocalCommand`:

- If `handle()` returns an array **without** a `status` key → treated as **output only** → worker sends **COMPLETED** with that as `outputData`.
- If `handle()` returns **`status`** (and optional `reasonForIncompletion`, `outputData`, **`terminal`**) → passed through to the SDK worker → **`TaskClient::fail(..., $terminal)`** when failed.

---

## 10. The worker (`php artisan conductor:work`)

### 10.1 What it does each cycle

For **each** registered `task_handlers` class (in order):

1. **`poll(taskType)`** — `GET .../tasks/poll/{taskType}`  
   - **204 / empty** → no task; try next type or sleep.
2. **`ack(taskId)`** — `POST .../tasks` with **IN_PROGRESS** (lease / “picked up”).
3. **Invoke `TaskHandler::handle($task)`** (wrapped as in §9.6).
4. **`complete`** or **`fail`** — `POST .../tasks` with proper body (`outputData` must JSON-encode as **object** `{}` when empty — handled inside `TaskClient` in the package).

### 10.2 Running workers in Docker

```bash
docker compose exec app php artisan conductor:work
```

Or use the **`conductor-worker`** service if defined in Compose (same image, different command).

**Important:** With **multiple** task types, one process cycles through all types each iteration; under load you may run **multiple worker processes** for throughput (see package notes on concurrency).

---

## 11. HTTP routes & UI

| Method | Path | Controller action | Purpose |
|--------|------|---------------------|---------|
| GET | `/` | closure | Welcome page. |
| GET | `/orders` | `OrderController@index` | List + create form. |
| POST | `/orders` | `OrderController@store` | Validate `amount`, `email`; `OrderService::create()`; redirect to show. |
| GET | `/orders/{order}` | `OrderController@show` | Detail + timeline (Blade). |
| GET | `/orders/{order}/status` | `OrderController@status` | JSON: order row, `events`, optional Conductor `getWorkflow` payload. |

### 11.1 Live updates

**`orders/show.blade.php`** polls **`/orders/{id}/status`** every **3 seconds** and re-renders the timeline + summary fields.

### 11.2 CSRF

`POST /orders` is a **web** route: the form includes `@csrf`.

---

## 12. Logging & timeline

| Sink | Content |
|------|---------|
| **`order_events` table** | Structured timeline for the UI. |
| **Laravel log** (`storage/logs/laravel.log`) | Lines like `Order #12 → Payment FAILED (retrying)` via `OrderService::auditLog()`. |

---

## 13. Demo command (`demo:orders`)

```bash
php artisan demo:orders
php artisan demo:orders --count=10
```

**Steps:**

1. Calls `OrderService::registerWorkflowDefinition()`.
2. Creates **N** random orders (Faker email + amount).
3. Each call runs **`OrderService::create()`** → workflows started.
4. Prints a line per order with **id**, **email**, **amount**, **workflow_id**.
5. Reminds you to run **`conductor:work`** separately.

---

## 14. End-to-end walkthroughs

### 14.1 Happy path (manual)

1. `docker compose up -d` (or local MySQL + Conductor).
2. `php artisan migrate`.
3. Open **http://localhost:8000/orders**, submit the form.
4. In a terminal: `php artisan conductor:work`.
5. Watch **order detail**: steps advance; timeline fills; status → **completed** when notification runs.

### 14.2 Observe a payment retry

1. Create several orders (or `demo:orders`).
2. Run worker; when payment “fails”, UI shows **retry_count** increase and a **warning** timeline row.
3. Conductor may run **`payment_process`** again until success or **retry exhaustion**.

### 14.3 Observe terminal failure (inventory / fraud)

- **Inventory:** order moves to **failed**, reason **out_of_stock**; workflow stops early.
- **Fraud:** **failed**, reason **fraud**.

### 14.4 PHPUnit

```bash
php artisan test
```

(Add feature tests against `Order` routes with `Conductor::fake()` if you want CI without a live Conductor.)

---

## 15. Troubleshooting

| Symptom | Likely cause | What to check |
|---------|----------------|---------------|
| `Connection refused` to Conductor from Laravel | Wrong `CONDUCTOR_SERVER` or stripped env under `artisan serve` | `.env` + Dockerfile `--no-reload` + entrypoint `CONDUCTOR_SERVER` |
| `500` on `POST .../tasks` “JSON parse” / Map | Empty `outputData` sent as `[]` | Use current `orkes-laravel` `TaskClient` (encodes `{}`) |
| Worker runs but order stuck on `processing` | No worker or Conductor unhealthy | `docker compose ps`, Conductor `/health`, run `conductor:work` |
| Workflow started but tasks never complete | Handlers not registered / wrong task names | `config/conductor.php` must match `OrderWorkflow` task **names** |
| Payment retries exhausted, order still `processing` | DB not updated on Conductor-only failure | Known demo limitation; add a sync job or workflow status poller if needed |

---

## 16. Optional: Conductor UI & API

- **Swagger / UI:** http://localhost:8090 (version-dependent).
- **Health:** `GET http://localhost:8090/health`
- **Start workflow manually:** `POST /api/workflow` with body `name`, `version`, `input` — same shape as `OrderService::create()` uses internally.

---

## 17. Related files

| Area | Paths |
|------|--------|
| Workflow | `app/Workflows/OrderWorkflow.php` |
| Service | `app/Services/OrderService.php` |
| Handlers | `app/Tasks/*.php` |
| HTTP | `app/Http/Controllers/OrderController.php`, `routes/web.php` |
| Views | `resources/views/orders/`, `resources/views/layouts/app.blade.php` |
| Config | `config/conductor.php` |
| Migrations | `database/migrations/*_create_orders_table.php`, `*_create_order_events_table.php` |
| Command | `app/Console/Commands/DemoOrdersCommand.php` |
| Conductor config (Docker) | `docker/conductor/config.properties` |
| Short overview | `docs/ECOMMERCE_WORKFLOW_DEMO.md` |
| Package quirks | `docs/ORKEES_LARAVEL_PACKAGE_BUGS.md` |

---

**End of guide.** Keep this file with the repo as the **single detailed reference** for the e-commerce Conductor integration.
