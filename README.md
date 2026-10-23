# RealWorld API (Laravel)

API-only implementation of the [RealWorld](https://realworld-docs.netlify.app/) specification, built with Laravel 13. The project exposes JSON REST endpoints for users, profiles, articles, comments, tags, favorites and follows. It does not include a frontend or web application.

## Stack

- PHP 8.3+
- Laravel 13
- JWT authentication with `tymon/jwt-auth`
- Pest 5
- SQLite by default, configurable through `.env`

## Requirements

- PHP 8.3 or newer
- Composer 2

## Installation

```sh
git clone https://github.com/Edtowerss1/realworld-laravel-api.git
cd realworld-laravel-api
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate
```

Set `JWT_SECRET` in `.env` before using authenticated endpoints. The application uses SQLite by default; to use another database, update the `DB_*` variables in `.env` and run the migrations again.

## Running locally

```sh
php artisan serve
```

The API is available at `http://localhost:8000/api`. Laravel's built-in health endpoint is available at `/up`.

## Authentication

Register or log in to receive a JWT:

```sh
curl -X POST http://localhost:8000/api/users/login \
	-H 'Content-Type: application/json' \
	-d '{"user":{"email":"user@example.com","password":"password"}}'
```

Send the returned token with protected requests using either header format:

```http
Authorization: Bearer <token>
```

```http
Authorization: Token <token>
```

Request bodies follow the RealWorld format: user data is wrapped in `user`, article data in `article`, and comment data in `comment`.

## API endpoints

### Health and public endpoints

| Method | URI | Description |
| --- | --- | --- |
| GET | `/up` | Laravel health check |
| GET | `/api/v1/health` | API health check |
| POST | `/api/users` | Register a user |
| POST | `/api/users/login` | Log in and receive a JWT |
| GET | `/api/user` | Get the current user (authenticated) |
| GET | `/api/profiles/{username}` | Get a profile |
| GET | `/api/articles` | List articles; supports `tag`, `author`, `favorited`, `limit` and `offset` |
| GET | `/api/articles/{article}` | Get an article by slug |
| GET | `/api/articles/{article}/comments` | List article comments |
| GET | `/api/tags` | List tags |

### Authenticated endpoints

| Method | URI | Description |
| --- | --- | --- |
| PUT | `/api/user` | Update the current user |
| GET | `/api/articles/feed` | Get the feed of followed authors |
| POST | `/api/articles` | Create an article |
| PUT | `/api/articles/{article}` | Update an article |
| DELETE | `/api/articles/{article}` | Delete an article |
| POST | `/api/articles/{article}/favorite` | Favorite an article |
| DELETE | `/api/articles/{article}/favorite` | Unfavorite an article |
| POST | `/api/articles/{article}/comments` | Add a comment |
| DELETE | `/api/articles/{article}/comments/{comment}` | Delete a comment |
| POST | `/api/profiles/{username}/follow` | Follow a user |
| DELETE | `/api/profiles/{username}/follow` | Unfollow a user |

Validation and API errors are returned as JSON. Validation errors use the RealWorld-style format:

```json
{
	"errors": {
		"email": ["has already been taken"]
	}
}
```

## Development commands

```sh
# Run the test suite
composer test

# Run Laravel Pint
vendor/bin/pint

# Run PHPStan/Larastan
composer stan
```

## Project structure

- `routes/api.php` — API route definitions.
- `app/Http/Controllers/Api/V1` — API controllers.
- `app/Http/Requests/Api/V1` — request validation.
- `app/Http/Resources/Api/V1` — JSON resource transformers.
- `app/Http/Middleware/Api/V1` — API middleware, including token header conversion.
- `app/Models` — Eloquent models.
- `database/migrations` — database schema.
- `tests/Feature/Api` — API feature tests.

## License

This project is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
