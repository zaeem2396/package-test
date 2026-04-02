# E-commerce workflow demo (`order_processing`)

Production-style showcase: **Laravel + `conductor/orkes-laravel` + Conductor OSS**, with **Blade + Bootstrap 5** UI.

**For a full runbook** (Docker, each task handler, worker loop, UI polling, troubleshooting), see **[CONDUCTOR_ECOMMERCE_TESTING.md](../CONDUCTOR_ECOMMERCE_TESTING.md)** in the repo root.

## Flow

Workflow start `input` is mapped into each SIMPLE task via **`inputParameters`** (`${workflow.input.order_id}`, etc.) so workers receive `order_id`, `amount`, `user_email` in **`inputData`**.

1. **inventory_check** — ~20% terminal fail (out of stock)  
2. **payment_process** — ~30% retriable fail; workflow **`retryCount: 5`** on this task  
3. **fraud_check** — ~12% terminal fail (fraud)  
4. **create_shipping** — tracking id  
5. **send_notification** — marks order **completed**

## Structure

| Path | Role |
|------|------|
| `app/Workflows/OrderWorkflow.php` | DSL + `payment_process` retry |
| `app/Tasks/*.php` | Conductor `TaskHandler` implementations |
| `app/Services/OrderService.php` | DB + register/start workflow + events |
| `resources/views/orders/` | List + detail (auto-poll) |

## Run (Docker)

From repo root:

1. `docker compose up -d --build`
2. `docker compose exec app php artisan migrate --force`
3. **Workers:** `docker compose exec app php artisan conductor:work` (or the `conductor-worker` service)
4. **UI:** [http://localhost:8000/orders](http://localhost:8000/orders) — create orders with **POST /orders**
5. **Bulk demo:** `docker compose exec app php artisan demo:orders --count=8`

Logs (in container): `storage/logs/laravel.log` (e.g. `Order #12 → Payment FAILED (retrying)`).

## API

- `POST /orders` — form: `amount`, `email` (CSRF)  
- `GET /orders/{id}/status` — JSON for live UI (order + `order_events` + optional Conductor workflow)

## Package note

Terminal task failures use **`FAILED_WITH_TERMINAL_ERROR`** via handler result `terminal => true` (see `orkes-laravel` `TaskClient::fail(..., $terminal)` and `Worker`).
