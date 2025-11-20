<?php

namespace App\Http\Controllers;

use App\Models\ContractAppendixTemplate;
use App\Models\ContractAppendixTemplatePlaceholderMapping;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ContractAppendixTemplatePlaceholderMappingController extends Controller
{
    use AuthorizesRequests;

    /**
     * Lấy danh sách mappings của template
     */
    public function index(ContractAppendixTemplate $template)
    {
        $this->authorize('view', $template);

        $mappings = $template->placeholderMappings()
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $mappings,
            'meta' => [
                'total' => $mappings->count(),
                'auto_mapped' => $mappings->where('data_source', '!=', 'MANUAL')->count(),
                'manual_required' => $mappings->where('data_source', 'MANUAL')->count(),
            ],
        ]);
    }

    /**
     * Cập nhật 1 mapping
     */
    public function update(Request $request, ContractAppendixTemplate $template, ContractAppendixTemplatePlaceholderMapping $mapping)
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'data_source' => 'required|in:CONTRACT,COMPUTED,MANUAL,SYSTEM',
            'source_path' => 'nullable|string|max:255',
            'default_value' => 'nullable|string|max:255',
            'transformer' => 'nullable|string|in:number_format,currency_to_words,date_vn,datetime_vn,gender_vn,marital_status_vn,contract_type_vn,uppercase,lowercase,ucfirst',
            'formula' => 'nullable|string',
            'is_required' => 'boolean',
        ]);

        $mapping->update($validated);

        return response()->json([
            'success' => true,
            'data' => $mapping->fresh(),
            'message' => 'Cập nhật mapping thành công.',
        ]);
    }

    /**
     * Bulk update nhiều mappings
     */
    public function bulkUpdate(Request $request, ContractAppendixTemplate $template)
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'mappings' => 'required|array',
            'mappings.*.id' => 'required|uuid|exists:contract_appendix_template_placeholder_mappings,id',
            'mappings.*.data_source' => 'required|in:CONTRACT,COMPUTED,MANUAL,SYSTEM',
            'mappings.*.source_path' => 'nullable|string|max:255',
            'mappings.*.default_value' => 'nullable|string|max:255',
            'mappings.*.transformer' => 'nullable|string',
            'mappings.*.is_required' => 'boolean',
        ]);

        $updated = 0;
        foreach ($validated['mappings'] as $mappingData) {
            $mapping = ContractAppendixTemplatePlaceholderMapping::find($mappingData['id']);
            if ($mapping && $mapping->appendix_template_id === $template->id) {
                $mapping->update($mappingData);
                $updated++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật {$updated} mappings.",
        ]);
    }

    /**
     * Apply preset cho placeholder
     */
    public function applyPreset(Request $request, ContractAppendixTemplate $template, ContractAppendixTemplatePlaceholderMapping $mapping)
    {
        $this->authorize('update', $template);

        $presets = config('appendix_placeholders.presets', []);
        $placeholderKey = $mapping->placeholder_key;

        if (!isset($presets[$placeholderKey])) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy preset cho placeholder này.',
            ], 404);
        }

        [$dataSource, $sourcePath, $transformer, $defaultValue] = $presets[$placeholderKey];

        $mapping->update([
            'data_source' => $dataSource,
            'source_path' => $sourcePath,
            'transformer' => $transformer,
            'default_value' => $defaultValue,
        ]);

        return response()->json([
            'success' => true,
            'data' => $mapping->fresh(),
            'message' => 'Đã áp dụng preset.',
        ]);
    }

    /**
     * Lấy danh sách presets có sẵn
     */
    public function presets()
    {
        $presets = config('appendix_placeholders.presets', []);
        $transformers = config('contract_placeholders.transformers', []);
        $dataSources = config('contract_placeholders.data_sources', []);

        return response()->json([
            'success' => true,
            'data' => [
                'presets' => $presets,
                'transformers' => $transformers,
                'data_sources' => $dataSources,
            ],
        ]);
    }

    /**
     * Re-sync placeholders từ file DOCX
     */
    public function resync(ContractAppendixTemplate $template)
    {
        $this->authorize('update', $template);

        if ($template->engine !== 'DOCX_MERGE' || !$template->body_path) {
            return response()->json([
                'success' => false,
                'message' => 'Template không phải DOCX_MERGE hoặc chưa có file.',
            ], 422);
        }

        $docxPath = \Illuminate\Support\Facades\Storage::disk('public')->path($template->body_path);
        if (!file_exists($docxPath)) {
            return response()->json([
                'success' => false,
                'message' => 'File DOCX không tồn tại.',
            ], 404);
        }

        // Extract placeholders từ DOCX
        $extractor = app(\App\Services\PlaceholderExtractorService::class);
        $currentPlaceholders = $extractor->extractFromDocx($docxPath);

        // Get existing mappings
        $existingKeys = $template->placeholderMappings()->pluck('placeholder_key')->toArray();

        // Compare manually (không dùng comparePlaceholders vì nó cần 2 file paths)
        $comparison = [
            'added' => array_values(array_diff($currentPlaceholders, $existingKeys)),
            'removed' => array_values(array_diff($existingKeys, $currentPlaceholders)),
            'unchanged' => array_values(array_intersect($currentPlaceholders, $existingKeys)),
        ];

        // Delete removed placeholders
        if (!empty($comparison['removed'])) {
            $template->placeholderMappings()
                ->whereIn('placeholder_key', $comparison['removed'])
                ->delete();
        }

        $presets = config('appendix_placeholders.presets', []);

        // Update existing placeholders if preset has changed
        $updatedCount = 0;
        if (!empty($comparison['unchanged'])) {
            foreach ($comparison['unchanged'] as $key) {
                if (isset($presets[$key])) {
                    $mapping = $template->placeholderMappings()
                        ->where('placeholder_key', $key)
                        ->first();

                    if ($mapping) {
                        [$dataSource, $sourcePath, $transformer, $defaultValue] = $presets[$key];

                        // Only update if different from preset
                        if ($mapping->data_source !== $dataSource
                            || $mapping->source_path !== $sourcePath
                            || $mapping->transformer !== $transformer
                            || $mapping->default_value !== $defaultValue) {

                            $mapping->update([
                                'data_source' => $dataSource,
                                'source_path' => $sourcePath,
                                'transformer' => $transformer,
                                'default_value' => $defaultValue,
                            ]);
                            $updatedCount++;
                        }
                    }
                }
            }
        }

        // Add new placeholders
        if (!empty($comparison['added'])) {
            $displayOrder = $template->placeholderMappings()->max('display_order') ?? 0;

            foreach ($comparison['added'] as $key) {
                $displayOrder++;

                if (isset($presets[$key])) {
                    [$dataSource, $sourcePath, $transformer, $defaultValue] = $presets[$key];
                    $template->placeholderMappings()->create([
                        'placeholder_key' => $key,
                        'data_source' => $dataSource,
                        'source_path' => $sourcePath,
                        'transformer' => $transformer,
                        'default_value' => $defaultValue,
                        'is_required' => false,
                        'display_order' => $displayOrder,
                    ]);
                } else {
                    // No preset found, create as MANUAL
                    $template->placeholderMappings()->create([
                        'placeholder_key' => $key,
                        'data_source' => 'MANUAL',
                        'source_path' => null,
                        'transformer' => null,
                        'default_value' => null,
                        'is_required' => false,
                        'display_order' => $displayOrder,
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đồng bộ placeholders thành công.',
            'stats' => [
                'added' => count($comparison['added']),
                'removed' => count($comparison['removed']),
                'unchanged' => count($comparison['unchanged']),
                'updated' => $updatedCount,
            ],
        ]);
    }
}
