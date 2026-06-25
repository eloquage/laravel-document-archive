<?php

use Eloquage\LaravelDocumentArchive\Data\ManifestEntry;
use Eloquage\LaravelDocumentArchive\Enums\DocumentStatus;
use Eloquage\LaravelDocumentArchive\Services\ManifestService;
use Eloquage\LaravelDocumentArchive\Support\DocumentPathResolver;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('legacy');
    Storage::fake('manifests');

    $manifest = file_get_contents(__DIR__.'/../Fixtures/evidence_manifest_sample.json');
    Storage::disk('legacy')->put('denuncia-v1/479-26-0T9MTS/evidence_manifest.json', $manifest);
});

it('looks up manifest entry by source file', function () {
    $service = new ManifestService(new DocumentPathResolver);

    $entry = $service->lookup('479-26-0T9MTS', sourceFile: 'PSP_Diligencia_16-10-2025.pdf');

    expect($entry)->not->toBeNull()
        ->and($entry['canonical_basename'])->toBe('2023-03-13-479-26-0T9MTS-AUTO-DE-DECLARACOES-6');
});

it('searches manifest by query substring', function () {
    $service = new ManifestService(new DocumentPathResolver);

    $results = $service->search('479-26-0T9MTS', 'whatsapp');

    expect($results)->toHaveCount(1)
        ->and($results[0]['status'])->toBe('renamed');
});

it('adds a manifest entry', function () {
    $service = new ManifestService(new DocumentPathResolver);

    $service->addEntry('479-26-0T9MTS', new ManifestEntry(
        sourceFile: 'new-file.pdf',
        canonicalBasename: '0000-00-00-479-26-0T9MTS-NEW-FILE',
        status: DocumentStatus::Pending,
    ));

    $entry = $service->lookup('479-26-0T9MTS', sourceFile: 'new-file.pdf');

    expect($entry)->not->toBeNull()
        ->and($entry['status'])->toBe('pending');
});

it('updates manifest entry status by basename', function () {
    $service = new ManifestService(new DocumentPathResolver);

    $service->updateStatus(
        '479-26-0T9MTS',
        '2023-03-13-479-26-0T9MTS-AUTO-DE-DECLARACOES-6',
        DocumentStatus::Renamed,
    );

    $entry = $service->lookup('479-26-0T9MTS', sourceFile: 'PSP_Diligencia_16-10-2025.pdf');

    expect($entry['status'])->toBe('renamed');
});
