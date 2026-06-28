<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Events;

use Eloquage\LaravelDocumentArchive\Data\ArchiveContext;
use Eloquage\LaravelDocumentArchive\Data\TranscriptProcessedData;
use Illuminate\Foundation\Events\Dispatchable;

class TranscriptProcessed
{
    use Dispatchable;

    public function __construct(
        public TranscriptProcessedData $result,
        public readonly ArchiveContext $context,
    ) {}
}
