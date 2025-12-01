<?php

namespace App\Http\Controllers;

use App\Models\{Contract, ContractTemplate};
use App\Enums\ActivityLogDescription;
use Illuminate\Http\Request;
use App\Services\ContractGenerateService;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ContractGenerateController extends Controller
{
    use AuthorizesRequests;

    public function generate(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract); // hoặc quyền riêng nếu bạn muốn

        $templateId = $request->input('template_id');
        $template = $templateId ? ContractTemplate::findOrFail($templateId) : null;

        $res = ContractGenerateService::generate($contract, $template);

        $contract->update([
            'template_id' => $template?->id ?? $contract->template_id,
            'generated_pdf_path' => $res['path'],
        ]);

        activity('contract')
            ->performedOn($contract)->causedBy($request->user())
            ->withProperties([
                'action' => 'generated_from_template',
                'template_id' => $template?->id,
                'file' => $res,
            ])->log(ActivityLogDescription::CONTRACT_GENERATED_PDF->value);

        return back()->with('success', 'Đã sinh file hợp đồng.')->with('generated_url', $res['url']);
    }
}
