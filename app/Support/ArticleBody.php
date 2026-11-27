<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

final readonly class ArticleBody
{
    private function __construct(public ?string $lead, public array $sections, public ?string $flow) {}

    public static function fromPlainText(string $text): self
    {
        return new self(nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false), [], null);
    }

    public static function parse(string $sanitizedHtml): self
    {
        $document = new DOMDocument;
        $document->loadHTML('<!doctype html><html><head><meta charset="UTF-8"></head><body>'.$sanitizedHtml.'</body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $body = $document->getElementsByTagName('body')->item(0);
        if (! $body instanceof DOMElement) {
            return new self(null, [], null);
        }

        foreach (iterator_to_array($body->getElementsByTagName('h1'), false) as $heading) {
            $subheading = $document->createElement('h2');
            while ($heading->firstChild !== null) {
                $subheading->appendChild($heading->firstChild);
            }
            $heading->parentNode->replaceChild($subheading, $heading);
        }

        $lead = null;
        $first = self::contentNodes($body)[0] ?? null;
        if ($first instanceof DOMElement && $first->tagName === 'p') {
            $lead = self::innerHtml($first);
            $body->removeChild($first);
        }

        $nodes = self::contentNodes($body);
        $sections = self::readSections($nodes);

        return new self($lead, $sections, $sections === [] && $nodes !== [] ? self::innerHtml($body) : null);
    }

    private static function contentNodes(DOMElement $element): array
    {
        return array_values(array_filter(iterator_to_array($element->childNodes, false),
            static fn (DOMNode $node): bool => $node->nodeType !== XML_COMMENT_NODE
                && ($node->nodeType !== XML_TEXT_NODE || trim($node->textContent) !== '')));
    }

    private static function readSections(array $nodes): array
    {
        $sections = [];
        for ($index = 0; $index < count($nodes);) {
            $heading = $nodes[$index++];
            if (! $heading instanceof DOMElement || $heading->tagName !== 'h2') {
                return [];
            }

            $paragraphs = [];
            while (($nodes[$index] ?? null) instanceof DOMElement && $nodes[$index]->tagName === 'p') {
                $paragraphs[] = self::innerHtml($nodes[$index++]);
            }

            $points = [];
            $list = $nodes[$index] ?? null;
            if ($list instanceof DOMElement && $list->tagName === 'ul') {
                foreach (self::contentNodes($list) as $point) {
                    if (! $point instanceof DOMElement || $point->tagName !== 'li') {
                        return [];
                    }
                    $points[] = self::innerHtml($point);
                }
                $index++;
            }

            $sections[] = ['heading' => self::innerHtml($heading), 'paragraphs' => $paragraphs, 'points' => $points];
        }

        return $sections;
    }

    private static function innerHtml(DOMElement $element): string
    {
        return implode('', array_map(static fn (DOMNode $child): string => (string) $element->ownerDocument->saveHTML($child),
            iterator_to_array($element->childNodes, false)));
    }
}
