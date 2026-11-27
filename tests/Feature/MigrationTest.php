<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Access;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class MigrationTest extends TestCase
{
    public function test_upgrading_legacy_records_preserves_identity_password_and_article_text(): void
    {
        Schema::dropAllTables();
        $paths = array_slice(glob(base_path('database/migrations/*.php')), 0, 3);
        $paths = array_map(fn ($path) => 'database/migrations/'.basename($path), $paths);
        $this->artisan('migrate', ['--path' => $paths, '--force' => true])->assertExitCode(0);
        $id = (string) Str::uuid();
        $hash = password_hash('LegacyPassword123!', PASSWORD_BCRYPT, ['cost' => 4]);
        DB::table('administrators')->insert([
            'id' => $id, 'mobile' => '9123456789', 'name' => 'Legacy operator', 'password_hash' => $hash,
            'security_stamp' => (string) Str::uuid(), 'is_active' => true,
            'permissions' => '["content.read","content.write","media.read","media.write"]',
        ]);
        DB::table('articles')->insert(['slug' => 'legacy-story', 'title' => 'Legacy story', 'excerpt' => 'Excerpt', 'body' => '<script>literal legacy text</script>', 'created_at' => now(), 'updated_at' => now()]);
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        self::assertSame($hash, DB::table('administrators')->where('id', $id)->value('password_hash'));
        self::assertContains('roles.write', Access::permissions($id));
        $article = DB::table('articles')->where('slug', 'legacy-story')->first();
        self::assertTrue(Str::isUuid($article->id));
        self::assertSame(0, (int) $article->body_is_html);
        $this->get('http://web.test/articles/legacy-story')->assertOk()->assertSee('&lt;script&gt;', false);
        DB::disconnect('mariadb');
    }

    public function test_mariadb_supports_foreign_keys_and_repeated_migration(): void
    {
        Schema::dropAllTables();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        self::assertSame('mariadb', DB::connection()->getDriverName());
        self::assertSame(1, (int) DB::selectOne('SELECT @@foreign_key_checks AS enabled')->enabled);
        DB::table('faqs')->insert(['id' => 'd623dc3f-27fc-43a2-aeb3-0de76ef8cd34', 'question' => 'Preserved?', 'answer' => 'Yes', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        self::assertTrue(DB::table('faqs')->where('id', 'd623dc3f-27fc-43a2-aeb3-0de76ef8cd34')->exists());
        self::assertTrue(Schema::hasTable('media_cleanup_intents'));
        DB::disconnect('mariadb');
    }

    public function test_database_transaction_rolls_back_and_foreign_keys_reject_orphans(): void
    {
        try {
            DB::transaction(function (): void {
                DB::table('services')->insert(['slug' => 'rolled-back', 'title' => 'Transient', 'sort_order' => 1]);
                throw new \RuntimeException('synthetic rollback');
            });
        } catch (\RuntimeException $error) {
            self::assertSame('synthetic rollback', $error->getMessage());
        }
        self::assertFalse(DB::table('services')->where('slug', 'rolled-back')->exists());
        $this->expectException(QueryException::class);
        DB::table('projects')->insert(['slug' => 'orphan', 'title' => 'Orphan', 'service_slug' => 'missing', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    }
}
