# Bugs found in zaeem2396/laravel-nats (tested in package-test)

This document lists bugs and issues found while testing the laravel-nats package locally in this repo. Package path: `/home/zaeem/dev/laravel-nats`.

**How to run the app:** See [README.md](README.md) for full steps (Docker, migrations, worker, Mailhog, Logs).

**Summary:** (1) Queue subject prefix from config ignored. (2) Delayed jobs never use JetStream. (3) `release($delay)` with raw JSON breaks reprocessing. (4) Failed job DB uses wrong config key (`connection` vs `database`). (5) Failed job flush uses timestamp instead of date for comparison.

---

## 1. Queue subject prefix from config is ignored

**Location:** `NatsConnector::connect()`, `NatsQueue`

**Description:** The connector reads `$config['prefix']` (e.g. from `config/queue.php` or `config/nats.php` queue prefix) and uses it only for normalizing the DLQ subject. It never passes the prefix to `NatsQueue`. `NatsQueue` has a hardcoded `protected string $subjectPrefix = 'laravel.queue.'` and no constructor parameter or setter for it.

**Impact:** Setting `NATS_QUEUE_PREFIX` or `prefix` in the queue connection has no effect. All queue subjects use `laravel.queue.{queue}` regardless of config.

**Suggested fix:** Add a `subjectPrefix` (or `prefix`) parameter to `NatsQueue::__construct()` and pass `$config['prefix'] ?? 'laravel.queue.'` from `NatsConnector::connect()`.

---

## 2. Delayed jobs never use JetStream even when enabled

**Location:** `NatsQueue::later()`

**Description:** When the queue connection has `delayed.enabled => true`, the connector sets up JetStream and passes `$jetStream` and `$delayedConfig` into `NatsQueue`. However, `NatsQueue::later()` always does:

```php
return $this->push($job, $data, $queue);
```

It never checks `$this->jetStream` or `$this->delayedConfig`. So delayed jobs are always pushed immediately; the JetStream delay stream is never used.

**Impact:** `Queue::connection('nats')->later(60, $job)` and `$job->release($delay)` with delay do not actually delay; they push immediately. Documentation says delayed jobs require JetStream and config; the wiring is there but the implementation in `later()` is missing.

**Suggested fix:** In `NatsQueue::later()`, when `$this->jetStream` and `$this->delayedConfig` are set, publish the job payload to the JetStream delay stream with the appropriate subject and message metadata so a delay processor can deliver it when due. Otherwise keep current behavior (push immediately) and optionally log a warning when delay is requested but delayed is disabled.

---

## 3. Release with delay breaks: raw JSON passed to `later()` then `push()`

**Location:** `NatsJob::release()`, `NatsQueue::later()`, `Illuminate\Queue\Queue::createPayloadArray()`

**Description:** When a job calls `$this->release(60)` (release back to queue with delay), `NatsJob::release()` builds an updated payload (with incremented attempts), JSON-encodes it, and calls:

```php
$this->nats->later($delay, $newPayload, '', $this->queue);
```

So the second argument to `later()` is a **raw JSON string** (the full job payload). `NatsQueue::later()` then does:

```php
return $this->push($job, $data, $queue);
```

So `push()` is called with `$job` = raw JSON string. Laravel’s `Queue::push($job, $data, $queue)` calls `createPayload($job, ...)`. In `createPayloadArray()`, a string is treated as a job **class name** and passed to `createStringPayload()`, which expects a class name and optional data, not a pre-built JSON payload. The result is a wrong payload (e.g. displayName set to the entire JSON string) and broken job when reprocessed.

**Impact:** Any job that uses `release($delay)` with `$delay > 0` (e.g. retry with backoff) will be re-queued with an invalid payload and fail or misbehave when the worker picks it up.

**Suggested fix (one of):**

