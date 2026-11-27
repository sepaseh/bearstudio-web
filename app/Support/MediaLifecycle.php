<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class MediaLifecycle
{
    public const IMAGE_CONTEXTS = ['article', 'project', 'service', 'approach', 'content', 'logo', 'video'];

    private const CLEANUP_BATCH = 20;

    public function __construct(private readonly AdministratorSession $session) {}

    public static function directory(string $kind): string
    {
        return storage_path('app/private/media/'.$kind);
    }

    public static function original(string $kind, string $id, ?string $context = null): string
    {
        return $kind === 'images'
            ? self::imageDirectory($context).'/'.$id.'.webp'
            : self::directory($kind).'/'.$id.'.mp4';
    }

    public static function thumbnail(string $kind, string $id, ?string $context = null): string
    {
        return $kind === 'images'
            ? self::imageDirectory($context).'/thumbnail/'.$id.'.webp'
            : self::directory($kind).'/'.$id.'.webp';
    }

    private static function imageDirectory(?string $context): string
    {
        if (! in_array($context, self::IMAGE_CONTEXTS, true)) {
            throw new \InvalidArgumentException('Unknown image context.');
        }

        return self::directory('images').'/'.$context;
    }

    public static function legacyImage(string $kind, string $id, bool $thumbnail = false): string
    {
        return self::directory($kind).'/'.$id.($thumbnail ? '.thumb.webp' : '.webp');
    }

    public static function storedThumbnail(string $kind, string $id, ?string $context = null): string
    {
        $path = self::thumbnail($kind, $id, $context);

        return is_file($path) ? $path : self::legacyImage($kind, $id, thumbnail: true);
    }

    public function withUploadSlot(string $kind, Closure $operation): mixed
    {
        $directory = self::directory($kind);
        if (! is_dir($directory) && ! @mkdir($directory, 0750, true) && ! is_dir($directory)) {
            self::unavailable();
        }

        $slots = $kind === 'images' ? 2 : 1;
        for ($index = 0; $index < $slots; $index++) {
            $handle = @fopen($directory.'/upload-'.$index.'.lock', 'c');
            if ($handle === false) {
                self::unavailable();
            }

            try {
                if (flock($handle, LOCK_EX | LOCK_NB)) {
                    return $operation();
                }
            } finally {
                fclose($handle);
            }
        }

        throw new ApiError(429, 'RATE_LIMITED', 'Another upload is in progress. Retry shortly.', headers: ['Retry-After' => '1']);
    }

    public function publish(
        string $kind, string $id, Request $request, Closure $write, Closure $validate, ?string $context = null,
    ): array {
        $original = self::original($kind, $id, $context);
        $thumbnail = self::thumbnail($kind, $id, $context);
        foreach (array_unique([dirname($original), dirname($thumbnail)]) as $directory) {
            if (! is_dir($directory) && ! @mkdir($directory, 0750, true) && ! is_dir($directory)) {
                self::unavailable();
            }
        }

        WriteTransaction::run(function () use ($request, $kind, $id): void {
            $this->session->require($request, 'media.write', true);
            $this->intents($kind)->insert(['kind' => $kind, 'id' => $id, 'reason' => 'upload', 'created_at' => time()]);
        });

        try {
            $metadata = $write($original.'.part', $thumbnail.'.part');
            $metadata = WriteTransaction::run(function () use ($request, $kind, $id, $original, $thumbnail, $metadata, $validate): array {
                $actor = $this->session->require($request, 'media.write', true);
                $intent = $this->intents($kind)->where('id', $id)->first();
                if ($intent === null || $intent->reason !== 'upload' || $intent->created_at <= $this->cutoff($kind)) {
                    ApiError::abort(409, 'UPLOAD_EXPIRED', 'The upload expired. Upload the file again.');
                }

                $validate();
                if (! @rename($original.'.part', $original)
                    || (is_file($thumbnail.'.part') && ! @rename($thumbnail.'.part', $thumbnail))) {
                    self::unavailable();
                }

                $record = ['id' => $id, 'created_at' => now()] + $metadata;
                DB::table($kind)->insert($record);
                $this->intents($kind)->where('id', $id)->delete();
                Access::audit($actor['id'], $kind.'.upload', $kind.'/'.$id, ['sizeBytes' => $record['size_bytes']]);

                return $record;
            });
        } catch (Throwable $exception) {
            $this->tryCleanup($kind, $request, $id, rejectedUpload: true);
            throw $exception;
        } finally {
            @unlink($original.'.part');
            @unlink($thumbnail.'.part');
        }

        $this->tryCleanup($kind, $request);

        return $metadata;
    }

    public function delete(string $kind, string $id, Request $request): void
    {
        WriteTransaction::run(function () use ($kind, $id, $request): void {
            $actor = $this->session->require($request, 'media.write', true);
            if (! DB::table($kind)->where('id', $id)->exists()) {
                if ($this->intents($kind)->where('id', $id)->exists()) {
                    return;
                }
                ApiError::abort(404, 'NOT_FOUND', 'Media not found.');
            }

            if ($this->referenced($kind, $id)) {
                ApiError::abort(409, $kind === 'images' ? 'IMAGE_IN_USE' : 'VIDEO_IN_USE', 'The media is referenced by content or settings.');
            }

            DB::table($kind)->where('id', $id)->delete();
            DB::table('media_cleanup_intents')->updateOrInsert(['kind' => $kind, 'id' => $id], [
                'reason' => 'delete', 'created_at' => time(), 'last_attempt_at' => null,
            ]);
            Access::audit($actor['id'], $kind.'.delete', $kind.'/'.$id);
        });

        $this->tryCleanup($kind, $request, $id);
    }

    public function cleanup(string $kind, Request $request, ?string $preferredId = null, bool $rejectedUpload = false): array
    {
        $result = WriteTransaction::run(function () use ($kind, $request, $preferredId, $rejectedUpload): array {
            $actor = $this->session->require($request, 'media.write', true);
            $selection = $this->intents($kind)->where(function (Builder $query) use ($kind, $rejectedUpload, $preferredId): void {
                $query->where('reason', 'delete')->orWhere('created_at', '<=', $this->cutoff($kind));
                if ($rejectedUpload && $preferredId !== null) {
                    $query->orWhere('id', $preferredId);
                }
            });
            if ($preferredId !== null) {
                $selection->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$preferredId]);
            }

            $intents = $selection->orderByRaw('COALESCE(last_attempt_at, created_at)')
                ->orderBy('created_at')->orderBy('id')->limit(self::CLEANUP_BATCH)->get();
            $deleted = 0;
            $pending = 0;
            foreach ($intents as $intent) {
                $row = $this->intents($kind)->where('id', $intent->id);
                if (DB::table($kind)->where('id', $intent->id)->exists()) {
                    $row->delete();

                    continue;
                }

                if ($this->removeFiles($kind, $intent->id)) {
                    $row->delete();
                    $deleted++;
                } else {
                    $row->update(['last_attempt_at' => time()]);
                    $pending++;
                }
            }

            $result = ['processed' => $intents->count(), 'deleted' => $deleted, 'pending' => $pending];
            if ($result['processed'] > 0) {
                Access::audit($actor['id'], $kind.'.cleanup', $kind, $result);
            }

            return $result;
        });

        if ($result['pending'] > 0) {
            rescue(fn () => Log::warning('media.cleanup.pending', ['kind' => $kind, ...$result]), report: false);
        }

        return $result;
    }

    private function referenced(string $kind, string $id): bool
    {
        $references = $kind === 'images' ? [
            'articles' => ['cover_image_id'],
            'projects' => ['poster_image_id'],
            'service_images' => ['image_id'],
            'home_pages' => ['hero_poster_image_id', 'about_poster_image_id', 'why_image_id'],
            'home_page_approaches' => ['image_id'],
            'app_settings' => ['light_logo_id', 'dark_logo_id'],
            'videos' => ['poster_image_id'],
        ] : [
            'projects' => ['full_video_id', 'preview_video_id'],
            'home_pages' => ['hero_video_id', 'about_video_id'],
        ];
        foreach ($references as $table => $fields) {
            if (Schema::hasTable($table) && DB::table($table)->whereAny($fields, '=', $id)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function removeFiles(string $kind, string $id): bool
    {
        if (! Str::isUuid($id)) {
            return false;
        }

        $paths = [self::legacyImage($kind, $id, thumbnail: true)];
        if ($kind === 'images') {
            $paths[] = self::legacyImage($kind, $id);
            foreach (self::IMAGE_CONTEXTS as $context) {
                $paths[] = self::original($kind, $id, $context);
                $paths[] = self::thumbnail($kind, $id, $context);
            }
        } else {
            $paths[] = self::original($kind, $id);
            $paths[] = self::thumbnail($kind, $id);
            $paths[] = self::directory($kind).'/'.$id.'.webm';
        }

        $complete = true;
        foreach ([...$paths, ...array_map(static fn (string $path): string => $path.'.part', $paths)] as $path) {
            clearstatcache(true, $path);
            if ((file_exists($path) || is_link($path)) && ! @unlink($path)) {
                $complete = false;
            }
        }

        return $complete;
    }

    private function tryCleanup(string $kind, Request $request, ?string $id = null, bool $rejectedUpload = false): void
    {
        try {
            $this->cleanup($kind, $request, $id, $rejectedUpload);
        } catch (Throwable $exception) {
            // A durable intent is retried independently of the acknowledged operation.
            rescue(fn () => Log::warning('media.cleanup.deferred', [
                'kind' => $kind,
                'exception_type' => $exception::class,
            ]), report: false);
        }
    }

    private function intents(string $kind): Builder
    {
        return DB::table('media_cleanup_intents')->where('kind', $kind);
    }

    private function cutoff(string $kind): int
    {
        return time() - ($kind === 'images' ? 300 : 900);
    }

    private static function unavailable(): never
    {
        ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
    }
}
