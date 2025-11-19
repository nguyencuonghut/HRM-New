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

        // 2) Fill DOCX
        $processor = new TemplateProcessor($templatePath);
        foreach ($data as $key => $value) {
            $processor->setValue($key, $value);
        }

        // 3) Lưu DOCX tạm
        $tmpDir  = storage_path('app/tmp/contracts');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $tmpDocx = $tmpDir . '/contract_' . $contract->id . '_v' . $template->version . '.docx';
        $processor->saveAs($tmpDocx);

        // 4) Convert DOCX -> PDF
        $phpWord  = IOFactory::load($tmpDocx);

        // Save to HTML file first
        $htmlFile = $tmpDir . '/contract_' . $contract->id . '_v' . $template->version . '.html';
        $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
        $htmlWriter->save($htmlFile);

        // Read HTML content and fix encoding for Vietnamese
        $htmlContent = file_get_contents($htmlFile);

        // Add UTF-8 meta and font CSS for proper Vietnamese rendering
        $css = '<meta charset="UTF-8">
            <style>
                body, p, td, th, div, span {
                    font-family: "dejavu sans", "DejaVu Sans", "Times New Roman", sans-serif !important;
                    font-size: 11pt;
                }
            </style>';

        // Insert CSS right after <head>
        $htmlContent = preg_replace('/<head>/i', '<head>' . $css, $htmlContent, 1);

        // Use DomPDF with Vietnamese font support
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'dejavu sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlContent, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $relativePdfPath = 'contracts/generated/contract_' . $contract->id . '_v' . $template->version . '.pdf';
        $fullPdfPath     = storage_path('app/public/' . $relativePdfPath);

        // Đảm bảo thư mục tồn tại
        Storage::disk('public')->makeDirectory('contracts/generated');

        // Save PDF
        file_put_contents($fullPdfPath, $dompdf->output());

        // Cleanup
        if (is_file($htmlFile)) {
            unlink($htmlFile);
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
}
