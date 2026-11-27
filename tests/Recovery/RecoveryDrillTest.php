<?php

declare(strict_types=1);

namespace Tests\Recovery;

use App\Support\MediaLifecycle;
use App\Support\SiteSettings;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;
use Tests\Support\MariaDatabase;
use Tests\TestCase;

final class RecoveryDrillTest extends TestCase
{
    public function test_recovers_a_matching_database_and_private_media_snapshot(): void
    {
        $operator = $this->signIn(['media.read', 'media.write']);
        $keyProbe = Crypt::encryptString('synthetic-recovery-check');
        $profiles = SiteSettings::profiles();
        $profiles['article'] = ['width' => 16, 'height' => 16, 'size' => 1048576];
        $profiles['thumbnail'] = ['width' => 8, 'height' => 8];
        DB::table('app_settings')->where('id', 1)->update(['image_profiles_json' => json_encode($profiles, JSON_THROW_ON_ERROR)]);
        $id = $this->post('http://admin.web.test/api/v1/admin/images', [
            'context' => 'article',
            'file' => UploadedFile::fake()->image('recovery.png', 32, 32),
        ], ['X-CSRF-TOKEN' => $this->csrf, 'CONTENT_TYPE' => 'multipart/form-data', 'Accept' => 'application/json'])->assertCreated()->json('id');
        $original = MediaLifecycle::original('images', $id, 'article');
        $thumbnail = MediaLifecycle::thumbnail('images', $id, 'article');
        $originalHash = $this->hash($original);
        $thumbnailHash = $this->hash($thumbnail);
        $migrations = DB::table('migrations')->orderBy('id')->pluck('migration')->all();

        // The fixture has no other writers, so the dump and media copy represent the same point.
        $filesystem = new Filesystem;
        $backup = $this->sandbox.'/backup';
        $restore = $this->sandbox.'/restore';
        $filesystem->makeDirectory($backup, 0700);
        $options = $this->clientOptions();
        $database = $backup.'/database.sql';
        $this->dumpDatabase($options, $database);
        $dumpHash = $this->hash($database);
        self::assertTrue($filesystem->copyDirectory(storage_path('app/private/media'), $backup.'/media'));

        $target = MariaDatabase::create();
        try {
            $this->importDatabase($options, $database, $target);
            self::assertSame($dumpHash, $this->hash($database));
            $filesystem->makeDirectory($restore.'/app/private', 0700, true);
            self::assertTrue($filesystem->copyDirectory($backup.'/media', $restore.'/app/private/media'));

            DB::purge('mariadb');
            config(['database.connections.mariadb.database' => $target]);
            $this->app->useStoragePath($restore);
            self::assertSame($target, DB::connection()->getDatabaseName());
            self::assertSame($migrations, DB::table('migrations')->orderBy('id')->pluck('migration')->all());
            self::assertTrue(DB::table('images')->where('id', $id)->where('context', 'article')->exists());
            self::assertSame($originalHash, $this->hash(MediaLifecycle::original('images', $id, 'article')));
            self::assertSame($thumbnailHash, $this->hash(MediaLifecycle::thumbnail('images', $id, 'article')));

            $response = $this->get('http://web.test/api/v1/public/images/'.$id)
                ->assertOk()->assertHeader('Content-Type', 'image/webp');
            self::assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
            self::assertSame($originalHash, $this->hash($response->baseResponse->getFile()->getPathname()));

            // Rebuild the encrypter from the matching test key and establish a new session.
            $this->app->forgetInstance('encrypter');
            Crypt::clearResolvedInstance('encrypter');
            self::assertSame('synthetic-recovery-check', Crypt::decryptString($keyProbe));
            $this->defaultCookies = [];
            $this->unencryptedCookies = [];
            $this->csrf = '';
            $this->admin('GET', '/api/v1/auth/session')->assertUnauthorized();
            $this->token();
            $this->admin('POST', '/api/v1/auth/login', ['mobile' => '9123456789', 'password' => 'SyntheticPass123!'])->assertNoContent();
            $this->admin('GET', '/api/v1/auth/session')->assertOk()->assertJsonPath('id', $operator);
            $this->admin('GET', '/api/v1/admin/images/'.$id)->assertOk()->assertJsonPath('id', $id);
            $this->admin('GET', '/api/v1/admin/users')->assertForbidden()->assertHeader('X-Error-Code', 'FORBIDDEN');
        } finally {
            DB::purge('mariadb');
            config(['database.connections.mariadb.database' => $this->testDatabase]);
            $this->app->useStoragePath($this->sandbox);
            MariaDatabase::drop($target);
        }
    }

    private function clientOptions(): string
    {
        $options = tempnam($this->sandbox, 'mariadb-client-');
        self::assertIsString($options);
        $db = MariaDatabase::environment($this->testDatabase);
        $content = "[client]\nprotocol=tcp\n";
        foreach (['host' => 'DB_HOST', 'port' => 'DB_PORT', 'user' => 'DB_USERNAME', 'password' => 'DB_PASSWORD'] as $option => $key) {
            $value = addcslashes($db[$key], "\\\"\n\r\t");
            $content .= $option.'="'.$value."\"\n";
        }
        self::assertSame(strlen($content), file_put_contents($options, $content));

        return $options;
    }

    private function dumpDatabase(string $options, string $destination): void
    {
        $process = new Process([
            getenv('BEARSTUDIO_MARIADB_DUMP') ?: 'mariadb-dump',
            '--defaults-file='.$options,
            '--single-transaction',
            '--skip-lock-tables',
            '--hex-blob',
            '--result-file='.$destination,
            $this->testDatabase,
        ]);
        $process->run();
        self::assertTrue($process->isSuccessful(), 'The synthetic MariaDB dump failed. Check the client tools and fixture database permissions.');
    }

    private function importDatabase(string $options, string $source, string $target): void
    {
        $input = fopen($source, 'rb');
        self::assertIsResource($input);
        try {
            $process = new Process([
                getenv('BEARSTUDIO_MARIADB_CLIENT') ?: 'mariadb',
                '--defaults-file='.$options,
                '--binary-mode',
                '--database='.$target,
            ]);
            $process->setInput($input)->run();
            self::assertTrue($process->isSuccessful(), 'Restoring the synthetic MariaDB dump into a fresh test database failed.');
        } finally {
            fclose($input);
        }
    }

    private function hash(string $file): string
    {
        self::assertFileExists($file);
        $hash = hash_file('sha256', $file);
        self::assertIsString($hash);

        return $hash;
    }
}
