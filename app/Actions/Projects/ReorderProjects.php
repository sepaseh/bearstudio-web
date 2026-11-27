<?php

namespace App\Actions\Projects;

use App\Support\AdministratorSession;
use App\Support\ApiError;
use App\Support\WriteTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReorderProjects
{
    public function __construct(private readonly AdministratorSession $session) {}

    /** @param list<string> $slugs */
    public function run(Request $request, array $slugs): void
    {
        WriteTransaction::run(function () use ($request, $slugs): void {
            $this->session->require($request, 'content.write', true);
            $current = DB::table('projects')->orderBy('slug')->pluck('slug')->all();
            $submitted = $slugs;
            sort($submitted, SORT_STRING);
            if ($submitted !== $current) {
                ApiError::abort(400, 'VALIDATION_FAILED', 'Supply every current project slug exactly once.');
            }

            foreach ($slugs as $position => $slug) {
                DB::table('projects')->where('slug', $slug)->where('sort_order', '!=', $position)
                    ->update(['sort_order' => $position]);
            }
        });
    }
}
