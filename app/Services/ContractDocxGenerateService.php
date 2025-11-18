<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;

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

        // 1) Build merge data
        $data = ContractMergeDataBuilder::build($contract);

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
        $vietnameseCss = '<style>
            body, *, p, td, th, div, span {
                font-family: "DejaVu Sans", "Times New Roman", serif !important;
                font-size: 11pt;
            }
        </style>';

        // Ensure UTF-8 meta charset
        if (strpos($htmlContent, '<meta charset') === false) {
            $headPos = strpos($htmlContent, '</head>');
            if ($headPos !== false) {
                $htmlContent = substr_replace(
                    $htmlContent,
                    '<meta charset="UTF-8">' . $vietnameseCss,
                    $headPos,
                    0
                );
            }
        }

        // Use DomPDF to convert HTML to PDF
        $dompdf = new Dompdf();
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
