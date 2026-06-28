<?php

use Eloquage\LaravelDocumentArchive\Data\ArchiveContext;
use Eloquage\LaravelDocumentArchive\Events\MissingTranscriptDetected;

it('builds archive context for collection', function () {
    $context = ArchiveContext::forCollection('479-26-0T9MTS')
        ->withBasename('2023-03-13-479-26-0T9MTS-SCAN')
        ->withSourceFile('scan.pdf');

    expect($context->collection)->toBe('479-26-0T9MTS')
        ->and($context->canonicalBasename)->toBe('2023-03-13-479-26-0T9MTS-SCAN')
        ->and($context->sourceFile)->toBe('scan.pdf');
});

it('includes archive context on missing transcript event', function () {
    $event = new MissingTranscriptDetected(
        collection: '479-26-0T9MTS',
        canonicalBasename: '2023-03-13-479-26-0T9MTS-SCAN',
        language: null,
        sourceFile: 'scan.pdf',
        expectedPath: 'cases:479-26-0T9MTS/2023-03-13-479-26-0T9MTS-SCAN-EN.md',
    );

    expect($event->context->collection)->toBe('479-26-0T9MTS')
        ->and($event->context->sourceFile)->toBe('scan.pdf')
        ->and($event->language)->toBeNull();
});
