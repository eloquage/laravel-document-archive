# Laravel Document Archive

Filesystem document archive with JSON manifest, bilingual transcripts, ingest/validate pipeline, and special-source readers (WhatsApp/Facebook).

This package handles **storage and manifest I/O only**. It does not include AI, OCR, vector search, or MCP. Pair with [`eloquage/laravel-document-processing`](https://github.com/eloquage/laravel-document-processing) for OCR/translation and [`eloquage/laravel-vector-search`](https://github.com/eloquage/laravel-vector-search) for semantic search.

## Installation

```bash
composer require eloquage/laravel-document-archive
```

Publish configuration:

```bash
php artisan vendor:publish --tag=laravel-document-archive-config
```

Publish the disk stub and merge into `config/filesystems.php`:

```bash
php artisan vendor:publish --tag=laravel-document-archive-config
```

Copy the `disks` array from `config/document-archive-disks.php` into your application's `config/filesystems.php`.

## Configuration

Define collections in `config/document-archive.php` (config key `cases` for backward compatibility):

```php
'cases' => [
    '479-26-0T9MTS' => [
        'nuipc' => '479/26.0T9MTS',
        'label' => 'Counter-complaint',
    ],
    '384-22-0PIPRT' => [
        'nuipc' => '384/22.0PIPRT',
        'label' => 'Domestic violence complaint',
    ],
],
```

Default disks (override via env):

| Key | Env | Default disk name |
|-----|-----|-------------------|
| intake | `DOCUMENT_ARCHIVE_INTAKE_DISK` | `intake` |
| manifests | `DOCUMENT_ARCHIVE_MANIFESTS_DISK` | `manifests` |
| collections | `DOCUMENT_ARCHIVE_COLLECTIONS_DISK` | `collections` |
| legacy | `DOCUMENT_ARCHIVE_LEGACY_DISK` | `legacy` |

## Usage

### Ingest new files from intake disk

```php
use Eloquage\LaravelDocumentArchive\Services\ArchivePipelineService;

$pipeline = app(ArchivePipelineService::class);

// Dry run (default)
$result = $pipeline->ingest('479-26-0T9MTS', dryRun: true);

// Register new files in manifest
$result = $pipeline->ingest('479-26-0T9MTS', dryRun: false);
```

### Validate manifest vs transcripts

```php
$report = $pipeline->validate('479-26-0T9MTS');

// Dispatch MissingTranscriptDetected events for fixable issues
$report = $pipeline->validate('479-26-0T9MTS', fix: true);
```

### Listen for missing transcripts

```php
use Eloquage\LaravelDocumentArchive\Events\MissingTranscriptDetected;

Event::listen(MissingTranscriptDetected::class, function (MissingTranscriptDetected $event) {
    // e.g. queue OCR/translation for $event->sourceFile in $event->collection
});
```

### Manifest and transcript I/O

```php
use Eloquage\LaravelDocumentArchive\Contracts\ManifestService;
use Eloquage\LaravelDocumentArchive\Contracts\TranscriptService;

$manifest = app(ManifestService::class);
$entry = $manifest->lookup('479-26-0T9MTS', sourceFile: 'document.pdf');

$transcripts = app(TranscriptService::class);
$english = $transcripts->read('479-26-0T9MTS', $entry['canonical_basename'], 'en');
$transcripts->write('479-26-0T9MTS', $entry['canonical_basename'], 'pt', $portugueseMarkdown);
```

## Testing

```bash
composer test
composer analyse
composer format
```

## License

MIT
