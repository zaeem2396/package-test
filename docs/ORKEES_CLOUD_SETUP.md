# Orkes Cloud — connect this PoC and trigger `order_processing`

**Secrets:** Put real keys only in **`.env`** (gitignored). A placeholder snippet is in [env-orkes-snippet.env](env-orkes-snippet.env). Do not commit credentials.

This app calls the **Conductor REST API**. Orkes Conductor Cloud uses the same API shape; you point Laravel at your cluster URL and authenticate with an **application access key** (recommended) or a **JWT**.

## 1. What to create in the Orkes UI

### A. Application + access key (what you copy into `.env`)

1. In Orkes: **Access Control → Applications** → create or select an application.
2. Grant the app roles that allow **starting workflows** and **task polling** (workers), per your security model.
3. **Create access key** and copy:
   - **Key ID** (UUID) → `CONDUCTOR_AUTH_KEY`
   - **Key secret** (shown once) → `CONDUCTOR_AUTH_SECRET`
4. Copy the **Server URL** shown next to the key (must be the API base, usually ends with `/api`) → `CONDUCTOR_SERVER_URL`

You do **not** paste the “dashboard user” token from **Copy token** into production `.env` unless you also set `CONDUCTOR_AUTH_HEADER_STYLE=orkes`; that token expires quickly. Prefer **key + secret** so `conductor/orkes-laravel` can call `POST {base}/token` and send `X-Authorization` on API requests.

### B. Task definitions (register before the workflow)

Create **five** task definitions, each **type SIMPLE**, names **exactly**:

| Task name          | Notes                          |
|--------------------|--------------------------------|
| `inventory_check`  |                                |
| `payment_process`  | optional: align retries in UI  |
| `fraud_check`      |                                |
| `create_shipping`  |                                |
| `send_notification`|                                |

If the UI asks for a worker language / timeout, defaults are fine for a PoC.

### C. Workflow definition

1. **Definitions → Workflows** → create/import.
2. Paste the JSON from this repo: **[order_processing_workflow.json](orkes/order_processing_workflow.json)** (same as `App\Workflows\OrderWorkflow::definition()` in code).
3. Save / publish so the name **`order_processing`** exists on that cluster.

## 2. Laravel `.env` (Orkes)

Use a **`conductor/orkes-laravel`** version that includes Orkes auth (token exchange + `X-Authorization`). Run `composer update conductor/orkes-laravel` after upgrading the package. Then set:

```env
CONDUCTOR_SERVER_URL=https://YOUR-CLUSTER.orkescloud.com/api
CONDUCTOR_AUTH_KEY=your-key-id
CONDUCTOR_AUTH_SECRET=your-key-secret
```

Comment out or remove local-only `CONDUCTOR_SERVER=http://conductor-server:8080/api` when testing Orkes from your machine.

**Optional:** If you only have a long-lived JWT:

```env
CONDUCTOR_SERVER_URL=https://YOUR-CLUSTER.orkescloud.com/api
CONDUCTOR_TOKEN=your-jwt
CONDUCTOR_AUTH_HEADER_STYLE=orkes
```

## 3. Trigger from localhost

```bash
php artisan conductor:start order_processing --input='{"order_id":1,"amount":99.99,"user_email":"you@example.com"}'
```

The UI under **Executions** should show the new run. For workers to complete SIMPLE tasks, run **`conductor:work`** (see below).

### Workers (move tasks from Scheduled → Completed)

SIMPLE tasks stay **Scheduled** until a worker **polls** Orkes and completes them. Use the **same** `.env` as `conductor:start` (`CONDUCTOR_SERVER_URL`, `CONDUCTOR_AUTH_KEY`, `CONDUCTOR_AUTH_SECRET`).

**Host (long-running):**

```bash
php artisan conductor:work
```

**Single poll cycle (debug / scripts):**

```bash
php artisan conductor:work --once
```

**Docker Compose:** `app`, `queue-worker`, and `conductor-worker` load `.env` via `env_file` and do not override `CONDUCTOR_SERVER`, so Orkes settings apply. The compose file also **bind-mounts** `../orkes-laravel` over `vendor/conductor/orkes-laravel` so the running containers use your local SDK (Orkes token + `X-Authorization`). Remove that volume in `docker-compose.yml` if you do not keep the SDK repo alongside this project.

After editing `.env`, run:

```bash
docker compose up -d --build app conductor-worker
```

Ensure MySQL is reachable from the container (`DB_HOST=mysql` in Compose overrides `.env` for DB). The image still runs `composer install` at build time; the mount replaces the installed package at runtime.

## 4. Relationship: UI vs this repo

| Item | Where it lives |
|------|----------------|
| Workflow + task **definitions** | Orkes cluster (create/import in UI or API) |
| **Secrets** | Only in your `.env` / secret store, never in git |
| **Runtime** (start workflow, poll tasks) | This Laravel app via `conductor/orkes-laravel` |

The JSON file in `docs/orkes/` is for **import** into Orkes so the server knows the graph; the SDK does not upload it automatically unless you add code to call the metadata API.
