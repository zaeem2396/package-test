# TaskBoard – Laravel full-stack demo

A Laravel 12 demo app showcasing **events**, **jobs**, **queues**, **factories**, **seeders**, and intentionally **slow routes** for testing (dashboard, reports). The app is fully **Dockerized**.

## Features

- **Models:** User, Project, Task, Activity (with relationships)
- **Events:** `TaskCreated`, `TaskCompleted`, `ProjectUpdated`
- **Listeners:** Log activity, dispatch notifications, sync project stats (queued)
- **Jobs:** `SendTaskNotificationJob`, `SyncProjectStatsJob`, `ProcessTaskReminderJob` (database queue)
- **Slow routes:** Dashboard (~400ms), Tasks index (~300ms), Reports Analytics (~1.2s), Reports Export (~800ms)
- **Seed data:** 50+ users, 25 projects, 3–12 tasks per project (run `php artisan db:seed`)

## Docker

### Run with Docker Compose

```bash
docker compose up -d
```

Then:

```bash
# Migrate and seed (50 users, projects, tasks)
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

- **App:** http://localhost:8000  
- **phpMyAdmin:** http://localhost:8080 (mysql / secret)

The **queue worker** runs in a separate container (`queue-worker`) and processes jobs from the `database` queue.

### Services

| Service        | Purpose                    |
|----------------|----------------------------|
| app            | Laravel app (port 8000)    |
| queue-worker   | `php artisan queue:work database` |
| mysql          | MySQL 8                    |
| phpmyadmin     | DB UI (port 8080)          |

## Local development (no Docker)

1. Copy `.env.example` to `.env`, set `DB_*` and `QUEUE_CONNECTION=database`.
2. `composer install && php artisan key:generate && php artisan migrate --seed`.
3. `php artisan serve` and in another terminal: `php artisan queue:work database`.

## Routes

| Route | Description |
|-------|-------------|
| `/` | Welcome |
| `/dashboard` | Dashboard (slow) |
| `/projects` | Project list |
| `/projects/create` | New project |
| `/projects/{id}` | Project detail + tasks |
| `/tasks` | Task list (filter by status) |
| `/tasks/create` | New task |
| `/tasks/{id}` | Task detail, mark complete |
| `/reports/analytics` | Analytics (slow) |
| `/reports/export` | Export view (slow) |

Creating a task dispatches `TaskCreated`, queues `ProcessTaskReminderJob` (delayed 5 min), and listeners run via the queue. Completing a task fires `TaskCompleted` and dispatches `SendTaskNotificationJob`.

## License

MIT.
