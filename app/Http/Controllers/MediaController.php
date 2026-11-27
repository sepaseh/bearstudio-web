<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Access;
use App\Support\AccessInput;
use App\Support\AdministratorSession;
use App\Support\ApiError;
use App\Support\MediaLifecycle;
use App\Support\SiteSettings;
use App\Support\UploadedMedia;
use App\Support\WriteTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

final class MediaController
{
    private const PAGE_SIZE = 24;

    private const EDITORIAL_CONTEXTS = ['article', 'project', 'service'];

    private const RAW_IMAGE_LIMIT = 10 * 1024 * 1024;

    private const VIDEO_THUMBNAIL_LIMIT = 1024 * 1024;

    private const MULTIPART_OVERHEAD = 65536;

    public function __construct(
        private readonly AdministratorSession $session,
        private readonly MediaLifecycle $lifecycle,
    ) {}

    public function listImages(Request $request): JsonResponse
    {
        $this->session->require($request, 'media.read');
        $page = $this->page($request, ['page', 'context']);
        $context = $request->query('context');
        $query = DB::table('images');
        if ($context === 'editorial') {
            $query->whereIn('context', self::EDITORIAL_CONTEXTS);
        } elseif ($context !== null) {
            $query->where('context', $this->context($context));
        }

        return $this->json([
            'total' => $query->count(),
            'items' => $query->orderByDesc('created_at')->orderBy('id')
                ->forPage($page, self::PAGE_SIZE)->get()->map($this->imageRecord(...)),
        ]);
    }

    public function listVideos(Request $request): JsonResponse
    {
        $this->session->require($request, 'media.read');
        $page = $this->page($request, ['page']);

        return $this->json([
            'total' => DB::table('videos')->count(),
            'items' => DB::table('videos')->orderByDesc('created_at')->orderBy('id')
                ->forPage($page, self::PAGE_SIZE)->get()->map($this->videoRecord(...)),
        ]);
    }

    public function getImage(Request $request, string $id): JsonResponse
    {
        $this->session->require($request, 'media.read');
        AccessInput::noQuery($request);

        return $this->json($this->imageRecord($this->read('images', $id)));
    }

    public function getVideo(Request $request, string $id): JsonResponse
    {
        $this->session->require($request, 'media.read');
        AccessInput::noQuery($request);

        return $this->json($this->videoRecord($this->read('videos', $id)));
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $this->session->require($request, 'media.write', true);
        AccessInput::noQuery($request);
        $this->requestLimit($request, self::RAW_IMAGE_LIMIT + self::MULTIPART_OVERHEAD);
        if ($this->mediaType($request) !== 'multipart/form-data') {
            ApiError::abort(415, 'UNSUPPORTED_MEDIA_TYPE', 'Upload a multipart image.');
        }
        if (array_keys($request->request->all()) !== ['context'] || array_keys($request->allFiles()) !== ['file']
            || ! $request->file('file') instanceof UploadedFile) {
            AccessInput::invalid('file', 'Upload exactly one file and one context.');
        }

        $context = $this->context($request->request->get('context'));
        $profile = $this->imageProfile($context);
        $thumbnail = SiteSettings::profiles()['thumbnail'];
        $id = (string) Str::uuid();
        $metadata = $this->lifecycle->withUploadSlot('images', fn () => $this->lifecycle->publish(
            'images', $id, $request,
            function (string $original, string $derivative) use ($request, $profile, $thumbnail, $context): array {
                $source = UploadedMedia::image($request->file('file'), $profile['size']);
                $width = $profile['width'];
                $height = $profile['height'];
                if (imagesx($source) < $width || imagesy($source) < $height) {
                    AccessInput::invalid('file', 'The oriented image must be at least as large as the target dimensions.');
                }
                $size = UploadedMedia::webp($source, $original, $width, $height, $profile['size']);
                UploadedMedia::webp($source, $derivative, $thumbnail['width'], $thumbnail['height']);

                return ['context' => $context, 'width' => $width, 'height' => $height, 'size_bytes' => $size];
            },
            function () use ($context, $profile, $thumbnail): void {
                if ($this->imageProfile($context) != $profile || SiteSettings::profiles()['thumbnail'] != $thumbnail) {
                    ApiError::abort(409, 'SETTINGS_CHANGED', 'Image settings changed. Reload and upload again.');
                }
            },
            context: $context,
        ));

        return $this->json($this->imageRecord((object) $metadata), 201)->header('Location', '/api/v1/admin/images/'.$id);
    }

