<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Support;

use Eloquage\LaravelDocumentArchive\Enums\DocumentStatus;
use InvalidArgumentException;

class ManifestSchemaValidator
{
    public const string VERSION = 'v1';

    /**
     * @return list<string>
     */
    public function validateEntry(string $key, mixed $entry): array
    {
        $errors = [];

        if (! is_array($entry)) {
            return ["Entry '{$key}' must be an object."];
        }

        try {
            SafePathSegment::assert((string) ($entry['source_file'] ?? $key), 'source_file');
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        }

        $basename = $entry['canonical_basename'] ?? null;

        if (! is_string($basename) || $basename === '') {
            $errors[] = "Entry '{$key}' is missing canonical_basename.";
        } else {
            try {
                SafePathSegment::assertBasename($basename);
            } catch (InvalidArgumentException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        $status = (string) ($entry['status'] ?? '');

        if ($status !== '' && DocumentStatus::tryFrom($status) === null) {
            $errors[] = "Entry '{$key}' has invalid status: {$status}.";
        }

        $documentDate = $entry['document_date'] ?? null;

        if ($documentDate !== null && ! is_string($documentDate)) {
            $errors[] = "Entry '{$key}' document_date must be a string.";
        } elseif (is_string($documentDate) && $documentDate !== '' && ! $this->isValidDocumentDate($documentDate)) {
            $errors[] = "Entry '{$key}' has invalid document_date: {$documentDate}.";
        }

        $outputs = $entry['outputs'] ?? [];

        if ($outputs !== [] && ! is_array($outputs)) {
            $errors[] = "Entry '{$key}' outputs must be an array.";
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $files
     * @return array{valid: array<string, array<string, mixed>>, invalid: list<array{key: string, errors: list<string>}>}
     */
    public function validateFiles(array $files): array
    {
        $valid = [];
        $invalid = [];

        foreach ($files as $key => $entry) {
            if (! is_string($key)) {
                continue;
            }

            try {
                SafePathSegment::assert($key, 'manifest key');
            } catch (InvalidArgumentException $exception) {
                $invalid[] = ['key' => (string) $key, 'errors' => [$exception->getMessage()]];

                continue;
            }

            $errors = $this->validateEntry($key, $entry);

            if ($errors !== []) {
                $invalid[] = ['key' => $key, 'errors' => $errors];

                continue;
            }

            /** @var array<string, mixed> $entry */
            $valid[$key] = $entry;
        }

        return ['valid' => $valid, 'invalid' => $invalid];
    }

    private function isValidDocumentDate(string $date): bool
    {
        if ($date === '0000-00-00') {
            return true;
        }

        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    }
}
