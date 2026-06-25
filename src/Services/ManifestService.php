<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Services;

use Eloquage\LaravelDocumentArchive\Contracts\ManifestService as ManifestServiceContract;
use Eloquage\LaravelDocumentArchive\Data\ManifestEntry;
use Eloquage\LaravelDocumentArchive\Enums\DocumentStatus;
use Eloquage\LaravelDocumentArchive\Exceptions\ManifestNotFoundException;
use Eloquage\LaravelDocumentArchive\Support\DocumentPathResolver;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ManifestService implements ManifestServiceContract
{
    /** @var array<string, array<string, mixed>> */
    private array $cache = [];

    public function __construct(
        private DocumentPathResolver $pathResolver,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function lookup(
        string $collection,
        ?string $sourceFile = null,
        ?string $canonicalBasename = null,
        ?string $nuipc = null,
    ): ?array {
        $entries = $this->allEntries($collection);

        foreach ($entries as $key => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if ($sourceFile !== null && ($entry['source_file'] ?? $key) === $sourceFile) {
                return $this->enrichEntry($key, $entry, $collection);
            }

            if ($canonicalBasename !== null && ($entry['canonical_basename'] ?? null) === $canonicalBasename) {
                return $this->enrichEntry($key, $entry, $collection);
            }

            if ($nuipc !== null) {
                $collectionNuipc = config("document-archive.cases.{$collection}.nuipc")
                    ?? config("document-archive.collections.{$collection}.nuipc");

                if ($collectionNuipc === $nuipc || str_contains((string) ($entry['canonical_basename'] ?? ''), str_replace('/', '-', $nuipc))) {
                    return $this->enrichEntry($key, $entry, $collection);
                }
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(
        string $collection,
        string $query,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $limit = 20,
    ): array {
        $queryLower = strtolower($query);
        $results = [];

        foreach ($this->allEntries($collection) as $key => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if ($status !== null && ($entry['status'] ?? null) !== $status) {
                continue;
            }

            $documentDate = (string) ($entry['document_date'] ?? '');

            if ($dateFrom !== null && $documentDate !== '' && $documentDate < $dateFrom) {
                continue;
            }

            if ($dateTo !== null && $documentDate !== '' && $documentDate > $dateTo) {
                continue;
            }

            $haystack = strtolower(json_encode($entry).$key);

            if ($queryLower !== '' && ! str_contains($haystack, $queryLower)) {
                continue;
            }

            $results[] = $this->enrichEntry($key, $entry, $collection);

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    public function allEntries(string $collection): array
    {
        if (isset($this->cache[$collection])) {
            return $this->cache[$collection];
        }

        $this->pathResolver->validateCollection($collection);
        $disk = $this->pathResolver->manifestDiskName($collection);
        $relative = $this->pathResolver->manifestRelativePath($collection);

        if (! Storage::disk($disk)->exists($relative)) {
            throw new ManifestNotFoundException("Manifest not found for collection {$collection} at {$disk}:{$relative}");
        }

        $decoded = json_decode((string) Storage::disk($disk)->get($relative), true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException("Invalid manifest JSON for collection {$collection}");
        }

        $files = $decoded['files'] ?? $decoded;

        if (! is_array($files)) {
            throw new InvalidArgumentException("Unexpected manifest structure for collection {$collection}");
        }

        /** @var array<string, mixed> $files */
        return $this->cache[$collection] = $files;
    }

    public function hasSourceFile(string $collection, string $sourceFile): bool
    {
        $entries = $this->allEntries($collection);

        foreach ($entries as $key => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (($entry['source_file'] ?? $key) === $sourceFile) {
                return true;
            }
        }

        return false;
    }

    public function addEntry(string $collection, ManifestEntry $entry): void
    {
        $entries = $this->allEntries($collection);
        $entries[$entry->sourceFile] = $entry->toArray();
        $this->writeEntries($collection, $entries);
    }

    public function updateStatus(string $collection, string $basename, DocumentStatus $status): void
    {
        $entries = $this->allEntries($collection);
        $updated = false;

        foreach ($entries as $key => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (($entry['canonical_basename'] ?? null) !== $basename) {
                continue;
            }

            $entries[$key]['status'] = $status->value;
            $updated = true;

            break;
        }

        if (! $updated) {
            throw new InvalidArgumentException("No manifest entry found for basename: {$basename}");
        }

        $this->writeEntries($collection, $entries);
    }

    /**
     * @return array{source_file: string, canonical_basename: string, status: string, document_date: string}
     */
    public function buildPendingEntry(string $collection, string $sourceFile): array
    {
        $basename = pathinfo($sourceFile, PATHINFO_FILENAME);
        $slug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $basename) ?? 'INTAKE');
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'INTAKE';
        }

        return [
            'source_file' => $sourceFile,
            'canonical_basename' => "0000-00-00-{$collection}-{$slug}",
            'status' => DocumentStatus::Pending->value,
            'document_date' => '0000-00-00',
        ];
    }

    public function markSuccess(
        string $collection,
        string $sourceFile,
        string $basename,
        string $extension,
        string $tier,
        array $outputs,
    ): void {
        $entries = $this->allEntries($collection);
        $key = $sourceFile;

        foreach ($entries as $entryKey => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (($entry['source_file'] ?? $entryKey) === $sourceFile) {
                $key = $entryKey;

                break;
            }
        }

        $entries[$key] = array_merge(
            is_array($entries[$key] ?? null) ? $entries[$key] : [],
            [
                'source_file' => $sourceFile,
                'canonical_basename' => $basename,
                'status' => DocumentStatus::Success->value,
                'outputs' => $outputs,
                'model' => $tier,
            ],
        );

        /** @var array<string, array<string, mixed>> $entries */
        $this->writeEntries($collection, $entries);
    }

    /**
     * @param  array<string, array<string, mixed>>  $files
     */
    public function writeEntries(string $collection, array $files): void
    {
        $this->pathResolver->validateCollection($collection);
        $disk = $this->pathResolver->manifestDiskName($collection);
        $relative = $this->pathResolver->manifestRelativePath($collection);

        $payload = ['files' => $files];

        Storage::disk($disk)->put(
            $relative,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        );

        unset($this->cache[$collection]);
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    private function enrichEntry(string $key, array $entry, string $collection): array
    {
        return array_merge($entry, [
            'manifest_key' => $key,
            'collection' => $collection,
            'source_file' => $entry['source_file'] ?? $key,
        ]);
    }
}
