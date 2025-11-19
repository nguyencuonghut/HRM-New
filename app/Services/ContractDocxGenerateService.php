<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;

class ContractDocxGenerateService
{
    /**
     * Generate PDF từ template DOCX.
     * Trả về ['path' => 'contracts/generated/xxx.pdf', 'url' => '...'].
     */
    public static function generate(Contract $contract, ?ContractTemplate $template = null): array
    {
        // Chọn template
        $template = $template ?: self::resolveTemplate($contract);

        if ($template->engine !== 'DOCX_MERGE') {
            throw new \RuntimeException('Template engine must be DOCX_MERGE');
        }

        $templatePath = Storage::disk('public')->path($template->body_path);
        if (!is_file($templatePath)) {
            throw new \RuntimeException("Template DOCX not found: {$template->body_path}");
        }

        // 1) Build merge data dynamically from placeholder mappings
        $data = DynamicPlaceholderResolverService::resolve($contract, $template);

        // 2) Fill DOCX using custom merge to preserve list formatting
        $tmpDir  = storage_path('app/tmp/contracts');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $tmpDocx = $tmpDir . '/contract_' . $contract->id . '_v' . $template->version . '.docx';

        // Use DocxMergeService to preserve formatting (PhpWord destroys list formatting)
        DocxMergeService::merge($templatePath, $data, $tmpDocx);

        // 4) Convert DOCX -> PDF using LibreOffice (preserves formatting)
        $relativePdfPath = 'contracts/generated/contract_' . $contract->id . '_v' . $template->version . '.pdf';
        $fullPdfPath     = storage_path('app/public/' . $relativePdfPath);

        // Đảm bảo thư mục tồn tại
        Storage::disk('public')->makeDirectory('contracts/generated');

        // Try LibreOffice first (best quality)
        $libreOfficePath = self::findLibreOfficePath();

        if ($libreOfficePath) {
            // Use LibreOffice for conversion
            $envVars = [
                'HOME=' . escapeshellarg(sys_get_temp_dir()),
                'LANG=en_US.UTF-8',
                'LC_ALL=en_US.UTF-8',
            ];

            $outDir = dirname($fullPdfPath);
            $command = sprintf(
                '%s %s --headless --convert-to pdf:writer_pdf_Export --outdir %s %s 2>&1',
                implode(' ', $envVars),
                escapeshellarg($libreOfficePath),
                escapeshellarg($outDir),
                escapeshellarg($tmpDocx)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($fullPdfPath)) {
                // Fallback to HTML method
                self::convertViaHtml($tmpDocx, $fullPdfPath);
            }

            // Cleanup DOCX
            @unlink($tmpDocx);
        } else {
            // Fallback if LibreOffice not available
            self::convertViaHtml($tmpDocx, $fullPdfPath);
            @unlink($tmpDocx);
        }

        return [
            'path' => $relativePdfPath,
            'url'  => asset('storage/' . $relativePdfPath),
        ];
    }

    protected static function resolveTemplate(Contract $contract): ContractTemplate
    {
        if ($contract->template_id) {
            return ContractTemplate::whereKey($contract->template_id)->firstOrFail();
        }

        // fallback theo type
        return ContractTemplate::where('type', $contract->contract_type)
            ->where('engine', 'DOCX_MERGE')
            ->where('is_active', true)
            ->orderByDesc('version')
            ->firstOrFail();
    }

    /**
     * Find LibreOffice executable path
     */
    protected static function findLibreOfficePath(): ?string
    {
        $possiblePaths = [
            '/usr/bin/libreoffice',
            '/usr/bin/soffice',
            '/usr/local/bin/libreoffice',
            '/usr/local/bin/soffice',
            '/opt/libreoffice/program/soffice',
            'C:\Program Files\LibreOffice\program\soffice.exe',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Fallback: Convert DOCX to PDF via HTML using PhpWord + DomPDF
     */
    protected static function convertViaHtml(string $docxPath, string $pdfPath): void
    {
        $phpWord = IOFactory::load($docxPath);

        $tmpHtml = sys_get_temp_dir() . '/contract_' . uniqid() . '.html';
        $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
        $htmlWriter->save($tmpHtml);

        $htmlContent = file_get_contents($tmpHtml);

        // Add UTF-8 meta and font CSS
        $css = '<meta charset="UTF-8">
            <style>
                body, p, td, th, div, span, li {
                    font-family: "dejavu sans", "DejaVu Sans", "Times New Roman", sans-serif !important;
                    font-size: 11pt;
                    line-height: 1.5;
                }
                ol, ul {
                    margin-left: 20px;
                    padding-left: 20px;
                }
            </style>';

        $htmlContent = preg_replace('/<head>/i', '<head>' . $css, $htmlContent, 1);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'dejavu sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlContent, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        file_put_contents($pdfPath, $dompdf->output());

        @unlink($tmpHtml);
    }
}
