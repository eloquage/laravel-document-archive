<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Services;

use Eloquage\LaravelDocumentArchive\Support\DocumentPathResolver;
use Eloquage\LaravelDocumentArchive\Support\SafePathSegment;
use Eloquage\LaravelDocumentArchive\Support\Utf8Text;
use Illuminate\Support\Facades\Storage;

class SpecialSourceService
{
    public function __construct(
        private DocumentPathResolver $pathResolver,
    ) {}

    /**
     * @return array{success: bool, messages?: list<string>, count?: int, error?: string}
     */
    public function readWhatsApp(
        string $collection,
        ?string $party = null,
        ?string $canonicalBasename = null,
        ?string $date = null,
        ?string $query = null,
        int $limit = 50,
    ): array {
        $this->pathResolver->validateCollection($collection);

        if ($canonicalBasename !== null) {
            SafePathSegment::assertBasename($canonicalBasename);
        }

        $prefix = config('document-archive.legacy_prefix', 'denuncia-v1');
        $prefix = is_string($prefix) ? $prefix : 'denuncia-v1';
        $directory = "{$prefix}/{$collection}";
        $globPattern = config('document-archive.special_sources.whatsapp_glob', '*WHATSAPP-CHAT*.txt');
        $globPattern = is_string($globPattern) ? $globPattern : '*WHATSAPP-CHAT*.txt';
        $needle = strtoupper(str_replace(['*', '.txt'], '', $globPattern));

        $legacyDisk = config('document-archive.disks.legacy', 'legacy');
        $legacyDisk = is_string($legacyDisk) ? $legacyDisk : 'legacy';
        $files = Storage::disk($legacyDisk)->allFiles($directory);
        $matches = [];

        foreach ($files as $file) {
            if (! str_contains(strtoupper($file), $needle) || ! str_ends_with($file, '.txt')) {
                continue;
            }

            if ($canonicalBasename !== null && ! str_contains($file, $canonicalBasename)) {
                continue;
            }

            if ($party !== null && ! str_contains(strtolower($file), strtolower($party))) {
                continue;
            }

            $content = Utf8Text::sanitize((string) Storage::disk($legacyDisk)->get($file));
            $lines = $this->filterLines($content, $date, $query);

            if ($lines === []) {
                continue;
            }

            $matches[] = [
                'file' => $file,
                'lines' => array_slice($lines, 0, $limit),
            ];
        }

        if ($matches === []) {
            return ['success' => false, 'error' => 'No matching WhatsApp chat files found.'];
        }

        return [
            'success' => true,
            'messages' => array_map(
                fn (array $match): string => "# {$match['file']}\n".implode("\n", $match['lines']),
                $matches,
            ),
            'count' => count($matches),
        ];
    }

    /**
     * @return array{success: bool, messages?: list<string>, count?: int, error?: string}
     */
    public function readFacebook(
        string $collection,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $participant = null,
        ?string $query = null,
        int $limit = 50,
    ): array {
        $this->pathResolver->validateCollection($collection);
        $relative = $this->pathResolver->specialSourceRelativePath($collection, 'facebook_html');

        $legacyDisk = config('document-archive.disks.legacy', 'legacy');
        $legacyDisk = is_string($legacyDisk) ? $legacyDisk : 'legacy';

        if (! Storage::disk($legacyDisk)->exists($relative)) {
            return ['success' => false, 'error' => "Facebook HTML export not found: {$relative}"];
        }

        $content = Utf8Text::sanitize((string) Storage::disk($legacyDisk)->get($relative));
        $messages = $this->extractFacebookMessages($content, $dateFrom, $dateTo, $participant, $query, $limit);

        if ($messages === []) {
            return ['success' => false, 'error' => 'No matching Facebook messages found.'];
        }

        return [
            'success' => true,
            'messages' => $messages,
            'count' => count($messages),
        ];
    }

    /**
     * @return list<string>
     */
    private function filterLines(string $content, ?string $date, ?string $query): array
    {
        $lines = explode("\n", $content);
        $filtered = [];

        foreach ($lines as $line) {
            if ($date !== null && ! str_contains($line, $date)) {
                continue;
            }

            if ($query !== null && ! str_contains(strtolower($line), strtolower($query))) {
                continue;
            }

            $filtered[] = $line;
        }

        return $filtered;
    }

    /**
     * @return list<string>
     */
    private function extractFacebookMessages(
        string $html,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $participant,
        ?string $query,
        int $limit,
    ): array {
        $messages = [];

        if (preg_match_all('/<div class="[^"]*message[^"]*"[^>]*>(.*?)<\/div>/is', $html, $matches) !== false) {
            foreach ($matches[1] as $block) {
                $text = trim(html_entity_decode(strip_tags($block)));

                if ($text === '') {
                    continue;
                }

                if ($participant !== null && ! str_contains(strtolower($text), strtolower($participant))) {
                    continue;
                }

                if ($query !== null && ! str_contains(strtolower($text), strtolower($query))) {
                    continue;
                }

                if ($dateFrom !== null || $dateTo !== null) {
                    $dateMatch = null;

                    if (preg_match('/\d{4}-\d{2}-\d{2}/', $text, $dateMatch)) {
                        $msgDate = $dateMatch[0];

                        if ($dateFrom !== null && $msgDate < $dateFrom) {
                            continue;
                        }

                        if ($dateTo !== null && $msgDate > $dateTo) {
                            continue;
                        }
                    }
                }

                $messages[] = $text;

                if (count($messages) >= $limit) {
                    break;
                }
            }
        }

        if ($messages === [] && $query !== null) {
            $lines = explode("\n", strip_tags($html));

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || ! str_contains(strtolower($line), strtolower($query))) {
                    continue;
                }

                $messages[] = $line;

                if (count($messages) >= $limit) {
                    break;
                }
            }
        }

        return $messages;
    }
}
