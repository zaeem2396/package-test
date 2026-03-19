# `conductor/orkes-laravel` — issues / limitations found via package-test PoC

These items are observations from integrating the package into **`package-test`** (`feature/conductor-orkes-poc`). They may be intentional design choices; treat as a backlog for the package maintainers.

---

## 1. Laravel `TaskHandler` always completes successfully in Artisan workers

**Location:** `WorkerCommand`, `LocalCommand`

Handlers registered via `TaskHandler::handle()` are wrapped so the inner worker always receives:

```php
return ['status' => 'COMPLETED', 'outputData' => $output];
```

**Impact:** Implementations cannot return **`FAILED`** with `reasonForIncompletion` through the Laravel integration without throwing. Throwing eventually maps to `fail()` after retries (per `Worker` logic), which is not the same as an explicit structured failure.

**Suggestion:** Allow `TaskHandler::handle()` to return a shape that includes `status` + optional `reasonForIncompletion`, or add a dedicated interface for “result discriminated union”.

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

**Not a bug:** Local Conductor requires Redis/Elasticsearch (or other backends) per upstream docs. The PoC does not ship a full stack; use official Docker Compose or Orkes.

---

## 6. Search / inspect query dialect

**Location:** `WorkflowClient::search()`, `InspectCommand`

**Impact:** Query strings are server-specific; failed searches often look like generic HTTP errors. Operators should refer to their Conductor version’s search documentation.

---

If you fix any of the above in **`orkes-laravel`**, this file can be trimmed or linked to CHANGELOG entries.
