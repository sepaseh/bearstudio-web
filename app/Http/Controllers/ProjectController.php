<?php

namespace App\Http\Controllers;

use App\Actions\Projects\PublishProject;
use App\Actions\Projects\ReorderProjects;
use App\Queries\Projects\ProjectCatalog;
use App\Support\AdministratorSession;
use App\Support\ApiError;
use App\Support\ProjectInput;
use App\Support\WriteTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use stdClass;

final class ProjectController
{
    public function __construct(
        private readonly AdministratorSession $session,
        private readonly ProjectCatalog $catalog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->session->require($request, 'content.read');
        $this->noQuery($request);

        return $this->json($this->catalog->ordered()->map($this->adminRecord(...)));
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $this->session->require($request, 'content.read');
        $this->noQuery($request);

        return $this->json($this->adminRecord($this->catalog->find($slug)));
    }

    public function store(Request $request, PublishProject $publish): JsonResponse
    {
        $this->session->require($request, 'content.write', true);
        $this->noQuery($request);
        $item = $this->adminRecord($publish->create($request, ProjectInput::read($request, true)));

        return $this->json($item, 201)->header('Location', '/api/v1/admin/projects/'.$item['slug']);
    }

    public function update(Request $request, string $slug, PublishProject $publish): JsonResponse
    {
        $this->session->require($request, 'content.write', true);
        $this->noQuery($request);
        $record = $publish->update($request, $slug, ProjectInput::read($request, false));

        return $this->json($this->adminRecord($record));
    }

    public function destroy(Request $request, string $slug): Response
    {
        $this->noQuery($request);
        if ($request->getContent() !== '') {
            ApiError::abort(400, 'VALIDATION_FAILED', 'Delete requests do not accept a body.');
        }

        WriteTransaction::run(function () use ($request, $slug) {
            $this->session->require($request, 'content.write', true);
            if (DB::table('projects')->where('slug', $slug)->delete() !== 1) {
                ApiError::abort(404, 'NOT_FOUND', 'Project not found.');
            }
        });

        return response()->noContent()->header('Cache-Control', 'no-store');
    }

    public function sort(Request $request, ReorderProjects $reorder): Response
    {
        $this->session->require($request, 'content.write', true);
        $this->noQuery($request);
        $reorder->run($request, ProjectInput::sortSlugs($request));

        return response()->noContent()->header('Cache-Control', 'no-store');
    }

    public function publicIndex(Request $request): JsonResponse
    {
        $this->noQuery($request);

        return $this->json($this->catalog->ordered(public: true)->map($this->publicRecord(...)));
    }

    public function publicShow(Request $request, string $slug): JsonResponse
    {
        $this->noQuery($request);
        $catalog = $this->catalog->ordered(public: true);
        $index = $catalog->search(fn (stdClass $project) => $project->slug === $slug);
        if ($index === false) {
            ApiError::abort(404, 'NOT_FOUND', 'Project not found.');
        }

        $total = $catalog->count();
        $next = [];
        for ($offset = 1; $offset < min($total, 3); $offset++) {
            $next[] = $this->publicRecord($catalog[($index + $offset) % $total]);
        }

        return $this->json([
            'project' => $this->publicRecord($catalog[$index]),
            'nextProjects' => $next,
            'index' => $index,
            'total' => $total,
        ]);
    }

    private function adminRecord(stdClass $item): array
    {
        $posterId = $item->poster_image_id;
        $posterUrl = $posterId ? '/api/v1/public/images/'.$posterId : $item->poster_url;

        return [
            'title' => $item->title,
            'slug' => $item->slug,
            'service' => ['name' => $item->service_title, 'slug' => $item->service_slug],
            'description' => $item->summary,
            'poster' => ['id' => $posterId, 'url' => $posterUrl, 'thumb' => $posterId ? $posterUrl.'/thumbnail' : null],
            'video' => $this->video($item->full_video_id, $item->full_video_url),
            'preview' => $this->video($item->preview_video_id, $item->preview_video_url),
            'watch' => $item->watch_url ? ['url' => $item->watch_url, 'label' => $item->watch_label] : null,
            'isFeatured' => (bool) $item->is_featured,
        ];
    }

    private function publicRecord(stdClass $item): array
    {
        $record = $this->adminRecord($item);
        $record['poster'] = [
            'id' => $record['poster']['id'],
            'url' => $record['poster']['url'],
            'width' => $item->poster_width,
            'height' => $item->poster_height,
            'alt' => $item->title,
        ];
        $record['videoUrl'] = $record['video']['url'] ?? null;
        $record['previewUrl'] = $record['preview']['url'] ?? null;
        unset($record['video'], $record['preview']);

        return $record;
    }

    private function video(?string $id, ?string $url): ?array
    {
        if ($id) {
            $url = '/api/v1/public/videos/'.$id;
        }

        return $url ? ['id' => $id, 'url' => $url] : null;
    }

    private function noQuery(Request $request): void
    {
        if ($request->query() !== []) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'This endpoint does not accept query parameters.');
        }
    }

    private function json(mixed $body, int $status = 200): JsonResponse
    {
        return response()->json($body, $status, ['Cache-Control' => 'no-store']);
    }
}
