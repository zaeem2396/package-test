# `conductor/orkes-laravel` — issues / limitations found via package-test

These items are observations from integrating the package into **`package-test`** (e-commerce Conductor demo). They may be intentional design choices; treat as a backlog for the package maintainers.

---

## Conductor semantics: workflow `input` vs SIMPLE task `inputData`

**Not a package bug.** Conductor does **not** automatically copy workflow start `input` into each SIMPLE task. If polled tasks show **`inputData: {}`**, add per-task **`inputParameters`** in the workflow JSON (e.g. `"order_id": "${workflow.input.order_id}"`). The DSL’s `->inputParameters([...])` on `Workflow::define()` is workflow-level documentation only until the package adds helpers to map inputs onto each `->task()`.

---

## 1. Laravel `TaskHandler` return shape (partially addressed)

**Location:** `WorkerCommand`, `LocalCommand`, `TaskHandler` docblock

Handlers may return **plain output** (wrapped as `COMPLETED`) **or** an explicit array including `status` (`COMPLETED` / `FAILED`), `reasonForIncompletion`, `outputData`, and `terminal` (maps to `FAILED_WITH_TERMINAL_ERROR` on Conductor).

**Remaining gap:** This is convention-based on the return array; a stricter typed interface or DTO could reduce mistakes.

---

## 2. `worker_concurrency` and `--concurrency` are not implemented

**Location:** `config/conductor.php` (`worker_concurrency`), `WorkerCommand` (`--concurrency` described as “reserved”)

**Impact:** Multi-worker concurrency must be achieved by running **multiple processes** (e.g. several `php artisan conductor:work` terminals or a process manager), not a single command instance.

**Suggestion:** Either implement pooling / fork / async poll, or remove/rename the option and document process-based scaling only.

---

## 3. `Worker` constructor supports `maxRetries`; Laravel does not expose it

**Location:** `Conductor\Task\Worker` vs `WorkerCommand` / `LocalCommand`

**Impact:** Retry behavior for handler exceptions is fixed at the `Worker` default unless using the SDK worker directly in custom code.

**Suggestion:** Add `conductor.worker_max_retries` (or similar) and pass it into `new Worker(...)`.

---

## 4. Standalone `ConductorClient::fromArray` vs Laravel HTTP client feature parity

**Location:** `ConductorClient::fromArray()` builds a plain `HttpClient` **without** `RetryHandler`.

**Impact:** Laravel apps get optional retry via config; standalone scripts must construct `HttpClient` manually to match.

**Suggestion:** Document clearly, or extend `fromArray()` to accept retry options.

---

## 5. Conductor infrastructure is outside the package

**Not a bug:** Local Conductor requires Redis/Elasticsearch (or other backends) per upstream docs. This repo’s Docker Compose is an example stack only; use official Conductor docs or Orkes Cloud as needed.

---

## 6. Search / inspect query dialect

**Location:** `WorkflowClient::search()`, `InspectCommand`

**Impact:** Query strings are server-specific; failed searches often look like generic HTTP errors. Operators should refer to their Conductor version’s search documentation.

---

If you fix any of the above in **`orkes-laravel`**, this file can be trimmed or linked to CHANGELOG entries.
