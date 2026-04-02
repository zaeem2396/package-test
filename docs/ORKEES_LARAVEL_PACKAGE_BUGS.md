# `conductor/orkes-laravel` — integration notes

Observations from wiring this repo’s **e-commerce Conductor demo** into **`conductor/orkes-laravel`**. Items marked **Fixed upstream** have been addressed in the package (see branch / `main` on [orkes-laravel](https://github.com/zaeem2396/orkes-laravel)).

---

## Conductor semantics: workflow `input` vs SIMPLE task `inputData`

**Not a package bug.** Conductor does **not** automatically copy workflow start `input` into each SIMPLE task. Use per-task **`inputParameters`** (e.g. `"order_id": "${workflow.input.order_id}"`). This demo does that in `app/Workflows/OrderWorkflow.php`. The DSL’s `->inputParameters([...])` on `Workflow::define()` is workflow-level metadata until the package adds helpers to map inputs onto each `->task()`.

---

## ~~`worker_concurrency` / `--concurrency` (misleading)~~ — **Fixed upstream**

Previously the publishable config exposed `worker_concurrency` and `conductor:work` had a no-op `--concurrency` flag. These were removed in favor of documenting **process-based scaling** (multiple `conductor:work` processes). Config now uses **`worker_max_retries`** / `CONDUCTOR_WORKER_MAX_RETRIES` for **handler** exception retries.

---

## ~~`Worker` `maxRetries` not exposed in Laravel~~ — **Fixed upstream**

`config/conductor.php` includes **`worker_max_retries`**; `conductor:work` and `conductor:local` pass it into `Conductor\Task\Worker`.

---

## ~~`ConductorClient::fromArray` without HTTP retry~~ — **Fixed upstream**

`fromArray()` accepts **`retry_enabled`**, **`retry_max_attempts`**, **`retry_initial_delay_ms`** and wires the same **`RetryHandler`** pattern as the Laravel service provider.

---

## Laravel `TaskHandler` return shape (convention)

Handlers return either plain output (treated as `COMPLETED`) or an array with `status`, `reasonForIncompletion`, `outputData`, `terminal`. A stricter typed API could reduce mistakes; tracked as an enhancement, not a defect.

---

## Conductor infrastructure

**Not a package issue:** running Conductor OSS requires Redis, Elasticsearch (or your stack’s equivalents), etc. This repo’s **`docker-compose.yml`** is an example only.

---

## Search / inspect query dialect

**Operational:** `WorkflowClient::search()` and `conductor:inspect` query strings are server-specific; see your Conductor version’s search documentation.
