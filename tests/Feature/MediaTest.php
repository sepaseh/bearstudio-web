<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\MediaLifecycle;
use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

final class MediaTest extends TestCase
{
    public function test_video_upload_range_delivery_and_deletion_use_private_storage(): void
    {
        $this->signIn();
        $box = static fn (string $type, string $payload): string => pack('N', strlen($payload) + 8).$type.$payload;
        // Structural MP4 fixture, not a decodable film or an external media dependency.
        $bytes = $box('ftyp', 'isom'.pack('N', 0)).$box('moov', $box('free', 'x')).$box('mdat', 'synthetic');
        $response = $this->call('POST', 'http://admin.web.test/api/v1/admin/videos', [], $this->prepareCookiesForRequest(), [], [
            'CONTENT_TYPE' => 'video/mp4', 'CONTENT_LENGTH' => strlen($bytes),
            'HTTP_X_CSRF_TOKEN' => $this->csrf, 'HTTP_ACCEPT' => 'application/json',
        ], $bytes)->assertCreated();
        $id = $response->json('id');
        self::assertFileExists(MediaLifecycle::original('videos', $id));
        $this->get('http://web.test/api/v1/public/videos/'.$id, ['Range' => 'bytes=0-7'])
            ->assertStatus(206)->assertHeader('Content-Type', 'video/mp4')->assertHeader('Content-Length', '8');
        $this->admin('DELETE', '/api/v1/admin/videos/'.$id)->assertNoContent();
        self::assertFileDoesNotExist(MediaLifecycle::original('videos', $id));
        self::assertFalse(DB::table('videos')->where('id', $id)->exists());
    }

    private function smallProfiles(): void
    {
        $profiles = SiteSettings::profiles();
        $profiles['article'] = ['width' => 16, 'height' => 16, 'size' => 1048576];
        $profiles['thumbnail'] = ['width' => 8, 'height' => 8];
        DB::table('app_settings')->where('id', 1)->update(['image_profiles_json' => json_encode($profiles, JSON_THROW_ON_ERROR)]);
    }

    private function upload(UploadedFile $file)
    {
        $response = $this->post('http://admin.web.test/api/v1/admin/images', ['context' => 'article', 'file' => $file], [
            'X-CSRF-TOKEN' => $this->csrf, 'CONTENT_TYPE' => 'multipart/form-data', 'Accept' => 'application/json',
        ]);
        foreach ($response->headers->getCookies() as $cookie) {
            $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
        }

        return $response;
    }

    public function test_image_upload_normalizes_bytes_and_delete_removes_original_and_thumbnail(): void
    {
        $this->signIn();
        $this->smallProfiles();
        $id = $this->upload(UploadedFile::fake()->image('sample.png', 32, 32))->assertCreated()->json('id');
        $original = MediaLifecycle::original('images', $id, 'article');
        $thumbnail = MediaLifecycle::thumbnail('images', $id, 'article');
        self::assertFileExists($original);
        self::assertSame(IMAGETYPE_WEBP, getimagesize($original)[2]);
        self::assertSame(16, getimagesize($original)[0]);
        self::assertFileExists($thumbnail);
        $this->get('http://web.test/api/v1/public/images/'.$id)->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->admin('DELETE', '/api/v1/admin/images/'.$id)->assertNoContent();
        self::assertFileDoesNotExist($original);
        self::assertFileDoesNotExist($thumbnail);
        self::assertFalse(DB::table('images')->where('id', $id)->exists());
        self::assertSame(0, DB::table('media_cleanup_intents')->count());
        $this->get('http://web.test/api/v1/public/images/'.$id)->assertNotFound();
    }

    public function test_referenced_image_cannot_be_deleted(): void
    {
        $this->signIn();
        $this->smallProfiles();
        $id = $this->upload(UploadedFile::fake()->image('cover.png', 32, 32))->assertCreated()->json('id');
        DB::table('articles')->insert([
            'id' => (string) Str::uuid(), 'slug' => 'synthetic-article', 'title' => 'Synthetic article',
            'excerpt' => 'Excerpt', 'body' => 'Body', 'cover_image_id' => $id,
            'category_slug' => DB::table('article_categories')->value('slug'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->admin('DELETE', '/api/v1/admin/images/'.$id)->assertStatus(409)->assertHeader('X-Error-Code', 'IMAGE_IN_USE');
        self::assertFileExists(MediaLifecycle::original('images', $id, 'article'));
        self::assertTrue(DB::table('images')->where('id', $id)->exists());
    }

    public function test_invalid_and_undersized_images_leave_no_ready_record_or_staged_files(): void
    {
        $this->signIn();
        $this->smallProfiles();
        $this->upload(UploadedFile::fake()->createWithContent('fake.txt', 'not an image'))->assertStatus(415);
        $this->upload(UploadedFile::fake()->image('tiny.png', 2, 2))->assertStatus(400);
        self::assertSame(0, DB::table('images')->count());
        self::assertSame(0, DB::table('media_cleanup_intents')->count());
        self::assertSame([], glob(MediaLifecycle::directory('images').'/*/*.part'));
    }

    public function test_cleanup_removes_only_expired_unpublished_uploads(): void
    {
        $this->signIn();
        $expired = (string) Str::uuid();
        $active = (string) Str::uuid();
        foreach ([$expired => time() - 86400, $active => time()] as $id => $time) {
            $file = MediaLifecycle::original('images', $id, 'article').'.part';
            if (! is_dir(dirname($file))) {
                mkdir(dirname($file), 0750, true);
            }
            file_put_contents($file, 'synthetic partial upload');
            DB::table('media_cleanup_intents')->insert(['kind' => 'images', 'id' => $id, 'reason' => 'upload', 'created_at' => $time]);
        }
        $this->admin('POST', '/api/v1/admin/images/cleanup')->assertOk()->assertJsonPath('deleted', 1);
        self::assertFileDoesNotExist(MediaLifecycle::original('images', $expired, 'article').'.part');
        self::assertFileExists(MediaLifecycle::original('images', $active, 'article').'.part');
        self::assertTrue(DB::table('media_cleanup_intents')->where('id', $active)->exists());
    }

    public function test_failed_file_cleanup_stays_pending_and_is_logged_without_a_file_path(): void
    {
        $this->signIn();
        $logger = Log::spy();
        $id = (string) Str::uuid();
        $unremovable = MediaLifecycle::original('images', $id, 'article').'.part';
        // unlink cannot remove this directory. It models a file operation that must be retried.
        mkdir($unremovable, 0750, true);
        DB::table('media_cleanup_intents')->insert(['kind' => 'images', 'id' => $id, 'reason' => 'delete', 'created_at' => time()]);

        $this->admin('POST', '/api/v1/admin/images/cleanup')->assertOk()->assertJsonPath('pending', 1);
        self::assertNotNull(DB::table('media_cleanup_intents')->where('id', $id)->value('last_attempt_at'));
        $logger->shouldHaveReceived('warning', ['media.cleanup.pending', ['kind' => 'images', 'processed' => 1, 'deleted' => 0, 'pending' => 1]])->once();
    }
}
