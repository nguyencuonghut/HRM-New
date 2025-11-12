<?php

namespace App\Services;

use App\Models\ContractAppendix;
use App\Models\ContractAppendixTemplate;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractAppendixGenerateService
{
    /**
     * Generate PDF for appendix and return stored path.
     */
    public function generate(ContractAppendix $appendix, ?ContractAppendixTemplate $template = null): string
    {
        $contract   = $appendix->contract()->with(['employee', 'department', 'position'])->first();
        $employee   = $contract->employee;
        $department = $contract->department;
        $position   = $contract->position;

        // pick template
        if (! $template && $appendix->template_id) {
            $template = ContractAppendixTemplate::find($appendix->template_id);
        }

        if (! $template) {
            $template = ContractAppendixTemplate::where('appendix_type', $appendix->appendix_type)
                ->where('is_default', true)
                ->first();
        }

        $view = $template?->blade_view ?? 'contracts.appendixes.default';

        // dữ liệu cũ để compare (tuỳ bạn lấy từ contract chính)
        $old = [
            'base_salary'        => $contract->base_salary ?? null,
            'insurance_salary'   => $contract->insurance_salary ?? null,
            'position_allowance' => $contract->position_allowance ?? null,
        ];

        $company = [
            'name'          => config('app.company_name', 'CÔNG TY ..........'),
            'address'       => config('app.company_address', '...................'),
            'representative'=> config('app.company_representative', '........'),
            'position'      => config('app.company_representative_position', 'Giám đốc'),
        ];

        $data = [
            'contract'   => $contract,
            'appendix'   => $appendix,
            'employee'   => $employee,
            'department' => $department,
            'position'   => $position,
            'company'    => $company,
            'old'        => $old,
        ];

        $pdf = Pdf::loadView($view, $data);

        $fileName = 'contract_appendixes/' . $contract->id . '/' . ($appendix->appendix_no ?? $appendix->id) . '.pdf';
        Storage::disk('public')->makeDirectory(dirname($fileName));
        Storage::disk('public')->put($fileName, $pdf->output());

        // lưu path vào appendix
        $appendix->generated_pdf_path = $fileName;
        $appendix->save();

        return $fileName;
    }
}
