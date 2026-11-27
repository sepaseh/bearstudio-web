# Bear Studio Web

This Laravel application serves the Bear Studio Blade website and the API for the React management panel. It uses MariaDB. The public site includes Home, Projects, Services, Articles and sitemap discovery. The panel manages projects, services, Home, FAQs, articles and categories, site settings, users and roles.

See [the design](docs/design.md), [API contract](docs/openapi.json), and [installation](DEPLOY.md).

The public site uses `https://3earstudio.com`. The panel opens at `https://admin.3earstudio.com/` and uses its own origin for authentication and management requests.

## Local development

Install PHP 8.4.1+, Composer and the extensions in the installation guide. Install the locked dependencies with `composer install`. Build and deploy the React management application from its independent project. This checkout serves its API only.

Copy `.env.example` to `.env` and set a unique `APP_KEY` with `php artisan key:generate`. For local HTTP development, set `APP_URL=http://127.0.0.1:8000`, `ADMIN_HOST=localhost`, and `SESSION_SECURE_COOKIE=false`. Create a private MariaDB database and set DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD, run `php artisan migrate`, and run `php artisan admin:create` in a private terminal. Start the app with `php artisan serve --host=127.0.0.1 --port=8000`. Use `http://127.0.0.1:8000/` for the public site and `http://localhost:8000/api/v1/` for the administrative API. Start the panel independently and configure its development server to forward same-origin API requests to this backend.

Tailwind, Motion, Inter and original presentation assets in `public/site-assets` come from the existing Bear Studio Web build. Asset notices are colocated with the bundles and fonts. Content, user records and upload bytes from the reference installation stay outside the trial.

## Verification

See [testing and CI](docs/testing.md) for isolated test fixtures, quality checks, browser checks and release verification.

## API documentation

The HTTP contract is maintained in [docs/openapi.json](docs/openapi.json). Import this file into any compatible OpenAPI tool. With APP_ENV=local, interactive Swagger UI is available on the admin host at /dev/api-docs and its definition at /dev/openapi.json. Both routes return 404 outside local development. No separate client collection is maintained. Install development assets with npm ci --ignore-scripts.
