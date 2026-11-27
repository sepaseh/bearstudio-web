<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use stdClass;

final class ProjectInput
{
    public const CATALOG_LIMIT = 1000;

    private const FIELDS = ['title', 'slug', 'serviceSlug', 'description', 'poster', 'video', 'preview', 'watch', 'isFeatured'];

    public static function read(Request $request, bool $creating): array
    {
        $payload = self::decode($request, 65536);
        if (! $payload instanceof stdClass || array_diff(array_keys((array) $payload), self::FIELDS)) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'Send only supported project fields.');
        }

        $fields = array_filter((array) $payload, fn ($value) => $value !== null);

        if ($creating) {
            foreach (['title', 'slug', 'serviceSlug', 'poster'] as $required) {
                if (! array_key_exists($required, $fields)) {
                    self::invalid($required);
                }
            }
        } elseif ($fields === []) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'Supply a project field to update.');
        }

        return $fields;
    }

    public static function sortSlugs(Request $request): array
    {
        $payload = self::decode($request, 262144);
        if (! $payload instanceof stdClass || array_keys((array) $payload) !== ['slugs']
            || ! is_array($payload->slugs) || count($payload->slugs) > self::CATALOG_LIMIT) {
            self::invalid('slugs');
        }

        foreach ($payload->slugs as $slug) {
            if (! is_string($slug)) {
                self::invalid('slugs');
            }
        }

        return $payload->slugs;
    }

    public static function changes(array $fields): array
    {
        $result = [];

        foreach ($fields as $field => $value) {
            switch ($field) {
                case 'title':
                    $result['title'] = self::text($value, 500, $field);
                    break;
                case 'slug':
                    $result['slug'] = self::slug($value, $field);
                    break;
                case 'serviceSlug':
                    $slug = self::slug($value, $field);
                    if (! DB::table('services')->where('slug', $slug)->exists()) {
                        self::invalid($field);
                    }
                    $result['service_slug'] = $slug;
                    break;
                case 'description':
                    $result['summary'] = self::text($value, 2000, $field, true);
                    break;
                case 'isFeatured':
                    if (! is_bool($value)) {
                        self::invalid($field);
                    }
                    $result['is_featured'] = $value;
                    break;
                case 'poster':
                    [$result['poster_image_id'], $result['poster_url']] = self::source($value, $field, true);
                    break;
                case 'video':
                    [$result['full_video_id'], $result['full_video_url']] = self::source($value, $field);
                    break;
                case 'preview':
                    [$result['preview_video_id'], $result['preview_video_url']] = self::source($value, $field);
                    break;
                case 'watch':
                    [$result['watch_url'], $result['watch_label']] = self::watch($value);
                    break;
            }
        }

        return $result;
    }

    private static function decode(Request $request, int $maximumBytes): mixed
    {
        if (strlen($request->getContent()) > $maximumBytes) {
            ApiError::abort(413, 'REQUEST_TOO_LARGE', 'The project request is too large.');
        }

        if (! $request->isJson()) {
            ApiError::abort(415, 'UNSUPPORTED_MEDIA_TYPE', 'Send project fields as JSON.');
        }

        try {
            return json_decode($request->getContent(), false, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'Send a valid JSON object.');
        }
    }

    private static function source(mixed $value, string $field, bool $image = false): array
    {
        if (! $value instanceof stdClass || array_diff(array_keys((array) $value), ['id', 'url'])) {
            self::invalid($field);
        }

        $id = $value->id ?? null;
        $url = $value->url ?? null;

        if ($id === null && $url === null && ! $image) {
            return [null, null];
        }

        if (($id === null) === ($url === null)) {
            self::invalid($field);
        }

        if ($url !== null) {
            $url = self::https($url, $field);
            if (! $image && ! str_ends_with(strtolower((string) parse_url($url, PHP_URL_PATH)), '.mp4')) {
                self::invalid($field);
            }

            return [null, $url];
        }

        if (! is_string($id) || ! Str::isUuid($id)) {
            self::invalid($field);
        }

        $id = strtolower($id);
        $reference = DB::table($image ? 'images' : 'videos')->where('id', $id);
        if ($image) {
            $reference->where('context', 'project');
        }
        if (! $reference->exists()) {
            self::invalid($field);
        }

        return [$id, null];
    }

    private static function watch(mixed $value): array
    {
        if (! $value instanceof stdClass || array_diff(array_keys((array) $value), ['url', 'label'])) {
            self::invalid('watch');
        }

        if (! isset($value->url) && ! isset($value->label)) {
            return [null, null];
        }

        return [self::https($value->url ?? null, 'watch'), self::text($value->label ?? null, 80, 'watch')];
    }

    private static function slug(mixed $value, string $field): string
    {
        $slug = strtolower(self::text($value, 160, $field));
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)) {
            self::invalid($field);
        }

        return $slug;
    }

    private static function https(mixed $value, string $field): string
    {
        $url = self::text($value, 2048, $field);
        if (! filter_var($url, FILTER_VALIDATE_URL) || preg_match('/[\x00-\x20\x7f]/', $url) || str_contains($url, '\\')) {
            self::invalid($field);
        }

        $components = parse_url($url);
        if (strtolower($components['scheme'] ?? '') !== 'https' || isset($components['user']) || isset($components['pass'])) {
            self::invalid($field);
        }

        return $url;
    }

    private static function text(mixed $value, int $maximumLength, string $field, bool $allowEmpty = false): string
    {
        if (! is_string($value) || str_contains($value, "\0")) {
            self::invalid($field);
        }

        $trimmed = trim($value);
        if ((! $allowEmpty && $trimmed === '') || mb_strlen($trimmed) > $maximumLength) {
            self::invalid($field);
        }

        return $trimmed;
    }

    private static function invalid(string $field): never
    {
        ApiError::abort(400, 'VALIDATION_FAILED', 'Check the supplied project fields.', [
            $field => 'Provide a valid value for this field.',
        ]);
    }
}
