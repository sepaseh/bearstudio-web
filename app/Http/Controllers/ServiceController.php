<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Access;
use App\Support\AccessInput;
use App\Support\AdministratorSession;
use App\Support\ApiError;
use App\Support\ArticleInput;
use App\Support\ServiceCatalog;
use App\Support\WriteTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class ServiceController
{
    public function __construct(private readonly AdministratorSession $session) {}

    public function index(Request $request): JsonResponse
    {
        $this->session->require($request, 'content.read');
        AccessInput::noQuery($request);

        return $this->json(['items' => ServiceCatalog::adminRecords()]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $this->session->require($request, 'content.read');
        AccessInput::noQuery($request);

        return $this->json($this->record($slug));
    }

    public function store(Request $request): JsonResponse
    {
        $record = WriteTransaction::run(function () use ($request): array {
            $actor = $this->session->require($request, 'content.write', true);
            [$fields, $imageIds] = $this->changes($request, creating: true);
            if (DB::table('services')->count() >= ServiceCatalog::LIMIT) {
                ApiError::abort(409, 'CONTENT_LIMIT', 'The service catalog has reached its group limit.');
            }

            $slug = $fields['slug'];
            $this->requireAvailableSlug($slug);
            $position = (DB::table('services')->max('sort_order') ?? -1) + 1;
            DB::table('services')->insert($fields + ['sort_order' => $position]);
            $this->replaceImages($slug, $imageIds);
            Access::audit($actor['id'], 'services.create', 'services/'.$slug, ['imageCount' => count($imageIds)]);

            return $this->record($slug);
        });

        return $this->json($record, 201)->header('Location', '/api/v1/admin/services/'.$record['slug']);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        $record = WriteTransaction::run(function () use ($request, $slug): array {
            $actor = $this->session->require($request, 'content.write', true);
            $this->requireService($slug);
            [$fields, $imageIds] = $this->changes($request, creating: false);

            $replacement = $fields['slug'] ?? $slug;
            if ($replacement !== $slug) {
                $this->requireAvailableSlug($replacement);
            }
            if ($fields !== []) {
                DB::table('services')->where('slug', $slug)->update($fields);
            }
            if ($imageIds !== null) {
                $this->replaceImages($replacement, $imageIds);
            }
            $details = $replacement !== $slug ? ['previousSlug' => $slug] : [];
            Access::audit($actor['id'], 'services.update', 'services/'.$replacement, $details);

            return $this->record($replacement);
        });

        return $this->json($record);
    }

    public function destroy(Request $request, string $slug): Response
    {
        WriteTransaction::run(function () use ($request, $slug): void {
            $actor = $this->session->require($request, 'content.write', true);
            AccessInput::noQuery($request);
            AccessInput::noBody($request);
            $this->requireService($slug);
            if (DB::table('projects')->where('service_slug', $slug)->exists()) {
                ApiError::abort(409, 'SERVICE_IN_USE', 'A project still references this service.');
            }

            DB::table('services')->where('slug', $slug)->delete();
            Access::audit($actor['id'], 'services.delete', 'services/'.$slug);
        });

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    public function sort(Request $request): Response
    {
        WriteTransaction::run(function () use ($request): void {
            $actor = $this->session->require($request, 'content.write', true);
            $input = AccessInput::read($request, ['slugs'], 256 * 1024);
            $slugs = $input->slugs ?? null;
            if (! is_array($slugs) || count($slugs) > ServiceCatalog::LIMIT) {
                AccessInput::invalid('slugs', 'Supply every current service slug exactly once.');
            }
            $current = DB::table('services')->orderBy('slug')->pluck('slug')->all();
            $submitted = $slugs;
            foreach ($submitted as $slug) {
                if (! is_string($slug) || ! ArticleInput::canonicalSlug($slug) || $slug === 'sort') {
                    AccessInput::invalid('slugs', 'Supply every current service slug exactly once.');
                }
            }
            sort($submitted, SORT_STRING);
            if ($submitted !== $current) {
                AccessInput::invalid('slugs', 'Supply every current service slug exactly once.');
            }

            $changed = 0;
            foreach ($slugs as $position => $slug) {
                $changed += DB::table('services')->where('slug', $slug)->where('sort_order', '!=', $position)
                    ->update(['sort_order' => $position]);
            }
            Access::audit($actor['id'], 'services.sort', 'services', [
                'groupCount' => count($slugs),
                'changedCount' => $changed,
            ]);
        });

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    public function publicIndex(Request $request): JsonResponse
    {
        AccessInput::noQuery($request);

        return $this->json(ServiceCatalog::publicRecords());
    }

    public function page(): Response
    {
        return response()->view('public.services', [
            'title' => 'Services',
            'canonicalPath' => '/services',
            'description' => 'Explore Bear Studio’s services in film and video production, animation and motion design, VFX, AI and immersive experiences, sound design and music.',
            'services' => ServiceCatalog::publicRecords(),
        ], headers: ['Cache-Control' => 'no-store']);
    }

    private function changes(Request $request, bool $creating): array
    {
        $allowed = ['slug', 'title', 'description', 'tags', 'imageIds'];
        $input = AccessInput::read($request, $allowed, 256 * 1024);
        $values = array_filter((array) $input, static fn ($value) => $value !== null);
        if ($creating && count($values) !== count($allowed)) {
            AccessInput::invalid('input', 'Supply a slug, title, description, tags and imageIds.');
        }
        if ($values === []) {
            AccessInput::invalid('input', 'Supply at least one editable field.');
        }

        $fields = [];
        if (isset($values['slug'])) {
            $fields['slug'] = ArticleInput::slug($values['slug']);
            if ($fields['slug'] === 'sort') {
                AccessInput::invalid('slug', 'Choose a slug other than sort.');
            }
        }
        if (isset($values['title'])) {
            $fields['title'] = $this->text($values['title'], 'title', 200);
        }
        if (isset($values['description'])) {
            $fields['description'] = $this->text($values['description'], 'description', 5000);
        }
        if (isset($values['tags'])) {
            if (! is_array($values['tags']) || count($values['tags']) > 20) {
                AccessInput::invalid('tags', 'Supply up to 20 ordered tag strings.');
            }
            $tags = array_map(fn ($tag) => $this->text($tag, 'tags', 80), $values['tags']);
            $fields['tags'] = json_encode($tags, JSON_THROW_ON_ERROR);
        }
        $imageIds = isset($values['imageIds']) ? AccessInput::ids($values['imageIds'], 'imageIds', 3) : null;

        return [$fields, $imageIds];
    }

    private function replaceImages(string $slug, array $ids): void
    {
        $previous = DB::table('service_images')->where('service_slug', $slug)->pluck('alt', 'image_id')->all();
        $newIds = array_diff($ids, array_keys($previous));
        if (DB::table('images')->whereIn('id', $newIds)->where('context', 'service')->count() !== count($newIds)) {
            AccessInput::invalid('imageIds', 'Choose existing ready service images.');
        }
        DB::table('service_images')->where('service_slug', $slug)->delete();
        foreach ($ids as $position => $id) {
            DB::table('service_images')->insert([
                'service_slug' => $slug,
                'image_id' => $id,
                'sort_order' => $position,
                'alt' => $previous[$id] ?? '',
            ]);
        }
    }

    private function requireService(string $slug): void
    {
        if (! ArticleInput::canonicalSlug($slug) || ! DB::table('services')->where('slug', $slug)->exists()) {
            ApiError::abort(404, 'NOT_FOUND', 'Service not found.');
        }
    }

    private function requireAvailableSlug(string $slug): void
    {
        if (DB::table('services')->where('slug', $slug)->exists()) {
            ApiError::abort(409, 'SLUG_IN_USE', 'The slug belongs to another service.');
        }
    }

    private function record(string $slug): array
    {
        return ServiceCatalog::adminRecords($slug)[0] ?? ApiError::abort(404, 'NOT_FOUND', 'Service not found.');
    }

    private function text(mixed $value, string $field, int $max): string
    {
        $text = is_string($value) && ! str_contains($value, "\0") ? preg_replace('/\A\s+|\s+\z/u', '', $value) : null;
        if ($text === null || $text === '' || mb_strlen($text, 'UTF-8') > $max) {
            AccessInput::invalid($field, 'Supply a nonempty value of at most '.$max.' characters.');
        }

        return $text;
    }

    private function json(array $body, int $status = 200): JsonResponse
    {
        return response()->json($body, $status, ['Cache-Control' => 'no-store']);
    }
}
