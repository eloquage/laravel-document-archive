<?php

use Eloquage\LaravelDocumentArchive\Events\MissingTranscriptDetected;
use Eloquage\LaravelDocumentArchive\Services\ArchivePipelineService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('intake');
    Storage::fake('legacy');
    Storage::fake('manifests');
    Storage::fake('collections');

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

it('dispatches a single missing transcript event per manifest entry when validate fix is enabled', function () {
    Event::fake([MissingTranscriptDetected::class]);

    $result = app(ArchivePipelineService::class)->validate('479-26-0T9MTS', fix: true);

    expect($result->fixesApplied)->toBe(1)
        ->and($result->capabilities['validate_fix'])->toBeTrue();

    Event::assertDispatched(MissingTranscriptDetected::class, 1);
    Event::assertDispatched(MissingTranscriptDetected::class, function (MissingTranscriptDetected $event): bool {
        return $event->language === null
            && $event->context->collection === '479-26-0T9MTS';
    });
});

it('does not duplicate manifest entries on repeated ingest', function () {
    Storage::disk('intake')->put('479-26-0T9MTS/new-intake.pdf', 'content');

    $pipeline = app(ArchivePipelineService::class);
    $first = $pipeline->ingest('479-26-0T9MTS', dryRun: false);
    $second = $pipeline->ingest('479-26-0T9MTS', dryRun: false);

    expect($first->registered)->toBe(['new-intake.pdf'])
        ->and($second->registered)->toBe([])
        ->and($second->count)->toBe(0);
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
