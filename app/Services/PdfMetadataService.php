<?php

namespace App\Services;

class PdfMetadataService
{
    /**
     * Extract metadata from a PDF file located at absolute path $filePath.
     *
     * @param  string  $filePath  Absolute file path on disk
     * @return array [
     *               'page_count' => int,
     *               'file_size' => int,
     *               'file_hash' => string,
     *               'title' => ?string,
     *               'author' => ?string,
     *               'creator' => ?string
     *               ]
     */
    public function extract(string $filePath): array
    {
        $result = [
            'page_count' => 1,
            'file_size' => 0,
            'file_hash' => null,
            'title' => null,
            'author' => null,
            'creator' => null,
        ];

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return $result;
        }

        $result['file_size'] = (int) filesize($filePath);
        $result['file_hash'] = hash_file('sha256', $filePath);
        $result['page_count'] = $this->extractPageCount($filePath);

        $metadata = $this->extractPdfInfo($filePath);
        $result = array_merge($result, $metadata);

        return $result;
    }

    /**
     * Fast, reliable page counting for standard, linearized, and multi-object PDFs.
     */
    public function extractPageCount(string $filePath): int
    {
        if (! file_exists($filePath) || filesize($filePath) === 0) {
            return 1;
        }

        // 1. Fast regex scan for /Type /Pages /Count N
        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            return 1;
        }

        $maxPages = 1;
        $foundCount = false;

        // Read chunks looking for /Type\s*/Pages.*?/Count\s+(\d+)
        $buffer = '';
        while (! feof($handle)) {
            $chunk = fread($handle, 65536);
            $buffer .= $chunk;

            // Search for /Count <digits>
            if (preg_match_all('/\/Count\s+(\d+)/', $buffer, $matches)) {
                foreach ($matches[1] as $cnt) {
                    $c = (int) $cnt;
                    if ($c > $maxPages) {
                        $maxPages = $c;
                        $foundCount = true;
                    }
                }
            }

            // Keep the tail of buffer to avoid missing tokens spanning chunk boundaries
            if (strlen($buffer) > 32768) {
                $buffer = substr($buffer, -4096);
            }
        }
        fclose($handle);

        if ($foundCount && $maxPages >= 1) {
            return $maxPages;
        }

        // 2. Secondary fallback: count individual /Type\s*/Page (excluding /Pages)
        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            return 1;
        }

        $pageCount = 0;
        $buffer = '';
        while (! feof($handle)) {
            $buffer .= fread($handle, 65536);
            if (preg_match_all('/\/Type\s*\/Page\b(?!\s*s)/', $buffer, $matches)) {
                $pageCount += count($matches[0]);
            }
            if (strlen($buffer) > 32768) {
                $buffer = substr($buffer, -4096);
            }
        }
        fclose($handle);

        return max(1, $pageCount);
    }

    /**
     * Extract title, author, creator from PDF Info dictionary.
     */
    protected function extractPdfInfo(string $filePath): array
    {
        $info = [
            'title' => null,
            'author' => null,
            'creator' => null,
        ];

        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            return $info;
        }

        // Typically Info dictionary is near the end or beginning of the file
        // Read first 32KB
        $header = fread($handle, 32768);
        fseek($handle, max(0, filesize($filePath) - 65536));
        $footer = fread($handle, 65536);
        fclose($handle);

        $combined = $header."\n".$footer;

        if (preg_match('/\/Title\s*\(([^)]+)\)/', $combined, $m)) {
            $info['title'] = $this->sanitizePdfString($m[1]);
        }
        if (preg_match('/\/Author\s*\(([^)]+)\)/', $combined, $m)) {
            $info['author'] = $this->sanitizePdfString($m[1]);
        }
        if (preg_match('/\/Creator\s*\(([^)]+)\)/', $combined, $m)) {
            $info['creator'] = $this->sanitizePdfString($m[1]);
        }

        return $info;
    }

    protected function sanitizePdfString(string $raw): string
    {
        $clean = preg_replace('/[^\x20-\x7E]/', '', $raw);

        return trim(str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $clean));
    }
}
