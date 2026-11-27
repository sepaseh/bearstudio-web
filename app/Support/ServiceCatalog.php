<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use stdClass;

final class ServiceCatalog
{
    public const LIMIT = 24;

    public static function adminRecords(?string $slug = null): array
    {
        return self::records($slug, public: false);
    }

    public static function publicRecords(): array
    {
        return self::records(null, public: true);
    }

    private static function records(?string $slug, bool $public): array
    {
        $rows = DB::table('services')
            ->leftJoin('service_images', 'services.slug', '=', 'service_images.service_slug')
            ->leftJoin('images', 'service_images.image_id', '=', 'images.id')
            ->when($slug !== null, fn ($query) => $query->where('services.slug', $slug))
            ->orderBy('services.sort_order')->orderBy('services.slug')->orderBy('service_images.sort_order')
            ->get(['services.slug', 'services.title', 'services.description', 'services.tags',
                'service_images.image_id', 'service_images.alt', 'images.width', 'images.height', 'images.context']);

        $records = [];
        foreach ($rows->groupBy('slug') as $group) {
            $service = $group->first();
            $images = $group->filter(fn (stdClass $row) => $row->image_id !== null);
            if ($public && $images->contains(fn (stdClass $row) => $row->context !== 'service')) {
                continue;
            }

            $records[] = [
                'slug' => $service->slug,
                'title' => $service->title,
                'description' => $service->description,
                'tags' => json_decode($service->tags, true, flags: JSON_THROW_ON_ERROR),
                'images' => $images->map(fn (stdClass $image) => self::image($image, $public))->values()->all(),
            ];
        }

        return $records;
    }

    private static function image(stdClass $image, bool $public): array
    {
        $url = '/api/v1/public/images/'.$image->image_id;
        $item = ['id' => $image->image_id, 'url' => $url, 'thumb' => $url.'/thumbnail'];

        return $public ? $item + ['width' => $image->width, 'height' => $image->height, 'alt' => $image->alt] : $item;
    }
}
