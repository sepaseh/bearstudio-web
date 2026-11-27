<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class HomePage
{
    public const ID = 1;

    public static function record(bool $public): ?array
    {
        $query = DB::table('home_pages as page')->where('page.id', self::ID)
            ->join('images as hero', 'page.hero_poster_image_id', '=', 'hero.id')
            ->leftJoin('images as about', 'page.about_poster_image_id', '=', 'about.id')
            ->join('images as why', 'page.why_image_id', '=', 'why.id')
            ->join('home_page_approaches as approach', 'page.id', '=', 'approach.home_page_id')
            ->join('images as card', 'approach.image_id', '=', 'card.id')
            ->orderBy('approach.position');

        $rows = $query->get([
            'page.*', 'hero.width as hero_width', 'hero.height as hero_height', 'hero.context as hero_context',
            'about.width as about_width', 'about.height as about_height', 'about.context as about_context',
            'why.width as why_width', 'why.height as why_height', 'why.context as why_context',
            'approach.title as card_title', 'card.id as card_id', 'card.width as card_width',
            'card.height as card_height', 'card.context as card_context',
        ]);
        $page = $rows->first();
        if ($page === null) {
            return null;
        }
        if ($public) {
            $editorial = ['article', 'project', 'service'];
            if (! in_array($page->hero_context, [...$editorial, 'content'], true)
                || ! in_array($page->why_context, [...$editorial, 'content'], true)
                || ($page->about_context !== null && ! in_array($page->about_context, $editorial, true))
                || $rows->contains(fn ($row) => ! in_array($row->card_context, [...$editorial, 'approach'], true))) {
                return null;
            }
        }

        $heroVideo = self::video($page->hero_video_id, $page->hero_video_url);
        $aboutVideo = self::video($page->about_video_id, $page->about_video_url);
        $content = [
            'heroDescription' => $page->hero_description,
            $public ? 'heroVideoUrl' : 'heroVideo' => $public ? ($heroVideo['url'] ?? null) : $heroVideo,
            'heroPoster' => self::image($public, $page->hero_poster_image_id, $page->hero_width, $page->hero_height, $page->hero_description),
            'aboutTitle' => $page->about_title,
            'aboutSubtitle' => $page->about_subtitle,
            'aboutDescription' => $page->about_description,
            $public ? 'aboutVideoUrl' : 'aboutVideo' => $public ? ($aboutVideo['url'] ?? null) : $aboutVideo,
            'aboutPoster' => $page->about_poster_image_id === null ? null : self::image(
                $public, $page->about_poster_image_id, $page->about_width, $page->about_height, $page->about_poster_alt,
            ),
            'approach' => $rows->map(fn ($row): array => [
                'title' => $row->card_title,
                'image' => self::image($public, $row->card_id, $row->card_width, $row->card_height, $row->card_title),
            ])->all(),
            'whyTitle' => $page->why_title,
            'whySubtitle' => $page->why_subtitle,
            'whyDescription' => $page->why_description,
            'whyImage' => self::image($public, $page->why_image_id, $page->why_width, $page->why_height, $page->why_title),
        ];

        return $public ? $content : ['key' => 'home', 'content' => $content];
    }

    private static function image(bool $public, string $id, int $width, int $height, string $alt): array
    {
        $url = '/api/v1/public/images/'.$id;
        $image = ['url' => $url, 'thumb' => $url.'/thumbnail', 'alt' => $alt];

        return $public ? ['id' => $id, 'width' => $width, 'height' => $height] + $image : ['imageId' => $id] + $image;
    }

    private static function video(?string $id, ?string $direct): ?array
    {
        $url = $id === null ? $direct : '/api/v1/public/videos/'.$id;

        return $url === null ? null : ['id' => $id, 'url' => $url];
    }
}
