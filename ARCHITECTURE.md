# Event-Driven Order Processing PoC – Architecture

This document describes the **Laravel NATS Proof of Concept**: an event-driven order processing system using [zaeem2396/laravel-nats](https://github.com/zaeem2396/laravel-nats) and NATS (with JetStream).

---

## Overview

The PoC demonstrates **all major features** of the package in one cohesive flow:

| Feature | Where it's used |
|--------|------------------|
| **Publish / Subscribe** | `orders.created`, `orders.shipped` |
| **Wildcard subscriptions** | `orders.*` subscriber logs every order event |
| **Request / Reply (RPC)** | `payments.validate` – API requests, payment service replies |
| **Laravel Queue (NATS driver)** | `FulfillOrderJob` on connection `nats` |
| **Multiple NATS connections** | `default` for core events, `analytics` for `metrics.orders` |
| **JetStream** | ORDERS stream for `orders.>` persistence; queue uses JetStream for delayed jobs |
| **Event chaining** | orders.created → orders.paid (implicit) → orders.shipped |

---

## Components

### 1. Laravel app (API + web)

- **Routes:** `POST /orders`, `GET /orders`, `GET /orders/create`, `GET /orders/{order}`
- **OrderController:** Creates order, publishes `orders.created`, calls `payments.validate` (request/reply), dispatches `FulfillOrderJob` on queue `nats`.
- **Order model:** `reference`, `customer_email`, `amount`, `status`, `payment_response`, `shipped_at`.

### 2. Payment responder (RPC server)

- **Command:** `php artisan nats:payment-responder`
- **Behavior:** Subscribes to `payments.validate`; for each request, replies with `{ approved: true/false, transaction_id, ... }`. PoC approves when amount &lt; 10000.
- **Pattern:** Request/Reply (RPC over NATS).

### 3. NATS queue worker

- **Command:** `php artisan queue:work nats`
- **Behavior:** Processes jobs from the NATS queue. `FulfillOrderJob` marks order as shipped, publishes `orders.shipped` (default connection) and `metrics.orders` (analytics connection).

### 4. Orders wildcard subscriber

- **Command:** `php artisan nats:orders-subscriber`
- **Behavior:** Subscribes to `orders.*` and logs every order event (e.g. `orders.created`, `orders.shipped`) to console and `nats_activities` table.

### 5. JetStream ORDERS stream (optional)

- **Command:** `php artisan nats:setup-orders-stream`
- **Behavior:** Creates stream `ORDERS` with subject `orders.>` so all order events are persisted by JetStream.

---

## Message flow (text diagram)

```
  [Browser]                    [Laravel API]                  [NATS]                    [Workers]
      |                              |                            |                           |
      |  POST /orders                 |                            |                           |
      |------------------------------>|                            |                           |
      |                              |  publish orders.created    |                           |
      |                              |--------------------------->|                           |
      |                              |                            |-----> orders.* subscriber |
      |                              |  request payments.validate |       (logs event)        |
      |                              |--------------------------->|                           |
      |                              |                            |-----> payment-responder   |
      |                              |                            |       (replies approved)  |
      |                              |  reply (approved)           |<------                    |
      |                              |<---------------------------|                           |
      |                              |  dispatch FulfillOrderJob   |                           |
      |                              |  (queue: nats)              |                           |
      |                              |--------------------------->|-----> queue worker        |
      |                              |                            |       (FulfillOrderJob)   |
      |                              |                            |                           |
      |                              |                            |  publish orders.shipped   |
      |                              |                            |<--------------------------|
      |                              |                            |-----> orders.* subscriber |
      |                              |                            |  publish metrics.orders   |
      |                              |                            |  (analytics connection)   |
      |                              |                            |<--------------------------|
      |  redirect orders/{id}        |                            |                           |
      |<------------------------------|                            |                           |
```

---

## Configuration

### config/nats.php

- **default:** Main connection (host/port from `NATS_HOST`, `NATS_PORT`).
- **analytics:** Second connection for metrics; defaults to same host/port; override with `NATS_ANALYTICS_*` for a separate cluster in production.

### config/queue.php

- **nats** connection: Uses same NATS host/port; queue name from `NATS_QUEUE` (default `default`). JetStream is used for delayed jobs when enabled.

### Docker

NATS runs with JetStream:

```bash
docker compose up -d nats
# Or full stack:
docker compose up -d
```

NATS CLI to inspect:

```bash
docker exec -it test_nats nats stream info ORDERS
docker exec -it test_nats nats stream ls
```

---

## Code snippets

### Publishing (default connection)

```php
use LaravelNats\Laravel\Facades\Nats;

Nats::publish('orders.created', [
    'order_id' => $order->id,
    'reference' => $order->reference,
    'amount' => (float) $order->amount,
]);
```

### Publishing (analytics connection)

```php
Nats::connection('analytics')->publish('metrics.orders', [
    'event' => 'order.shipped',
    'order_id' => $order->id,
    'at' => now()->toIso8601String(),
]);
```

### Subscribing (wildcard)

```php
Nats::subscribe('orders.*', function ($message) {
    $subject = $message->getSubject();
    $payload = $message->getDecodedPayload();
    logger()->info("Order event: {$subject}", $payload);
});
Nats::connection()->wait();
```

### Request / Reply (RPC)

**Requester (API):**

```php
$reply = Nats::request('payments.validate', [
    'order_id' => $order->id,
    'amount' => (float) $order->amount,
], timeout: 5.0);
$body = $reply->getDecodedPayload();
$approved = $body['approved'] ?? false;
```

**Responder (command):**

```php
Nats::subscribe('payments.validate', function ($message) {
    $payload = $message->getDecodedPayload();
    $replyTo = $message->getReplyTo();
    if (!$replyTo) return;
    Nats::publish($replyTo, ['approved' => true, 'transaction_id' => 'txn_...']);
});
Nats::connection()->wait();
```

### Queue job (NATS driver)

```php
// In controller:
FulfillOrderJob::dispatch($order)->onConnection('nats');

// In config/queue.php: 'nats' connection with driver 'nats'
// Worker: php artisan queue:work nats
```

---

## Running the PoC

1. **Start infrastructure:** `docker compose up -d` (NATS + app + DB).
2. **Run migrations:** `docker compose exec app php artisan migrate`.
3. **Create JetStream stream (optional):** `docker compose exec app php artisan nats:setup-orders-stream`.
4. **Start payment responder** (separate terminal): `php artisan nats:payment-responder`.
5. **Start orders subscriber** (separate terminal): `php artisan nats:orders-subscriber`.
6. **Start queue worker:** `php artisan queue:work nats`.
7. **Open app:** http://localhost:2331/orders/create and submit an order.

For a single-node PoC, steps 4–6 can run on the same machine (three terminals or a process manager).

---

## Event chaining

- **orders.created** – Published when order is created (POST /orders).
- **orders.paid** – Represented by payment reply and `order->markPaid()`; no separate subject in this PoC.
- **orders.shipped** – Published by `FulfillOrderJob` after marking the order shipped.

The wildcard subscriber sees **orders.created** and **orders.shipped**; the JetStream stream `ORDERS` (subject `orders.>`) persists all of these if the stream is created.

---

## Portfolio checklist

- [x] Publish/Subscribe  
- [x] Wildcard `orders.*`  
- [x] Request/Reply `payments.validate`  
- [x] Laravel Queue with NATS driver and `queue:work nats`  
- [x] Multiple connections (default + analytics)  
- [x] JetStream stream for order events  
- [x] Event chaining (created → paid → shipped)  
- [x] Docker NATS with JetStream  
- [x] Clear structure: routes, controllers, jobs, commands, config  
- [x] README and architecture docs  
