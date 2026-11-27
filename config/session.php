<?php

return [
    'driver' => 'database',
    'lifetime' => 480,
    'expire_on_close' => true,
    'encrypt' => true,
    'files' => storage_path('framework/sessions'),
    'connection' => null,
    'table' => 'sessions',
    'lottery' => [2, 100],
    'cookie' => env('SESSION_SECURE_COOKIE', true) ? '__Host-BearStudio-Pilot' : 'BearStudio-Pilot-Local',
    'path' => '/',
    'domain' => null,
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
];
