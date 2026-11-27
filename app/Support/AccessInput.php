<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JsonException;
use stdClass;

final class AccessInput
{
    public static function read(Request $request, array $allowed, int $limit = 65536): stdClass
    {
        self::noQuery($request);
        $body = $request->getContent();
        if (strlen($body) > $limit) {
            ApiError::abort(413, 'REQUEST_TOO_LARGE', 'The access request is too large.');
        }
        if (! $request->isJson()) {
            ApiError::abort(415, 'UNSUPPORTED_MEDIA_TYPE', 'Use application/json.');
        }

        try {
            $input = json_decode($body, false, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            self::invalid('input', 'Send a valid JSON object.');
        }
        if (! $input instanceof stdClass || array_diff(array_keys((array) $input), $allowed) !== []) {
            self::invalid('input', 'Send only supported fields.');
        }

        return $input;
    }

    public static function name(mixed $value): string
    {
        $name = is_string($value) ? preg_replace('/\A\s+|\s+\z/u', '', $value) : null;
        if ($name === null || $name === '' || mb_strlen($name, 'UTF-8') > 120) {
            self::invalid('name', 'Use a name of 1–120 characters.');
        }

        return $name;
    }

    public static function ids(mixed $value, string $field, ?int $max = null): array
    {
        if (! is_array($value) || ($max !== null && count($value) > $max)) {
            self::invalid($field, 'Choose a valid list of identifiers.');
        }

        $seen = [];
        foreach ($value as $id) {
            if (! is_string($id) || ! Str::isUuid($id) || $id === '00000000-0000-0000-0000-000000000000') {
                self::invalid($field, 'Choose valid identifiers.');
            }
            $normalized = strtolower($id);
            if (isset($seen[$normalized])) {
                self::invalid($field, 'Choose each identifier only once.');
            }
            $seen[$normalized] = true;
        }

        return array_keys($seen);
    }

    public static function noQuery(Request $request): void
    {
        if ($request->query->count() !== 0) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'This endpoint does not accept query parameters.');
        }
    }

    public static function noBody(Request $request): void
    {
        if ($request->getContent() !== '') {
            ApiError::abort(400, 'VALIDATION_FAILED', 'This endpoint does not accept a request body.');
        }
    }

    public static function invalid(string $field, string $message): never
    {
        ApiError::abort(400, 'VALIDATION_FAILED', $message, [$field => $message]);
    }
}
