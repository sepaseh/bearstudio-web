<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\PublicEditorial;
use App\Support\WebsiteSeo;
use DOMDocument;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;

final class WebsiteDiscoveryController
{
    private const URL_LIMIT = 50_000;

    public function robots(): Response
    {
        $rules = "User-agent: *\nDisallow: /api/v1/admin/\nDisallow: /api/v1/auth/\nDisallow: /swagger\nDisallow: /openapi\n";
        if (WebsiteSeo::allowsIndexing()) {
            $rules .= "\nSitemap: ".WebsiteSeo::url('/sitemap.xml')."\n";
        }

        return $this->response($rules, 200, 'text/plain');
    }

    public function sitemap(): Response
    {
        if (! WebsiteSeo::allowsIndexing()) {
            return $this->response('', 404, 'text/plain');
        }

        try {
            $articles = PublicEditorial::articles()->orderBy('articles.slug')
                ->limit(self::URL_LIMIT + 1)->pluck('articles.slug');
            $projects = PublicEditorial::projects()->orderBy('projects.slug')
                ->limit(self::URL_LIMIT + 1)->pluck('projects.slug');
        } catch (QueryException $exception) {
            report($exception);

            return $this->response('', 503, 'text/plain');
        }

        $paths = ['/', '/projects', '/services', '/articles'];
        if (count($paths) + $articles->count() + $projects->count() > self::URL_LIMIT) {
            return $this->response('', 503, 'text/plain');
        }
        foreach ($articles as $slug) {
            $paths[] = '/articles/'.rawurlencode($slug);
        }
        foreach ($projects as $slug) {
            $paths[] = '/projects/'.rawurlencode($slug);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $set = $document->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $document->appendChild($set);
        foreach ($paths as $path) {
            $url = WebsiteSeo::url($path);
            if (strlen($url) >= 2048) {
                return $this->response('', 503, 'text/plain');
            }
            $element = $set->appendChild($document->createElement('url'));
            $location = $element->appendChild($document->createElement('loc'));
            $location->appendChild($document->createTextNode($url));
        }
        $xml = $document->saveXML();
        if ($xml === false || strlen($xml) > 52_428_800) {
            return $this->response('', 503, 'text/plain');
        }

        return $this->response($xml, 200, 'application/xml');
    }

    private function response(string $body, int $status, string $type): Response
    {
        return response($body, $status, ['Cache-Control' => 'no-store', 'Content-Type' => $type.'; charset=utf-8']);
    }
}
