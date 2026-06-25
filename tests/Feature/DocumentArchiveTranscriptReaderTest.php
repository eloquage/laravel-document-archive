<?php

use Eloquage\LaravelDocumentArchive\Services\DocumentArchiveTranscriptReader;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('legacy');
    Storage::fake('manifests');

    Storage::disk('legacy')->put(
        'denuncia-v1/479-26-0T9MTS/evidence_manifest.json',
        $this->manifestFixture(),
    );

    $transcript = file_get_contents(__DIR__.'/../Fixtures/transcripts/2023-03-13-479-26-0T9MTS-AUTO-DE-DECLARACOES-6-EN.md');
    Storage::disk('legacy')->put(
        'denuncia-v1/479-26-0T9MTS/2023-03-13-479-26-0T9MTS-AUTO-DE-DECLARACOES-6-EN.md',
        $transcript,
    );
});

it('reads EN transcript for canonical basename', function () {
    $result = app(DocumentArchiveTranscriptReader::class)->read(
        '2023-03-13-479-26-0T9MTS-AUTO-DE-DECLARACOES-6',
        'en',
    );

    expect($result['success'])->toBeTrue()
        ->and($result['content'])->toContain('test declaration document');
});

it('truncates transcript when max_chars is set', function () {
    $result = app(DocumentArchiveTranscriptReader::class)->read(
        '2023-03-13-479-26-0T9MTS-AUTO-DE-DECLARACOES-6',
        maxChars: 40,
    );

    expect($result['success'])->toBeTrue()
        ->and($result['content'])->toContain('[truncated]');
});
