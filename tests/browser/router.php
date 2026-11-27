<?php

declare(strict_types=1);
use Illuminate\Http\Request;

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Only expose static files beneath the public asset directory.
if (str_starts_with($url, '/site-assets/') && ! str_contains(rawurldecode($url), '..') && is_file(dirname(__DIR__, 2).'/public'.$url)) {
    return false;
}
$app = require __DIR__.'/bootstrap.php';
$app->handleRequest(Request::capture());
