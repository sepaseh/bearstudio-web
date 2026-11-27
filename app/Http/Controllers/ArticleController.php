<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Access;
use App\Support\AccessInput;
use App\Support\AdministratorSession;
use App\Support\ApiError;
use App\Support\ArticleInput;
use App\Support\PublicEditorial;
use App\Support\WriteTransaction;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

final class ArticleController
{
    private const PAGE_SIZE = 24;

    public function __construct(private readonly AdministratorSession $session) {}

    public function index(Request $request): JsonResponse
    {
        $this->session->require($request, 'articles.read');
        $params = $request->query();
        $page = filter_var($params['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => [
            'min_range' => 1, 'max_range' => intdiv(2147483647, self::PAGE_SIZE) + 1,
        ]]);
        if (array_diff(array_keys($params), ['page']) !== [] || $page === false) {
            AccessInput::invalid('page', 'Use only a valid page number.');
        }

        $total = DB::table('articles')->count();
        $items = $this->catalog()->orderByDesc('articles.updated_at')->orderBy('articles.id')
            ->forPage($page, self::PAGE_SIZE)->get()->map(fn (stdClass $item) => $this->record($item));

        return $this->json(['items' => $items, 'total' => $total]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->session->require($request, 'articles.read');
        AccessInput::noQuery($request);

        return $this->json($this->record($this->find(strtolower($id)), details: true));
    }

