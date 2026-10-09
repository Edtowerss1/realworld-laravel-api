# RealWorld API (Laravel)

API-only implementation of the [RealWorld](https://realworld-docs.netlify.app/) spec, built with Laravel 13.

This project is intentionally a REST API: there is no frontend, no Vite/Tailwind toolchain, and no web routes. All functionality is served as JSON.

## Stack

- PHP 8.3+
- Laravel 13
- Laravel Sanctum (token authentication)
- Pest 5 (testing)
- SQLite by default (configurable through `.env`)

## Requirements

- PHP 8.3+
- Composer 2

## Setup

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Running

```sh
php artisan serve
```

The API is served under the `/api` prefix.

## Routes

| Method | URI | Description |
| --- | --- | --- |
| GET | `/up` | Framework health check |
| GET | `/api/v1/health` | API health check (JSON) |

## Testing

```sh
php artisan test
```

## Structure

- `routes/api.php` — versioned API routes (`v1` group).
- `app/Http/Controllers/Api/V1` — API controllers.
- `app/Http/Requests` — form request validation.
- `app/Http/Resources` — API resource transformers.
- `app/Http/Middleware` — custom middleware.

## Status

Scaffolding is ready. Business endpoints (authentication, articles, comments, tags, profiles/follows) are not implemented yet.
