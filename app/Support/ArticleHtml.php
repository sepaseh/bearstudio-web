<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final class ArticleHtml
{
    private const TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'blockquote', 'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a'];

    private const DISCARD = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'svg', 'math'];

    public static function normalize(mixed $input): string
    {
        if (! is_string($input) || str_contains($input, "\0")) {
            AccessInput::invalid('body', 'Supply visible article text.');
        }

        self::checkLength($input);
        $document = new DOMDocument;
        $document->loadHTML('<!doctype html><html><head><meta charset="UTF-8"></head><body>'.$input.'</body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $body = $document->getElementsByTagName('body')->item(0);
        $html = $body ? trim(self::children($body)) : '';
        $text = html_entity_decode(strip_tags($html), ENT_HTML5, 'UTF-8');
        if (preg_match('/[^\s\p{C}\x{034f}\x{fe00}-\x{fe0f}\x{e0100}-\x{e01ef}]/u', $text) !== 1) {
            AccessInput::invalid('body', 'Supply visible article text.');
        }

        self::checkLength($html);

        return $html;
    }

    private static function children(DOMNode $parent): string
    {
        $html = '';
        foreach ($parent->childNodes as $node) {
            if ($node instanceof DOMText) {
                $html .= self::escape($node->nodeValue);
            } elseif ($node instanceof DOMElement && ! in_array($node->tagName, self::DISCARD, true)) {
                $html .= self::element($node);
            }
        }

        return $html;
    }

    private static function element(DOMElement $element): string
    {
        $tag = $element->tagName;
        if ($tag === 'br') {
            return '<br>';
        }

        $body = self::children($element);
        if (! in_array($tag, self::TAGS, true)) {
            return $body;
        }

        $attributes = '';
        if ($tag === 'a') {
            $href = $element->getAttribute('href');
            if (self::httpLink($href)) {
                $attributes .= ' href="'.self::escape($href).'"';
            }
            if ($element->hasAttribute('title')) {
                $attributes .= ' title="'.self::escape($element->getAttribute('title')).'"';
            }
        }
        if ($tag === 'li' && $element->getAttribute('data-list') === 'bullet') {
            $attributes = ' data-list="bullet"';
        }

        return '<'.$tag.$attributes.'>'.$body.'</'.$tag.'>';
    }

    private static function httpLink(string $href): bool
    {
        if (! filter_var($href, FILTER_VALIDATE_URL) || preg_match('/[\s\p{C}]/u', $href)
            || str_contains($href, '\\')) {
            return false;
        }

        $parts = parse_url($href);

        return in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && isset($parts['host']) && ! isset($parts['user']) && ! isset($parts['pass']);
    }

    private static function checkLength(string $html): void
    {
        if (mb_strlen($html, 'UTF-8') > 100000) {
            ApiError::abort(413, 'REQUEST_TOO_LARGE', 'Article HTML exceeds its size allowance.');
        }
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
