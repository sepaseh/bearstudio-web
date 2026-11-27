<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class PublicEditorial
{
    public static function projects(): Builder
    {
        return DB::table('projects')
            ->join('services', 'services.slug', '=', 'projects.service_slug')
            ->leftJoin('images as project_poster', 'project_poster.id', '=', 'projects.poster_image_id')
            ->where(function (Builder $query): void {
                $query->whereNull('projects.poster_image_id')->orWhere('project_poster.context', 'project');
            });
    }

    public static function articles(): Builder
    {
        return DB::table('articles')
            ->leftJoin('article_categories', 'article_categories.slug', '=', 'articles.category_slug')
            ->leftJoin('images as article_cover', 'article_cover.id', '=', 'articles.cover_image_id')
            ->where(function (Builder $query): void {
                $query->whereNull('articles.cover_image_id')->orWhereIn('article_cover.context', ['article', 'project']);
            });
    }
}
