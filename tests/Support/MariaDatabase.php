<?php

declare(strict_types=1);

namespace Tests\Support;

use PDO;
use RuntimeException;

final class MariaDatabase
{
    public static function connection(): PDO
    {
        $host = getenv('BEARSTUDIO_TEST_DB_HOST') ?: '127.0.0.1';
        if (! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new RuntimeException('Tests require a local MariaDB server.');
        }

        return new PDO('mysql:host='.$host.';port='.(getenv('BEARSTUDIO_TEST_DB_PORT') ?: '3306'), getenv('BEARSTUDIO_TEST_DB_USER') ?: 'root', getenv('BEARSTUDIO_TEST_DB_PASSWORD') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    public static function create(): string
    {
        $name = 'bearstudio_test_'.bin2hex(random_bytes(12));
        self::connection()->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        return $name;
    }

    public static function drop(string $name): void
    {
        if (! preg_match('/^bearstudio_test_[a-f0-9]{24}$/D', $name)) {
            throw new RuntimeException('Refusing to drop a database outside the test namespace.');
        }
        self::connection()->exec('DROP DATABASE `'.$name.'`');
    }

    public static function environment(string $name): array
    {
        return [
            'DB_CONNECTION' => 'mariadb', 'DB_DATABASE' => $name,
            'DB_HOST' => getenv('BEARSTUDIO_TEST_DB_HOST') ?: '127.0.0.1',
            'DB_PORT' => getenv('BEARSTUDIO_TEST_DB_PORT') ?: '3306',
            'DB_USERNAME' => getenv('BEARSTUDIO_TEST_DB_USER') ?: 'root',
            'DB_PASSWORD' => getenv('BEARSTUDIO_TEST_DB_PASSWORD') ?: '',
        ];
    }
}
