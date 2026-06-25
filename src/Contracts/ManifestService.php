<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Contracts;

use Eloquage\LaravelDocumentArchive\Data\ManifestEntry;
use Eloquage\LaravelDocumentArchive\Enums\DocumentStatus;

interface ManifestService
{
    /**
     * @return array<string, mixed>|null
     */
    public function lookup(
        string $collection,
        ?string $sourceFile = null,
        ?string $canonicalBasename = null,
        ?string $nuipc = null,
    ): ?array;

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
    ): array;

    /**
     * @return array<string, mixed>
     */
    public function allEntries(string $collection): array;

    public function hasSourceFile(string $collection, string $sourceFile): bool;

    public function addEntry(string $collection, ManifestEntry $entry): void;

    public function updateStatus(string $collection, string $basename, DocumentStatus $status): void;

    /**
     * @return array{source_file: string, canonical_basename: string, status: string, document_date: string}
     */
    public function buildPendingEntry(string $collection, string $sourceFile): array;

    /**
     * @param  list<string>  $outputs
     */
    public function markSuccess(
        string $collection,
        string $sourceFile,
        string $basename,
        string $extension,
        string $tier,
        array $outputs,
    ): void;

    /**
     * @param  array<string, array<string, mixed>>  $files
     */
    public function writeEntries(string $collection, array $files): void;
}