- Add `NatsQueue::laterRaw(int $delay, string $payload, ?string $queue = null)` that either uses JetStream delay when available or, if not, re-publishes via `pushRaw()` after a delay (e.g. sleep or a simple delay mechanism). Then in `NatsJob::release()` when `$delay > 0`, call `$this->nats->laterRaw($delay, $newPayload, $this->queue)` instead of `later($delay, $newPayload, ...)`.
- Or, inside `NatsQueue::later()`, detect when `$job` is a string that looks like JSON (e.g. `trim($job)[0] === '{'`) and call `pushRaw($job, $queue)` (and handle delay via JetStream if enabled) instead of `push($job, $data, $queue)`.

---

## 4. Failed job DB connection uses wrong config key

**Location:** `NatsJob::getFailedJobProvider()`

**Description:** The code uses:

```php
$connection = $config->get('queue.failed.connection', config('database.default'));
```

Laravel’s `config/queue.php` uses the key **`database`** for the failed job database connection, not `connection`:

```php
'failed' => [
    'driver'   => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
    'database' => env('DB_CONNECTION', 'sqlite'),
    'table'    => 'failed_jobs',
],
```

So `queue.failed.connection` is always `null`, and the fallback `config('database.default')` is used. If the application sets a dedicated connection for failed jobs via `queue.failed.database`, the package will ignore it.

**Impact:** Failed NATS jobs are always logged to the default database connection. Apps that use a different connection for `queue.failed.database` will not have NATS failed jobs stored in that connection.

**Suggested fix:** Use the same key as Laravel:

```php
$connection = $config->get('queue.failed.database', config('database.default'));
```

---

## 5. Failed job flush uses timestamp instead of date for comparison

**Location:** `NatsFailedJobProvider::flush()`

**Description:** When flushing failed jobs older than N hours, the code uses:

```php
$query->where('failed_at', '<', now()->subHours($hours)->timestamp);
```

Laravel’s `DatabaseFailedJobProvider` and `DatabaseUuidFailedJobProvider` use a `DateTimeInterface` for the comparison, e.g. `Date::now()->subHours($hours)` or `$before` (Carbon). The `failed_at` column is typically a timestamp/datetime column; comparing with an integer (unix timestamp) may behave differently across databases and is inconsistent with Laravel’s other failed job providers.

**Impact:** `queue:flush` with a `--hours` option may behave inconsistently; best to match Laravel’s pattern for portability.

**Suggested fix:** Use a date instance for the comparison:

```php
if ($hours !== null) {
    $query->where('failed_at', '<', now()->subHours($hours));
}
```

---

## Workaround used in package-test for delayed jobs

Until **Bug #2** (delayed jobs / `later()`) is fixed in the package, this app implements delayed email by:

- Dispatching the job **immediately** to NATS (no `->delay()`).
- Passing the desired delay (seconds) into the job constructor.
- In the job’s `handle()`, calling `sleep($delaySeconds)` (capped at 1 hour) **before** sending the email.

So the worker is busy for the delay period; the email is sent after that. This is a stopgap until the package supports `later()` (e.g. via JetStream).

---

## Scenarios tested in this repo

- **Connection:** `Nats::connection()` and connect - OK  
- **Publish:** `Nats::publish('test.subject', $payload)` - OK  
- **Subscribe:** `Nats::subscribe()` + `Nats::process(1.0)` - OK  
- **Queue dispatch:** `ProcessTestJob::dispatch()->onConnection('nats')` - OK (dispatch works; worker not fully exercised here due to DB/cache from host)  
- **JetStream:** `Nats::jetstream()->isAvailable()` and `nats:stream:list` - OK when NATS is run with `--jetstream`

---

## How to run the app and tests locally

For full steps to run the package-test app (Docker, migrations, worker, Mailhog), see [README.md](README.md).

Quick checks:

1. Start NATS (with JetStream):  
   `docker compose up -d nats`
2. Run the NATS test command:  
   `php artisan nats:test --scenario=all`  
   Or individual scenarios: `connection`, `publish`, `subscribe`, `queue-dispatch`, `jetstream`.
3. Optional: set `QUEUE_CONNECTION=nats` and run a queue worker (ensure DB/cache are reachable for Laravel’s queue state, e.g. run app in Docker or use SQLite and file cache when testing from host).

---

*Generated from local testing in package-test; package path: `/home/zaeem/dev/laravel-nats`.*
