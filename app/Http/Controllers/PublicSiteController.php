<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ArticleBody;
use App\Support\FaqCatalog;
use App\Support\HomePage;
use App\Support\PublicEditorial;
use App\Support\ServiceCatalog;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class PublicSiteController
{
    public function home(): View
    {
        $catalog = $this->catalog();
        $projects = $catalog->where('featured', true)->take(12)->values();
        $home = HomePage::record(public: true);

        return view('public.home', [
            'canonicalPath' => '/',
            'description' => 'Bear Studio creates advertising films, animation, visual effects, sound design and original music. '
                .($projects->isEmpty() ? 'Get in touch.' : 'Explore selected projects and get in touch.'),
            'projects' => $projects,
            'totalProjects' => $catalog->count(),
            'homeContent' => $home,
            'services' => ServiceCatalog::publicRecords(),
            'faqs' => FaqCatalog::records(),
            'socialImage' => $home['heroPoster']['url'] ?? null,
            'socialImageAlt' => $home['heroPoster']['alt'] ?? null,
        ]);
    }

    public function projects(Request $request): View
    {
        $catalog = $this->catalog();
        $services = $catalog->pluck('service', 'serviceSlug');
        $category = $request->query->all()['category'] ?? null;
        $selected = is_string($category) && $services->has($category) ? $category : null;

        return view('public.projects', [
            'title' => 'Projects',
            'canonicalPath' => '/projects',
            'description' => $catalog->isNotEmpty()
                ? 'Explore '.$catalog->count().' Bear Studio projects across animation, editing, sound design, showreels and AI-assisted production.'
                : 'Explore Bear Studio projects in animation, editing, sound design, showreels and AI-assisted production.',
            'services' => $services,
            'selected' => $selected,
            'projects' => $selected === null ? $catalog : $catalog->where('serviceSlug', $selected)->values(),
        ]);
    }

    public function project(string $slug): View
    {
        $catalog = $this->catalog();
        $index = $catalog->search(fn (array $project): bool => $project['slug'] === $slug);
        abort_if($index === false, 404);

        $project = $catalog[$index];
        $next = [];
        for ($offset = 1; $offset <= min(2, $catalog->count() - 1); $offset++) {
            $next[] = $catalog[($index + $offset) % $catalog->count()];
        }

        return view('public.project', [
            'title' => $project['title'],
            'canonicalPath' => $project['path'],
            'description' => $project['summary'] ?: $project['title'].' — '.$project['service'].'.',
            'socialImage' => $project['poster'],
            'socialImageAlt' => $project['title'],
            'project' => $project,
            'next' => $next,
        ]);
    }

    public function articles(): View
    {
        return view('public.articles', [
            'title' => 'Articles',
            'canonicalPath' => '/articles',
            'description' => 'Notes on film, motion design and the creative process, from the first brief to the final frame.',
            'articles' => $this->articleArchive(),
        ]);
    }

    public function article(string $slug): View
    {
        $article = PublicEditorial::articles()->where('articles.slug', $slug)->first([
            'articles.*', 'article_categories.name as category_name', 'article_cover.width as cover_width',
            'article_cover.height as cover_height',
        ]);
        abort_if($article === null, 404);

        $archive = $this->articleArchive();
        $index = $archive->search(fn (array $entry): bool => $entry['slug'] === $article->slug);
        $next = $index !== false && $archive->count() > 1 ? $archive[($index + 1) % $archive->count()] : null;
        $settings = SiteSettings::publicRecord();

        return view('public.article', [
            'title' => $article->title,
            'canonicalPath' => '/articles/'.rawurlencode($article->slug),
            'description' => $article->excerpt,
            'socialImage' => $this->mediaUrl('images', $article->cover_image_id, null),
            'socialImageAlt' => $article->title,
            'article' => $article,
            'articleBody' => $article->body_is_html ? ArticleBody::parse($article->body) : ArticleBody::fromPlainText($article->body),
            'articleSite' => $settings['site'],
            'settings' => $settings,
            'nextArticle' => $next,
        ]);
    }

    private function articleArchive(): Collection
    {
        return PublicEditorial::articles()
            ->select('articles.slug', 'articles.title', 'articles.excerpt', 'articles.cover_image_id',
                'article_categories.name as category_name', 'article_cover.width as cover_width', 'article_cover.height as cover_height')
            ->orderByDesc('articles.created_at')
            ->orderBy('articles.id')
            ->orderBy('articles.slug')
            ->get()
            ->map(fn (object $article): array => [
                'slug' => $article->slug,
                'path' => '/articles/'.rawurlencode($article->slug),
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'category' => $article->category_name,
                'cover' => $this->mediaUrl('images', $article->cover_image_id, null),
                'coverWidth' => $article->cover_width,
                'coverHeight' => $article->cover_height,
            ]);
    }

    private function catalog(): Collection
    {
        return PublicEditorial::projects()
            ->select('projects.*', 'services.title as service_title', 'project_poster.width as poster_width', 'project_poster.height as poster_height')
            ->orderBy('projects.sort_order')
            ->orderBy('projects.slug')
            ->get()
            ->map(fn (object $row): array => [
                'slug' => $row->slug,
                'path' => '/projects/'.rawurlencode($row->slug),
                'title' => $row->title,
                'summary' => $row->summary ?? '',
                'service' => $row->service_title,
                'serviceSlug' => $row->service_slug,
                'featured' => (bool) $row->is_featured,
                'poster' => $this->mediaUrl('images', $row->poster_image_id, $row->poster_url),
                'posterWidth' => $row->poster_width,
                'posterHeight' => $row->poster_height,
                'video' => $this->mediaUrl('videos', $row->full_video_id, $row->full_video_url),
                'preview' => $this->mediaUrl('videos', $row->preview_video_id, $row->preview_video_url),
                'watch' => $row->watch_url,
                'watchLabel' => $row->watch_label,
            ]);
    }

    private function mediaUrl(string $resource, ?string $id, ?string $url): ?string
    {
        return $id === null ? $url : '/api/v1/public/'.$resource.'/'.rawurlencode($id);
    }
}
