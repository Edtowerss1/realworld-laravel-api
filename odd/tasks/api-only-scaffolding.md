# API-Only Scaffolding — realworld-laravel-api

## Objective
Convert the default Laravel full-stack skeleton into an API-only REST project with clean scaffolding to start building the RealWorld API.

## Problem
The project boots as a full-stack Laravel app: Vite/Tailwind frontend, `routes/web.php` rendering a Blade welcome view, and no `routes/api.php`. This mixes concerns and adds dead weight for a pure JSON API.

## Why
The user explicitly wants a REST API project, not a full-stack Laravel app, with scaffolding ready to build on.

## Scope
**In:**
- Install Laravel API support (Sanctum + `routes/api.php`).
- Remove the frontend toolchain and assets.
- Remove web routing; keep API + console + health.
- Scaffold the API folder structure and a versioned route group.
- Update tests, composer scripts, and env files accordingly.

**Out:**
- Business logic (auth, articles, comments, tags, follows).
- Auth strategy beyond the Sanctum default.
- Git initialization.

## Constraints
- Laravel 13.35, PHP 8.4, Pest 5.
- Keep it minimal; no gratuitous abstractions.
- JSON responses for API; health endpoint preserved.

## Tasks
- [x] T1 — Run `php artisan install:api` and migrate (Sanctum).
- [x] T2 — Remove frontend: `package.json`, `vite.config.js`, `.npmrc`, `resources/`.
- [x] T3 — Remove `routes/web.php` and web routing in `bootstrap/app.php`.
- [x] T4 — Scaffold `app/Http` (Controllers/Api/V1, Requests, Resources, Middleware).
- [x] T5 — Create versioned `routes/api.php` with a health endpoint.
- [x] T6 — Update `composer.json` setup script + env files (drop `VITE_*`).
- [x] T7 — Update tests for the API (`/up`, `/api/v1/health`).
- [x] T8 — Verify: `route:list` + Pest suite.

## Acceptance criteria
- No frontend files or Vite/Tailwind references remain.
- `php artisan route:list` shows API routes and health, no web `/` route.
- `php artisan test` passes.
- `routes/api.php` exists and is registered.

## Checks
- `php artisan route:list`
- `php artisan test`
- `php artisan config:clear`

## Progress
- T1–T8 complete. Orchestrator spot check: `route:list` shows 5 routes (no web `/`), `php artisan test` 3 passed, `composer validate` valid, frontend files and `routes/web.php` removed.
- Native RDD review: lineage `review-33c5783452f5a217`, target `sha256:1be6b32f…`, risk medium, 10 files / 342 lines. Consent granted by the user.
- First attempt BLOCKED (session `worktree` = `/` because the project was unversioned at session start). After restarting OpenCode from the project dir, the review ran and was APPROVED; acknowledgement burned authority (`gentle-ai.review-acknowledged/v1`, consumed_revision `sha256:83509c8e…`, action `acknowledged`).
- Non-blocking advisory follow-ups (lens `review-reliability`, informational — no correction opened):
  - `R3-HEALTH-UNCONDITIONAL` (WARNING): `/api/v1/health` always returns 200 and probes no dependency; behaves as liveness, not readiness.
  - `R3-JSON-ERRORS-UNPROVED` (WARNING): `shouldRenderJsonWhen` is active but no test proves the API error path renders JSON.
  - `R3-HEALTH-ASSERTIONS-PARTIAL` (SUGGESTION): health test uses a subset JSON match.
  - `R3-EXAMPLE-TEST-NO-APP-SIGNAL` (SUGGESTION): `/up` test asserts only status.
  - `R3-ENV-DATABASE-STORES-UNBACKED` (SUGGESTION): `.env.example` defaults SESSION/QUEUE/CACHE to `database`; only the token migration was in scope.

## Next step
- Scaffolding delivered and reviewed (approved).
- Full-stack residue cleanup done: removed `*.blade.php`/`*.css` rules from `.gitattributes`, frontend entries from `.gitignore` (`/node_modules`, `/public/build`, `/public/fonts-manifest.dev.json`, `/public/hot`), deleted `public/favicon.ico` and `public/robots.txt`, and rewrote `README.md` for API-only.
- Optional: make the initial commit; then start building the RealWorld API (auth via Sanctum, articles, comments, tags, follows).
