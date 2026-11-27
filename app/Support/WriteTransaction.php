<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

final class WriteTransaction
{
    public static function run(Closure $callback): mixed
    {
        $waiting = $acquired = null;
        try {
            return DB::transaction(function () use ($callback, &$waiting, &$acquired): mixed {
                // Preserve cross-catalog authorization and ordering until commit or rollback.
                $waiting = hrtime(true);
                DB::table('write_mutex')->where('id', 1)->lockForUpdate()->firstOrFail();
                $acquired = hrtime(true);

                return $callback();
            });
        } finally {
            if ($waiting !== null && Context::has('request_id')) {
                $finished = hrtime(true);
                $timing = Context::getHidden('write_mutex_timing', [
                    'write_mutex_attempts' => 0,
                    'write_mutex_wait_ms' => 0.0,
                    'write_mutex_hold_ms' => 0.0,
                ]);
                $timing['write_mutex_attempts']++;
                $timing['write_mutex_wait_ms'] += (($acquired ?? $finished) - $waiting) / 1e6;
                $timing['write_mutex_hold_ms'] += $acquired === null ? 0 : ($finished - $acquired) / 1e6;
                Context::addHidden('write_mutex_timing', $timing);
            }
        }
    }
}
