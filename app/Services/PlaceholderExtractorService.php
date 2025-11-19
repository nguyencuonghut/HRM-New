<?php

namespace App\Services;

use ZipArchive;

class PlaceholderExtractorService
{
    /**
     * Extract tất cả placeholders từ DOCX file
     *
     * @param string $docxPath Full path đến file DOCX
     * @return array Mảng các placeholder keys (không có ${})
     * @throws \RuntimeException
     */
    public static function extractFromDocx(string $docxPath): array
    {
        if (!file_exists($docxPath)) {
            throw new \RuntimeException("DOCX file not found: {$docxPath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException("Cannot open DOCX file: {$docxPath}");
        }

        // Extract document.xml content
        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($documentXml === false) {
            throw new \RuntimeException("Cannot read document.xml from DOCX");
        }

        // Clean XML: Remove all XML tags, keep only text content
        // This handles cases where placeholders are split across multiple <w:t> tags
        $cleanText = preg_replace('/<[^>]+>/', '', $documentXml);

        // Extract all ${...} patterns from clean text
        $placeholders = [];
        preg_match_all('/\$\{([^}]+)\}/', $cleanText, $matches);

        if (!empty($matches[1])) {
            // Remove any remaining whitespace or newlines
            $placeholders = array_map('trim', $matches[1]);
            $placeholders = array_unique($placeholders);
            sort($placeholders);
        }

        return $placeholders;
    }

    /**
     * Extract placeholders từ uploaded file
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array
     */
    public static function extractFromUploadedFile($file): array
    {
        $tempPath = $file->getRealPath();
        return self::extractFromDocx($tempPath);
    }

    /**
     * Validate xem DOCX có chứa placeholder nào không
     *
     * @param string $docxPath
     * @return bool
     */
    public static function hasPlaceholders(string $docxPath): bool
    {
        try {
            $placeholders = self::extractFromDocx($docxPath);
            return count($placeholders) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * So sánh placeholders giữa 2 file để detect thay đổi
     *
     * @param string $oldDocxPath
     * @param string $newDocxPath
     * @return array ['added' => [], 'removed' => [], 'unchanged' => []]
     */
    public static function comparePlaceholders(string $oldDocxPath, string $newDocxPath): array
    {
        $oldPlaceholders = self::extractFromDocx($oldDocxPath);
        $newPlaceholders = self::extractFromDocx($newDocxPath);

        return [
            'added' => array_values(array_diff($newPlaceholders, $oldPlaceholders)),
            'removed' => array_values(array_diff($oldPlaceholders, $newPlaceholders)),
            'unchanged' => array_values(array_intersect($oldPlaceholders, $newPlaceholders)),
        ];
    }
}
