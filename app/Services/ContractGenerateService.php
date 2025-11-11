<?php

namespace App\Services;

use App\Models\{Contract, ContractTemplate, Employee, Department, Position};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ContractGenerateService
{
    /**
     * Render hợp đồng ra PDF từ template (Blade) và lưu vào storage.
     * @return array ['path' => '...', 'url' => '...']
     */
    public static function generate(Contract $contract, ?ContractTemplate $template = null): array
    {
        $template = $template ?: ($contract->template_id ? ContractTemplate::find($contract->template_id) : null);
        if (!$template) {
            // fallback theo contract_type
            $template = ContractTemplate::where('type', $contract->contract_type)->where('is_active', true)->latest('version')->firstOrFail();
        }

        $employee   = $contract->employee;
        $department = $contract->department;
        $position   = $contract->position;
        $terms      = CurrentContractTermsService::build($contract);

        $view = $template->body_path; // ví dụ 'contracts/templates/probation'
        $html = view($view, compact('employee','department','position','contract','terms','template'))->render();

        $pdf  = Pdf::loadHTML($html)->setPaper('a4');
        $fileName = 'contract_'.$contract->id.'_v'.$template->version.'.pdf';
        $path = "contracts/generated/{$fileName}";

        Storage::disk('public')->put($path, $pdf->output());

        return [
            'path' => $path,                            // contracts/generated/contract_xxx.pdf
            'url'  => asset("storage/{$path}"),         // /storage/contracts/generated/...
            'file' => $fileName,
        ];
    }
}
