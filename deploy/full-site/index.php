<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$application = dirname(__DIR__).'/bearstudio-backend';

if (! is_file($application.'/vendor/autoload.php') || ! is_file($application.'/bootstrap/app.php')) {
    http_response_code(503);
    header('Cache-Control: no-store');
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Bear Studio is temporarily unavailable.');
}

require $application.'/vendor/autoload.php';

$app = require $application.'/bootstrap/app.php';
$marker = $application.'/release-id';
if (is_readable($marker)) {
    $releaseId = trim((string) file_get_contents($marker));
    if (preg_match('/\A[a-f0-9]{32}\z/', $releaseId) === 1) {
        header('X-BearStudio-Release: '.$releaseId);
    }
}
$app->usePublicPath(__DIR__)->handleRequest(Request::capture());
