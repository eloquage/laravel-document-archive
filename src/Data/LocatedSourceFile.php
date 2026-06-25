<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Data;

readonly class LocatedSourceFile
{
    public function __construct(
        public string $disk,
        public string $relativePath,
        public string $extension,
    ) {}
}