    public function store(Request $request): JsonResponse
    {
        $item = WriteTransaction::run(function () use ($request): array {
            $actor = $this->session->require($request, 'articles.write', true);
            $changes = ArticleInput::changes($request, creating: true);
            $this->validateReferences($changes);
            $this->requireAvailableSlug($changes['slug']);
            $id = (string) Str::uuid();
            DB::table('articles')->insert($changes + ['id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            Access::audit($actor['id'], 'articles.create', 'articles/'.$id);

            return $this->record($this->find($id), details: true);
        });

        return $this->json($item, 201)->header('Location', '/api/v1/admin/articles/'.$item['id']);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $id = strtolower($id);
        $item = WriteTransaction::run(function () use ($request, $id): array {
            $actor = $this->session->require($request, 'articles.write', true);
            $current = $this->find($id);
            $changes = ArticleInput::changes($request, creating: false);
            $this->validateReferences($changes, $current);
            if (isset($changes['slug']) && $changes['slug'] !== $current->slug) {
                $this->requireAvailableSlug($changes['slug']);
            }
            DB::table('articles')->where('id', $id)->update($changes + ['updated_at' => now()]);
            Access::audit($actor['id'], 'articles.update', 'articles/'.$id);

            return $this->record($this->find($id), details: true);
        });

        return $this->json($item);
    }

    public function destroy(Request $request, string $id): Response
    {
        $id = strtolower($id);
        WriteTransaction::run(function () use ($request, $id): void {
            $actor = $this->session->require($request, 'articles.write', true);
            AccessInput::noQuery($request);
            AccessInput::noBody($request);
            if (DB::table('articles')->where('id', $id)->delete() !== 1) {
                ApiError::abort(404, 'NOT_FOUND', 'Article not found.');
            }
            Access::audit($actor['id'], 'articles.delete', 'articles/'.$id);
        });

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    public function publicIndex(Request $request): JsonResponse
    {
        $params = $request->query();
        $page = filter_var($params['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => [
            'min_range' => 1, 'max_range' => intdiv(2147483647, self::PAGE_SIZE) + 1,
        ]]);
        if (array_diff(array_keys($params), ['page', 'title', 'category']) !== [] || $page === false) {
            AccessInput::invalid('page', 'Use a valid page and supported article query fields.');
        }
        $query = $this->publicCatalog();
        if (array_key_exists('title', $params)) {
            $title = is_string($params['title']) ? trim($params['title']) : '';
            if ($title === '' || mb_strlen($title, 'UTF-8') > 200) {
                AccessInput::invalid('title', 'Supply a title of 1–200 characters.');
            }
            $query->whereRaw('instr(lower(articles.title), lower(?)) > 0', [$title]);
        }
        if (array_key_exists('category', $params)) {
            $slug = ArticleInput::slug($params['category']);
            $query->where('articles.category_slug', $slug);
        }
        $total = (clone $query)->count();
        $items = $query->orderByDesc('articles.created_at')->orderBy('articles.slug')
            ->forPage($page, self::PAGE_SIZE)->get()->map($this->publicRecord(...));

        return $this->json(['items' => $items, 'total' => $total]);
    }

    public function publicShow(Request $request, string $slug): JsonResponse
    {
        AccessInput::noQuery($request);
        $item = $this->publicCatalog()->addSelect('articles.body', 'articles.body_is_html')
            ->where('articles.slug', $slug)->first()
            ?? ApiError::abort(404, 'NOT_FOUND', 'Article not found.');

        return $this->json($this->publicRecord($item) + [
            'body' => $item->body_is_html ? $item->body : '<p>'.nl2br(e($item->body), false).'</p>',
        ]);
    }

    private function publicCatalog(): Builder
    {
        return PublicEditorial::articles()->whereNotNull('articles.cover_image_id')
            ->select('articles.id', 'articles.slug', 'articles.title', 'articles.excerpt', 'articles.cover_image_id',
                'articles.category_slug', 'article_categories.name as category_name',
                'article_cover.width as cover_width', 'article_cover.height as cover_height');
    }

    private function publicRecord(stdClass $article): array
    {
        $url = '/api/v1/public/images/'.$article->cover_image_id;

        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'category' => ['name' => $article->category_name, 'slug' => $article->category_slug],
            'coverImage' => [
                'id' => $article->cover_image_id,
                'url' => $url,
                'width' => $article->cover_width,
                'height' => $article->cover_height,
                'alt' => $article->title,
                'thumb' => $url.'/thumbnail',
            ],
        ];
    }

    private function validateReferences(array $changes, ?stdClass $current = null): void
    {
        if (isset($changes['category_slug']) && ! DB::table('article_categories')
            ->where('slug', $changes['category_slug'])->exists()) {
            AccessInput::invalid('category.slug', 'Choose an existing article category.');
        }
        if (isset($changes['cover_image_id']) && $changes['cover_image_id'] !== $current?->cover_image_id
            && ! DB::table('images')->where('id', $changes['cover_image_id'])->where('context', 'article')->exists()) {
            AccessInput::invalid('cover.id', 'Choose a ready article image.');
        }
    }

    private function requireAvailableSlug(string $slug): void
    {
        if (DB::table('articles')->where('slug', $slug)->exists()) {
            ApiError::abort(409, 'SLUG_IN_USE', 'The slug belongs to another article.');
        }
    }

    private function catalog(): Builder
    {
        return DB::table('articles')->leftJoin('article_categories', 'articles.category_slug', '=', 'article_categories.slug')
            ->select('articles.id', 'articles.slug', 'articles.title', 'articles.excerpt', 'articles.cover_image_id',
                'articles.category_slug', 'article_categories.name as category_name');
    }

    private function find(string $id): stdClass
    {
        return $this->catalog()->addSelect('articles.body', 'articles.body_is_html')->where('articles.id', $id)->first()
            ?? ApiError::abort(404, 'NOT_FOUND', 'Article not found.');
    }

    private function record(stdClass $item, bool $details = false): array
    {
        $url = $item->cover_image_id ? '/api/v1/public/images/'.$item->cover_image_id : null;
        $result = [
            'id' => $item->id,
            'title' => $item->title,
            'slug' => $item->slug,
            'excerpt' => $item->excerpt,
            'category' => ['slug' => $item->category_slug, 'name' => $item->category_name],
            'cover' => ['id' => $item->cover_image_id, 'url' => $url, 'thumb' => $url ? $url.'/thumbnail' : null],
        ];
        if ($details) {
            $result['body'] = $item->body_is_html ? $item->body : '<p>'.nl2br(e($item->body), false).'</p>';
        }

        return $result;
    }

    private function json(array $body, int $status = 200): JsonResponse
    {
        return response()->json($body, $status, ['Cache-Control' => 'no-store']);
    }
}