    public function uploadVideo(Request $request): JsonResponse
    {
        $this->session->require($request, 'media.write', true);
        AccessInput::noQuery($request);
        $kind = $this->mediaType($request);
        if (! in_array($kind, ['video/mp4', 'multipart/form-data'], true)) {
            ApiError::abort(415, 'UNSUPPORTED_MEDIA_TYPE', 'Upload MP4 video content.');
        }
        $maximum = (int) config('pilot.maximum_video_bytes');
        $multipart = $kind === 'multipart/form-data';
        $this->requestLimit($request, $maximum + ($multipart ? self::VIDEO_THUMBNAIL_LIMIT + self::MULTIPART_OVERHEAD : 0));
        $poster = null;
        $thumbnail = null;
        if ($multipart) {
            $fields = $request->request->all();
            $files = $request->allFiles();
            if (array_diff(array_keys($fields), ['posterId']) !== []
                || array_diff(array_keys($files), ['file', 'thumbnail']) !== []
                || ! ($files['file'] ?? null) instanceof UploadedFile
                || (array_key_exists('thumbnail', $files) === array_key_exists('posterId', $fields))) {
                AccessInput::invalid('file', 'Upload one MP4 file with either a thumbnail or a posterId.');
            }
            if (array_key_exists('posterId', $fields)) {
                $poster = AccessInput::ids([$fields['posterId']], 'posterId')[0];
            } else {
                if (! $files['thumbnail'] instanceof UploadedFile) {
                    AccessInput::invalid('thumbnail', 'Upload one JPEG or WebP video thumbnail.');
                }
                $thumbnail = SiteSettings::profiles()['thumbnail'];
            }
        }

        $id = (string) Str::uuid();
        $metadata = $this->lifecycle->withUploadSlot('videos', fn () => $this->lifecycle->publish(
            'videos', $id, $request,
            function (string $original, string $derivative) use ($request, $multipart, $maximum, $poster, $thumbnail): array {
                if ($multipart) {
                    $file = $request->file('file');
                    if (! $file->isValid()) {
                        AccessInput::invalid('file', 'Select one MP4 video.');
                    }
                    if (strtolower($file->getClientMimeType()) !== 'video/mp4') {
                        ApiError::abort(415, 'UNSUPPORTED_MEDIA_TYPE', 'Only MP4 video is supported.');
                    }
                    if ($file->getSize() > $maximum) {
                        ApiError::abort(413, 'REQUEST_TOO_LARGE', 'The video exceeds its byte allowance.');
                    }

                    $source = @fopen($file->getPathname(), 'rb');
                } else {
                    $source = $request->getContent(true);
                }

                $this->stageVideo($source, $original, $maximum);
                $size = UploadedMedia::checkVideo($original, $maximum);
                if ($thumbnail !== null) {
                    $image = UploadedMedia::image($request->file('thumbnail'), self::VIDEO_THUMBNAIL_LIMIT, videoThumbnail: true);
                    if (imagesx($image) < $thumbnail['width'] || imagesy($image) < $thumbnail['height']) {
                        AccessInput::invalid('thumbnail', 'The thumbnail must be at least as large as the target dimensions.');
                    }
                    UploadedMedia::webp($image, $derivative, $thumbnail['width'], $thumbnail['height'], self::VIDEO_THUMBNAIL_LIMIT);
                }

                return ['size_bytes' => $size, 'poster_image_id' => $poster];
            },
            function () use ($thumbnail, $poster): void {
                if ($thumbnail !== null && SiteSettings::profiles()['thumbnail'] != $thumbnail) {
                    ApiError::abort(409, 'SETTINGS_CHANGED', 'Image settings changed. Reload and upload again.');
                }
                if ($poster !== null) {
                    $this->validatePoster($poster);
                }
            },
        ));

        return $this->json($this->videoRecord((object) $metadata), 201)->header('Location', '/api/v1/admin/videos/'.$id);
    }

