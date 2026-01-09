<?php

namespace App\Http\Controllers;

use App\Models\CompanyRegion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Http\Requests\StoreCompanyRegionRequest;
use App\Http\Requests\UpdateCompanyRegionRequest;
use App\Http\Resources\CompanyRegionResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CompanyRegionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of company regions
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $status = (string) $request->get('status', '');

        $query = CompanyRegion::query();

        // Search
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('region', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status === 'current') {
            $query->current();
        } elseif ($status === 'historical') {
            $query->historical();
        }

        // Sort
        $query->orderBy('effective_from', 'desc');

        $regions = $query->get();

        return Inertia::render('CompanyRegionIndex', [
            'regions' => CompanyRegionResource::collection($regions)->resolve(),
            'enums' => [
                'regions' => [
                    ['value' => 1, 'label' => 'Vùng I (Hà Nội, TP.HCM, thành phố lớn)'],
                    ['value' => 2, 'label' => 'Vùng II (các tỉnh thành khác)'],
                    ['value' => 3, 'label' => 'Vùng III (các tỉnh trung du, miền núi)'],
                    ['value' => 4, 'label' => 'Vùng IV (vùng sâu, vùng xa)'],
                ],
                'statuses' => [
                    ['value' => '', 'label' => 'Tất cả'],
                    ['value' => 'current', 'label' => 'Đang áp dụng'],
                    ['value' => 'historical', 'label' => 'Đã kết thúc'],
                ],
            ],
        ]);
    }

    /**
     * Store a newly created region
     */
    public function store(StoreCompanyRegionRequest $request)
    {
        $validated = $request->validated();

        // If this is a new current region, close the previous one BEFORE checking overlap
        if (!isset($validated['effective_to'])) {
            $this->closePreviousRegion($validated['effective_from']);
        }

        // Check for overlapping periods AFTER closing previous region
        $this->checkOverlap($validated['effective_from'], $validated['effective_to'] ?? null);

        $region = CompanyRegion::create($validated);

        activity()
            ->performedOn($region)
            ->causedBy($request->user())
            ->withProperties([
                'attributes' => [
                    'region' => $region->region_name,
                    'effective_from' => $region->effective_from->format('d/m/Y'),
                    'effective_to' => $region->effective_to?->format('d/m/Y') ?? 'Hiện tại',
                ]
            ])
            ->log('Tạo cấu hình vùng BHXH');

        return redirect()->route('company-regions.index')
            ->with([
                'message' => 'Đã tạo cấu hình vùng BHXH mới thành công!',
                'type' => 'success'
            ]);
    }

    /**
     * Update the specified region
     */
    public function update(UpdateCompanyRegionRequest $request, CompanyRegion $companyRegion)
    {
        $oldData = [
            'region' => $companyRegion->region_name,
            'effective_from' => $companyRegion->effective_from->format('d/m/Y'),
            'effective_to' => $companyRegion->effective_to?->format('d/m/Y') ?? 'Hiện tại',
        ];

        $validated = $request->validated();

        // Check for overlapping periods (excluding current record)
        $this->checkOverlap(
            $validated['effective_from'],
            $validated['effective_to'] ?? null,
            $companyRegion->id
        );

        $companyRegion->update($validated);
        $companyRegion->refresh();

        $newData = [
            'region' => $companyRegion->region_name,
            'effective_from' => $companyRegion->effective_from->format('d/m/Y'),
            'effective_to' => $companyRegion->effective_to?->format('d/m/Y') ?? 'Hiện tại',
        ];

        activity()
            ->performedOn($companyRegion)
            ->causedBy($request->user())
            ->withProperties([
                'old' => $oldData,
                'attributes' => $newData
            ])
            ->log('Cập nhật cấu hình vùng BHXH');

        return redirect()->route('company-regions.index')
            ->with([
                'message' => 'Đã cập nhật cấu hình vùng BHXH thành công!',
                'type' => 'success'
            ]);
    }

    /**
     * Remove the specified region
     */
    public function destroy(CompanyRegion $companyRegion)
    {
        // Don't allow deleting the current region if it's the only one
        if ($companyRegion->isActive()) {
            $activeCount = CompanyRegion::current()->count();
            if ($activeCount <= 1) {
                return redirect()->route('company-regions.index')
                    ->with([
                        'message' => 'Không thể xóa vùng BHXH hiện tại duy nhất!',
                        'type' => 'error'
                    ]);
            }
        }

        activity()
            ->performedOn($companyRegion)
            ->causedBy(request()->user())
            ->withProperties([
                'attributes' => [
                    'region' => $companyRegion->region_name,
                    'effective_from' => $companyRegion->effective_from->format('d/m/Y'),
                ]
            ])
            ->log('Xóa cấu hình vùng BHXH');

        $companyRegion->delete();

        return redirect()->route('company-regions.index')
            ->with([
                'message' => 'Đã xóa cấu hình vùng BHXH thành công!',
                'type' => 'success'
            ]);
    }

    /**
     * Check for overlapping periods
     */
    private function checkOverlap($effectiveFrom, $effectiveTo, $excludeId = null)
    {
        $query = CompanyRegion::query();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $overlapping = $query->where(function ($q) use ($effectiveFrom, $effectiveTo) {
            // Case 1: New period starts during existing period
            $q->where(function ($subQ) use ($effectiveFrom) {
                $subQ->where('effective_from', '<=', $effectiveFrom)
                    ->where(function ($innerQ) use ($effectiveFrom) {
                        $innerQ->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $effectiveFrom);
                    });
            });

            // Case 2: New period ends during existing period
            if ($effectiveTo) {
                $q->orWhere(function ($subQ) use ($effectiveTo) {
                    $subQ->where('effective_from', '<=', $effectiveTo)
                        ->where(function ($innerQ) use ($effectiveTo) {
                            $innerQ->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', $effectiveTo);
                        });
                });
            }

            // Case 3: New period contains existing period
            $q->orWhere(function ($subQ) use ($effectiveFrom, $effectiveTo) {
                $subQ->where('effective_from', '>=', $effectiveFrom);
                if ($effectiveTo) {
                    $subQ->where(function ($innerQ) use ($effectiveTo) {
                        $innerQ->whereNull('effective_to')
                            ->orWhere('effective_to', '<=', $effectiveTo);
                    });
                }
            });
        })->exists();

        if ($overlapping) {
            abort(422, 'Khoảng thời gian hiệu lực bị trùng lặp với cấu hình khác!');
        }
    }

    /**
     * Close previous current region when creating a new one
     */
    private function closePreviousRegion($newEffectiveFrom)
    {
        CompanyRegion::whereNull('effective_to')
            ->update([
                'effective_to' => date('Y-m-d', strtotime($newEffectiveFrom . ' -1 day')),
            ]);
    }
}
