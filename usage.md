# Usage Guide

This document describes how to set up, run, and use the NATS demo application.

---

## Table of contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Running with Docker](#running-with-docker)
4. [Running without Docker](#running-without-docker)
5. [Application URLs](#application-urls)
6. [NATS Dashboard](#nats-dashboard)
7. [Queue worker](#queue-worker)
8. [Testing](#testing)
9. [Troubleshooting](#troubleshooting)

---

## Prerequisites

- **PHP** 8.2 or higher  
- **Composer**  
- **Docker & Docker Compose** (for Docker setup)  
- **NATS server** 2.x with JetStream (for delayed jobs and streams)

---

## Installation

```bash
# Clone or enter the project directory
cd package-test

# Install dependencies (includes zaeem2396/laravel-nats)
composer install

# Environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
```

Optional: publish NATS config to customize connections:

```bash
php artisan vendor:publish --tag=nats-config
```

---

## Running with Docker

### 1. Start all services

Starts the Laravel app, MySQL, NATS (with JetStream), Mailhog, and phpMyAdmin:

```bash
docker compose up -d
```

### 2. Run migrations (first time)

```bash
docker compose exec app php artisan migrate --force
```

### 3. Clear config cache (if you see "Connection to localhost:4222 refused")

```bash
docker compose exec app php artisan config:clear
```

### 4. Start the queue worker (for jobs and delayed jobs)

In a separate terminal (or run in background):

```bash
docker compose exec app php artisan queue:work nats
```

To run the worker in the background:

```bash
docker compose run -d --name nats_worker app php artisan queue:work nats --sleep=3
```

### 5. Open the app

- **App (home):** [http://localhost:2331](http://localhost:2331)  
- **NATS Dashboard:** [http://localhost:2331/nats](http://localhost:2331/nats)  
- **phpMyAdmin:** [http://localhost:8023](http://localhost:8023)  
- **Mailhog (SMTP UI):** [http://localhost:8025](http://localhost:8025)  
- **NATS monitoring:** [http://localhost:8222](http://localhost:8222) (when NATS is running)

---

## Running without Docker

1. **Start NATS** with JetStream, e.g.:

   ```bash
   docker run -d --name nats -p 4222:4222 -p 8222:8222 nats:2.10 --jetstream
   ```

2. **Configure `.env`:**

   ```env
   NATS_HOST=localhost
   NATS_PORT=4222
   QUEUE_CONNECTION=nats
   DB_CONNECTION=mysql
   # ... set DB_*, MAIL_*, etc.
   ```

3. **Run migrations and serve:**

   ```bash
   php artisan migrate
   php artisan serve
   ```

4. **Start the queue worker** in another terminal:

   ```bash
   php artisan queue:work nats
   ```

5. Open [http://localhost:8000](http://localhost:8000) and click **NATS Demo** or go to [http://localhost:8000/nats](http://localhost:8000/nats).

---

## Application URLs

| URL | Description |
|-----|-------------|
| `/` | Laravel welcome page; link to NATS Demo |
| `/nats` | NATS Dashboard + **Recent activity** feed |
| `/nats/activity` | Full **activity feed** (all events) |
| `/nats/streams` | JetStream streams list |
| `/nats/failed-jobs` | Failed queue jobs (from database) |

---

## NATS Dashboard

Open **NATS Demo** from the home page or go to `/nats`. The dashboard shows a **Recent activity** section: every publish, job dispatch, job completion (when the worker runs), and request/reply is logged so you see real output. Use **Activity** in the nav (or **View full activity feed**) for the full list.

### 1. Publish message

- **Subject:** e.g. `demo.events`  
- **Payload:** JSON, e.g. `{"message": "Hello NATS"}`  
- **Action:** Publishes the message to NATS. No subscriber required.

### 2. Request / Reply

- **Subject:** e.g. `demo.ping`  
- **Payload:** JSON (optional)  
- **Action:** Sends a request and waits for a reply (timeout 5s).  
- **Note:** You need a subscriber that replies on that subject; otherwise you’ll see an error.

### 3. Queue: dispatch job

- **Order ID:** e.g. `ORD-123`  
- **Amount:** number (optional)  
- **Action:** Dispatches `ProcessOrderJob` to the NATS queue.  
- **Requirement:** Run `php artisan queue:work nats` so the job is processed.

### 4. Queue: delayed job (JetStream)

- **Delay (seconds):** 1–3600  
- **Message:** reminder text  
- **Action:** Schedules `SendReminderJob` to run after the given delay.  
- **Requirement:** NATS must be started with JetStream (`--jetstream`). The queue worker must be running.

### 5. Queue: failing job (DLQ demo)

- **Action:** Dispatches a job that always fails.  
- **Result:** After retries, the job appears in **Failed Jobs** and (if configured) in the Dead Letter Queue.

### 6. Quick links

- **JetStream Streams:** List streams (and message counts if available).  
- **Failed Jobs:** List rows from the `failed_jobs` table.

---

## Queue worker

Jobs are only processed when a worker is running:

```bash
# Docker
docker compose exec app php artisan queue:work nats

# Local
php artisan queue:work nats
```

Useful options:

- `--queue=high,default` – queues to process  
- `--tries=3` – max attempts per job  
- `--timeout=60` – job timeout (seconds)  
- `--once` – process one job and exit  

---

## Testing

### Run all tests

```bash
composer test
```

Or:

```bash
php artisan config:clear
php artisan test
```

### Test suites

- **Unit:** Job classes (`ProcessOrderJob`, `SendReminderJob`, `FailingDemoJob`).  
- **Feature:** NATS dashboard, publish, request/reply, queue dispatch/delayed/failing, JetStream streams, failed jobs, validation.  
- **UI (acceptance):** Home link, dashboard sections, forms, navigation, success/error messages, validation errors.

### Run only UI tests

```bash
php artisan test tests/Feature/NatsUiTest.php
```

### Run only NATS-related tests

```bash
php artisan test --filter=Nats
```

---

## Troubleshooting

### "Connection to localhost:4222 refused"

- **Docker:** Ensure the app container has `NATS_HOST=nats` (set in `docker-compose.yml`). Clear config:  
  `docker compose exec app php artisan config:clear`
- **Local:** Ensure NATS is running and `NATS_HOST=localhost` (or your NATS host) in `.env`.

### JetStream "not available" on dashboard

- Start NATS with JetStream:  
  `docker run -d -p 4222:4222 -p 8222:8222 nats:2.10 --jetstream`  
  or in Docker Compose use `command: ["--jetstream", "-m", "8222"]` for the NATS service.

### Jobs not processing

- Start the queue worker: `php artisan queue:work nats`.  
- Confirm `QUEUE_CONNECTION=nats` in `.env` (or in the container env).  
- Check NATS is reachable (see "Connection to localhost:4222 refused" above).

### Delayed jobs not running

- JetStream must be enabled on NATS.  
- Queue worker must be running.  
- In `config/queue.php`, the `nats` connection should have `delayed.enabled` (or equivalent) set to `true`.

### Failed jobs page empty or error

- Ensure migrations have run so the `failed_jobs` table exists.  
- The page catches DB errors and shows an empty list if the table is missing or the driver is wrong.

---

## Environment variables (reference)

| Variable | Description | Default |
|----------|-------------|---------|
| `NATS_HOST` | NATS server host | `localhost` (or `nats` in Docker when unset) |
| `NATS_PORT` | NATS server port | `4222` |
| `NATS_QUEUE` | Queue name | `default` |
| `NATS_QUEUE_DLQ` | Dead letter queue name | `failed` |
| `QUEUE_CONNECTION` | Default queue driver | `nats` (set in Docker) |
| `NATS_QUEUE_DELAYED_ENABLED` | Enable JetStream delayed jobs | `true` |

See `.env.example` and `config/nats.php` / `config/queue.php` for more options.
