<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractTemplateContentRequest;
use App\Http\Resources\ContractTemplateResource;
use App\Models\ContractTemplate;
use App\Services\TemplateRenderService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class ContractTemplateEditorController extends Controller
{
    use AuthorizesRequests;

    public function editor(ContractTemplate $template)
    {
        $this->authorize('update', $template);

        return Inertia::render('ContractTemplateEditor', [
            'template'   => new ContractTemplateResource($template),
            'sampleData' => [
                'employee' => ['full_name' => 'Nguyễn Văn A', 'employee_code' => 'NV001'],
                'department' => ['name' => 'Phòng Kỹ Thuật'],
                'position' => ['title' => 'Kỹ Sư Phần Mềm'],
                'contract' => [
                    'contract_number' => 'HĐ-2025-001',
                    'start_date'      => now()->toDateString(),
                    'end_date'        => now()->addMonths(12)->toDateString(),
                ],
                'terms' => [
                    'base_salary'      => number_format(15000000, 0, ',', '.'),
                    'insurance_salary' => number_format(12000000, 0, ',', '.'),
                    'position_allowance' => number_format(2000000, 0, ',', '.'),
                    'working_time'     => 'T2–T6 08:00–17:00',
                    'work_location'    => 'Văn phòng Ninh Bình',
                    'other_allowances' => [
                        ['name'=>'Ăn ca','amount'=>number_format(650000, 0, ',', '.')],
                        ['name'=>'Điện thoại','amount'=>number_format(200000, 0, ',', '.')],
                    ],
                ],
                'company'  => ['name' => 'Công ty ABC'],
            ],
        ]);
    }

    public function updateContent(ContractTemplateContentRequest $request, ContractTemplate $template)
    {
        $this->authorize('update', $template);

        // Chỉ cho edit nếu là LIQUID
        if ($request->input('engine') !== 'LIQUID' || !$template->isLiquid()) {
            return back()->withErrors(['content' => 'Chỉ cho phép chỉnh sửa template dạng Liquid.']);
        }

        $content = (string) $request->input('content', '');

        // Chặn nhanh một số thẻ
        $forbidden = ['{% include', '{% render', '{% layout', '<?php', '<?='];
        foreach ($forbidden as $bad) {
            if (Str::contains($content, $bad)) {
                return back()->withErrors(['content' => "Template chứa thẻ không được phép: $bad"]);
            }
        }

        $before = $template->getOriginal();

        $template->update([
            'content'    => $content,
            'version'    => ($template->version ?? 1) + 1,
            'updated_by' => $request->user()->id,
        ]);

        activity()->causedBy($request->user())->performedOn($template)
            ->withProperties(['old' => $before, 'attributes' => $template->toArray()])
            ->log('contract_template.liquid_updated');

        return redirect()->route('contract-templates.editor', $template->id)
            ->with('success', 'Đã lưu nội dung template (Liquid).');
    }

    public function preview(ContractTemplateContentRequest $request, ContractTemplate $template, TemplateRenderService $renderer)
    {
        $this->authorize('update', $template);

        if ($request->input('engine') !== 'LIQUID' || !$template->isLiquid()) {
            return response('Chỉ preview Liquid.', 422);
        }

        $content = (string) $request->input('content', $template->content ?? '');
        $data    = (array) $request->input('data', []);

        // Gộp thêm sample mặc định nếu FE thiếu
        $data = array_replace_recursive([
            'employee' => ['full_name' => 'Nguyễn Văn A', 'employee_code' => 'NV001'],
            'contract' => ['contract_number' => 'HĐ-2025-001', 'start_date' => now()->toDateString()],
            'company'  => ['name' => 'Công ty ABC'],
        ], $data);

        try {
            $html = $renderer->renderLiquid($content, $data);
            return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
        } catch (\Throwable $e) {
            return response('Lỗi render: '.$e->getMessage(), 422);
        }
    }
}
