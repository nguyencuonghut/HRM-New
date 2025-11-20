<?php

namespace App\Services;

use App\Models\ContractAppendix;
use App\Models\ContractAppendixTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ContractAppendixGenerateService
{
    /**
     * Generate PDF for appendix using DOCX template and return stored path.
     */
    public function generate(ContractAppendix $appendix, ?ContractAppendixTemplate $template = null): string
    {
        // Load appendix with full relationships (important for resolver)
        $appendix = ContractAppendix::with([
            'contract.employee.ward.province',
            'contract.employee.tempWard.province',
            'contract.department',
            'contract.position'
        ])->findOrFail($appendix->id);

        $contract = $appendix->contract;

        // Pick template
        if (!$template && $appendix->template_id) {
            $template = ContractAppendixTemplate::find($appendix->template_id);
        }

        if (!$template) {
            $template = ContractAppendixTemplate::where('appendix_type', $appendix->appendix_type)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
        }

        if (!$template) {
            throw new \Exception('Không tìm thấy template phù hợp cho loại phụ lục: ' . $appendix->appendix_type);
        }

        if (!$template->body_path) {
            throw new \Exception('Template chưa có file DOCX.');
        }

        $docxPath = Storage::disk('public')->path($template->body_path);
        if (!file_exists($docxPath)) {
            throw new \Exception('File DOCX không tồn tại: ' . $template->body_path);
        }

        // Use DynamicPlaceholderResolverService to resolve placeholders
        $resolver = app(DynamicPlaceholderResolverService::class);
        $mergeData = $resolver->resolve($appendix, $template);

        // Debug: Log merge data
        Log::info('ContractAppendixGenerateService - Merge Data', [
            'appendix_id' => $appendix->id,
            'template_id' => $template->id,
            'merge_data_count' => count($mergeData),
            'merge_data' => $mergeData,
        ]);

        // Prepare temp path for merged DOCX
        $mergedDocxPath = storage_path('app/temp/' . uniqid('appendix_') . '.docx');
        if (!file_exists(dirname($mergedDocxPath))) {
            mkdir(dirname($mergedDocxPath), 0755, true);
        }

        // Merge DOCX with resolved data
        $docxMerge = app(DocxMergeService::class);
        $docxMerge->merge($docxPath, $mergeData, $mergedDocxPath);

        // Convert DOCX to PDF via LibreOffice
        $pdfPath = $this->convertToPdfViaLibreOffice($mergedDocxPath);

        // Cleanup merged DOCX
        if (file_exists($mergedDocxPath)) {
            @unlink($mergedDocxPath);
        }

        if (!$pdfPath || !file_exists($pdfPath)) {
            throw new \Exception('Không thể convert DOCX sang PDF.');
        }

        // Store PDF to public storage
        $fileName = 'contract_appendixes/' . $contract->id . '/' . ($appendix->appendix_no ?? $appendix->id) . '.pdf';
        Storage::disk('public')->makeDirectory(dirname($fileName));

        // Move PDF from temp to storage
        $storagePath = Storage::disk('public')->path($fileName);
        rename($pdfPath, $storagePath);

        // Save path to appendix
        $appendix->generated_pdf_path = $fileName;
        $appendix->save();

        return $fileName;
    }

    /**
     * Convert DOCX to PDF using LibreOffice
     */
    private function convertToPdfViaLibreOffice(string $docxPath): ?string
    {
        $outputDir = dirname($docxPath);
        $pdfPath = $outputDir . '/' . pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';

        // Find LibreOffice binary
        $libreOfficePath = $this->findLibreOfficePath();
        if (!$libreOfficePath) {
            Log::error('LibreOffice not found');
            return null;
        }

        // Execute conversion
        $command = sprintf(
            '%s --headless --convert-to pdf:writer_pdf_Export --outdir %s %s 2>&1',
            escapeshellarg($libreOfficePath),
            escapeshellarg($outputDir),
            escapeshellarg($docxPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($pdfPath)) {
            Log::error('LibreOffice conversion failed', [
                'command' => $command,
                'output' => $output,
                'return_code' => $returnCode,
            ]);
            return null;
        }

        return $pdfPath;
    }

    /**
     * Auto-detect LibreOffice installation path
     */
    private function findLibreOfficePath(): ?string
    {
        $possiblePaths = [
            '/usr/bin/libreoffice',           // Linux standard
            '/usr/bin/soffice',                // Alternative Linux
            '/usr/local/bin/libreoffice',      // Linux custom install
            '/opt/libreoffice/program/soffice', // Custom install
            '/Applications/LibreOffice.app/Contents/MacOS/soffice', // macOS
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe', // Windows
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try using `which` command
        $which = trim(shell_exec('which libreoffice 2>/dev/null') ?? '');
        if ($which && file_exists($which)) {
            return $which;
        }

        return null;
    }
}
