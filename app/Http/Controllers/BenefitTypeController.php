<?php

namespace App\Http\Controllers;

use App\Models\BenefitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Http\Requests\StoreBenefitTypeRequest;
use App\Http\Requests\UpdateBenefitTypeRequest;
use App\Http\Resources\BenefitTypeResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BenefitTypeController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /benefit-types
     * Danh sách loại phúc lợi
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', BenefitType::class);

        $search = trim((string) $request->get('search', ''));
        $isActive = $request->get('is_active');

        $query = BenefitType::query()
            ->withCount('payouts')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('code', 'like', "%{$search}%")
                       ->orWhere('name', 'like', "%{$search}%")
                       ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($isActive !== null && $isActive !== '', fn($q) => $q->where('is_active', $isActive))
            ->orderBy('name');

        $benefitTypes = $query->get();

        return Inertia::render('BenefitType/Index', [
            'benefitTypes' => BenefitTypeResource::collection($benefitTypes)->resolve(),
        ]);
    }

    /**
     * POST /benefit-types
     * Tạo mới loại phúc lợi
     */
    public function store(StoreBenefitTypeRequest $request)
    {
        // $this->authorize('create', BenefitType::class);

        DB::beginTransaction();
        try {
            $benefitType = BenefitType::create($request->validated());

            DB::commit();
            return redirect()->back()->with('success', 'Tạo loại phúc lợi thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * PUT /benefit-types/{id}
     * Cập nhật loại phúc lợi
     */
    public function update(UpdateBenefitTypeRequest $request, BenefitType $benefitType)
    {
        // $this->authorize('update', $benefitType);

        DB::beginTransaction();
        try {
            $benefitType->update($request->validated());

            DB::commit();
            return redirect()->back()->with('success', 'Cập nhật loại phúc lợi thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * DELETE /benefit-types/{id}
     * Xóa loại phúc lợi
     */
    public function destroy(BenefitType $benefitType)
    {
        // $this->authorize('delete', $benefitType);

        // Kiểm tra xem có khoản chi nào đang dùng loại này không
        if ($benefitType->payouts()->count() > 0) {
            return redirect()->back()->withErrors(['error' => 'Không thể xóa loại phúc lợi đã có khoản chi!']);
        }

        DB::beginTransaction();
        try {
            $benefitType->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Xóa loại phúc lợi thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * DELETE /benefit-types/bulk-delete
     * Xóa nhiều loại phúc lợi
     */
    public function bulkDelete(Request $request)
    {
        // $this->authorize('delete', BenefitType::class);

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return redirect()->back()->withErrors(['error' => 'Vui lòng chọn ít nhất một loại phúc lợi!']);
        }

        // Kiểm tra các loại có khoản chi không
        $typesWithPayouts = BenefitType::whereIn('id', $ids)
            ->has('payouts')
            ->count();

        if ($typesWithPayouts > 0) {
            return redirect()->back()->withErrors(['error' => 'Không thể xóa loại phúc lợi đã có khoản chi!']);
        }

        DB::beginTransaction();
        try {
            BenefitType::whereIn('id', $ids)->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Xóa loại phúc lợi thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
}
