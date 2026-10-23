<p align="center"><img src="art/banner.png" alt="Laravel Document Archive" width="100%"></p>

# Laravel Document Archive

[![Tests](https://github.com/eloquage/laravel-document-archive/actions/workflows/run-tests.yml/badge.svg)](https://github.com/eloquage/laravel-document-archive/actions/workflows/run-tests.yml)
[![License](https://img.shields.io/github/license/eloquage/laravel-document-archive)](LICENSE)

A Laravel package for archiving source files, validating JSON manifests, and reading bilingual transcripts. Ingesting files is idempotent; AI, OCR, search, and MCP tools are separate packages.

**Requirements:** PHP 8.4+ and Laravel 11, 12, or 13.

This package handles **storage and manifest I/O only**. It does not include AI, OCR, vector search, or MCP. Pair with:

| Package                                                                                                   | Purpose                         |
| --------------------------------------------------------------------------------------------------------- | ------------------------------- |
| [`eloquage/laravel-document-processing`](https://github.com/eloquage/laravel-document-processing)         | OCR + translation pipeline      |
| [`eloquage/laravel-document-archive-vector`](https://github.com/eloquage/laravel-document-archive-vector) | Semantic/fulltext search bridge |
| [`eloquage/laravel-document-archive-mcp`](https://github.com/eloquage/laravel-document-archive-mcp)       | MCP tools for agents            |
| [`eloquage/laravel-document-archive-ai`](https://github.com/eloquage/laravel-document-archive-ai)         | Laravel AI SDK agents/tools     |

## Installation

```bash
composer require eloquage/laravel-document-archive
```

Publish configuration:

```bash
php artisan vendor:publish --tag=laravel-document-archive-config
```

Merge the disk stub from `config/document-archive-disks.php` into `config/filesystems.php`.

## Quick start

Register a collection in `config/document-archive.php`:

```php
return [
    'cases' => [
        '479-26-0T9MTS' => [
            'nuipc' => '479/26.0T9MTS',
            'label' => 'Counter-complaint',
        ],
    ],
];
```

Place new files under the configured intake disk, then run an ingest and read the resulting manifest/transcript:

```php
use Eloquage\LaravelDocumentArchive\Services\ArchivePipelineService;
use Eloquage\LaravelDocumentArchive\Contracts\ManifestService;
use Eloquage\LaravelDocumentArchive\Contracts\TranscriptService;

app(ArchivePipelineService::class)->ingest('479-26-0T9MTS');
$entry = app(ManifestService::class)->lookup('479-26-0T9MTS', sourceFile: 'scan.pdf');
$text = app(TranscriptService::class)->read(
    '479-26-0T9MTS',
    $entry['canonical_basename'],
    'en',
);
```

The package stores files and manifest data; it does not perform OCR or translation.

## Disk layout

```
intake/                          # New files awaiting ingest
  {collection}/scan.pdf

manifests/                       # Preferred manifest location
  {collection}.json

collections/                     # Transcript output (configurable disk)
  {collection}/{basename}-EN.md
  {collection}/{basename}-PT.md

legacy/{prefix}/                 # Pre-migration imports (default prefix: denuncia-v1)
  {collection}/evidence_manifest.json
  {collection}/{basename}-EN.md
  {collection}/*WHATSAPP-CHAT*.txt
  {collection}/Facebook_.../export.html
```

Default disks (override via env):

| Key         | Env                                 | Default     |
| ----------- | ----------------------------------- | ----------- |
| intake      | `DOCUMENT_ARCHIVE_INTAKE_DISK`      | `intake`    |
| manifests   | `DOCUMENT_ARCHIVE_MANIFESTS_DISK`   | `manifests` |
| collections | `DOCUMENT_ARCHIVE_COLLECTIONS_DISK` | `cases`     |
| legacy      | `DOCUMENT_ARCHIVE_LEGACY_DISK`      | `legacy`    |

## Configuration

Define collections in `config/document-archive.php` (key `cases` for backward compatibility):

```php
'cases' => [
    '479-26-0T9MTS' => [
        'nuipc' => '479/26.0T9MTS',
        'label' => 'Counter-complaint',
    ],
],
```

Use `Eloquage\LaravelDocumentArchive\Support\CollectionValidation` for form/MCP enum rules.

## Manifest JSON schema (v1)

Top-level shape:

```json
{
  "files": {
    "source-filename.pdf": {
      "source_file": "source-filename.pdf",
      "canonical_basename": "2025-01-01-collection-SCAN",
      "status": "pending"
    }
  }
}
```

| Field                | Type     | Required | Notes                                |
| -------------------- | -------- | -------- | ------------------------------------ |
| `source_file`        | string   | yes      | Safe path segment; no `..`           |
| `canonical_basename` | string   | yes      | e.g. `2023-03-13-479-26-0T9MTS-SCAN` |
| `status`             | string   | yes      | `pending`, `success`, `renamed`      |
| `document_date`      | string   | no       | `YYYY-MM-DD` or `0000-00-00`         |
| `outputs`            | string[] | no       | Generated artifact filenames         |
| `model`              | string   | no       | Processing tier used                 |

Example entry:

```json
{
    "source_file": "PSP_Diligencia_16-10-2025.pdf",
    "canonical_basename": "2023-03-13-479-26-0T9MTS-AUTO-DE-DECLARACOES-6",
    "status": "success",
    "document_date": "2023-03-13",
    "outputs": [
        "2023-03-13-479-26-0T9MTS-AUTO-DE-DECLARACOES-6-PT.md",
        "2023-03-13-479-26-0T9MTS-AUTO-DE-DECLARACOES-6-EN.md"
    ]
}
```

Validation runs on manifest load and before writes via `ManifestSchemaValidator`.

## Usage

### Ingest

```php
use Eloquage\LaravelDocumentArchive\Services\ArchivePipelineService;

$pipeline = app(ArchivePipelineService::class);
$result = $pipeline->ingest('479-26-0T9MTS', dryRun: false); // idempotent: skips existing manifest keys
```

### Validate

```php
$report = $pipeline->validate('479-26-0T9MTS');
$report = $pipeline->validate('479-26-0T9MTS', fix: true); // dispatches one MissingTranscriptDetected per entry
```

### Manifest and transcripts

```php
use Eloquage\LaravelDocumentArchive\Contracts\ManifestService;
use Eloquage\LaravelDocumentArchive\Contracts\TranscriptService;

$manifest = app(ManifestService::class);
$entry = $manifest->lookup('479-26-0T9MTS', sourceFile: 'document.pdf');

$transcripts = app(TranscriptService::class);
$english = $transcripts->read('479-26-0T9MTS', $entry['canonical_basename'], 'en');
```

### Source file locator

```php
use Eloquage\LaravelDocumentArchive\Contracts\SourceFileLocator;

$located = app(SourceFileLocator::class)->locate('479-26-0T9MTS', 'scan.pdf');
// LocatedSourceFile: disk, relativePath, extension
```

### Special sources

```php
use Eloquage\LaravelDocumentArchive\Services\SpecialSourceService;

$whatsapp = app(SpecialSourceService::class)->readWhatsApp('479-26-0T9MTS', query: 'hello');
$facebook = app(SpecialSourceService::class)->readFacebook('479-26-0T9MTS', query: 'participant');
```

Configure paths in `document-archive.special_sources`.

### Transcript reader (basename → collection resolution)

```php
use Eloquage\LaravelDocumentArchive\Services\DocumentArchiveTranscriptReader;

$result = app(DocumentArchiveTranscriptReader::class)->read('2023-03-13-479-26-0T9MTS-SCAN', 'en', maxChars: 5000);
```

## Events

All events expose `ArchiveContext $context` plus backward-compatible scalar properties.

| Event                       | When                                           | Key payload                                                                                         |
| --------------------------- | ---------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| `MissingTranscriptDetected` | `validate(fix: true)` finds missing transcript | `collection`, `canonicalBasename`, `sourceFile`, `expectedPath`; `language` is null (all languages) |
| `TranscriptWritten`         | `TranscriptService::write()`                   | `collection`, `canonicalBasename`, `language`                                                       |
| `ManifestEntryUpdated`      | Manifest status/output change                  | `collection`, `canonicalBasename`, `entry`                                                          |
| `ManifestEntryRemoved`      | Manifest entry deleted                         | `collection`, `canonicalBasename`, `sourceFile`                                                     |

### MissingTranscriptDetected listener (processing)

```php
use Eloquage\LaravelDocumentArchive\Events\MissingTranscriptDetected;

Event::listen(MissingTranscriptDetected::class, function (MissingTranscriptDetected $event) {
    // $event->context->collection, ->sourceFile
    // laravel-document-processing queues ProcessDocumentJob when installed
});
```

## Search (optional)

Default binding is `NullDocumentArchiveSearchService` (returns `status: skipped`). Install `eloquage/laravel-document-archive-vector` to enable semantic search.

```php
use Eloquage\LaravelDocumentArchive\Support\ArchiveCapabilities;

ArchiveCapabilities::searchEnabled(); // true when vector bridge installed
```

Index name: `document-archive.vector_index` (default `document-archive`).

## Integration map

| Consumer                    | Uses                                                                                   |
| --------------------------- | -------------------------------------------------------------------------------------- |
| **processing**              | `MissingTranscriptDetected` → `ProcessDocumentJob` with `ArchiveContext`               |
| **document-archive-mcp**    | 12 tools: manifest, transcript, search, pipeline, indexing                             |
| **document-archive-ai**     | `CorrelationAgent`, `ManifestLookupTool`, `SimilaritySearchTool`, `ReadTranscriptTool` |
| **document-archive-vector** | `TranscriptWritten` / `ManifestEntryUpdated` listeners, search contract                |
| **notebooklm**              | Soft coupling: read transcripts via MCP, sync to NotebookLM via `notebooklm_sync`      |

## Third-party packages (optional)

| Package                                | Recommendation                                                       |
| -------------------------------------- | -------------------------------------------------------------------- |
| `spatie/laravel-data`                  | Optional typed DTOs (`ManifestEntryData`) when installed             |
| `spatie/laravel-schemaless-attributes` | Host-app metadata only (manifests are JSON files)                    |
| `spatie/laravel-event-sourcing`        | Not required; domain events suffice                                  |
| `spatie/flysystem-dropbox`             | Configure in `document-archive.disks.source_search`                  |
| `spatie/laravel-backup`                | Backup `manifests` + `collections` disks; exclude transient `intake` |

## Testing

```bash
composer test
composer analyse
composer format
```

## License

MIT
