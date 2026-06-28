<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Data;

readonly class TranscriptProcessedData
{
    /**
     * @param  list<string>  $stagesCompleted
     */
    public function __construct(
        public string $collection,
        public string $sourceFile,
        public string $canonicalBasename,
        public string $tier,
        public array $stagesCompleted,
        public bool $skippedOcr,
        public bool $skippedTranslate,
        public ?float $ocrQualityScore,
        public ?float $translationQualityScore,
        public int $durationMs,
    ) {}
}
