<?php

namespace App\Http\Controllers;

use App\Models\EmployeeBenefitPayout;
use App\Models\Employee;
use App\Models\BenefitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Http\Requests\StoreEmployeeBenefitPayoutRequest;
use App\Http\Requests\UpdateEmployeeBenefitPayoutRequest;
use App\Http\Resources\EmployeeBenefitPayoutResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmployeeBenefitPayoutController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /employee-benefit-payouts
     * Danh sách khoản chi phúc lợi
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', EmployeeBenefitPayout::class);

        $search = trim((string) $request->get('search', ''));
        $year = $request->get('year');
        $month = $request->get('month');
        $employeeId = $request->get('employee_id');
        $benefitTypeId = $request->get('benefit_type_id');

        $query = EmployeeBenefitPayout::query()
            ->with(['employee:id,employee_code,full_name', 'benefitType:id,code,name', 'paidByUser:id,name'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->whereHas('employee', function ($qqq) use ($search) {
                        $qqq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%");
                    })
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
                });
            })
            ->when($year, fn($q) => $q->whereYear('paid_date', $year))
            ->when($month, fn($q) => $q->whereMonth('paid_date', $month))
            ->when($employeeId, fn($q) => $q->where('employee_id', $employeeId))
            ->when($benefitTypeId, fn($q) => $q->where('benefit_type_id', $benefitTypeId))
            ->orderBy('paid_date', 'desc')
            ->orderBy('created_at', 'desc');

        $payouts = $query->get();

        // Lấy danh sách nhân viên để dropdown
        // Note: display_name accessor will be auto-appended (defined in $appends)
        $employees = Employee::query()
            ->orderBy('full_name')
            ->get(['id', 'employee_code', 'full_name']);

        // Lấy danh sách loại phúc lợi active
        $benefitTypes = BenefitType::active()
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        // Lấy danh sách năm để filter
        $years = EmployeeBenefitPayout::query()
            ->selectRaw('DISTINCT YEAR(paid_date) as year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [date('Y')];
        }

        return Inertia::render('EmployeeBenefitPayout/Index', [
            'payouts' => EmployeeBenefitPayoutResource::collection($payouts)->resolve(),
            'employees' => $employees,
            'benefitTypes' => $benefitTypes,
            'years' => $years,
            'months' => [
                ['value' => 1, 'label' => 'Tháng 1'],
                ['value' => 2, 'label' => 'Tháng 2'],
                ['value' => 3, 'label' => 'Tháng 3'],
                ['value' => 4, 'label' => 'Tháng 4'],
                ['value' => 5, 'label' => 'Tháng 5'],
                ['value' => 6, 'label' => 'Tháng 6'],
                ['value' => 7, 'label' => 'Tháng 7'],
                ['value' => 8, 'label' => 'Tháng 8'],
                ['value' => 9, 'label' => 'Tháng 9'],
                ['value' => 10, 'label' => 'Tháng 10'],
                ['value' => 11, 'label' => 'Tháng 11'],
                ['value' => 12, 'label' => 'Tháng 12'],
            ],
            'paymentMethods' => [
                ['value' => 'CASH', 'label' => 'Tiền mặt'],
                ['value' => 'BANK_TRANSFER', 'label' => 'Chuyển khoản'],
            ],
        ]);
    }

    /**
     * POST /employee-benefit-payouts
     * Tạo mới khoản chi phúc lợi
     */
    public function store(StoreEmployeeBenefitPayoutRequest $request)
    {
        // $this->authorize('create', EmployeeBenefitPayout::class);

        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['paid_by'] = auth()->id();

            $payout = EmployeeBenefitPayout::create($data);

            DB::commit();
            return redirect()->back()->with('success', 'Tạo khoản chi phúc lợi thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * PUT /employee-benefit-payouts/{id}
     * Cập nhật khoản chi phúc lợi
     */
    public function update(UpdateEmployeeBenefitPayoutRequest $request, EmployeeBenefitPayout $employeeBenefitPayout)
    {
        // $this->authorize('update', $employeeBenefitPayout);

        DB::beginTransaction();
        try {
            $employeeBenefitPayout->update($request->validated());

            DB::commit();
            return redirect()->back()->with('success', 'Cập nhật khoản chi phúc lợi thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * DELETE /employee-benefit-payouts/{id}
     * Xóa khoản chi phúc lợi
     */
    public function destroy(EmployeeBenefitPayout $employeeBenefitPayout)
    {
        // $this->authorize('delete', $employeeBenefitPayout);

        DB::beginTransaction();
        try {
            $employeeBenefitPayout->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Xóa khoản chi phúc lợi thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * DELETE /employee-benefit-payouts/bulk-delete
     * Xóa nhiều khoản chi phúc lợi
     */
    public function bulkDelete(Request $request)
    {
        // $this->authorize('delete', EmployeeBenefitPayout::class);

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return redirect()->back()->withErrors(['error' => 'Vui lòng chọn ít nhất một khoản chi!']);
        }

        DB::beginTransaction();
        try {
            EmployeeBenefitPayout::whereIn('id', $ids)->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Xóa khoản chi phúc lợi thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
}
