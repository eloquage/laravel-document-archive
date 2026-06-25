<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Contracts;

use Eloquage\LaravelDocumentArchive\Data\LocatedSourceFile;

interface SourceFileLocator
{
    public function locate(string $collection, string $sourceFile): ?LocatedSourceFile;
}
