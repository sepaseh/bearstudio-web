<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use stdClass;

final class ArticleInput
{
    public static function changes(Request $request, bool $creating): array
    {
        $fields = ['title', 'slug', 'excerpt', 'body', 'category', 'cover'];
        $input = AccessInput::read($request, $fields, 512 * 1024);
        $values = array_filter((array) $input, static fn ($value): bool => $value !== null);
        if ($creating && count($values) !== count($fields)) {
            AccessInput::invalid('input', 'Supply a title, slug, excerpt, body, category and cover.');
        }
        if ($values === []) {
            AccessInput::invalid('input', 'Supply at least one editable field.');
        }

        $changes = [];
        foreach ($values as $field => $value) {
            switch ($field) {
                case 'title':
                    $changes['title'] = self::text($value, 'title', 200);
                    break;
                case 'slug':
                    $changes['slug'] = self::slug($value);
                    break;
                case 'excerpt':
                    $changes['excerpt'] = self::text($value, 'excerpt', 1000);
                    break;
                case 'body':
                    $changes['body'] = ArticleHtml::normalize($value);
                    $changes['body_is_html'] = true;
                    break;
                case 'category':
                    $slug = self::reference($value, 'category', 'slug');
                    if (! is_string($slug) || $slug === 'sort' || ! self::canonicalSlug($slug)) {
                        AccessInput::invalid('category.slug', 'Choose an existing article category.');
                    }
                    $changes['category_slug'] = $slug;
                    break;
                case 'cover':
                    $id = self::reference($value, 'cover', 'id');
                    $changes['cover_image_id'] = AccessInput::ids([$id], 'cover.id')[0];
                    break;
            }
        }

        return $changes;
    }

    public static function slug(mixed $value): string
    {
        $slug = strtolower(self::text($value, 'slug', 160));
        if (! self::canonicalSlug($slug)) {
            AccessInput::invalid('slug', 'Use lowercase ASCII letters, digits and single hyphens.');
        }

        return $slug;
    }

    public static function canonicalSlug(string $slug): bool
    {
        return strlen($slug) <= 160 && preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug) === 1;
    }

    private static function reference(mixed $value, string $field, string $key): mixed
    {
        if (! $value instanceof stdClass || array_keys((array) $value) !== [$key]) {
            AccessInput::invalid($field, 'Choose a valid reference.');
        }

        return $value->$key;
    }

    private static function text(mixed $value, string $field, int $maximum): string
    {
        $text = is_string($value) && ! str_contains($value, "\0") ? trim($value) : '';
        if ($text === '' || mb_strlen($text, 'UTF-8') > $maximum) {
            AccessInput::invalid($field, 'Supply a nonempty value of at most '.$maximum.' characters.');
        }

        return $text;
    }
}
