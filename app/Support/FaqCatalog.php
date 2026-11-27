<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FaqCatalog
{
    public static function records(): Collection
    {
        return DB::table('faqs')->orderBy('sort_order')->orderBy('id')->get(['id', 'question', 'answer']);
    }
}
