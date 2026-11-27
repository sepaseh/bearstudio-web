<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

foreach (['APP_CONFIG_CACHE', 'APP_ROUTES_CACHE', 'APP_SERVICES_CACHE', 'APP_PACKAGES_CACHE', 'LARAVEL_STORAGE_PATH'] as $key) {
    putenv($key);
    unset($_ENV[$key], $_SERVER[$key]);
}

// Set isolation before any framework bootstrap; never load the operator's .env.
foreach ([
    'APP_ENV' => 'testing',
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('t', 32)),
    'APP_URL' => 'http://web.test',
    'ADMIN_HOST' => 'admin.web.test',
    'APP_DEBUG' => 'false',
    'APP_ALLOW_INDEXING' => 'true',
    'SESSION_SECURE_COOKIE' => 'false',
    'BCRYPT_ROUNDS' => '4',
    'DB_CONNECTION' => 'mariadb',
    'DB_DATABASE' => 'unconfigured_test_database',
    'CACHE_STORE' => 'array',
    'LOG_CHANNEL' => 'stderr',
    'LOG_LEVEL' => 'warning',
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
