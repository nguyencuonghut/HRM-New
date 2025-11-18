<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractTemplateContentRequest;
use App\Http\Resources\ContractTemplateResource;
use App\Models\ContractTemplate;
use App\Services\TemplateRenderService;
use App\Services\ContractMergeDataBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;

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

    /**
     * Preview DOCX template với sample data.
     * Returns PDF stream.
     */
    public function docxPreview(Request $request, ContractTemplate $template)
    {
        $this->authorize('update', $template);

        if ($template->engine !== 'DOCX_MERGE') {
            return response('Chỉ preview DOCX_MERGE.', 422);
        }

        if (!$template->body_path || !Storage::disk('public')->exists($template->body_path)) {
            return response('File DOCX không tìm thấy.', 404);
        }

        try {
            // 1) Build sample data như cũ
            $sampleData = [
                'employee_full_name'    => 'Nguyễn Văn A',
                'employee_code'         => 'NV001',
                'department_name'       => 'Phòng Kỹ Thuật',
                'position_title'        => 'Kỹ Sư Phần Mềm',
                'contract_number'       => 'HĐ-2025-001',
                'contract_start_date'   => now()->format('d/m/Y'),
                'contract_end_date'     => now()->addMonths(12)->format('d/m/Y'),
                'base_salary'           => number_format(15000000, 0, ',', '.'),
                'insurance_salary'      => number_format(12000000, 0, ',', '.'),
                'position_allowance'    => number_format(2000000, 0, ',', '.'),
                'working_time'          => 'T2–T6 08:00–17:00',
                'work_location'         => 'Văn phòng Ninh Bình',
                'other_allowances_text' => "- Ăn ca: 650.000 đ\n- Điện thoại: 200.000 đ",
                'company_name'          => 'Công ty ABC',
            ];

            // 2) Merge DOCX
            $templatePath = Storage::disk('public')->path($template->body_path);
            $processor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

            foreach ($sampleData as $key => $value) {
                $processor->setValue($key, $value ?? '');
            }

            $tmpDir = storage_path('app/tmp/contracts');
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0775, true);
            }

            $timestamp = time();
            $tmpDocx = $tmpDir . "/preview_{$timestamp}.docx";
            $processor->saveAs($tmpDocx);

            // 3) DOCX -> HTML
            $phpWord   = IOFactory::load($tmpDocx);
            $htmlFile  = $tmpDir . "/preview_{$timestamp}.html";
            $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
            $htmlWriter->save($htmlFile);

            $htmlContent = file_get_contents($htmlFile);

            // 4) Bơm meta UTF-8 + CSS font Unicode
            $css = '<meta charset="UTF-8">
                <style>
                    body, p, td, th, div, span {
                        font-family: "dejavu sans", "DejaVu Sans", "Times New Roman", sans-serif !important;
                        font-size: 11pt;
                    }
                </style>';

            // chèn ngay sau <head>
            $htmlContent = preg_replace('/<head>/i', '<head>' . $css, $htmlContent, 1);

            // 5) Dompdf với defaultFont = 'dejavu sans'
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'dejavu sans'); // trùng tên đã config ở config/dompdf.php

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($htmlContent, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfContent = $dompdf->output();

            // cleanup
            @unlink($tmpDocx);
            @unlink($htmlFile);

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename=\"preview.pdf\"');

        } catch (\Throwable $e) {
            return response('Lỗi preview: ' . $e->getMessage(), 422);
        }
    }
}
