<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ContractGenerateService
{
    public function __construct(
        protected TemplateRenderService $renderer
    ) {
    }

    /**
     * Entry point dùng trong controller:
     * ContractGenerateService::generate($contract, $templateOptional)
     */
    public static function generate(Contract $contract, ?ContractTemplate $template = null): array
    {
        return app(self::class)->doGenerate($contract, $template);
    }

    /**
     * Thực hiện generate thực sự (instance, dùng được $this->renderer)
     */
    protected function doGenerate(Contract $contract, ?ContractTemplate $template = null): array
    {
        $template = $template ?: $this->resolveTemplate($contract);

        // 1) Template DOCX -> giao cho ContractDocxGenerateService
        if ($template->engine === 'DOCX_MERGE') {
            return ContractDocxGenerateService::generate($contract, $template);
        }

        // 2) Template LIQUID / BLADE -> render HTML rồi convert PDF
        $employee   = $contract->employee;
        $department = $contract->department;
        $position   = $contract->position;
        $terms      = CurrentContractTermsService::build($contract);

        // Tùy bạn định nghĩa config company, demo:
        $company = config('company', [
            'name' => config('app.name'),
        ]);

        $data = compact('employee', 'department', 'position', 'contract', 'terms', 'company');

        // Quan trọng: dùng TemplateRenderService
        $html = $this->renderer->renderContractTemplate($template, $data);

        $pdf = Pdf::loadHTML($html)->setPaper('a4');

        $fileName     = 'contract_' . $contract->id . '_v' . $template->version . '.pdf';
        $relativePath = "contracts/generated/{$fileName}";

        Storage::disk('public')->put($relativePath, $pdf->output());

        return [
            'path' => $relativePath,
            'url'  => asset("storage/{$relativePath}"),
            'file' => $fileName,
        ];
    }

    /**
     * Chọn template mặc định khi contract chưa gắn template_id.
     * Ưu tiên DOCX_MERGE -> LIQUID -> BLADE -> HTML_TO_PDF
     */
    protected function resolveTemplate(Contract $contract): ContractTemplate
    {
        if ($contract->template_id) {
            return ContractTemplate::findOrFail($contract->template_id);
        }

        return ContractTemplate::where('type', $contract->contract_type)
            ->where('is_active', true)
            ->orderByRaw("FIELD(engine, 'DOCX_MERGE', 'LIQUID', 'BLADE', 'HTML_TO_PDF')")
            ->orderByDesc('version')
            ->firstOrFail();
    }
}
