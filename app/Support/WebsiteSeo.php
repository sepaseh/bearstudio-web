<?php

declare(strict_types=1);

namespace App\Support;

final class WebsiteSeo
{
    public static function allowsIndexing(): bool
    {
        $configured = config('pilot.allow_indexing');

        return $configured === null ? app()->isProduction() : filter_var($configured, FILTER_VALIDATE_BOOL);
    }

    public static function url(string $path): string
    {
        return rtrim(config('app.url'), '/').$path;
    }

    public static function image(?string $source): ?string
    {
        if ($source === null || $source === '') {
            return null;
        }

        return str_starts_with($source, '/') && ! str_starts_with($source, '//') ? self::url($source) : $source;
    }

    public static function structured(array $site, string $path, ?object $article, ?string $image, iterable $projects): array
    {
        $organizationId = self::url('/#organization');
        $organization = [
            '@type' => 'Organization',
            '@id' => $organizationId,
            'name' => $site['brandName'],
            'url' => self::url('/'),
        ];
        if ($site['contactEmail'] !== null) {
            $organization['email'] = $site['contactEmail'];
        }
        if ($site['contact']['phone'] !== null) {
            $organization['telephone'] = $site['contact']['phone'];
        }
        if ($site['socialLinks'] !== []) {
            $organization['sameAs'] = array_column($site['socialLinks'], 'url');
        }

        $graph = [$organization];
        if ($article !== null) {
            $page = self::url($path);
            $entry = [
                '@type' => 'Article',
                '@id' => $page.'#article',
                'mainEntityOfPage' => $page,
                'headline' => $article->title,
                'description' => $article->excerpt,
                'publisher' => ['@id' => $organizationId],
            ];
            if (($article->category_name ?? null) !== null) {
                $entry['articleSection'] = $article->category_name;
            }
            if ($image !== null) {
                $entry['image'] = $image;
            }
            $graph[] = $entry;
        }

        $items = [];
        foreach ($projects as $project) {
            $link = $path === '/' ? ($project['watch'] ?? self::image($project['video'] ?? null)) : self::url($project['path']);
            if ($link !== null) {
                $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $project['title'], 'url' => $link];
            }
        }
        if ($items !== []) {
            $graph[] = ['@type' => 'ItemList', 'itemListElement' => $items];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }
}
