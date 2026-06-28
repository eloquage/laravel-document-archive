<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Data\Requests\Concerns;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait ValidatesArchiveRequest
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $rules
     */
    protected static function validated(array $input, array $rules): array
    {
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
