<?php

/*
|--------------------------------------------------------------------------
| Test Environment Safety Bootstrap
|--------------------------------------------------------------------------
|
| Force test-only environment values before Laravel boots so PHPUnit never
| touches the live local SQLite database.
|
*/

$forcedEnv = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'array',
    'APP_CONFIG_CACHE' => '/tmp/phpunit-config.php',
    'APP_EVENTS_CACHE' => '/tmp/phpunit-events.php',
    'APP_PACKAGES_CACHE' => '/tmp/phpunit-packages.php',
    'APP_ROUTES_CACHE' => '/tmp/phpunit-routes.php',
    'APP_SERVICES_CACHE' => '/tmp/phpunit-services.php',
];

foreach ($forcedEnv as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// Prevent stale cached bootstrap artifacts from affecting the test runtime.
foreach ([
    $_ENV['APP_CONFIG_CACHE'] ?? null,
    $_ENV['APP_EVENTS_CACHE'] ?? null,
    $_ENV['APP_PACKAGES_CACHE'] ?? null,
    $_ENV['APP_ROUTES_CACHE'] ?? null,
    $_ENV['APP_SERVICES_CACHE'] ?? null,
] as $cachePath) {
    if (is_string($cachePath) && $cachePath !== '' && file_exists($cachePath)) {
        @unlink($cachePath);
    }
}

require __DIR__ . '/../vendor/autoload.php';
