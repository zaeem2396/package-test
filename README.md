<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## NATS Queue App

This project is a **full NATS integration** demo using the [zaeem2396/laravel-nats](https://github.com/zaeem2396/laravel-nats) package from Packagist. It showcases publish, request/reply, NATS queue driver, delayed jobs (JetStream), Dead Letter Queue, and JetStream streams.

### How to run (Docker — recommended)

1. **Prerequisites:** Docker and Docker Compose; laravel-nats package at `../laravel-nats` (sibling of this repo). The Docker build and runtime mount this path so the package resolves.

2. **Start services:**
   ```bash
   cd package-test
   docker compose up -d
   ```

3. **Run migrations** (first time or after pulling new migrations):
   ```bash
   docker compose exec app php artisan migrate --force
   ```

4. **Start the NATS queue worker** (required for tasks and delayed emails):
   ```bash
   docker compose exec app php artisan queue:work nats
   ```
   Keep this terminal open. Or run the worker in the background:
   ```bash
   docker compose run -d --name package_test_worker app php artisan queue:work nats --sleep=3
   ```

5. **Open the app:** [http://localhost:2331](http://localhost:2331) (redirects to Dashboard). **Mailhog (emails):** [http://localhost:8025](http://localhost:8025).

6. **Optional:** Run `docker compose exec app php artisan nats:subscribe` in another terminal to log broadcasts and enable Ping (request/reply) on the Broadcast page.

**Notes:** The app container uses `NATS_HOST=nats`, `MAIL_HOST=mailhog`, and `APP_URL=http://localhost:2331` from `docker-compose.yml`. The entrypoint runs `php artisan config:clear` on startup so Laravel uses the container’s environment (avoids “Connection to localhost:4222 refused” when `.env` had different values).

### Running without Docker

1. Start NATS (e.g. `docker compose up -d nats`) and set `NATS_HOST=localhost` in `.env`. Configure DB and mail (e.g. `MAIL_MAILER=log` or smtp to Mailhog on `127.0.0.1:1025`).
2. `composer install` and `php artisan migrate`.
3. `php artisan serve` then open [http://localhost:8000](http://localhost:8000).
4. In another terminal: `php artisan queue:work nats`.

### App features (NATS Dashboard at /nats)

| Feature | Description |
|--------|-------------|
| **Publish** | Publish a message to any NATS subject (JSON payload). |
| **Request/Reply** | Send a request and wait for a reply (requires a responder). |
| **Queue: dispatch** | Dispatch `ProcessOrderJob` to the NATS queue. |
| **Queue: delayed** | Schedule `SendReminderJob` with a delay (JetStream). |
| **Queue: failing** | Dispatch a job that fails (DLQ + `failed_jobs` demo). |
| **JetStream Streams** | List JetStream streams. |
| **Failed Jobs** | List failed queue jobs from the database. |

- **Dead Letter Queue:** `NATS_QUEUE_DLQ=failed` in queue config.
- **Delayed jobs:** JetStream; `NATS_QUEUE_DELAYED_ENABLED=true`.
- **Tests:** Run `composer test` for Unit, Feature, and UI (acceptance) tests.
- **Usage:** See [usage.md](usage.md) for setup, NATS dashboard usage, queue worker, and troubleshooting.

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
