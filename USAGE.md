# TaskBoard – Usage Guide

This document describes how to run, use, and work with the TaskBoard Laravel application.

---

## Quick start (Docker)

1. **Start all services**
   ```bash
   docker compose up -d
   ```

2. **Run migrations and seed the database**
   ```bash
   docker compose exec app php artisan migrate --force
   docker compose exec app php artisan db:seed --force
   ```
   This creates 50+ users, 25 projects, and multiple tasks per project.

3. **Open the app**
   - **App:** http://localhost:8000  
   - **phpMyAdmin:** http://localhost:8080 (login: `laravel` / `secret`)

4. **Use the app**
   - From the welcome page, click **Dashboard** or go to http://localhost:8000/dashboard.
   - Use the nav to open **Projects**, **Tasks**, **Analytics**, and **Export**.

---

## Running without Docker

1. Copy `.env.example` to `.env` and set your database and queue settings:
   - `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - `QUEUE_CONNECTION=database`

2. Install and bootstrap:
   ```bash
   composer install
   php artisan key:generate
   php artisan migrate --seed
   ```

3. Start the web server and the queue worker (in two terminals):
   ```bash
   php artisan serve
   php artisan queue:work database --sleep=3 --tries=3
   ```

4. Open http://localhost:8000

---

## Routes and pages

| URL | Description |
|-----|-------------|
| `/` | Welcome page with link to Dashboard |
| `/dashboard` | Overview: user count, recent projects, recent tasks (intentionally slow ~400ms) |
| `/projects` | List of projects with task counts |
| `/projects/create` | Form to create a new project (requires a user in the system or auth) |
| `/projects/{id}` | Project detail: name, owner, list of tasks; add task or mark tasks complete |
| `/tasks` | List of tasks with optional filter by status |
| `/tasks/create` | Form to create a new task (project, title, description, assignee) |
| `/tasks/{id}` | Task detail; button to mark task complete |
| `/reports/analytics` | Analytics: user count, activity count, tasks by status, tasks per project (slow ~1.2s) |
| `/reports/export` | Table export of up to 200 tasks (slow ~800ms) |

---

## How the app behaves

- **Creating a project**  
  You must have at least one user in the database (or be authenticated). If there is no valid owner, you get an error and the form is re-displayed with your input.

- **Creating a task**  
  Fires the `TaskCreated` event, logs activity (if a creator is known), and dispatches `ProcessTaskReminderJob` with a 5-minute delay. The queue worker processes these jobs.

- **Marking a task complete**  
  Fires `TaskCompleted` and dispatches `SendTaskNotificationJob` (for the assignee). The queue worker processes it.

- **Updating a project**  
  Fires `ProjectUpdated` and the listener dispatches `SyncProjectStatsJob` to refresh cached project stats.

- **Slow routes**  
  Dashboard, tasks index, and report pages use short delays (`usleep`) and heavier queries on purpose so you can see loading behavior and test tooling.

---

## Assets (CSS/JS)

The app uses **Vite** when a build exists; otherwise it falls back to the **Tailwind CDN**.

- **With build:** Run `npm install && npm run build` (or `npm run dev`). Layouts use `@vite(...)` and `public/build/manifest.json`.
- **Without build:** No `npm` step is required. The layout uses the Tailwind CDN script so pages still render correctly (e.g. in Docker without a frontend build).

---

## Queue worker

- **Docker:** The `queue-worker` container runs `php artisan queue:work database --sleep=3 --tries=3`. Jobs are processed automatically.
- **Local:** Run `php artisan queue:work database` in a separate terminal so jobs (notifications, reminders, project stats) are processed.

**Test the queue (optional):**
```bash
docker compose exec app php artisan taskboard:test-queue
```
This dispatches a `ProcessTaskReminderJob` for the first task. Check the queue worker logs to confirm it ran.

---

## Database and seeding

- **Migrations:** `php artisan migrate` (or `migrate --force` in production/Docker).
- **Seed:** `php artisan db:seed --force` (or `db:seed` without `--force` in local).
- **Fresh seed:** `php artisan migrate:fresh --seed` (drops all tables and re-runs migrations and seeders).

Seed data includes:
- 50 users (plus one “Test User” at `test@example.com`)
- 25 projects (owners chosen at random from users)
- Several tasks per project (some assigned, some not)

---

## Docker tips

- **Reset MySQL and start clean:**  
  `docker compose down -v` then `docker compose up -d`. Then run `migrate --force` and `db:seed --force` again.

- **View logs:**  
  `docker compose logs -f app` or `docker compose logs -f queue-worker` or `docker compose logs -f mysql`.

- **Run artisan in the app container:**  
  `docker compose exec app php artisan <command>`  
  Examples: `migrate`, `db:seed`, `queue:work database`, `taskboard:test-queue`, `tinker`.

- **Entrypoint:** The app and queue-worker containers use `docker-entrypoint.sh` to sync `.env` with the DB credentials from `docker-compose.yml` (e.g. `DB_HOST=mysql`, `DB_PASSWORD=secret`) so the app always uses the same credentials as the MySQL service.

---

## Troubleshooting

| Issue | What to try |
|-------|-------------|
| **Vite manifest not found** | Normal if you haven’t run `npm run build`. The layout falls back to the Tailwind CDN; the app should still work. |
| **Access denied (MySQL)** | Ensure `.env` matches the MySQL service (e.g. `DB_USERNAME=laravel`, `DB_PASSWORD=secret`). If it still fails, run `docker compose down -v` and start again so MySQL is re-initialized. |
| **Queue jobs not running** | Ensure the queue-worker container is running (`docker compose ps`) and that `QUEUE_CONNECTION=database`. Check `docker compose logs queue-worker` for errors. |
| **Cannot create project** | You need at least one user in the database. Run `db:seed` or create a user first. |

---

## Summary

- Use **Docker** for a full stack (app + MySQL + queue worker + phpMyAdmin) with `docker compose up -d`, then migrate and seed.
- Use the **routes** above to browse projects, tasks, and reports; create projects/tasks and complete tasks to trigger events and jobs.
- Keep the **queue worker** running (via Docker or `php artisan queue:work database`) so jobs are processed.
- No **npm** build is required for basic usage; the app falls back to the Tailwind CDN when the Vite manifest is missing.
