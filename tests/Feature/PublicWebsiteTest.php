<?php

declare(strict_types=1);

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PublicWebsiteTest extends TestCase
{
    public static function pages(): array
    {
        return [['/'], ['/projects'], ['/services'], ['/articles']];
    }

    #[DataProvider('pages')]
    public function test_public_pages_render_canonical_and_structured_data_without_sessions(string $path): void
    {
        $response = $this->get('http://web.test'.$path)->assertOk();
        self::assertSame([], $response->headers->getCookies());
        $dom = new DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new DOMXPath($dom);
        self::assertSame('http://web.test'.$path, $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
        $structured = $xpath->evaluate('string(//script[@type="application/ld+json"])');
        self::assertIsArray(json_decode($structured, true, flags: JSON_THROW_ON_ERROR));
        self::assertSame(1, $xpath->query('//main[@id="main-content"]')->length);
    }

    public function test_public_and_admin_routes_are_isolated_by_host(): void
    {
        $this->getJson('http://web.test/api/v1/auth/session')->assertNotFound();
        $this->getJson('http://web.test/api/v1/admin/faqs')->assertNotFound();
        $this->admin('GET', '/api/v1/auth/session')->assertUnauthorized();
        $this->admin('GET', '/api/v1/public/projects')->assertNotFound();
        $this->get('http://web.test/admin/')->assertNotFound();
        $this->get('http://unknown.test/')->assertNotFound();
        $this->get('http://admin.web.test/projects')->assertNotFound();
        $this->get('http://admin.web.test/assets/app.js')->assertNotFound();
    }

    public function test_sitemap_is_canonical_and_staging_disables_discovery(): void
    {
        $xml = $this->get('http://web.test/sitemap.xml')->assertOk()->getContent();
        $document = new DOMDocument;
        self::assertTrue($document->loadXML($xml));
        $locations = array_map(fn ($node) => $node->textContent, iterator_to_array($document->getElementsByTagName('loc')));
        self::assertSame(['http://web.test/', 'http://web.test/projects', 'http://web.test/services', 'http://web.test/articles'], $locations);
        $this->get('http://web.test/robots.txt')->assertSee('Sitemap: http://web.test/sitemap.xml', false);
        $this->get('http://admin.web.test/robots.txt')->assertSee('Disallow: /', false)->assertDontSee('Sitemap:', false);
        config(['pilot.allow_indexing' => false]);
        $this->get('http://web.test/sitemap.xml')->assertNotFound();
        $this->get('http://web.test/')->assertSee('noindex,nofollow', false);
        $this->get('http://web.test/robots.txt')->assertDontSee('Sitemap:', false);
    }
}
