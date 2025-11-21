<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractTemplateRequest;
use App\Http\Resources\ContractTemplateResource;
use App\Models\ContractTemplate;
use App\Services\TemplateUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class ContractTemplateController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', ContractTemplate::class);

        $query = ContractTemplate::query()
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by type if provided (for API calls)
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $templates = $query->get();

        // Return JSON if requested (for API calls from frontend)
        if ($request->wantsJson() || $request->expectsJson() || $request->has('type')) {
            return response()->json([
                'data' => ContractTemplateResource::collection($templates)->resolve(),
            ]);
        }

        // Return Inertia page for normal web requests
        return Inertia::render('ContractTemplateIndex', [
            'templates' => ContractTemplateResource::collection($templates)->resolve(),
            'contractTypeOptions' => $this->contractTypeOptions(),
            'engineOptions'       => $this->engineOptions(),
            'statusOptions'       => [
                ['value' => 1, 'label' => 'Hoạt động'],
                ['value' => 0, 'label' => 'Ngừng dùng'],
            ],
            'placeholdersList'    => $this->placeholdersList(),
        ]);
    }

    public function store(ContractTemplateRequest $request)
    {
        $this->authorize('create', ContractTemplate::class);

        $data = $request->validated();

        // nếu is_default = true, bỏ default các template cùng type khác
        if (!empty($data['is_default'])) {
            ContractTemplate::where('type', $data['type'])
                ->update(['is_default' => false]);
        }

        $template = ContractTemplate::create($data);

        // Auto-create placeholder mappings nếu là DOCX_MERGE
        if ($template->engine === 'DOCX_MERGE' && $template->body_path) {
            $docxPath = Storage::disk('public')->path($template->body_path);
            if (file_exists($docxPath)) {
                TemplateUploadService::createPlaceholderMappings($template, $docxPath);
            }
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($template)
            ->withProperties([
                'attributes' => $template->toArray(),
            ])
            ->log('contract_template.created');

        return redirect()->route('contract-templates.index')
            ->with('success', 'Tạo mẫu hợp đồng thành công.');
    }

    public function update(ContractTemplateRequest $request, ContractTemplate $template)
    {
        $this->authorize('update', $template);

        $data = $request->validated();

        if (!empty($data['is_default'])) {
            ContractTemplate::where('type', $data['type'])
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $before = $template->getOriginal();
        $template->update($data);

        // Re-sync placeholder mappings nếu body_path thay đổi
        if ($template->engine === 'DOCX_MERGE' && $template->body_path && $template->wasChanged('body_path')) {
            $docxPath = Storage::disk('public')->path($template->body_path);
            if (file_exists($docxPath)) {
                // Xóa mappings cũ và tạo mới
                $template->placeholderMappings()->delete();
                TemplateUploadService::createPlaceholderMappings($template, $docxPath);
            }
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($template)
            ->withProperties([
                'old' => $before,
                'attributes' => $template->toArray(),
            ])
            ->log('contract_template.updated');

        return redirect()->route('contract-templates.index')
            ->with('success', 'Cập nhật mẫu hợp đồng thành công.');
    }

    public function destroy(Request $request, ContractTemplate $template)
    {
        $this->authorize('delete', $template);

        // Delete DOCX file if exists
        if ($template->engine === 'DOCX_MERGE' && $template->body_path) {
            TemplateUploadService::deleteDocxTemplate($template->body_path);
        }

        $snapshot = $template->toArray();
        $template->delete();

        activity()
            ->causedBy($request->user())
            ->performedOn($template)
            ->withProperties(['deleted' => $snapshot])
            ->log('contract_template.deleted');

        return redirect()->route('contract-templates.index')
            ->with('success', 'Đã xóa mẫu hợp đồng.');
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', ContractTemplate::class);

        $ids = (array) $request->input('ids', []);
        $items = ContractTemplate::whereIn('id', $ids)->get();

        foreach ($items as $item) {
            // Delete DOCX file if exists
            if ($item->engine === 'DOCX_MERGE' && $item->body_path) {
                TemplateUploadService::deleteDocxTemplate($item->body_path);
            }

            $snapshot = $item->toArray();
            $item->delete();

            activity()
                ->causedBy($request->user())
                ->performedOn($item)
                ->withProperties(['deleted' => $snapshot])
                ->log('contract_template.deleted');
        }

        return redirect()->route('contract-templates.index')
            ->with('success', 'Đã xóa các mẫu hợp đồng đã chọn.');
    }

    /**
     * Upload DOCX template file.
     *
     * POST /contract-templates/upload
     * Body: form-data with 'file' and 'contract_type'
     */
    public function upload(Request $request)
    {
        $this->authorize('create', ContractTemplate::class);

        $request->validate([
            'file' => 'required|file|mimes:docx',
            'contract_type' => 'required|string|in:PROBATION,FIXED_TERM,INDEFINITE,SERVICE,INTERNSHIP,PARTTIME',
        ]);

        try {
            $uploadResult = TemplateUploadService::uploadDocxTemplate(
                $request->file('file'),
                $request->input('contract_type')
            );

            return response()->json([
                'success' => true,
                'data' => $uploadResult,
                'message' => 'Tải file mẫu DOCX thành công.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function contractTypeOptions(): array
    {
        // Đồng bộ với enum bên Model/migration
        return [
            ['value' => 'PROBATION',   'label' => 'Thử việc'],
            ['value' => 'FIXED_TERM',  'label' => 'Xác định thời hạn'],
            ['value' => 'INDEFINITE',  'label' => 'Không xác định thời hạn'],
            ['value' => 'SERVICE',     'label' => 'Cộng tác/Dịch vụ'],
            ['value' => 'INTERNSHIP',  'label' => 'Thực tập'],
            ['value' => 'PARTTIME',    'label' => 'Bán thời gian'],
        ];
    }

    private function engineOptions(): array
    {
        return [
            ['value' => 'LIQUID',       'label' => 'Liquid Template'],
            ['value' => 'BLADE',        'label' => 'Blade View'],
            ['value' => 'HTML_TO_PDF',  'label' => 'HTML to PDF'],
            ['value' => 'DOCX_MERGE',   'label' => 'DOCX Merge'],
        ];
    }

    private function placeholdersList(): array
    {
        return [
            ['name' => 'employee.full_name',        'description' => 'Họ tên nhân viên'],
            ['name' => 'employee.employee_code',    'description' => 'Mã nhân viên'],
            ['name' => 'employee.id_number',        'description' => 'Số CMND/CCCD'],
            ['name' => 'employee.date_of_birth',    'description' => 'Ngày sinh'],
            ['name' => 'employee.address',          'description' => 'Địa chỉ'],
            ['name' => 'department.name',           'description' => 'Tên phòng ban'],
            ['name' => 'position.title',            'description' => 'Chức danh'],
            ['name' => 'contract.contract_number',  'description' => 'Số hợp đồng'],
            ['name' => 'contract.start_date',       'description' => 'Ngày bắt đầu'],
            ['name' => 'contract.end_date',         'description' => 'Ngày kết thúc'],
            ['name' => 'terms.base_salary',         'description' => 'Lương cơ bản'],
            ['name' => 'terms.insurance_salary',    'description' => 'Lương BHXH'],
            ['name' => 'terms.position_allowance',  'description' => 'Phụ cấp vị trí'],
            ['name' => 'terms.working_time',        'description' => 'Thời gian làm việc'],
            ['name' => 'terms.work_location',       'description' => 'Địa điểm làm việc'],
            ['name' => 'terms.other_allowances',    'description' => 'Các phụ cấp khác (array)'],
        ];
    }
}
