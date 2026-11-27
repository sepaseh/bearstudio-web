<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;

final class UploadedMedia
{
    public const MAX_NORMALIZED_BYTES = 8 * 1024 * 1024;

    public static function image(?UploadedFile $file, int $maxBytes, bool $videoThumbnail = false): GdImage
    {
        if ($file === null || ! $file->isValid()) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'Select one image file.');
        }

        if ($file->getSize() > $maxBytes) {
            ApiError::abort(413, 'REQUEST_TOO_LARGE', 'The image exceeds its allowed size.');
        }

        $formats = $videoThumbnail
            ? ['image/jpeg' => IMAGETYPE_JPEG, 'image/webp' => IMAGETYPE_WEBP]
            : ['image/jpeg' => IMAGETYPE_JPEG, 'image/png' => IMAGETYPE_PNG, 'image/webp' => IMAGETYPE_WEBP];
        $declared = strtolower($file->getClientMimeType());
        if (in_array($declared, ['', 'application/octet-stream'], true)
            && strtolower($file->getClientOriginalExtension()) === 'webp') {
            $declared = 'image/webp';
        }
        if (! isset($formats[$declared])) {
            ApiError::abort(415, 'UNSUPPORTED_MEDIA_TYPE', 'Choose a supported image format.');
        }

        $dimensions = @getimagesize($file->getPathname());
        if ($dimensions === false) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'Choose a valid JPEG, PNG or WebP image.');
        }
        if ($dimensions[2] !== $formats[$declared]) {
            ApiError::abort(415, 'UNSUPPORTED_MEDIA_TYPE', 'The image does not match its declared format.');
        }
        if ($dimensions[0] > 12000 || $dimensions[1] > 12000 || $dimensions[0] * $dimensions[1] > 16000000) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'The image dimensions are too large.');
        }

        $bytes = file_get_contents($file->getPathname());
        if ($bytes === false) {
            ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
        }
        if (self::animated($bytes, $dimensions[2])) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'Animated images are unsupported.');
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            ApiError::abort(400, 'VALIDATION_FAILED', 'The image could not be decoded.');
        }

        if ($dimensions[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $metadata = @exif_read_data($file->getPathname(), 'IFD0');
            $image = self::oriented($image, (int) ($metadata['Orientation'] ?? 1));
        }

        return $image;
    }

    public static function webp(GdImage $source, string $path, int $width, int $height, int $limit = self::MAX_NORMALIZED_BYTES): int
    {
        $image = self::crop($source, $width, $height);
        if (! @imagewebp($image, $path, 85) || ! is_file($path) || ($size = filesize($path)) < 12) {
            ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
        }
        if ($size > min($limit, self::MAX_NORMALIZED_BYTES)) {
            ApiError::abort(413, 'REQUEST_TOO_LARGE', 'The encoded image exceeds its byte allowance.');
        }

        return $size;
    }

    public static function thumbnailFromStored(
        string $path, int $sourceWidth, int $sourceHeight, int $width, int $height,
    ): string {
        $dimensions = @getimagesize($path);
        if (filesize($path) > self::MAX_NORMALIZED_BYTES || $dimensions === false || $dimensions[2] !== IMAGETYPE_WEBP
            || $dimensions[0] !== $sourceWidth || $dimensions[1] !== $sourceHeight || $sourceWidth * $sourceHeight > 16000000) {
            ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
        }

        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) {
            ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
        }

        try {
            $source = @imagecreatefromwebp($path);
            if ($source === false || ! @imagewebp(self::crop($source, $width, $height), $stream, 85)) {
                ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
            }
            rewind($stream);
            $bytes = stream_get_contents($stream, self::MAX_NORMALIZED_BYTES + 1);
            if ($bytes === false || strlen($bytes) < 12 || strlen($bytes) > self::MAX_NORMALIZED_BYTES) {
                ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
            }

            return $bytes;
        } finally {
            fclose($stream);
        }
    }

    private static function animated(string $bytes, int $format): bool
    {
        if ($format === IMAGETYPE_WEBP) {
            return strlen($bytes) >= 21 && substr($bytes, 12, 4) === 'VP8X' && (ord($bytes[20]) & 0x02) !== 0;
        }
        if ($format !== IMAGETYPE_PNG) {
            return false;
        }

        for ($offset = 8, $size = strlen($bytes); $offset <= $size - 12;) {
            $tag = substr($bytes, $offset + 4, 4);
            if ($tag === 'acTL') {
                return true;
            }
            if ($tag === 'IDAT' || $tag === 'IEND') {
                return false;
            }
            $offset += unpack('N', substr($bytes, $offset, 4))[1] + 12;
        }

        return false;
    }

    private static function crop(GdImage $source, int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $cropWidth = min($sourceWidth, (int) round($sourceHeight * $width / $height));
        $cropHeight = min($sourceHeight, (int) round($sourceWidth * $height / $width));
        $sourceX = (int) floor(($sourceWidth - $cropWidth) / 2);
        $sourceY = (int) floor(($sourceHeight - $cropHeight) / 2);
        imagecopyresampled($image, $source, 0, 0, $sourceX, $sourceY, $width, $height, $cropWidth, $cropHeight);

        return $image;
    }

    private static function oriented(GdImage $image, int $orientation): GdImage
    {
        if (in_array($orientation, [2, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        } elseif ($orientation === 4) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }

        $degrees = match ($orientation) {
            3 => 180,
            5, 8 => 90,
            6, 7 => -90,
            default => 0,
        };
        if ($degrees === 0) {
            return $image;
        }
        $rotated = imagerotate($image, $degrees, 0);
        if ($rotated === false) {
            ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Image processing is temporarily unavailable.');
        }

        return $rotated;
    }

    public static function checkVideo(string $path, int $maxBytes): int
    {
        $size = filesize($path);
        if ($size > $maxBytes) {
            ApiError::abort(413, 'REQUEST_TOO_LARGE', 'The video exceeds the 100 MiB limit.');
        }

        $stream = @fopen($path, 'rb');
        if ($size === false || $stream === false) {
            ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Media storage is temporarily unavailable.');
        }

        try {
            $remaining = 10000;
            self::inspectBoxes($stream, $size, $remaining);
        } finally {
            fclose($stream);
        }

        return $size;
    }

    private static function inspectBoxes(mixed $stream, int $end, int &$remaining, int $depth = 1): void
    {
        if ($depth > 8) {
            self::invalidVideo();
        }

        $top = $depth === 1;
        $seen = [];
        $first = true;
        $offset = ftell($stream);
        while ($offset < $end) {
            if ($remaining-- < 1 || $end - $offset < 8) {
                self::invalidVideo();
            }

            $box = unpack('Nsize/a4type', self::boxBytes($stream, 8));
            $header = 8;
            $length = $box['size'];
            $type = $box['type'];
            if ($length === 1) {
                if ($end - $offset < 16) {
                    self::invalidVideo();
                }
                $parts = unpack('Nhigh/Nlow', self::boxBytes($stream, 8));
                $length = $parts['high'] === 0 ? $parts['low'] : $end + 1;
                $header = 16;
            } elseif ($length === 0) {
                $length = $end - $offset;
            }
            if ($type === 'uuid') {
                $header += 16;
            }
            if ($length < $header || $length > $end - $offset || ($top && $first && $type !== 'ftyp')) {
                self::invalidVideo();
            }

            $payload = $length - $header;
            if ($top && in_array($type, ['ftyp', 'moov', 'mdat'], true)) {
                if ($payload === 0 || ($type !== 'mdat' && isset($seen[$type]))) {
                    self::invalidVideo();
                }
                if ($type === 'ftyp') {
                    if ($payload < 8 || ($payload - 8) % 4 !== 0) {
                        self::invalidVideo();
                    }
                    $brand = self::boxBytes($stream, 4);
                    if ($brand === 'qt  ' || preg_match('/\A[\x20-\x7e]{4}\z/D', $brand) !== 1) {
                        self::invalidVideo();
                    }
                }
                $seen[$type] = true;
            }

            if (in_array($type, ['moov', 'trak', 'mdia', 'minf', 'stbl', 'dinf', 'edts',
                'mvex', 'moof', 'traf', 'mfra'], true)) {
                self::inspectBoxes($stream, $offset + $length, $remaining, $depth + 1);
            }
            $first = false;
            $offset += $length;
            if (fseek($stream, $offset) !== 0) {
                self::invalidVideo();
            }
        }
        if ($offset !== $end || ($top && ! isset($seen['ftyp'], $seen['moov'], $seen['mdat']))) {
            self::invalidVideo();
        }
    }

    private static function boxBytes(mixed $stream, int $length): string
    {
        $bytes = fread($stream, $length);
        if ($bytes === false || strlen($bytes) !== $length) {
            self::invalidVideo();
        }

        return $bytes;
    }

    private static function invalidVideo(): never
    {
        ApiError::abort(400, 'VALIDATION_FAILED', 'The MP4 container is invalid or incomplete.');
    }
}
