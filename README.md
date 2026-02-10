# NATS Queue App & Laravel-NATS PoC

Laravel demo app for the [zaeem2396/laravel-nats](https://github.com/zaeem2396/laravel-nats) package. It includes **tasks**, **broadcasts**, **delayed emails**, **Chat PoC** (real-time collaboration), and a **full-feature NATS PoC** with pass/fail scenarios for every package capability.

**Package issues:** See [BUGS.md](BUGS.md) for known issues and workarounds.

---

## Table of contents

- [Prerequisites](#prerequisites)
- [Setup](#setup)
- [How to test](#how-to-test)
- [App features reference](#app-features-reference)
- [Running without Docker](#running-without-docker)
- [License](#license)

---

## Prerequisites

- **Docker** and **Docker Compose**
- **Git**
- **PHP 8.2+** (only if you run the app locally without Docker)

### Optional: runtime-insight (path dependency)

The **Runtime Insight** features (“Insight test” page) use `clarityphp/runtime-insight`. If you need that, clone it as a sibling and add a path repo (or install from Packagist if available):

```
parent-directory/
├── package-test/     ← this repo
└── runtime-insight/  ← only if you use Insight test
```

Otherwise you can skip it; the rest of the app works without it.

---

## Setup

### Step 1: Clone this repo

```bash
git clone <this-repo-url> package-test
cd package-test
```

If you use the `poc/real-time-collab` branch for Chat:

```bash
git checkout poc/real-time-collab
```

### Step 2: Install dependencies (laravel-nats from Packagist)

Install PHP dependencies. The app requires `zaeem2396/laravel-nats`, which is installed from [Packagist](https://packagist.org/packages/zaeem2396/laravel-nats):

```bash
composer require zaeem2396/laravel-nats
```

Or, if the package is already in `composer.json`, run:

```bash
composer install
```

### Step 3: Environment file

```bash
cp .env.example .env
php artisan key:generate
```

For **Docker**, the app container gets `NATS_HOST`, `NATS_PORT`, `MAIL_*`, and `APP_URL` from `docker-compose.yml`; you can leave those unset or commented in `.env` so container env wins. For **local** runs, set at least:

- `DB_*` (or use SQLite)
- `NATS_HOST=localhost` (if NATS runs on the host)
- `APP_URL=http://localhost:8000` (or your URL)

### Step 4: Start services with Docker

From the `package-test` directory:

```bash
docker compose up -d
```

This starts:

- **app** (Laravel) on port **2331**
- **mysql** on 3306
- **nats** with JetStream on 4222 (client) and 8222 (monitoring)
- **mailhog** on 1025 (SMTP) and 8025 (Web UI)
- **phpmyadmin** on 8023 (optional)

Verify:

```bash
docker compose ps
```

All services should be “Up”.

### Step 5: Run migrations

```bash
docker compose exec app php artisan migrate --force
```

This creates tables for tasks, broadcast logs, NATS activity logs, chat (if on Chat branch), and PoC demo logs.

### Step 6: (Chat PoC only) Create JetStream stream for chat

If you use the Chat feature:

```bash
docker compose exec app php artisan chat:setup-jetstream
```

You should see: `JetStream stream CHAT_MESSAGES is ready.`

### Step 7: Start background processes (separate terminals)

For full testing you need:

**Terminal A – Queue worker (required for tasks, delayed email, queue PoC, Chat @mentions):**

```bash
docker compose exec app php artisan queue:work nats
```

Leave it running.

**Terminal B – PoC subscriber (for NATS PoC Pub/Sub “last received”):**

```bash
docker compose exec app php artisan poc:subscriber
```

**Terminal C – PoC responder (for NATS PoC Request/Reply pass):**

```bash
docker compose exec app php artisan poc:responder
```

**Optional – Chat presence (for “Who’s here?” in Chat):**

```bash
docker compose exec app php artisan chat:presence-worker
```

**Optional – Broadcast subscriber (for Broadcast page “received” and Ping):**

```bash
docker compose exec app php artisan nats:subscribe
```

### Step 8: Open the app

- **App:** [http://localhost:2331](http://localhost:2331)
- **Mailhog (emails):** [http://localhost:8025](http://localhost:8025)
- **phpMyAdmin:** [http://localhost:8023](http://localhost:8023) (if needed)

---

## How to test

### 1. Dashboard & tasks

1. Go to **http://localhost:2331** (redirects to Dashboard).
2. Click **Tasks** or **New task**.
3. Create a task:
   - Enter a message.
   - Choose “Run now” or “Run after X seconds”.
   - Submit.
4. **Expected:** Task appears in the table. If the **queue worker** (Terminal A) is running, “Run now” tasks move to “Processing” then “Completed” shortly. “Run after” tasks run after the delay.
5. Optional: Set `NOTIFY_EMAIL` in `.env` and run a task; when it completes you should get an email (check Mailhog at http://localhost:8025).

### 2. Broadcast (pub/sub + request/reply)

1. Click **Broadcast** in the nav.
2. Enter a subject (e.g. `test.hello`) and a JSON payload. Click **Publish**.
3. **Expected:** “Published” message. If **nats:subscribe** is running in another terminal, that terminal shows the received message; the Broadcast page may show recent received if wired.
4. **Ping (request/reply):** Click **Ping**. With a responder running (e.g. `nats:subscribe` handling the ping subject), you get a reply; otherwise you may see a timeout (expected fail).

### 3. Delayed email

1. Click **Delayed email** in the nav.
2. Enter recipient, subject, body, and delay in seconds (e.g. 10). Submit.
3. **Expected:** Job is queued. The **queue worker** (Terminal A) will wait for the delay (in-job sleep) then send the email. After the delay, open Mailhog (http://localhost:8025) and confirm the email.

### 4. Status

1. Click **Status** in the nav.
2. **Expected:** “NATS connected” and “JetStream available” (NATS runs with `--jetstream` in Docker).
3. Use “Send test email” and check Mailhog to confirm mail delivery.

### 5. Logs

1. Click **Logs** in the nav.
2. **Expected:** Table of NATS activity (task queued/completed, emails, etc.). Use filters: All / Tasks / Emails.

### 6. Chat PoC (if on branch with Chat)

1. Ensure migrations and `chat:setup-jetstream` are done; queue worker (Terminal A) running.
2. Click **Chat** in the nav.
3. Click **New room**, enter a name, create. Open the room.
4. Enter your name and a message, send. **Expected:** Message appears in the list; it’s stored in DB and published to NATS subject `chat.room.{id}`.
5. Open the same room in another browser/tab; **expected:** Polling loads the same messages.
6. **Who’s here?:** Click “Who’s here?”. With **chat:presence-worker** running you get “Recently active” names; otherwise you may see “No one recently”.
7. **@mention:** Type a message containing `@alice` (or any word after `@`). Send. **Expected:** With the queue worker running, `ProcessChatNotificationJob` runs (check Logs or worker output).

### 7. NATS PoC (full-feature demo)

Go to **NATS PoC** in the nav (or http://localhost:2331/nats-poc). Ensure **Terminal A (queue:work nats)**, **Terminal B (poc:subscriber)**, and **Terminal C (poc:responder)** are running.

#### 7.1 Pub/Sub

1. Open **Pub/Sub**.
2. Leave subject as `poc.demo.events` (or change it). Click **Publish**.
3. **Expected (pass):** Success message. In Terminal B (poc:subscriber) you see the message. “Last received” on the page updates. PoC log shows **Pass** for scenario `pubsub`.

#### 7.2 Request/Reply

1. Open **Request/Reply**.
2. Leave subject `poc.demo.request`, click **Send request**.
3. **Expected (pass):** With Terminal C (poc:responder) running, you get a JSON reply (e.g. echo + `replied_at`). Log shows **Pass** for `request_reply_pass`.
4. **Expected (fail):** Stop the responder (Ctrl+C in Terminal C). Send request again. **Expected:** Timeout error after a few seconds. Log shows **Fail** for `request_reply_fail`.

#### 7.3 Multiple connections

1. Open **Multiple connections**.
2. Choose **default** or **secondary**, click **Publish**.
3. **Expected (pass):** Success; log shows **Pass** for `multi_connection`.

#### 7.4 Queue driver

1. Open **Queue**.
2. **Dispatch (pass):** Enter a message, click **Dispatch**. **Expected:** Worker (Terminal A) processes it; log shows `queue_dispatch` Pass. “Queue logs” table shows the entry.
3. **Retries + backoff (pass):** Click **Dispatch retryable job**. **Expected:** Worker fails the job 3 times then succeeds on the 4th; logs show retries and final success (`queue_retry`).
4. **Failed job + DLQ (fail scenario):** Click **Dispatch failing job**. **Expected:** After retries, job appears in `failed_jobs` table; “Failed jobs in DB” count increases; if DLQ is configured, message is also published to the DLQ subject. Log may show `queue_failed` and `queue_failed_callback` (from the job’s `failed()` method).
5. **Delayed job (pass):** Set delay e.g. 10 seconds, click **Schedule delayed job**. **Expected:** After ~10 seconds the worker processes it; log shows `queue_delayed` Pass (requires JetStream delayed enabled in queue config).

#### 7.5 JetStream

1. Open **JetStream**.
2. **Expected:** “JetStream available” and stream list if any streams exist.
3. **Create stream:** Stream name e.g. `POC_DEMO`, subjects `poc.demo.>`. Click **Create**. **Expected:** Success; stream appears in the list.
4. **Publish:** Subject `poc.demo.events`, click **Publish**. **Expected:** Message is stored in the stream (subject matches `poc.demo.>`).
5. **Stream info:** Stream `POC_DEMO`, click **Get info**. **Expected:** Name, message count, bytes (e.g. 1 message).
6. **Get message:** Stream `POC_DEMO`, sequence `1`, click **Get message**. **Expected:** JSON of the first message.
7. **Create consumer:** Stream `POC_DEMO`, consumer e.g. `poc_demo_consumer`, click **Create consumer**. **Expected:** Success.
8. **Fetch + ack:** Stream `POC_DEMO`, consumer `poc_demo_consumer`, action **ack**, click **Fetch & apply**. **Expected (pass):** If a message is available, you get “Fetched message, ack: …”. If the stream is empty or consumer has no pending messages, you may see “JetStream fetch next message timed out” (expected when there’s nothing to fetch).
9. **Purge:** Stream `POC_DEMO`, click **Purge**. **Expected:** All messages removed from the stream.

#### 7.6 Failure scenarios

1. Open **Failure scenarios**.
2. **Request timeout:** Click **Trigger timeout**. **Expected:** Error message like “JetStream fetch next message timed out” or request timeout; log shows a failure entry. This demonstrates “no responder” behaviour.
3. Failed job + DLQ is exercised from **Queue** → “Dispatch failing job” (see 7.4).

### 8. Insight test (Runtime Insight)

If you have `runtime-insight` and routes enabled:

1. Click **Insight test** in the nav.
2. Use the links to trigger deliberate errors (null pointer, type error, etc.). **Expected:** Errors are reported; you can use `php artisan insight:explain` (or the package’s explain command) with the log to get explanations.

---

## App features reference

| Page           | Description |
|----------------|-------------|
| **Dashboard**  | List of tasks; run now or run after delay via NATS queue. |
| **New task**   | Create a task (immediate or delayed). |
| **Broadcast**  | Publish to a NATS subject; optional subscriber and Ping (request/reply). |
| **Delayed email** | Queue an email to be sent after a delay (worker sleeps then sends); view in Mailhog. |
| **Status**     | NATS and JetStream connection status; send test email. |
| **Logs**       | NATS activity (tasks, emails); filter by All / Tasks / Emails. |
| **Chat**       | Rooms, live messages (polling), JetStream stream, “Who’s here?” (request/reply), @mention → queue job. |
| **NATS PoC**   | Full demo: Pub/Sub, Request/Reply, multiple connections, Queue (dispatch, retry, failed, DLQ, delayed), JetStream (streams, consumers, fetch/ack), failure scenarios. |
| **Insight test** | Deliberate errors for Runtime Insight (if installed). |

Optional `.env`:

- `NOTIFY_EMAIL` – receive email when a task completes.
- `NATS_QUEUE_DLQ=failed` – route failed jobs to a DLQ subject (default in queue config for PoC).

---

## Running without Docker

1. **NATS:** Start NATS with JetStream, e.g.  
   `docker run -d -p 4222:4222 -p 8222:8222 nats:2.10 --jetstream`  
   or run from your host. Set `NATS_HOST=localhost` in `.env`.

2. **Database:** Use MySQL/SQLite/Postgres and set `DB_*` in `.env`.

3. **Mail:** e.g. `MAIL_MAILER=log` or Mailhog on `127.0.0.1:1025`.

4. **App:**
   ```bash
   composer install
   cp .env.example .env && php artisan key:generate
   php artisan migrate --force
   php artisan serve
   ```
   Open http://localhost:8000.

5. **Workers:** In separate terminals:
   - `php artisan queue:work nats`
   - `php artisan poc:subscriber`
   - `php artisan poc:responder`
   (and optionally `php artisan chat:setup-jetstream`, `chat:presence-worker`, `nats:subscribe`).

---

## License

MIT.
