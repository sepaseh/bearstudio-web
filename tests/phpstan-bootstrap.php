<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;

require_once __DIR__.'/bootstrap.php';

// Replace Larastan's default app bootstrap so static analysis cannot read operator state.
$scratch = sys_get_temp_dir().'/bearstudio-analysis-'.bin2hex(random_bytes(10));
foreach (['framework/views', 'framework/sessions', 'framework/cache', 'logs', 'bootstrap/cache'] as $path) {
    mkdir($scratch.'/'.$path, 0750, true);
}
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->useEnvironmentPath($scratch);
$app->loadEnvironmentFrom('.env.testing');
$app->useStoragePath($scratch);
$app->useBootstrapPath($scratch.'/bootstrap');
$app->make(Kernel::class)->bootstrap();
if (! defined('LARAVEL_VERSION')) {
    define('LARAVEL_VERSION', $app->version());
}
register_shutdown_function(static function () use ($app, $scratch): void {
    $app['db']->disconnect();
    (new Filesystem)->deleteDirectory($scratch);
});