    public function updateVideoPoster(Request $request, string $id): JsonResponse
    {
        $input = AccessInput::read($request, ['posterId']);
        $poster = AccessInput::ids([$input->posterId ?? null], 'posterId')[0];
        $video = WriteTransaction::run(function () use ($request, $id, $poster): stdClass {
            $actor = $this->session->require($request, 'media.write', true);
            $video = $this->read('videos', $id);
            $this->validatePoster($poster);
            DB::table('videos')->where('id', $video->id)->update(['poster_image_id' => $poster]);
            Access::audit($actor['id'], 'videos.update', 'videos/'.$video->id, ['posterId' => $poster]);
            $video->poster_image_id = $poster;

            return $video;
        });

        return $this->json($this->videoRecord($video));
    }

    public function deleteImage(Request $request, string $id): Response
    {
        return $this->delete('images', $request, $id);
    }

    public function deleteVideo(Request $request, string $id): Response
    {
        return $this->delete('videos', $request, $id);
    }

    public function cleanupImages(Request $request): JsonResponse
    {
        return $this->cleanup('images', $request);
    }

    public function cleanupVideos(Request $request): JsonResponse
    {
        return $this->cleanup('videos', $request);
    }

    public function image(Request $request, string $id): BinaryFileResponse
    {
        AccessInput::noQuery($request);
        $media = $this->read('images', $id);

        return $this->send($request, $media->id, $this->original('images', $media), 'image/webp');
    }

    public function imageThumbnail(Request $request, string $id): Response
    {
        AccessInput::noQuery($request);
        $media = $this->read('images', $id);
        $path = MediaLifecycle::storedThumbnail('images', $media->id, $media->context);
        if (is_file($path)) {
            return $this->send($request, $media->id, $path, 'image/webp');
        }

        $original = $this->original('images', $media);
        $thumbnail = SiteSettings::profiles()['thumbnail'];
        $bytes = UploadedMedia::thumbnailFromStored(
            $original, (int) $media->width, (int) $media->height, $thumbnail['width'], $thumbnail['height'],
        );

        return response($bytes, headers: $this->deliveryHeaders('image/webp'));
    }

    public function video(Request $request, string $id): BinaryFileResponse
    {
        AccessInput::noQuery($request);
        $media = $this->read('videos', $id);

        return $this->send($request, $media->id, $this->original('videos', $media), 'video/mp4');
    }

    public function videoThumbnail(Request $request, string $id): BinaryFileResponse
    {
        AccessInput::noQuery($request);
        $media = $this->read('videos', $id);
        $path = MediaLifecycle::storedThumbnail('videos', $media->id);
        if (! is_file($path)) {
            ApiError::abort(404, 'NOT_FOUND', 'Video thumbnail not found.');
        }

        return $this->send($request, $media->id, $path, 'image/webp');
    }

    private function delete(string $kind, Request $request, string $id): Response
    {
        AccessInput::noQuery($request);
        AccessInput::noBody($request);
        $this->lifecycle->delete($kind, strtolower($id), $request);

        return response()->noContent(headers: ['Cache-Control' => 'no-store']);
    }

    private function cleanup(string $kind, Request $request): JsonResponse
    {
        AccessInput::noQuery($request);
        AccessInput::noBody($request);

        return $this->json($this->lifecycle->cleanup($kind, $request));
    }

    private function mediaType(Request $request): string
    {
        return strtolower(trim(explode(';', $request->headers->get('Content-Type', ''), 2)[0]));
    }

    private function requestLimit(Request $request, int $maximum): void
    {
        $length = $request->headers->get('Content-Length');
        if ($length !== null && ctype_digit($length) && (int) $length > $maximum) {
            ApiError::abort(413, 'REQUEST_TOO_LARGE', 'The upload exceeds its byte allowance.');
        }
    }

