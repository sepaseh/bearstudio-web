<?php

namespace App\Actions\Projects;

use App\Queries\Projects\ProjectCatalog;
use App\Support\AdministratorSession;
use App\Support\ApiError;
use App\Support\ProjectInput;
use App\Support\WriteTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

final class PublishProject
{
    public function __construct(
        private readonly AdministratorSession $session,
        private readonly ProjectCatalog $catalog,
    ) {}

    public function create(Request $request, array $fields): stdClass
    {
        return WriteTransaction::run(function () use ($request, $fields): stdClass {
            $this->session->require($request, 'content.write', true);
            $changes = ProjectInput::changes($fields);
            $this->checkPublishable($changes);
            $this->requireAvailableSlug($changes['slug']);

            if (DB::table('projects')->count() >= ProjectInput::CATALOG_LIMIT) {
                ApiError::abort(409, 'CONTENT_LIMIT', 'The project catalog is full.');
            }

            $position = (DB::table('projects')->max('sort_order') ?? -1) + 1;
            DB::table('projects')->insert($changes + [
                'sort_order' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->catalog->find($changes['slug']);
        });
    }

    public function update(Request $request, string $slug, array $fields): stdClass
    {
        return WriteTransaction::run(function () use ($request, $fields, $slug): stdClass {
            $this->session->require($request, 'content.write', true);
            $current = $this->catalog->find($slug);
            $changes = ProjectInput::changes($fields);
            $this->checkPublishable($changes + (array) $current);
            $newSlug = $changes['slug'] ?? $slug;
            if ($newSlug !== $slug) {
                $this->requireAvailableSlug($newSlug);
            }

            DB::table('projects')->where('slug', $slug)->update($changes + ['updated_at' => now()]);

            return $this->catalog->find($newSlug);
        });
    }

    private function requireAvailableSlug(string $slug): void
    {
        if (DB::table('projects')->where('slug', $slug)->exists()) {
            ApiError::abort(409, 'SLUG_IN_USE', 'This project slug is already in use.');
        }
    }

    private function checkPublishable(array $item): void
    {
        if (($item['full_video_id'] ?? $item['full_video_url'] ?? $item['watch_url'] ?? null) === null) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'Provide a video or a Watch link.');
        }
    }
}
