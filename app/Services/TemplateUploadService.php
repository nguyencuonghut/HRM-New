<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;

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
            \Log::warning("Failed to delete template file: {$bodyPath}", ['error' => $e->getMessage()]);
            return true; // silent fail - don't block deletion
        }
    }
}