    private function stageVideo(mixed $source, string $destination, int $maximum): void
    {
        if (! is_resource($source)) {
            ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
        }
        try {
            $output = @fopen($destination, 'xb');
            if ($output === false) {
                ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
            }
            try {
                $bytes = stream_copy_to_stream($source, $output, $maximum + 1);
                if ($bytes === false) {
                    ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
                }
                if ($bytes > $maximum) {
                    ApiError::abort(413, 'REQUEST_TOO_LARGE', 'The video exceeds its byte allowance.');
                }
                if ($bytes === 0) {
                    AccessInput::invalid('file', 'Choose a nonempty video file.');
                }
            } finally {
                fclose($output);
            }
        } finally {
            fclose($source);
        }
    }

    private function validatePoster(string $id): void
    {
        if (! DB::table('images')->where('id', $id)->where('context', 'video')->exists()) {
            AccessInput::invalid('posterId', 'Choose an existing ready video image.');
        }
    }

    private function context(mixed $context): string
    {
        if (! in_array($context, MediaLifecycle::IMAGE_CONTEXTS, true)) {
            AccessInput::invalid('context', 'Choose article, project, service, approach, content, logo or video.');
        }

        return $context;
    }

    private function imageProfile(string $context): array
    {
        return SiteSettings::profiles()[$context === 'approach' ? 'service' : $context];
    }

    private function page(Request $request, array $allowed): int
    {
        $query = $request->query();
        $page = filter_var($query['page'] ?? 1, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => intdiv(2147483647, self::PAGE_SIZE) + 1],
        ]);
        if ($page === false || array_diff(array_keys($query), $allowed) !== []) {
            AccessInput::invalid('page', 'Use a valid page and supported query fields.');
        }

        return $page;
    }

    private function original(string $kind, stdClass $media): string
    {
        $path = MediaLifecycle::original($kind, $media->id, $media->context ?? null);
        if ($kind === 'images' && ! is_file($path)) {
            $path = MediaLifecycle::legacyImage($kind, $media->id);
        }
        if (! is_file($path) || ! is_readable($path) || filesize($path) !== (int) $media->size_bytes) {
            ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
        }

        return $path;
    }

    private function send(Request $request, string $id, string $path, string $type): BinaryFileResponse
    {
        if (! is_readable($path) || ($bytes = filesize($path)) < 1
            || ($type === 'image/webp' && $bytes > UploadedMedia::MAX_NORMALIZED_BYTES)) {
            ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
        }
        $response = new BinaryFileResponse($path, 200, $this->deliveryHeaders($type), false);
        $response->setEtag($id.'-'.$bytes);
        $response->isNotModified($request);

        return $response;
    }

    private function deliveryHeaders(string $type): array
    {
        return [
            'Cache-Control' => 'no-store',
            'Content-Type' => $type,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }

    private function read(string $table, string $id): stdClass
    {
        return DB::table($table)->where('id', strtolower($id))->first()
            ?? ApiError::abort(404, 'NOT_FOUND', 'Media not found.');
    }

    private function imageRecord(stdClass $image): array
    {
        $url = '/api/v1/public/images/'.$image->id;

        return [
            'id' => $image->id,
            'context' => $image->context,
            'width' => (int) $image->width,
            'height' => (int) $image->height,
            'sizeBytes' => (int) $image->size_bytes,
            'contentType' => 'image/webp',
            'previewUrl' => $url,
            'thumbnailUrl' => $url.'/thumbnail',
            'thumb' => $url.'/thumbnail',
        ];
    }

    private function videoRecord(stdClass $video): array
    {
        $url = '/api/v1/public/videos/'.$video->id;
        $thumbnailUrl = null;
        if (is_file(MediaLifecycle::storedThumbnail('videos', $video->id))) {
            $thumbnailUrl = $url.'/thumbnail';
        } elseif ($video->poster_image_id !== null) {
            $thumbnailUrl = '/api/v1/public/images/'.$video->poster_image_id.'/thumbnail';
        }

        return [
            'id' => $video->id,
            'sizeBytes' => (int) $video->size_bytes,
            'contentType' => 'video/mp4',
            'previewUrl' => $url,
            'thumbnailUrl' => $thumbnailUrl,
        ];
    }

    private function json(array $body, int $status = 200): JsonResponse
    {
        return response()->json($body, $status, ['Cache-Control' => 'no-store']);
    }
}
