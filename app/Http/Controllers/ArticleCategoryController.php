<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Access;
use App\Support\AccessInput;
use App\Support\AdministratorSession;
use App\Support\ApiError;
use App\Support\ArticleInput;
use App\Support\WriteTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use stdClass;

final class ArticleCategoryController
{
    private const LIMIT = 1000;

    public function __construct(private readonly AdministratorSession $session) {}

    public function index(Request $request): JsonResponse
    {
        $this->session->require($request, 'articles.read');
        AccessInput::noQuery($request);
        $items = DB::table('article_categories')->orderBy('sort_order')->orderBy('slug')->get(['name', 'slug']);

        return $this->json(['items' => $items]);
    }

    public function publicIndex(Request $request): JsonResponse
    {
        AccessInput::noQuery($request);
        $items = DB::table('article_categories')->orderBy('sort_order')->orderBy('slug')->get(['name', 'slug']);

        return $this->json($items->all());
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $this->session->require($request, 'articles.read');
        AccessInput::noQuery($request);

        return $this->json($this->find($slug));
    }

    public function store(Request $request): JsonResponse
    {
        $item = WriteTransaction::run(function () use ($request): stdClass {
            $actor = $this->session->require($request, 'articles.write', true);
            $input = AccessInput::read($request, ['name', 'slug']);
            $slug = $this->newSlug($input->slug ?? null);
            $name = AccessInput::name($input->name ?? null);
            if (DB::table('article_categories')->count() >= self::LIMIT) {
                ApiError::abort(409, 'CONTENT_LIMIT', 'The category catalog has reached its item limit.');
            }
            $this->requireAvailableSlug($slug);
            $position = (DB::table('article_categories')->max('sort_order') ?? -1) + 1;
            DB::table('article_categories')->insert(['slug' => $slug, 'name' => $name, 'sort_order' => $position]);
            Access::audit($actor['id'], 'categories.create', 'article-categories/'.$slug);

            return $this->find($slug);
        });

        return $this->json($item, 201)->header('Location', '/api/v1/admin/article-categories/'.$item->slug);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        $item = WriteTransaction::run(function () use ($request, $slug): stdClass {
            $actor = $this->session->require($request, 'articles.write', true);
            $input = AccessInput::read($request, ['name', 'slug']);
            if (! isset($input->name) && ! isset($input->slug)) {
                AccessInput::invalid('input', 'Supply at least one editable field.');
            }
            $current = $this->find($slug);
            $name = isset($input->name) ? AccessInput::name($input->name) : $current->name;
            $replacement = isset($input->slug) ? $this->newSlug($input->slug) : $slug;
            if ($replacement !== $slug) {
                $this->requireAvailableSlug($replacement);
            }
            DB::table('article_categories')->where('slug', $slug)->update(['slug' => $replacement, 'name' => $name]);
            Access::audit($actor['id'], 'categories.update', 'article-categories/'.$slug, ['slug' => $replacement]);

            return $this->find($replacement);
        });

        return $this->json($item);
    }

    public function destroy(Request $request, string $slug): Response
    {
        WriteTransaction::run(function () use ($request, $slug): void {
            $actor = $this->session->require($request, 'articles.write', true);
            AccessInput::noQuery($request);
            AccessInput::noBody($request);
            $this->find($slug);
            if (DB::table('articles')->where('category_slug', $slug)->exists()) {
                ApiError::abort(409, 'CATEGORY_IN_USE', 'An article still references this category.');
            }
            DB::table('article_categories')->where('slug', $slug)->delete();
            Access::audit($actor['id'], 'categories.delete', 'article-categories/'.$slug);
        });

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    public function sort(Request $request): Response
    {
        WriteTransaction::run(function () use ($request): void {
            $actor = $this->session->require($request, 'articles.write', true);
            $input = AccessInput::read($request, ['slugs'], 256 * 1024);
            $slugs = $input->slugs ?? null;
            if (! is_array($slugs) || count($slugs) > self::LIMIT) {
                AccessInput::invalid('slugs', 'Supply every current category slug exactly once.');
            }
            foreach ($slugs as $slug) {
                if (! is_string($slug)) {
                    AccessInput::invalid('slugs', 'Supply every current category slug exactly once.');
                }
            }
            $current = DB::table('article_categories')->orderBy('slug')->pluck('slug')->all();
            $submitted = $slugs;
            sort($submitted, SORT_STRING);
            if ($submitted !== $current) {
                AccessInput::invalid('slugs', 'Supply every current category slug exactly once.');
            }
            foreach ($slugs as $position => $slug) {
                DB::table('article_categories')->where('slug', $slug)->where('sort_order', '!=', $position)
                    ->update(['sort_order' => $position]);
            }
            Access::audit($actor['id'], 'categories.sort', 'article-categories', ['itemCount' => count($slugs)]);
        });

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    private function find(string $slug): stdClass
    {
        return DB::table('article_categories')->where('slug', $slug)->first(['name', 'slug'])
            ?? ApiError::abort(404, 'NOT_FOUND', 'Article category not found.');
    }

    private function newSlug(mixed $value): string
    {
        $slug = ArticleInput::slug($value);
        if ($slug === 'sort') {
            AccessInput::invalid('slug', 'This slug is reserved for catalog sorting.');
        }

        return $slug;
    }

    private function requireAvailableSlug(string $slug): void
    {
        if (DB::table('article_categories')->where('slug', $slug)->exists()) {
            ApiError::abort(409, 'SLUG_IN_USE', 'The slug belongs to another article category.');
        }
    }

    private function json(array|stdClass $body, int $status = 200): JsonResponse
    {
        return response()->json($body, $status, ['Cache-Control' => 'no-store']);
    }
}
