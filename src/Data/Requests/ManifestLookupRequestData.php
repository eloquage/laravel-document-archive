<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Data\Requests;

use Eloquage\LaravelDocumentArchive\Data\Requests\Concerns\ValidatesArchiveRequest;
use Eloquage\LaravelDocumentArchive\Support\CollectionValidation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

class ManifestLookupRequestData extends Data
{
    use ValidatesArchiveRequest;

    public function __construct(
        public string $case,
        public ?string $source_file = null,
        public ?string $canonical_basename = null,
        public ?string $nuipc = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $case = self::resolveCase($input);

        if ($case === '') {
            throw new InvalidArgumentException('Collection parameter is required.');
        }

        $input['case'] = $case;

        try {
            $validated = self::validated($input, [
                'case' => CollectionValidation::requiredRule(),
                'source_file' => 'nullable|string',
                'canonical_basename' => 'nullable|string',
                'nuipc' => 'nullable|string',
            ]);
        } catch (ValidationException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), 0, $exception);
        }

        return new self(
            case: (string) $validated['case'],
            source_file: isset($validated['source_file']) ? (string) $validated['source_file'] : null,
            canonical_basename: isset($validated['canonical_basename']) ? (string) $validated['canonical_basename'] : null,
            nuipc: isset($validated['nuipc']) ? (string) $validated['nuipc'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function resolveCase(array $input): string
    {
        $collection = $input['collection'] ?? null;
        $case = $input['case'] ?? null;

        if (is_string($collection) && $collection !== '') {
            return $collection;
        }

        return is_string($case) ? $case : '';
    }

    /**
     * @return array<string, mixed>
     */
    public static function toJsonSchema(JsonSchema $schema, bool $includeCollectionAlias = true): array
    {
        $enum = CollectionValidation::schemaEnum();

        $caseSchema = $schema->string()->description('Collection directory.')->required();

        if ($enum !== []) {
            $caseSchema = $caseSchema->enum($enum);
        }

        $fields = [
            'case' => $caseSchema,
            'source_file' => $schema->string()->description('Original pre-processing filename.'),
            'canonical_basename' => $schema->string()->description('Canonical basename without suffix.'),
            'nuipc' => $schema->string()->description('Court process number (e.g. 479/26.0T9MTS).'),
        ];

        if ($includeCollectionAlias) {
            $collectionSchema = $schema->string()->description('Collection directory (alias for case).');

            if ($enum !== []) {
                $collectionSchema = $collectionSchema->enum($enum);
            }

            $fields['collection'] = $collectionSchema;
        }

        return $fields;
    }
}
