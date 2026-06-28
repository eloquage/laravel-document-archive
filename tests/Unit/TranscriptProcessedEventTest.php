<?php

use Eloquage\LaravelDocumentArchive\Data\ArchiveContext;
use Eloquage\LaravelDocumentArchive\Data\TranscriptProcessedData;
use Eloquage\LaravelDocumentArchive\Events\MissingTranscriptDetected;
use Eloquage\LaravelDocumentArchive\Events\TranscriptProcessed;

it('carries an optional tier on missing transcript events', function () {
    $event = new MissingTranscriptDetected(
        collection: '479-26-0T9MTS',
        canonicalBasename: '2023-03-13-479-26-0T9MTS-SCAN',
        language: null,
        sourceFile: 'scan.pdf',
        expectedPath: 'cases:479-26-0T9MTS/2023-03-13-479-26-0T9MTS-SCAN-EN.md',
        tier: 'complex',
    );

    expect($event->tier)->toBe('complex')
        ->and($event->context->tier)->toBe('complex');
});

it('supports tier on archive context', function () {
    $context = ArchiveContext::forCollection('479-26-0T9MTS')
        ->withTier('fast');

    expect($context->tier)->toBe('fast');
});

it('dispatches transcript processed with archive-native payload', function () {
    $data = new TranscriptProcessedData(
        collection: '479-26-0T9MTS',
        sourceFile: 'scan.pdf',
        canonicalBasename: '2023-03-13-479-26-0T9MTS-SCAN',
        tier: 'auto',
        stagesCompleted: ['ocr', 'translate', 'manifest'],
        skippedOcr: false,
        skippedTranslate: false,
        ocrQualityScore: 0.9,
        translationQualityScore: 0.85,
        durationMs: 120,
    );

    $event = new TranscriptProcessed(
        result: $data,
        context: ArchiveContext::forCollection('479-26-0T9MTS')->withBasename('2023-03-13-479-26-0T9MTS-SCAN'),
    );

    expect($event->result->canonicalBasename)->toBe('2023-03-13-479-26-0T9MTS-SCAN')
        ->and($event->context->collection)->toBe('479-26-0T9MTS');
});
