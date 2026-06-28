# Laravel Document Archive

Core filesystem archive: manifests, bilingual transcripts, ingest/validate pipeline.

## Services

| Service | Role |
|---------|------|
| `ArchivePipelineService` | Ingest intake files; validate manifest vs transcripts |
| `ManifestService` | JSON manifest CRUD, lookup, search |
| `TranscriptService` | Read/write `-EN.md` / `-PT.md` transcripts |
| `SourceFileLocator` | Find original source across configured disks |
| `SpecialSourceService` | WhatsApp chat + Facebook HTML export readers |
| `DocumentArchiveTranscriptReader` | Resolve collection by basename; read with truncation |
| `NullDocumentArchiveSearchService` | Default search stub (install vector bridge for real search) |

## ArchiveContext

Shared value object passed in events and downstream jobs:

```php
use Eloquage\LaravelDocumentArchive\Data\ArchiveContext;

$context = ArchiveContext::forCollection('479-26-0T9MTS')
->withBasename('2023-03-13-479-26-0T9MTS-SCAN')
->withSourceFile('scan.pdf');
```

## Config keys

| Key | Default | Notes |
|-----|---------|-------|
| `document-archive.cases` | `[]` | Collection definitions (canonical key) |
| `document-archive.vector_index` | `document-archive` | For downstream vector bridge |
| `document-archive.languages` | `pt`, `en` | Validate checks both |
| `document-archive.indexing.*` | — | Consumed by vector/processing packages |

## Events

- `MissingTranscriptDetected` — one event per manifest entry when `validate(fix: true)`; `language` is null
- `TranscriptWritten` — after transcript write
- `ManifestEntryUpdated` / `ManifestEntryRemoved` — manifest mutations

## Security

- Path segments validated via `SafePathSegment` (rejects `..`, null bytes, absolute paths)
- Manifest entries validated on load/write (`ManifestSchemaValidator` v1)

## Integrations

- **processing:** listens to `MissingTranscriptDetected`
- **document-archive-mcp:** 12 MCP tools
- **document-archive-ai:** correlation agent + tools
- **document-archive-vector:** replaces `DocumentArchiveSearchContract`; indexes on `TranscriptWritten`
