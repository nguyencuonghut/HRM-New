<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use App\Models\ContractTemplate;
use App\Models\ContractTemplatePlaceholderMapping;
use Illuminate\Support\Str;

class TemplateUploadService
{
    /**
     * Upload và validate DOCX template file.
     *
     * @param UploadedFile $file
     * @param string $type contract type (PROBATION, FIXED_TERM, etc.)
     * @return array ['body_path' => 'templates/contracts/{type}.docx']
     * @throws \RuntimeException
     */
    public static function uploadDocxTemplate(UploadedFile $file, string $type): array
    {
        // 1) Validate extension
        if ($file->getClientOriginalExtension() !== 'docx') {
            throw new \RuntimeException('File phải là định dạng .docx');
        }

        // 2) Validate MIME type
        $mimeType = $file->getMimeType();
        $validMimes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/octet-stream', // some systems return this for .docx
        ];
        if (!in_array($mimeType, $validMimes)) {
            throw new \RuntimeException('MIME type không hợp lệ cho .docx file');
        }

        // 3) Validate DOCX structure using PhpOffice
        try {
            $tempPath = $file->store('tmp');
            $fullTempPath = Storage::disk('local')->path($tempPath);

            IOFactory::load($fullTempPath); // Will throw if invalid DOCX

            Storage::disk('local')->delete($tempPath);
        } catch (\Exception $e) {
            Storage::disk('local')->delete($tempPath ?? null);
            throw new \RuntimeException('File DOCX không hợp lệ hoặc bị hỏng: ' . $e->getMessage());
        }

        // 4) Store file with type-based name (e.g., PROBATION.docx)
        $filename = strtolower($type) . '.docx';
        $dir = 'templates/contracts';

        // Ensure directory exists
        Storage::disk('public')->makeDirectory($dir);

        // Store file (will overwrite if exists)
        $path = $file->storeAs(
            $dir,
            $filename,
            'public'
        );

        // 5) Return body_path for DB storage
        return [
            'body_path' => $path, // e.g., 'templates/contracts/probation.docx'
            'filename'  => $filename,
        ];
    }

    /**
     * Auto-detect placeholders và tạo default mappings cho template
     *
     * @param ContractTemplate $template
     * @param string $docxPath Full path to DOCX file
     * @return array ['detected' => int, 'mapped' => int, 'unmapped' => int]
     */
    public static function createPlaceholderMappings(ContractTemplate $template, string $docxPath): array
    {
        // Extract placeholders từ DOCX
        $placeholders = PlaceholderExtractorService::extractFromDocx($docxPath);

        // Load presets
        $presets = config('contract_placeholders.presets', []);

        $mapped = 0;
        $unmapped = 0;
        $displayOrder = 0;

        foreach ($placeholders as $placeholder) {
            $displayOrder++;

            // Check xem đã có mapping chưa
            $exists = ContractTemplatePlaceholderMapping::where('template_id', $template->id)
                ->where('placeholder_key', $placeholder)
                ->exists();

            if ($exists) {
                continue; // Skip if already mapped
            }

            // Tìm preset hoặc dùng default
            if (isset($presets[$placeholder])) {
                [$dataSource, $sourcePath, $transformer, $defaultValue] = $presets[$placeholder];
                $mapped++;
            } else {
                // Unmapped - tạo mapping trống để user config sau
                $dataSource = 'MANUAL';
                $sourcePath = null;
                $transformer = null;
                $defaultValue = '';
                $unmapped++;
            }

            ContractTemplatePlaceholderMapping::create([
                'id' => Str::uuid(),
                'template_id' => $template->id,
                'placeholder_key' => $placeholder,
                'data_source' => $dataSource,
                'source_path' => $sourcePath,
                'default_value' => $defaultValue,
                'transformer' => $transformer,
                'formula' => null,
                'validation_rules' => null,
                'is_required' => false,
                'display_order' => $displayOrder,
            ]);
        }

        return [
            'detected' => count($placeholders),
            'mapped' => $mapped,
            'unmapped' => $unmapped,
        ];
    }

    /**
     * Delete DOCX template file from storage.
     *
     * @param string $bodyPath relative path (e.g., 'templates/contracts/probation.docx')
     * @return bool
     */
    public static function deleteDocxTemplate(string $bodyPath): bool
    {
        if (!$bodyPath) {
            return true; // no-op if empty
        }

        try {
            if (Storage::disk('public')->exists($bodyPath)) {
                Storage::disk('public')->delete($bodyPath);
            }
            return true;
        } catch (\Exception $e) {
            Log::warning("Failed to delete template file: {$bodyPath}", ['error' => $e->getMessage()]);
            return true; // silent fail - don't block deletion
        }
    }
}
