# Rightmo API

Laravel 13 product API with MySQL, Sanctum bearer tokens, focused services, and repositories behind contracts. The companion [Next.js frontend](https://github.com/ccdilan/rightmo-web) provides login, a responsive catalog, product details, and image replacement.

## Run the complete application

Requirements: Docker with Compose, Git, and OpenSSL. Clone the repositories next to each other:

```sh
git clone https://github.com/ccdilan/rightmo-api.git
git clone https://github.com/ccdilan/rightmo-web.git
cd rightmo-api
./bin/setup
docker compose exec app php artisan db:seed --class=DemoSeeder --no-interaction
```

Open [the workspace](http://localhost:3000). Demo credentials: **demo@rightmo.test** / **RightmoDemo!2026**. Seeding creates 24 products in four categories and local SVG illustrations. It is repeatable, preserves existing product edits/images, and refuses to run in production. Demo account and database credentials are for local development only.

`bin/setup` generates an ignored `.env.docker` with a random application key, builds all services, waits for their health checks, and applies migrations. Demo seeding remains an explicit step. Existing application data and Docker volumes are preserved.

Services: Next.js on port **3000**, Nginx/API on **8080**, PHP **8.5** FPM, and MySQL **8.4** on host port **33079**. The frontend calls Nginx over the Docker network; image URLs use `APP_URL=http://localhost:8080`. Database and uploaded images persist in named volumes. The app image includes development tools so the assessment tests can run inside the same PHP environment.

```sh
docker compose ps
docker compose logs --tail=100 app nginx web
./bin/test
docker compose exec app composer types:check
docker compose stop
```

Run `./bin/setup` after changing source to rebuild the containers. `docker compose down` preserves data; adding `--volumes` destroys the demo database and uploads. Uploads belong to the application volume and should be backed up alongside the database in a deployment.

## Native development

Use PHP 8.5 with Composer and the PDO MySQL, GD, mbstring, XML, intl, zip, and pcntl extensions. The installed test dependencies require PHP 8.4.1 or newer; PHP 8.5 is the supported container/CI target.

```sh
composer setup
# Review .env; defaults connect to the Docker MySQL service on port 33079.
docker compose up -d mysql
php artisan migrate
php artisan db:seed --class=DemoSeeder
php artisan storage:link
composer docs
php artisan serve --host=127.0.0.1 --port=8080
```

Stop the Nginx container first if port 8080 is occupied. Start the frontend with the instructions in its README. Laravel's original Inertia source remains unused; the API runtime, setup, and CI do not require Node or a Laravel frontend build.

## API

Open the **[Scribe API documentation](http://localhost:8080/docs/)** for endpoint descriptions, example requests/responses, and **Try it out**. Use **Try it out** on the login endpoint, then set the **Authorization** field on protected requests to `Bearer <data.token>` using the returned token. Interactive writes operate on the shared catalog; the example token is intentionally invalid.

Download the generated [OpenAPI 3.1 YAML](http://localhost:8080/docs/openapi.yaml), [OpenAPI JSON](http://localhost:8080/docs/openapi.json), or [Postman collection](http://localhost:8080/docs/collection.json). A reviewable copy of the [generated OpenAPI JSON](docs/openapi.json) is committed with the source. API information is at `/`; health is at `/up`.

Scribe 5 is a development dependency. Documentation comes from controller docblocks, Form Request rules and parameter descriptions, and fixed JSON responses in `docs/responses/`. Generation never calls API endpoints, queries catalog data, or creates a real token. PUT and PATCH have separate route definitions pointing to the same service-backed controller method so their different validation requirements appear correctly.

```sh
# Native PHP: regenerate after endpoint or validation changes.
composer docs
# Docker: documentation is generated and copied into Nginx during the build.
./bin/setup
```

The static site lives in ignored `public/docs/`; Scribe's intermediate `.scribe/` output is ignored too. Edit the source annotations/examples, not generated output. `composer docs` also updates `docs/openapi.json`. CI regenerates it and fails if the committed export is stale. No Laravel frontend build is needed. `SCRIBE_BASE_URL` defaults to `http://localhost:8080`; set it when generating docs for a different API origin (or pass `--build-arg SCRIBE_BASE_URL=https://api.example.com` to `docker compose build`).

```sh
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"demo@rightmo.test","password":"RightmoDemo!2026"}'
# Set TOKEN to the returned data.token, then:
curl 'http://localhost:8080/api/v1/products?search=studio&sort_by=price&sort_direction=asc' \
  -H "Authorization: Bearer $TOKEN"
curl -X POST http://localhost:8080/api/v1/products/1/image \
  -H "Authorization: Bearer $TOKEN" -F 'image=@/absolute/path/product.png'
```

Only login is public under `/api/v1`. Product/category routes and `/auth/me` and `/auth/logout` require a bearer token. All authenticated users share the same catalog and can modify all products. Login issues an eight-hour Sanctum token; logout revokes that token. Sanctum's session guard and stateful domains are disabled for this API. The Next.js server stores the token in an HttpOnly, host-only cookie and forwards it to Laravel; the token is never returned to browser JavaScript. Browser mutations require an exact trusted origin. HTTPS enables the Secure cookie flag.

Search uses literal, case-insensitive name substrings. Category and price filters combine. Sorting supports price or rating in either direction; omission means newest first, with ID tie-breakers. Pagination defaults to 12 and caps at 100. Names are trimmed and uniquely constrained by a case-insensitive MySQL collation (also accent-insensitive under `utf8mb4_unicode_ci`). Price is a nonnegative `DECIMAL(12,2)` returned as a string; rating is 0–5 with one decimal place. PUT requires all editable fields, including a nullable description; PATCH preserves absent fields. Images have their own upload endpoint.

Responses use `data`, with `links` and `meta` for lists. Validation errors use `message` and `errors`; other errors use `message`. Authentication, missing resources, validation, excessive request size, throttling, and internal failures return 401, 404, 422, 413, 429, and 500 respectively. Internal exception details are logged, never returned by API error responses.

## Architecture

`Form Request → Controller → Service → Repository contract → Eloquent`

Controllers handle HTTP orchestration and API Resources handle serialization. Services own application operations and transaction boundaries. Typed DTOs carry listing criteria and writes, including the distinction between an omitted field and an explicit null. Product/category/user repositories isolate persistence; the service container binds each to its Eloquent implementation. Authentication delegates token management to Sanctum. There are no generic base repositories or service interfaces without an interchangeable boundary.

`ImageStorage` isolates storage from the product workflow. Images accept JPEG, PNG, and WebP up to 5 MiB, with maximum dimensions of 10,000 × 10,000. Content validation happens in Laravel, filenames are generated, and API responses expose URLs rather than storage paths. Product images are public assets. Local demo SVGs are trusted repository assets; SVG uploads are rejected.

A replacement is stored first. The service locks the product row, updates its image path inside a transaction, and deletes the previous image after commit. Failed persistence removes the new file; cleanup failures are logged without undoing committed data. Product deletion uses the same row lock and after-commit cleanup. A process crash or persistent storage failure can leave an unreferenced file; durable cleanup jobs/object storage are deliberately outside this assessment. PHP and Nginx allow multipart overhead above the 5 MiB file limit.

## Tests and quality

```sh
./bin/test
./bin/test --coverage --min=80
./bin/test tests/Feature/ProductImageTest.php
# Native PHP, using the compose MySQL instance:
DB_PORT=33079 DB_USERNAME=rightmo DB_PASSWORD=rightmo_local composer test
```

New tests are PHPUnit classes. The existing two Pest starter tests remain; `php artisan test` runs both styles. API tests use real MySQL queries and Laravel storage fakes. The concurrency test runs two PHP processes against an actual locked product row. The documentation test generates Scribe output in an isolated directory with the database unavailable and checks route coverage, authentication, PUT/PATCH semantics, query filters, response types, and uploads. Test boot refuses any database name that does not end in `_testing`, protecting the application database. Never point tests at production credentials.

Coverage includes login/revocation/expiry/throttling, route protection, CRUD, unique names and duplicate write races, validation, literal search, filters/sorting/pagination, multipart validation, rollback cleanup, cleanup failure, and concurrent image replacements. `composer test` runs formatting checks, PHPStan level 7, and the complete suite. PHP changes are formatted with `vendor/bin/pint --dirty --format agent`.

GitHub Actions runs PHP 8.5 and MySQL 8.4, with PCOV line coverage required to reach 80%. The Docker test command enables PCOV; it is disabled for normal application requests. The [frontend repository](https://github.com/ccdilan/rightmo-web) has separate lint, TypeScript, test, and build checks plus an integration smoke test for the full Docker stack. Neither workflow deploys or publishes the application.
