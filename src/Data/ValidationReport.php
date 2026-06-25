<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Data;

class ValidationReport
{
    /**
     * @param  list<array<string, string>>  $issues
     * @param  array<string, bool>  $capabilities
     */
    public function __construct(
        public bool $success,
        public int $count,
        public array $issues,
        public bool $fixed,
        public int $fixesApplied,
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
            'count' => $this->count,
            'issues' => $this->issues,
            'fixed' => $this->fixed,
            'fixes_applied' => $this->fixesApplied,
            'message' => $this->message,
            'capabilities' => $this->capabilities,
        ];
    }
}
