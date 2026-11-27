<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

final class AdministratorPassword
{
    private const PREFIX = 'bcrypt-sha384:';

    public const POLICY = 'Use 12–128 characters with an uppercase Latin letter, a lowercase Latin letter, a digit, and a nonalphanumeric character.';

    public static function valid(mixed $password): bool
    {
        return is_string($password)
            && mb_check_encoding($password, 'UTF-8')
            && mb_strlen($password, 'UTF-8') >= 12
            && mb_strlen($password, 'UTF-8') <= 128
            && ! str_contains($password, "\0")
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1
            && preg_match('/[^A-Za-z0-9]/', $password) === 1;
    }

    public static function hash(string $password): string
    {
        return self::PREFIX.Hash::driver('bcrypt')->make(self::bcryptInput($password));
    }

    public static function verify(string $password, string $hash): bool
    {
        if (str_starts_with($hash, self::PREFIX)) {
            return password_verify(self::bcryptInput($password), substr($hash, strlen(self::PREFIX)));
        }

        // Existing pilot hashes use bcrypt directly and cover passwords up to 72 bytes.
        return strlen($password) <= 72 && ! str_contains($password, "\0") && password_verify($password, $hash);
    }

    private static function bcryptInput(string $password): string
    {
        return base64_encode(hash('sha384', $password, binary: true));
    }
}
