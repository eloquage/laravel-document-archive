<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Renamed = 'renamed';
}
