<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Data\Requests;

use Eloquage\LaravelDocumentArchive\Data\Requests\Concerns\ValidatesArchiveRequest;
use Eloquage\LaravelDocumentArchive\Support\CollectionValidation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

class SemanticSearchRequestData extends Data
{
    use ValidatesArchiveRequest;

    public function __construct(
        public string $query,
        public ?string $case = null,
        public int $limit = 10,
        public ?float $min_score = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input, int $defaultLimit = 10): self
    {
        try {
            $validated = self::validated($input, [
                'query' => 'required|string',
                'case' => CollectionValidation::optionalRule(),
                'limit' => 'nullable|integer|min:1|max:50',
                'min_score' => 'nullable|numeric|min:0|max:1',
            ]);
        } catch (ValidationException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), 0, $exception);
        }

        $minScore = $validated['min_score'] ?? null;

        return new self(
            query: (string) $validated['query'],
            case: isset($validated['case']) ? (string) $validated['case'] : null,
            limit: isset($validated['limit']) ? (int) $validated['limit'] : $defaultLimit,
            min_score: is_numeric($minScore) ? (float) $minScore : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toJsonSchema(JsonSchema $schema): array
    {
        $collections = CollectionValidation::schemaEnum();

        $caseSchema = $schema->string()->description('Optional case filter.');

        if ($collections !== []) {
            $caseSchema = $caseSchema->enum($collections);
        }

        return [
            'query' => $schema->string()->description('Natural language search query.')->required(),
            'case' => $caseSchema,
            'limit' => $schema->integer()->description('Maximum results.'),
            'min_score' => $schema->number()->description('Minimum similarity score (0-1).'),
        ];
    }
}
