<?php

declare(strict_types=1);
use Symfony\Component\Console\Input\ArrayInput;
use Tests\Support\MariaDatabase;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$storage = getenv('BEARSTUDIO_TEST_STORAGE');
if (! is_string($storage) || ! str_starts_with(realpath($storage) ?: '', realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'bearstudio-browser-')) {
    throw new RuntimeException('A disposable browser test directory is required.');
}
require_once dirname(__DIR__).'/Support/MariaDatabase.php';
if (PHP_SAPI === 'cli') {
    if (($argv[1] ?? '') === 'cleanup') {
        MariaDatabase::drop(trim(file_get_contents($storage.'/database-name')));
        exit(0);
    }
    file_put_contents($storage.'/database-name', MariaDatabase::create());
}
foreach ([
    'APP_ENV' => 'testing', 'APP_KEY' => 'base64:'.base64_encode(str_repeat('b', 32)),
    'APP_URL' => 'http://127.0.0.1:8765', 'ADMIN_HOST' => 'localhost',
    'APP_ALLOW_INDEXING' => 'true', 'SESSION_SECURE_COOKIE' => 'false',
    ...MariaDatabase::environment(trim(file_get_contents($storage.'/database-name'))),
    'LOG_CHANNEL' => 'stderr', 'APP_DEBUG' => 'false',
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->useEnvironmentPath($storage);
$app->loadEnvironmentFrom('.env.testing');
$app->useStoragePath($storage);
$app->useBootstrapPath($storage.'/bootstrap');
if (PHP_SAPI === 'cli') {
    exit($app->handleCommand(new ArrayInput(['command' => 'migrate', '--force' => true])));
}

return $app;
