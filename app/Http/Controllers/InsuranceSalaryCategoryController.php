<?php

namespace App\Http\Controllers;

use App\Models\InsuranceSalaryCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\StoreInsuranceSalaryCategoryRequest;
use App\Http\Requests\UpdateInsuranceSalaryCategoryRequest;
use App\Http\Resources\InsuranceSalaryCategoryResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class InsuranceSalaryCategoryController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of salary categories
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', InsuranceSalaryCategory::class);

        $search = trim((string) $request->get('search', ''));
        $isActiveQ = $request->has('is_active') ? $request->get('is_active') : null;

        // Normalize is_active from query
        $isActive = null;
        if ($isActiveQ === '1' || $isActiveQ === 'true' || $isActiveQ === 1 || $isActiveQ === true) {
            $isActive = true;
        } elseif ($isActiveQ === '0' || $isActiveQ === 'false' || $isActiveQ === 0 || $isActiveQ === false) {
            $isActive = false;
        }

        $query = InsuranceSalaryCategory::query()
            ->withCount('positions')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%")
                       ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(!is_null($isActive), fn($q) => $q->where('is_active', $isActive))
            ->ordered();

        $insuranceSalaryCategories = $query->get();

        return Inertia::render('InsuranceSalaryCategoryIndex', [
            'insuranceSalaryCategories' => InsuranceSalaryCategoryResource::collection($insuranceSalaryCategories)->resolve(),
        ]);
    }

    /**
     * Store a newly created insurance salary category
     */
    public function store(StoreInsuranceSalaryCategoryRequest $request)
    {
        $this->authorize('create', InsuranceSalaryCategory::class);

        $data = $request->validated();

        // Auto-generate code from name if not provided
        if (empty($data['code'])) {
            $data['code'] = \Illuminate\Support\Str::slug($data['name'], '_');
        }

        $insuranceInsuranceSalaryCategory = InsuranceSalaryCategory::create($data);

        activity()
            ->performedOn($insuranceSalaryCategory)
            ->causedBy($request->user())
            ->withProperties([
                'attributes' => [
                    'code' => $insuranceSalaryCategory->code,
                    'name' => $insuranceSalaryCategory->name,
                    'is_active' => $insuranceSalaryCategory->is_active ? 'Kích hoạt' : 'Vô hiệu hóa',
                ]
            ])
            ->log('Tạo nhóm chức danh BHXH');

        return redirect()->route('insurance-salary-categories.index')->with([
            'message' => 'Tạo nhóm chức danh thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Update the specified salary category
     */
    public function update(UpdateInsuranceSalaryCategoryRequest $request, InsuranceSalaryCategory $insuranceSalaryCategory)
    {
        $this->authorize('update', $insuranceSalaryCategory);

        $oldData = [
            'code' => $insuranceSalaryCategory->code,
            'name' => $insuranceSalaryCategory->name,
            'is_active' => $insuranceSalaryCategory->is_active ? 'Kích hoạt' : 'Vô hiệu hóa',
        ];

        $data = $request->validated();
        $insuranceSalaryCategory->update($data);

        activity()
            ->performedOn($insuranceSalaryCategory)
            ->causedBy($request->user())
            ->withProperties([
                'old' => $oldData,
                'attributes' => [
                    'code' => $insuranceSalaryCategory->code,
                    'name' => $insuranceSalaryCategory->name,
                    'is_active' => $insuranceSalaryCategory->is_active ? 'Kích hoạt' : 'Vô hiệu hóa',
                ]
            ])
            ->log('Cập nhật nhóm chức danh BHXH');

        return redirect()->route('insurance-salary-categories.index')->with([
            'message' => 'Cập nhật nhóm chức danh thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Remove the specified salary category
     */
    public function destroy(InsuranceSalaryCategory $insuranceSalaryCategory)
    {
        $this->authorize('delete', $insuranceSalaryCategory);

        // Check if category is being used by positions
        if ($insuranceSalaryCategory->positions()->count() > 0) {
            return redirect()->route('insurance-salary-categories.index')->with([
                'message' => 'Không thể xóa nhóm chức danh này vì đang có vị trí sử dụng!',
                'type' => 'error'
            ]);
        }

        $categoryName = $insuranceSalaryCategory->name;
        $insuranceSalaryCategory->delete();

        activity()
            ->causedBy($request()->user())
            ->withProperties([
                'attributes' => [
                    'name' => $categoryName
                ]
            ])
            ->log('Xóa nhóm chức danh BHXH');

        return redirect()->route('insurance-salary-categories.index')->with([
            'message' => 'Xóa nhóm chức danh thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Bulk delete salary categories
     */
    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', InsuranceSalaryCategory::class);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:salary_categories,id'
        ]);

        $categoriesToDelete = InsuranceSalaryCategory::whereIn('id', $request->ids)->get();

        // Check if any categories are being used
        $categoriesInUse = [];
        foreach ($categoriesToDelete as $category) {
            if ($category->positions()->count() > 0) {
                $categoriesInUse[] = $category->name;
            }
        }

        if (!empty($categoriesInUse)) {
            return redirect()->route('insurance-salary-categories.index')->with([
                'message' => 'Không thể xóa các nhóm sau vì đang có vị trí sử dụng: ' . implode(', ', $categoriesInUse),
                'type' => 'error'
            ]);
        }

        $deletedCount = InsuranceSalaryCategory::whereIn('id', $request->ids)->delete();

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'count' => $deletedCount,
                'ids' => $request->ids
            ])
            ->log('Xóa nhiều nhóm chức danh BHXH');

        return redirect()->route('insurance-salary-categories.index')->with([
            'message' => "Đã xóa {$deletedCount} nhóm chức danh!",
            'type' => 'success'
        ]);
    }
}
