<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

final class HomePageInput
{
    public static function read(Request $request, ?stdClass $previous, array $previousApproach): array
    {
        $input = AccessInput::read($request, [
            'heroDescription', 'heroVideo', 'heroPoster', 'aboutTitle', 'aboutSubtitle', 'aboutDescription',
            'aboutVideo', 'aboutPoster', 'approach', 'whyTitle', 'whySubtitle', 'whyDescription', 'whyImage',
        ], 256 * 1024);
        if (! is_array($input->approach ?? null) || count($input->approach) !== 4) {
            AccessInput::invalid('approach', 'Supply exactly four ordered Approach cards.');
        }
        if (isset($input->aboutVideo) !== isset($input->aboutPoster)) {
            AccessInput::invalid('aboutVideo', 'Supply an About video and poster together, or omit both.');
        }

        [$heroVideoId, $heroVideoUrl] = self::video($input->heroVideo ?? null, 'heroVideo');
        [$aboutVideoId, $aboutVideoUrl] = isset($input->aboutVideo)
            ? self::video($input->aboutVideo, 'aboutVideo')
            : [null, null];
        $aboutId = isset($input->aboutPoster)
            ? self::image($input->aboutPoster, 'aboutPoster', ['article', 'project', 'service'], validateAlt: true)
            : null;
        $values = [
            'hero_description' => self::text($input->heroDescription ?? null, 'heroDescription', 10000),
            'hero_video_id' => $heroVideoId,
            'hero_video_url' => $heroVideoUrl,
            'hero_poster_image_id' => self::image(
                $input->heroPoster ?? null, 'heroPoster', ['content'], $previous?->hero_poster_image_id,
            ),
            'about_title' => self::text($input->aboutTitle ?? null, 'aboutTitle', 200),
            'about_subtitle' => self::text($input->aboutSubtitle ?? null, 'aboutSubtitle', 1000, allowEmpty: true),
            'about_description' => self::text($input->aboutDescription ?? null, 'aboutDescription', 10000),
            'about_video_id' => $aboutVideoId,
            'about_video_url' => $aboutVideoUrl,
            'about_poster_image_id' => $aboutId,
            'about_poster_alt' => $aboutId === null ? '' : self::text($input->aboutPoster->alt ?? '', 'aboutPoster.alt', 300, allowEmpty: true),
            'why_title' => self::text($input->whyTitle ?? null, 'whyTitle', 200),
            'why_subtitle' => self::text($input->whySubtitle ?? null, 'whySubtitle', 1000, allowEmpty: true),
            'why_description' => self::text($input->whyDescription ?? null, 'whyDescription', 10000),
            'why_image_id' => self::image(
                $input->whyImage ?? null, 'whyImage', ['project'], $previous?->why_image_id, ['content'],
            ),
        ];

        $cards = [];
        foreach ($input->approach as $position => $card) {
            if (! $card instanceof stdClass || array_diff(array_keys((array) $card), ['title', 'image']) !== []) {
                AccessInput::invalid('approach', 'Supply a title and a managed image for each Approach card.');
            }
            $cards[] = [
                'home_page_id' => HomePage::ID,
                'position' => $position,
                'title' => self::text($card->title ?? null, 'approach.title', 200),
                'image_id' => self::image(
                    $card->image ?? null, 'approach.image', ['approach'], $previousApproach[$position] ?? null, validateAlt: true,
                ),
            ];
        }

        return [$values, $cards];
    }

    private static function image(
        mixed $input, string $field, array $allowed, ?string $previousId = null, array $legacy = [], bool $validateAlt = false,
    ): string {
        if (! $input instanceof stdClass || array_diff(array_keys((array) $input), ['imageId', 'alt']) !== []
            || (isset($input->alt) && ! is_string($input->alt))) {
            AccessInput::invalid($field, 'Choose a managed image.');
        }
        if ($validateAlt) {
            self::text(property_exists($input, 'alt') ? $input->alt : '', $field.'.alt', 300, allowEmpty: true);
        }
        $id = AccessInput::ids([$input->imageId ?? null], $field.'.imageId')[0];
        $context = DB::table('images')->where('id', $id)->value('context');
        $legacy = $id === $previousId ? [...$legacy, 'article', 'project', 'service'] : [];
        if (! in_array($context, [...$allowed, ...$legacy], true)) {
            AccessInput::invalid($field, 'Choose a ready image in an accepted context.');
        }

        return $id;
    }

    private static function video(mixed $input, string $field): array
    {
        if (! $input instanceof stdClass || array_diff(array_keys((array) $input), ['id', 'url']) !== []
            || isset($input->id) === isset($input->url)) {
            AccessInput::invalid($field, 'Supply exactly one managed MP4 ID or direct HTTPS MP4 address.');
        }

        if (isset($input->id)) {
            $id = AccessInput::ids([$input->id], $field.'.id')[0];
            if (! DB::table('videos')->where('id', $id)->exists()) {
                AccessInput::invalid($field.'.id', 'Choose a ready MP4 video.');
            }

            return [$id, null];
        }

        $url = self::text($input->url, $field.'.url', 2048);
        $parts = parse_url($url);
        if (! filter_var($url, FILTER_VALIDATE_URL) || preg_match('/[\x00-\x20\x7f]/', $url) || str_contains($url, '\\')
            || strtolower($parts['scheme'] ?? '') !== 'https' || ! isset($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || ! str_ends_with(strtolower($parts['path'] ?? ''), '.mp4')) {
            AccessInput::invalid($field.'.url', 'Use a direct HTTPS MP4 address without credentials, controls or backslashes.');
        }

        return [null, $url];
    }

    private static function text(mixed $value, string $field, int $maximum, bool $allowEmpty = false): string
    {
        $text = is_string($value) && ! str_contains($value, "\0") ? preg_replace('/\A\s+|\s+\z/u', '', $value) : null;
        if ($text === null || (! $allowEmpty && $text === '') || mb_strlen($text, 'UTF-8') > $maximum) {
            AccessInput::invalid($field, 'Supply text of at most '.$maximum.' characters.');
        }

        return $text;
    }
}
