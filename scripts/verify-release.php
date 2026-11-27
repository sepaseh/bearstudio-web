<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\MariaDatabase;

$release = realpath($argv[1] ?? '');
$build = realpath(dirname(__DIR__).'/.build');
if ($release === false || $build === false || ! str_starts_with($release, $build.DIRECTORY_SEPARATOR.'release-')) {
    throw new RuntimeException('Expected an exported release under this project .build directory.');
}
$backend = $release.'/bearstudio-backend';
$marker = $backend.'/release-id';
if (! is_file($marker) || preg_match('/\A[a-f0-9]{32}\z/', $releaseId = trim((string) file_get_contents($marker))) !== 1
    || basename($release) !== 'release-'.$releaseId) {
    throw new RuntimeException('The private release identifier does not match the export directory.');
}
require $backend.'/vendor/autoload.php';
$scratch = sys_get_temp_dir().'/bearstudio-release-'.bin2hex(random_bytes(10));
foreach (['db', 'framework/views', 'framework/sessions', 'framework/cache', 'logs', 'bootstrap/cache'] as $path) {
    mkdir($scratch.'/'.$path, 0750, true);
}
require_once dirname(__DIR__).'/tests/Support/MariaDatabase.php';
$testDatabase = MariaDatabase::create();
try {
    foreach ([
        'APP_ENV' => 'testing', 'APP_KEY' => 'base64:'.base64_encode(str_repeat('r', 32)),
        'APP_URL' => 'http://release.test', 'ADMIN_HOST' => 'admin.release.test',
        'APP_ALLOW_INDEXING' => 'true', 'SESSION_SECURE_COOKIE' => 'false',
        ...MariaDatabase::environment($testDatabase), 'LOG_CHANNEL' => 'stderr',
    ] as $key => $value) {
        putenv($key.'='.$value);
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
    $app = require $backend.'/bootstrap/app.php';
    $app->useEnvironmentPath($scratch);
    $app->loadEnvironmentFrom('.env.testing');
    $app->useStoragePath($scratch);
    $app->useBootstrapPath($scratch.'/bootstrap');
    $app->make(Kernel::class)->bootstrap();
    $app['config']->set('view.compiled', $scratch.'/framework/views');
    if (Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Release migration failed.');
    }
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    foreach ([
        'http://release.test/' => 200,
        'http://release.test/projects' => 200,
        'http://release.test/articles' => 200,
        'http://release.test/sitemap.xml' => 200,
        'http://release.test/api/v1/auth/session' => 404,
        'http://admin.release.test/api/v1/auth/session' => 401,
        'http://admin.release.test/projects' => 404,
    ] as $url => $status) {
        $request = Request::create($url, 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
        $response = $kernel->handle($request);
        if ($response->getStatusCode() !== $status) {
            throw new RuntimeException('Release HTTP smoke failed: '.$url.' returned '.$response->getStatusCode());
        }
        $kernel->terminate($request, $response);
    }
    fwrite(STDERR, 'Release boot, MariaDB migration, public pages and API host boundary verified.'.PHP_EOL);
} finally {
    if (isset($app)) {
        $app['db']->disconnect();
    }
    MariaDatabase::drop($testDatabase);
    (new Filesystem)->deleteDirectory($scratch);
}
