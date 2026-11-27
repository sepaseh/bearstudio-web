<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ApiError;
use App\Support\UploadedMedia;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VideoValidationTest extends TestCase
{
    private static function box(string $type, string $payload): string
    {
        return pack('N', strlen($payload) + 8).$type.$payload;
    }

    public static function malformedFiles(): array
    {
        return [
            ['not a video'], [self::box('mdat', 'x')],
            [pack('N', 1000).'ftypisom'.pack('N', 0)],
            [self::box('ftyp', 'qt  '.pack('N', 0))],
            [self::box('ftyp', 'isom'.pack('N', 0)).self::box('moov', self::box('free', 'x'))],
        ];
    }

    #[DataProvider('malformedFiles')]
    public function test_invalid_mp4_containers_are_rejected(string $bytes): void
    {
        $file = tempnam(sys_get_temp_dir(), 'bearstudio-video-');
        file_put_contents($file, $bytes);
        try {
            $this->expectException(ApiError::class);
            UploadedMedia::checkVideo($file, 1024);
        } finally {
            unlink($file);
        }
    }

    public function test_bounded_structural_mp4_validation_accepts_complete_boxes_and_rejects_oversize(): void
    {
        $bytes = self::box('ftyp', 'isom'.pack('N', 0)).self::box('moov', self::box('free', 'x')).self::box('mdat', 'synthetic');
        $file = tempnam(sys_get_temp_dir(), 'bearstudio-video-');
        file_put_contents($file, $bytes);
        try {
            self::assertSame(strlen($bytes), UploadedMedia::checkVideo($file, 1024));
            $this->expectException(ApiError::class);
            UploadedMedia::checkVideo($file, 8);
        } finally {
            unlink($file);
        }
    }
}
