# Deploying to Render (Free Tier)

This project includes `render.yaml` to provision a free Render Web Service (Docker) and free Postgres.

## One-time setup
1) Push this repository to GitHub (Render pulls from GitHub).
2) Sign in to Render → **New +** → **Blueprint** → select the repo (it auto-detects `render.yaml`).
3) Accept creating the service and database. Use region **Oregon** (matches the blueprint).
4) In the Web Service env vars (Render dashboard), set:
   - `APP_URL` = `https://<your-render-service-url>` (from the dashboard once created).
   - Ensure `APP_KEY` is present; the blueprint generates one, but regenerate if missing (`php artisan key:generate --show`).
5) Click **Create Web Service**; first build will take a few minutes.
6) After the first deploy, run migrations once via **Shell** → `php artisan migrate --force` (Docker services don’t support postDeployCommand in blueprint).

## What the blueprint does
- Builds a Docker image from `backend/Dockerfile` (PHP 8.2 + Apache, pdo_pgsql, mbstring, zip, intl, bcmath, Composer install, docroot `public/`).
- Provisions Postgres (`mtvts-db`) and injects `DATABASE_URL` + `DB_CONNECTION=pgsql`.
- Sets sensible production defaults: `APP_ENV=production`, `APP_DEBUG=false`, database-backed queue/session/cache.
- Migrations must be run manually once (`php artisan migrate --force` in Render Shell) since post-deploy commands aren’t supported on Docker blueprint services.

## Post-deploy checks
- Open the service URL: `https://<your-render-service-url>/api/violation-types` should return JSON.
- If you use queues: free tier cannot run a worker continuously; switch to sync/DB queue or a paid background worker.
- Cron tasks: free tier lacks built-in cron; use an external scheduler (e.g., cron-job.org) to hit a maintenance endpoint or Artisan route if needed.

## Local parity
- Set `.env` with `DB_CONNECTION=pgsql` and your local Postgres creds if you want parity.
- For CORS/Sanctum: update `config/cors.php` and `SANCTUM_STATEFUL_DOMAINS` / `SESSION_DOMAIN` to include your Render domain if you serve a web client from another host.
