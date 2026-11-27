<?php

declare(strict_types=1);

$document = new DOMDocument;
if (! $document->load(dirname(__DIR__).'/coverage/clover.xml', LIBXML_NONET)) {
    throw new RuntimeException('Generate a Clover coverage report first.');
}
$xpath = new DOMXPath($document);
$metrics = $xpath->query('/coverage/project/metrics')->item(0);
if (! $metrics instanceof DOMElement || (int) $metrics->getAttribute('statements') === 0) {
    throw new RuntimeException('Coverage report has no application statements.');
}
$coverage = 100 * (int) $metrics->getAttribute('coveredstatements') / (int) $metrics->getAttribute('statements');
printf('Application statement coverage: %.2f%% (required: 70%%).'.PHP_EOL, $coverage);
if ($coverage < 70) {
    exit(1);
}
