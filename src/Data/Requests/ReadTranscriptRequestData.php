<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Data\Requests;

use Eloquage\LaravelDocumentArchive\Data\Requests\Concerns\ValidatesArchiveRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

class ReadTranscriptRequestData extends Data
{
    use ValidatesArchiveRequest;

    public function __construct(
        public string $canonical_basename,
        public string $language = 'en',
        public ?int $start_line = null,
        public ?int $max_chars = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        try {
            $validated = self::validated($input, [
                'canonical_basename' => 'required|string',
                'language' => 'nullable|string|in:en,pt',
                'start_line' => 'nullable|integer|min:1',
                'max_chars' => 'nullable|integer|min:1',
            ]);
        } catch (ValidationException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), 0, $exception);
        }

        return new self(
            canonical_basename: (string) $validated['canonical_basename'],
            language: (string) ($validated['language'] ?? 'en'),
            start_line: isset($validated['start_line']) ? (int) $validated['start_line'] : null,
            max_chars: isset($validated['max_chars']) ? (int) $validated['max_chars'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toJsonSchema(JsonSchema $schema): array
    {
        return [
            'canonical_basename' => $schema->string()->description('Canonical basename without language suffix.')->required(),
            'language' => $schema->string()->description('en or pt.')->enum(['en', 'pt']),
            'start_line' => $schema->integer()->description('Start reading from this line (1-based).'),
            'max_chars' => $schema->integer()->description('Maximum characters to return.'),
        ];
    }
}
