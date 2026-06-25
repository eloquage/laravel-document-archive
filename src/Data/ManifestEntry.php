<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Data;

use Eloquage\LaravelDocumentArchive\Enums\DocumentStatus;

class ManifestEntry
{
    /**
     * @param  list<string>  $outputs
     */
    public function __construct(
        public string $sourceFile,
        public string $canonicalBasename,
        public DocumentStatus $status,
        public string $documentDate = '0000-00-00',
        public array $outputs = [],
    ) {}

    /**
     * @param  array<string, mixed>  $entry
     */
    public static function fromArray(string $key, array $entry): self
    {
        $status = DocumentStatus::tryFrom((string) ($entry['status'] ?? 'pending'))
            ?? DocumentStatus::Pending;

        $outputs = $entry['outputs'] ?? [];

        return new self(
            sourceFile: (string) ($entry['source_file'] ?? $key),
            canonicalBasename: (string) ($entry['canonical_basename'] ?? ''),
            status: $status,
            documentDate: (string) ($entry['document_date'] ?? '0000-00-00'),
            outputs: is_array($outputs) ? array_values(array_map('strval', $outputs)) : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_file' => $this->sourceFile,
            'canonical_basename' => $this->canonicalBasename,
            'status' => $this->status->value,
            'document_date' => $this->documentDate,
            'outputs' => $this->outputs,
        ];
    }
}
