<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Data;

class IngestResult
{
    /**
     * @param  list<string>  $newFiles
     * @param  list<string>  $registered
     * @param  array<string, bool>  $capabilities
     */
    public function __construct(
        public bool $success,
        public bool $dryRun,
        public int $count,
        public array $newFiles,
        public array $registered,
        public string $message,
        public array $capabilities,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'dry_run' => $this->dryRun,
            'count' => $this->count,
            'new_files' => $this->newFiles,
            'registered' => $this->registered,
            'message' => $this->message,
            'capabilities' => $this->capabilities,
        ];
    }
}
