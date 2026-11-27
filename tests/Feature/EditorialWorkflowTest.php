<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\MediaLifecycle;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class EditorialWorkflowTest extends TestCase
{
    private function image(string $context): string
    {
        $id = (string) Str::uuid();
        $file = MediaLifecycle::original('images', $id, $context);
        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0750, true);
        }
        $image = imagecreatetruecolor(32, 32);
        imagewebp($image, $file);
        DB::table('images')->insert(['id' => $id, 'context' => $context, 'width' => 32, 'height' => 32, 'size_bytes' => filesize($file), 'created_at' => now()]);

        return $id;
    }

    public function test_projects_publish_sort_rename_and_preserve_service_references(): void
    {
        $this->signIn();
        $service = ['slug' => 'film', 'title' => 'Film', 'description' => 'Synthetic films', 'tags' => ['Video'], 'imageIds' => []];
        $this->admin('POST', '/api/v1/admin/services', $service)->assertCreated();
        $poster = $this->image('project');
        $project = ['slug' => 'first-film', 'title' => 'First film', 'serviceSlug' => 'film', 'poster' => ['id' => $poster],
            'video' => ['url' => 'https://example.test/film.mp4'], 'description' => 'Synthetic description', 'isFeatured' => true];
        $this->admin('POST', '/api/v1/admin/projects', $project)->assertCreated();
        $project['slug'] = 'second-film';
        $this->admin('POST', '/api/v1/admin/projects', $project)->assertCreated();
        $this->admin('PUT', '/api/v1/admin/projects/sort', ['slugs' => ['second-film', 'first-film']])->assertNoContent();
        $this->admin('PUT', '/api/v1/admin/projects/sort', ['slugs' => ['first-film']])->assertStatus(400);
        $this->getJson('http://web.test/api/v1/public/projects')->assertOk()->assertJsonPath('0.slug', 'second-film');
        $this->get('http://web.test/projects/first-film')->assertOk()->assertSee('First film');
        $this->admin('DELETE', '/api/v1/admin/services/film')->assertStatus(409);
        $this->admin('PATCH', '/api/v1/admin/services/film', ['slug' => 'cinema'])->assertOk();
        self::assertSame(2, DB::table('projects')->where('service_slug', 'cinema')->count());
        $this->admin('PATCH', '/api/v1/admin/projects/first-film', ['slug' => 'renamed-film', 'title' => 'Renamed film'])->assertOk();
        $this->get('http://web.test/projects/first-film')->assertNotFound();
        $this->get('http://web.test/sitemap.xml')->assertSee('/projects/renamed-film', false);
        $this->admin('DELETE', '/api/v1/admin/projects/renamed-film')->assertNoContent();
        $this->admin('DELETE', '/api/v1/admin/projects/second-film')->assertNoContent();
        $this->admin('DELETE', '/api/v1/admin/services/cinema')->assertNoContent();
    }

    public function test_article_workflow_sanitizes_html_and_category_rename_is_atomic(): void
    {
        $this->signIn();
        $this->admin('POST', '/api/v1/admin/article-categories', ['name' => 'Stories', 'slug' => 'stories'])->assertCreated();
        $article = ['title' => 'Synthetic story', 'slug' => 'synthetic-story', 'excerpt' => 'Excerpt',
            'body' => '<p>Lead text</p><h2>Section</h2><p onclick="bad()">Safe body<script>bad()</script></p>',
            'category' => ['slug' => 'stories'], 'cover' => ['id' => $this->image('article')]];
        $id = $this->admin('POST', '/api/v1/admin/articles', $article)->assertCreated()->json('id');
        self::assertStringNotContainsString('script', DB::table('articles')->where('id', $id)->value('body'));
        $this->get('http://web.test/articles/synthetic-story')->assertOk()->assertSee('Safe body')->assertDontSee('onclick="bad()"', false);
        $this->admin('DELETE', '/api/v1/admin/article-categories/stories')->assertStatus(409);
        $this->admin('PATCH', '/api/v1/admin/article-categories/stories', ['slug' => 'journal'])->assertOk();
        self::assertSame('journal', DB::table('articles')->where('id', $id)->value('category_slug'));
        $this->admin('PATCH', '/api/v1/admin/articles/'.$id, ['body' => '<p>Edited</p>'])->assertOk();
        $this->getJson('http://web.test/api/v1/public/articles/synthetic-story')->assertOk();
        $this->admin('GET', '/api/v1/admin/articles?page=1')->assertOk()->assertJsonPath('total', 1);
        $this->admin('GET', '/api/v1/admin/articles?title=unsupported')->assertStatus(400);
        $this->admin('DELETE', '/api/v1/admin/articles/'.$id)->assertNoContent();
        $this->admin('DELETE', '/api/v1/admin/article-categories/journal')->assertNoContent();
    }

    public function test_settings_validate_full_document_and_publish_immediately(): void
    {
        $this->signIn();
        $settings = SiteSettings::adminRecord();
        unset($settings['lightLogo'], $settings['darkLogo']);
        $settings['site']['brandName'] = 'Synthetic Studio';
        $this->admin('PUT', '/api/v1/admin/settings', $settings)->assertOk()->assertJsonPath('site.brandName', 'Synthetic Studio');
        $this->get('http://web.test/')->assertSee('Synthetic Studio');
        $settings['site']['contactEmail'] = 'invalid-email';
        $audit = DB::table('access_audit')->count();
        $this->admin('PUT', '/api/v1/admin/settings', $settings)->assertStatus(400);
        self::assertSame($audit, DB::table('access_audit')->count());
        self::assertSame('Synthetic Studio', SiteSettings::adminRecord()['site']['brandName']);
    }

    public function test_home_replaces_four_cards_together_and_rejects_partial_catalog(): void
    {
        $this->signIn();
        $home = [
            'heroDescription' => 'Synthetic hero', 'heroVideo' => ['url' => 'https://example.test/hero.mp4'],
            'heroPoster' => ['imageId' => $this->image('content')], 'aboutTitle' => 'About us',
            'aboutSubtitle' => 'Subtitle', 'aboutDescription' => 'Description',
            'whyTitle' => 'Why us', 'whySubtitle' => 'Subtitle', 'whyDescription' => 'Description',
            'whyImage' => ['imageId' => $this->image('project')],
            'approach' => array_map(fn ($i) => ['title' => 'Step '.$i, 'image' => ['imageId' => $this->image('approach'), 'alt' => 'Synthetic step']], range(1, 4)),
        ];
        $this->admin('PUT', '/api/v1/admin/pages/home', $home)->assertOk()->assertJsonPath('content.heroDescription', 'Synthetic hero');
        $this->getJson('http://web.test/api/v1/public/pages/home')->assertOk()->assertJsonCount(4, 'approach');
        $this->get('http://web.test/')->assertSee('Synthetic hero');
        array_pop($home['approach']);
        $home['heroDescription'] = 'Invalid replacement';
        $this->admin('PUT', '/api/v1/admin/pages/home', $home)->assertStatus(400);
        self::assertSame('Synthetic hero', DB::table('home_pages')->value('hero_description'));
        self::assertSame(4, DB::table('home_page_approaches')->count());
    }
}
