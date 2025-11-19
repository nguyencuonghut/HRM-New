<?php

namespace App\Services;

/**
 * Service for merging DOCX templates while preserving formatting
 * PhpWord TemplateProcessor destroys list formatting, so we use direct XML manipulation
 */
class DocxMergeService
{
    /**
     * Merge DOCX template with data while preserving list formatting
     */
    public static function merge(string $templatePath, array $mergeData, string $outputPath): void
    {
        // Extract DOCX (it's a ZIP file)
        $zip = new \ZipArchive();

        if ($zip->open($templatePath) !== true) {
            throw new \Exception('Cannot open template DOCX file');
        }

        // Read document.xml
        $documentXml = $zip->getFromName('word/document.xml');

        if ($documentXml === false) {
            $zip->close();
            throw new \Exception('Cannot read document.xml from DOCX');
        }

        // Replace placeholders in XML while preserving all XML tags
        // First, fix split placeholders by removing ALL tags between ${ and }
        $documentXml = self::fixSplitPlaceholders($documentXml);

        // Now replace with values
        foreach ($mergeData as $key => $value) {
            $cleanValue = is_string($value) ? trim($value) : ($value ?? '');

            // Escape XML special characters in the value
            $cleanValue = htmlspecialchars($cleanValue, ENT_XML1 | ENT_QUOTES, 'UTF-8');

            // Replace ${key} with value - simple string replacement preserves all XML structure
            $documentXml = str_replace('${' . $key . '}', $cleanValue, $documentXml);
        }

        // Create new DOCX with modified document.xml
        $tmpZip = new \ZipArchive();

        if ($tmpZip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $zip->close();
            throw new \Exception('Cannot create output DOCX file');
        }

        // Copy all files from original DOCX
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            if ($filename === 'word/document.xml') {
                // Use our modified document.xml
                $tmpZip->addFromString($filename, $documentXml);
            } else {
                // Copy other files as-is (styles, numbering, etc.)
                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    $tmpZip->addFromString($filename, $content);
                }
            }
        }

        $zip->close();
        $tmpZip->close();
    }

    /**
     * Fix split placeholders by removing XML tags between ${ and }
     * Example: <w:t>${var</w:t><w:t>_name}</w:t> -> <w:t>${var_name}</w:t>
     */
    private static function fixSplitPlaceholders(string $xml): string
    {
        // Find all occurrences of ${ ... } that span multiple tags
        // Pattern: ${...potentially split across tags...}

        $xml = preg_replace_callback(
            '/\$\{([^}]*?)\}/',
            function($matches) {
                // Remove any XML tags within the placeholder
                $placeholder = $matches[0]; // Full ${...}
                $content = $matches[1]; // Just the variable name

                // Remove any tags like </w:t><w:t> or </w:t><w:t xml:space="preserve">
                $cleanContent = strip_tags($content);

                return '${' . $cleanContent . '}';
            },
            $xml
        );

        // Also handle split across runs: ${<anything>}
        // Remove closing/opening run tags within placeholders
        $xml = preg_replace_callback(
            '/(\$\{[^}]*?)<\/w:t><\/w:r><w:r[^>]*><w:t[^>]*>([^}]*?\})/',
            function($matches) {
                return $matches[1] . $matches[2]; // Just concatenate, remove middle tags
            },
            $xml
        );

        return $xml;
    }
}
