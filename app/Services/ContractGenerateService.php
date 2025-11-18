<?php

namespace App\Services;

use App\Models\{Contract, ContractTemplate};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ContractGenerateService
{
    public function __construct(private TemplateRenderService $renderer) {}

    /**
     * Render hợp đồng ra PDF từ template (BLADE hoặc LIQUID) và lưu vào storage.
     * @return array ['path' => '...', 'url' => '...']
     */
    public static function generate(Contract $contract, ?ContractTemplate $template = null): array
    {
        $template = $template
            ?: ($contract->template_id
                ? ContractTemplate::find($contract->template_id)
                : null);

        if (!$template) {
            // fallback theo contract_type
            $template = ContractTemplate::where('type', $contract->contract_type)
                ->where('is_active', true)
                ->latest('version')
                ->firstOrFail();
        }

        $employee   = $contract->employee;
        $department = $contract->department;
        $position   = $contract->position;

        // Lấy bộ terms (base_salary, insurance_salary, allowances...)
        $terms      = CurrentContractTermsService::build($contract);

        /* -------------------------------------------------------
         | 1. Build context dùng chung cho cả Blade và Liquid
         -------------------------------------------------------*/
        $context = [
            'employee'   => $employee,
            'department' => $department,
            'position'   => $position,
            'contract'   => $contract,
            'terms'      => $terms,
            'template'   => $template,
        ];

        /* -------------------------------------------------------
         | 2. Render template theo engine
         -------------------------------------------------------*/
        if ($template->engine === 'LIQUID') {

            // Đọc nội dung template .liquid từ storage/app
            $raw = Storage::get($template->body_path);

            // Gọi TemplateRenderService để parse Liquid
            $html = app(TemplateRenderService::class)->renderLiquid($raw, $context);

        } else {
            // Mặc định = BLADE
            $view = $template->viewPath(); // ví dụ: "contracts/templates/probation"
            $html = view($view, $context)->render();
        }

        /* -------------------------------------------------------
         | 3. Sinh PDF
         -------------------------------------------------------*/
        $pdf = Pdf::loadHTML($html)->setPaper('a4');

        $fileName = "contract_{$contract->id}_v{$template->version}.pdf";
        $path = "contracts/generated/{$fileName}";

        Storage::disk('public')->put($path, $pdf->output());

        return [
            'path' => $path,
            'url'  => asset("storage/{$path}"),
            'file' => $fileName,
        ];
    }
}
