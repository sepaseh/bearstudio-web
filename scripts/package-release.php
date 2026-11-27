<?php

declare(strict_types=1);

// Export only application source and locked production dependencies, never operator state.
$root = dirname(__DIR__);
$releaseId = bin2hex(random_bytes(16));
$directory = $root.'/.build/release-'.$releaseId;
$backend = $directory.'/bearstudio-backend';
mkdir($backend, 0750, true);
if (file_put_contents($backend.'/release-id', $releaseId.PHP_EOL) === false) {
    throw new RuntimeException('Could not write the release identifier.');
}
$copy = function (string $source, string $target) use (&$copy): void {
    if (is_link($source)) {
        throw new RuntimeException('Release inputs cannot be symbolic links.');
    }
    if (is_dir($source)) {
        if (! is_dir($target)) {
            mkdir($target, 0750, true);
        }
        foreach (new DirectoryIterator($source) as $entry) {
            if (! $entry->isDot()) {
                $copy($entry->getPathname(), $target.'/'.$entry->getFilename());
            }
        }
    } elseif (! copy($source, $target)) {
        throw new RuntimeException('Could not copy release input.');
    }
};
foreach (['app', 'config', 'database', 'resources', 'routes'] as $path) {
    $copy($root.'/'.$path, $backend.'/'.$path);
}
mkdir($backend.'/bootstrap/cache', 0750, true);
$copy($root.'/bootstrap/app.php', $backend.'/bootstrap/app.php');
if (is_file($root.'/bootstrap/providers.php')) {
    $copy($root.'/bootstrap/providers.php', $backend.'/bootstrap/providers.php');
}
foreach (['artisan', 'composer.json', 'composer.lock', '.env.example'] as $file) {
    $copy($root.'/'.$file, $backend.'/'.$file);
}
foreach (['app/private', 'db', 'framework/cache', 'framework/views', 'framework/sessions', 'logs'] as $path) {
    mkdir($backend.'/storage/'.$path, 0750, true);
}
mkdir($directory.'/public_html', 0750, true);
foreach (['site-assets', 'thumbnails', '.user.ini'] as $path) {
    $copy($root.'/public/'.$path, $directory.'/public_html/'.$path);
}
$copy($root.'/deploy/full-site/index.php', $directory.'/public_html/index.php');
$copy($root.'/deploy/full-site/.htaccess', $directory.'/public_html/.htaccess');
mkdir($directory.'/admin-api', 0750, true);
$copy($root.'/deploy/full-site/index.php', $directory.'/admin-api/index.php');
$copy($root.'/deploy/admin.htaccess', $directory.'/admin-api/api-and-spa.htaccess');
$copy($root.'/public/.user.ini', $directory.'/admin-api/.user.ini');
$options = getopt('', ['composer:']);
$command = isset($options['composer']) ? [PHP_BINARY, $options['composer']] : ['composer'];
$command = [...$command, 'install', '--working-dir='.$backend, '--no-dev', '--no-scripts', '--prefer-dist', '--no-interaction', '--no-progress', '--optimize-autoloader'];
$process = proc_open($command, [0 => STDIN, 1 => STDERR, 2 => STDERR], $pipes);
if (! is_resource($process) || proc_close($process) !== 0) {
    throw new RuntimeException('Production dependency installation failed. The incomplete export remains under .build for inspection.');
}
if (is_file($backend.'/.env') || is_dir($backend.'/vendor/phpunit') || is_dir($backend.'/tests')) {
    throw new RuntimeException('Release contains development or operator state.');
}
$process = proc_open([PHP_BINARY, $root.'/scripts/verify-release.php', $directory], [0 => STDIN, 1 => STDERR, 2 => STDERR], $pipes);
if (! is_resource($process) || proc_close($process) !== 0) {
    throw new RuntimeException('Exported release smoke verification failed.');
}
echo $directory.PHP_EOL;
