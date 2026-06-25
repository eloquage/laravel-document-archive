<?php

use Eloquage\LaravelDocumentArchive\Events\MissingTranscriptDetected;
use Eloquage\LaravelDocumentArchive\Services\ArchivePipelineService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('intake');
    Storage::fake('legacy');

    $manifest = file_get_contents(__DIR__.'/../Fixtures/evidence_manifest_sample.json');
    Storage::disk('legacy')->put('denuncia-v1/479-26-0T9MTS/evidence_manifest.json', $manifest);
    Storage::disk('legacy')->put(
        'denuncia-v1/479-26-0T9MTS/2023-03-13-479-26-0T9MTS-AUTO-DE-DECLARACOES-6-EN.md',
        '# transcript',
    );
});

it('lists intake files on ingest dry run', function () {
    Storage::disk('intake')->put('479-26-0T9MTS/new-file.pdf', 'content');

    $result = app(ArchivePipelineService::class)->ingest('479-26-0T9MTS', dryRun: true);

    expect($result->success)->toBeTrue()
        ->and($result->count)->toBe(1)
        ->and($result->capabilities['ingest_manifest_write'])->toBeTrue();
});

it('registers new intake files in manifest on execute', function () {
    Storage::disk('intake')->put('479-26-0T9MTS/new-intake.pdf', 'content');

    $result = app(ArchivePipelineService::class)->ingest('479-26-0T9MTS', dryRun: false);

    expect($result->registered)->toBe(['new-intake.pdf'])
        ->and($result->count)->toBe(1);

    $manifest = json_decode(Storage::disk('legacy')->get('denuncia-v1/479-26-0T9MTS/evidence_manifest.json'), true);

    expect($manifest['files']['new-intake.pdf']['status'])->toBe('pending');
});

it('dispatches missing transcript events when validate fix is enabled', function () {
    Event::fake([MissingTranscriptDetected::class]);

    $result = app(ArchivePipelineService::class)->validate('479-26-0T9MTS', fix: true);

    expect($result->fixesApplied)->toBeGreaterThan(0)
        ->and($result->capabilities['validate_fix'])->toBeTrue();

    Event::assertDispatched(MissingTranscriptDetected::class);
});

it('reports no issues when manifest and transcripts are consistent', function () {
    Storage::disk('legacy')->put(
        'denuncia-v1/479-26-0T9MTS/2023-03-13-479-26-0T9MTS-AUTO-DE-DECLARACOES-6-PT.md',
        '# pt transcript',
    );

    $result = app(ArchivePipelineService::class)->validate('479-26-0T9MTS');

    expect($result->count)->toBe(0)
        ->and($result->message)->toContain('consistent');
});
