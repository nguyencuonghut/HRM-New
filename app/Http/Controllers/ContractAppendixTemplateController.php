<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractAppendixTemplateRequest;
use App\Http\Resources\ContractAppendixTemplateResource;
use App\Models\ContractAppendixTemplate;
use App\Services\TemplateUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class ContractAppendixTemplateController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', ContractAppendixTemplate::class);

        $query = ContractAppendixTemplate::query()
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by appendix_type if provided
        if ($request->has('appendix_type')) {
            $query->where('appendix_type', $request->input('appendix_type'));
        }

        $templates = $query->get();

        // Return JSON if requested (for API calls from frontend)
        if ($request->wantsJson() || $request->expectsJson() || $request->has('appendix_type')) {
            return response()->json([
                'data' => ContractAppendixTemplateResource::collection($templates)->resolve(),
            ]);
        }

        // Return Inertia page for normal web requests
        return Inertia::render('ContractAppendixTemplateIndex', [
            'templates' => ContractAppendixTemplateResource::collection($templates)->resolve(),
            'appendixTypeOptions' => $this->appendixTypeOptions(),
            'statusOptions' => [
                ['value' => 1, 'label' => 'Hoạt động'],
                ['value' => 0, 'label' => 'Ngừng dùng'],
            ],
        ]);
    }

    public function store(ContractAppendixTemplateRequest $request)
    {
        $this->authorize('create', ContractAppendixTemplate::class);

        $data = $request->validated();

        // Nếu is_default = true, bỏ default các template cùng appendix_type khác
        if (!empty($data['is_default'])) {
            ContractAppendixTemplate::where('appendix_type', $data['appendix_type'])
                ->update(['is_default' => false]);
        }

        $template = ContractAppendixTemplate::create($data);

        // Auto-create placeholder mappings nếu body_path đã có
        if ($template->body_path) {
            $docxPath = Storage::disk('public')->path($template->body_path);
            if (file_exists($docxPath)) {
                TemplateUploadService::createPlaceholderMappingsForAppendix($template, $docxPath);
            }
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($template)
            ->withProperties([
                'attributes' => $template->toArray(),
            ])
            ->log('appendix_template.created');

        return redirect()->route('contract-appendix-templates.index')
            ->with('success', 'Tạo mẫu phụ lục thành công.');
    }

    public function update(ContractAppendixTemplateRequest $request, ContractAppendixTemplate $template)
    {
        $this->authorize('update', $template);

        $data = $request->validated();

        if (!empty($data['is_default'])) {
            ContractAppendixTemplate::where('appendix_type', $data['appendix_type'])
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $before = $template->getOriginal();
        $template->update($data);

        // Re-sync placeholder mappings nếu body_path thay đổi
        if ($template->body_path && $template->wasChanged('body_path')) {
            $docxPath = Storage::disk('public')->path($template->body_path);
            if (file_exists($docxPath)) {
                // Xóa mappings cũ và tạo mới
                $template->placeholderMappings()->delete();
                TemplateUploadService::createPlaceholderMappingsForAppendix($template, $docxPath);
            }
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($template)
            ->withProperties([
                'old' => $before,
                'attributes' => $template->toArray(),
            ])
            ->log('appendix_template.updated');

        return redirect()->route('contract-appendix-templates.index')
            ->with('success', 'Cập nhật mẫu phụ lục thành công.');
    }

    public function destroy(Request $request, ContractAppendixTemplate $template)
    {
        $this->authorize('delete', $template);

        // Delete DOCX file if exists
        if ($template->body_path) {
            TemplateUploadService::deleteDocxTemplate($template->body_path);
        }

        $snapshot = $template->toArray();
        $template->delete();

        activity()
            ->causedBy($request->user())
            ->performedOn($template)
            ->withProperties(['deleted' => $snapshot])
            ->log('appendix_template.deleted');

        return redirect()->route('contract-appendix-templates.index')
            ->with('success', 'Đã xóa mẫu phụ lục.');
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', ContractAppendixTemplate::class);

        $ids = (array) $request->input('ids', []);
        $items = ContractAppendixTemplate::whereIn('id', $ids)->get();

        foreach ($items as $item) {
            // Delete DOCX file if exists
            if ($item->body_path) {
                TemplateUploadService::deleteDocxTemplate($item->body_path);
            }

            $snapshot = $item->toArray();
            $item->delete();

            activity()
                ->causedBy($request->user())
                ->performedOn($item)
                ->withProperties(['deleted' => $snapshot])
                ->log('appendix_template.deleted');
        }

        return redirect()->route('contract-appendix-templates.index')
            ->with('success', 'Đã xóa các mẫu phụ lục đã chọn.');
    }

    /**
     * Upload DOCX template file for appendix.
     *
     * POST /contract-appendix-templates/upload
     * Body: form-data with 'file' and 'appendix_type'
     */
    public function upload(Request $request)
    {
        $this->authorize('create', ContractAppendixTemplate::class);

        $request->validate([
            'file' => 'required|file|mimes:docx',
            'appendix_type' => 'required|string|in:SALARY,ALLOWANCE,POSITION,DEPARTMENT,WORKING_TERMS,EXTENSION,OTHER',
        ]);

        try {
            $uploadResult = TemplateUploadService::uploadAppendixDocxTemplate(
                $request->file('file'),
                $request->input('appendix_type')
            );

            return response()->json([
                'success' => true,
                'data' => $uploadResult,
                'message' => 'Tải file mẫu DOCX phụ lục thành công.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function appendixTypeOptions(): array
    {
        return [
            ['value' => 'SALARY',        'label' => 'Điều chỉnh lương'],
            ['value' => 'ALLOWANCE',     'label' => 'Điều chỉnh phụ cấp'],
            ['value' => 'POSITION',      'label' => 'Điều chỉnh chức danh'],
            ['value' => 'DEPARTMENT',    'label' => 'Điều chuyển đơn vị'],
            ['value' => 'WORKING_TERMS', 'label' => 'Điều chỉnh điều kiện làm việc'],
            ['value' => 'EXTENSION',     'label' => 'Gia hạn hợp đồng'],
            ['value' => 'OTHER',         'label' => 'Khác'],
        ];
    }
}
