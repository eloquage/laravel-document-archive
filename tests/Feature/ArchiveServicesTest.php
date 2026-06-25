<?php

use Eloquage\LaravelDocumentArchive\Contracts\ManifestService;
use Eloquage\LaravelDocumentArchive\Contracts\SourceFileLocator;
use Eloquage\LaravelDocumentArchive\Contracts\TranscriptService;
use Eloquage\LaravelDocumentArchive\Exceptions\TranscriptNotFoundException;
use Eloquage\LaravelDocumentArchive\Services\SpecialSourceService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('intake');
    Storage::fake('collections');
    Storage::fake('manifests');
    Storage::fake('legacy');
});

it('writes pt transcript to collections disk', function () {
    $collection = '479-26-0T9MTS';
    $basename = '0000-00-00-479-26-0T9MTS-NOTES';

    app(TranscriptService::class)->write($collection, $basename, 'pt', 'Texto português.');

    expect(Storage::disk('collections')->exists("{$collection}/{$basename}-PT.md"))->toBeTrue()
        ->and(Storage::disk('collections')->get("{$collection}/{$basename}-PT.md"))->toBe('Texto português.');
});

it('reads transcript from collections disk', function () {
    $collection = '479-26-0T9MTS';
    $basename = '0000-00-00-479-26-0T9MTS-NOTES';

    app(TranscriptService::class)->write($collection, $basename, 'en', 'English text.');

    expect(app(TranscriptService::class)->read($collection, $basename, 'en'))->toBe('English text.');
});

it('throws when transcript is missing', function () {
    app(TranscriptService::class)->read('479-26-0T9MTS', 'missing-basename', 'en');
})->throws(TranscriptNotFoundException::class);

it('locates source file on intake disk', function () {
    $collection = '479-26-0T9MTS';
    $sourceFile = 'notes.txt';

    Storage::disk('intake')->put("{$collection}/{$sourceFile}", 'content');

    $located = app(SourceFileLocator::class)->locate($collection, $sourceFile);

    expect($located)->not->toBeNull()
        ->and($located->disk)->toBe('intake')
        ->and($located->relativePath)->toBe("{$collection}/{$sourceFile}")
        ->and($located->extension)->toBe('txt');
});

it('marks manifest entry as success', function () {
    $collection = '479-26-0T9MTS';
    $sourceFile = 'notes.txt';
    $basename = '0000-00-00-479-26-0T9MTS-NOTES';

    Storage::disk('manifests')->put("{$collection}.json", json_encode([
        'files' => [
            $sourceFile => [
                'source_file' => $sourceFile,
                'canonical_basename' => $basename,
                'status' => 'pending',
            ],
        ],
    ], JSON_PRETTY_PRINT));

    app(ManifestService::class)->markSuccess(
        $collection,
        $sourceFile,
        $basename,
        'txt',
        'complex',
        ["{$basename}.txt", "{$basename}-PT.md", "{$basename}-EN.md"],
    );

    $entry = app(ManifestService::class)->lookup($collection, $sourceFile);

    expect($entry['status'] ?? null)->toBe('success')
        ->and($entry['model'] ?? null)->toBe('complex');
});

it('reads whatsapp messages from legacy disk', function () {
    Storage::disk('legacy')->put(
        'denuncia-v1/479-26-0T9MTS/WHATSAPP-CHAT-test.txt',
        "2024-01-01 Hello\n2024-01-02 World",
    );

    $result = app(SpecialSourceService::class)->readWhatsApp('479-26-0T9MTS', query: 'hello');

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(1);
});

it('requires collection for facebook reader', function () {
    Storage::disk('legacy')->put(
        'denuncia-v1/479-26-0T9MTS/Facebook_Conversa_Mimi_Morticia/PROVA_C15_Mensagens_Facebook_Completas.html',
        '<div class="message">2024-01-01 Test participant message</div>',
    );

    $result = app(SpecialSourceService::class)->readFacebook('479-26-0T9MTS', query: 'participant');

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(1);
});
