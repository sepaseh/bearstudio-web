<?php

namespace App\Queries\Projects;

use App\Support\ApiError;
use App\Support\PublicEditorial;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

final class ProjectCatalog
{
    public function ordered(bool $public = false): Collection
    {
        return $this->query($public)->orderBy('projects.sort_order')->orderBy('projects.slug')->get();
    }

    public function find(string $slug): stdClass
    {
        $item = $this->query()->where('projects.slug', $slug)->first();
        if ($item === null) {
            ApiError::abort(404, 'NOT_FOUND', 'Project not found.');
        }

        return $item;
    }

    private function query(bool $public = false): Builder
    {
        $query = $public ? PublicEditorial::projects() : DB::table('projects')
            ->join('services', 'projects.service_slug', '=', 'services.slug')
            ->leftJoin('images as project_poster', 'projects.poster_image_id', '=', 'project_poster.id');

        return $query->select('projects.*', 'services.title as service_title',
            'project_poster.width as poster_width', 'project_poster.height as poster_height');
    }
}
