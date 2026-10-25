<?php

use Knuckles\Scribe\Config\AuthIn;
use Knuckles\Scribe\Config\Defaults;
use Knuckles\Scribe\Extracting\Strategies;

if (! class_exists(Defaults::class)) {
    return [];
}

return [
    'title' => 'Rightmo API',
    'description' => 'Version 1 of the shared Rightmo product catalog. All authenticated users have equal access.',
    'intro_text' => <<<'INTRO'
        Start with **Authentication → Log in**, then use the returned token as a Bearer token for other endpoints.
        Tokens expire after eight hours. Logging out revokes only the current token.

        Use **Try it out** to call this API directly. Writes change the shared catalog; image URLs are public assets.
        The example token is a placeholder. Demo credentials work only after explicitly running `DemoSeeder` locally.

        Responses use a `data` envelope; paginated lists also include `links` and `meta`.
        Errors contain `message`, with field-level `errors` for validation failures.
        Prices are decimal strings, and the frontend's display currency defaults to USD.
        See the repository README for setup and the repository/service architecture.
        INTRO,
    'base_url' => env('SCRIBE_BASE_URL', 'http://localhost:8080'),
    'routes' => [[
        'match' => ['prefixes' => ['api/v1/*'], 'domains' => ['*']],
        'include' => [],
        'exclude' => [],
    ]],
    'type' => 'static',
    'theme' => 'default',
    'static' => ['output_path' => 'public/docs'],
    'laravel' => ['add_routes' => false],
    'try_it_out' => ['enabled' => true, 'base_url' => null, 'use_csrf' => false],
    'auth' => [
        'enabled' => true,
        'default' => true,
        'in' => AuthIn::BEARER->value,
        'name' => 'Authorization',
        'use_value' => null,
        'placeholder' => '{YOUR_BEARER_TOKEN}',
        'extra_info' => 'Call POST /api/v1/auth/login to obtain data.token. Send it in the Authorization header. This API uses Sanctum bearer tokens, not cookie/CSRF authentication. The Next.js application separately keeps its token in an HttpOnly cookie.',
    ],
    'example_languages' => ['bash', 'javascript'],
    'postman' => ['enabled' => true, 'overrides' => []],
    'openapi' => ['enabled' => true, 'version' => '3.1.0', 'overrides' => ['info.version' => '1.0.0'], 'generators' => []],
    'groups' => ['default' => 'Products', 'order' => ['Authentication', 'Categories', 'Products' => ['GET /api/v1/products', 'GET /api/v1/products/{product}', 'POST /api/v1/products', 'PUT /api/v1/products/{product}', 'PATCH /api/v1/products/{product}', 'DELETE /api/v1/products/{product}'], 'Product images']],
    'logo' => false,
    'last_updated' => 'Generated from the Rightmo API source',
    'examples' => ['faker_seed' => 1234, 'models_source' => []],
    'strategies' => [
        'metadata' => [...Defaults::METADATA_STRATEGIES, Strategies\StaticData::withSettings(only: ['PUT api/v1/products/*'], data: ['title' => 'Replace a product'])],
        'headers' => [...Defaults::HEADERS_STRATEGIES, Strategies\StaticData::withSettings(data: ['Accept' => 'application/json'])],
        'urlParameters' => Defaults::URL_PARAMETERS_STRATEGIES,
        'queryParameters' => Defaults::QUERY_PARAMETERS_STRATEGIES,
        // Scribe does not infer Laravel's `present` rule as a required key.
        'bodyParameters' => [...Defaults::BODY_PARAMETERS_STRATEGIES, Strategies\StaticData::withSettings(only: ['PUT api/v1/products/*'], data: ['description' => ['required' => true, 'nullable' => true]])],
        // Fixed examples keep generation independent of application data and never invoke mutations.
        'responses' => [Strategies\Responses\UseResponseTag::class, Strategies\Responses\UseResponseFileTag::class],
        'responseFields' => [...Defaults::RESPONSE_FIELDS_STRATEGIES, Strategies\StaticData::withSettings(only: ['GET api/v1/products*', 'POST api/v1/products*', 'PUT api/v1/products/*', 'PATCH api/v1/products/*'], data: [
            'data.description' => ['nullable' => true],
            'data.image_url' => ['nullable' => true],
        ])],
    ],
    'database_connections_to_transact' => [],
];
