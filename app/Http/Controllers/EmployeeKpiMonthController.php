<?php

namespace App\Http\Controllers;

use App\Models\EmployeeKpiMonth;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Http\Requests\StoreEmployeeKpiMonthRequest;
use App\Http\Requests\UpdateEmployeeKpiMonthRequest;
use App\Http\Resources\EmployeeKpiMonthResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmployeeKpiMonthController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /employee-kpi-months
     * Trả về danh sách KPI tháng với filter
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', EmployeeKpiMonth::class);

        $search = trim((string) $request->get('search', ''));
        $year = $request->get('year');
        $month = $request->get('month');
        $employeeId = $request->get('employee_id');

        $query = EmployeeKpiMonth::query()
            ->with(['employee:id,employee_code,full_name', 'inputBy:id,name'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('employee', function ($qq) use ($search) {
                    $qq->where('full_name', 'like', "%{$search}%")
                       ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($year, fn($q) => $q->where('year', $year))
            ->when($month, fn($q) => $q->where('month', $month))
            ->when($employeeId, fn($q) => $q->where('employee_id', $employeeId))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('created_at', 'desc');

        $kpis = $query->get();

        // Lấy danh sách nhân viên để dropdown
        // Note: display_name accessor will be auto-appended (defined in $appends)
        $employees = Employee::query()
            ->orderBy('full_name')
            ->get(['id', 'employee_code', 'full_name']);

        // Lấy danh sách năm để filter (từ dữ liệu có sẵn)
        $years = EmployeeKpiMonth::query()
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Nếu không có năm nào, thêm năm hiện tại
        if (empty($years)) {
            $years = [date('Y')];
        }

        return Inertia::render('EmployeeKpiMonth/Index', [
            'kpis' => EmployeeKpiMonthResource::collection($kpis)->resolve(),
            'employees' => $employees,
            'years' => $years,
            'enums' => [
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
            ],
        ]);
    }

    /**
     * POST /employee-kpi-months
     */
    public function store(StoreEmployeeKpiMonthRequest $request)
    {
        // $this->authorize('create', EmployeeKpiMonth::class);

        $data = $request->validated();

        // Check duplicate
        $exists = EmployeeKpiMonth::where('employee_id', $data['employee_id'])
            ->where('year', $data['year'])
            ->where('month', $data['month'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['employee_id' => 'KPI tháng này của nhân viên đã tồn tại.'])
                ->withInput();
        }

        // Thêm thông tin người nhập
        $data['input_by'] = $request->user()->id;
        $data['input_at'] = now();

        $kpi = EmployeeKpiMonth::create($data);
        $kpi->load('employee');

        activity()
            ->performedOn($kpi)
            ->causedBy($request->user())
            ->withProperties([
                'attributes' => [
                    'employee' => $kpi->employee->full_name,
                    'year' => $kpi->year,
                    'month' => $kpi->month,
                    'kpi_score' => $kpi->kpi_score,
                ]
            ])
            ->log('Tạo KPI tháng');

        return redirect()->route('employee-kpi-months.index')
            ->with([
                'message' => 'Tạo KPI tháng thành công.',
                'type' => 'success'
            ]);
    }

    /**
     * PUT /employee-kpi-months/{employee_kpi_month}
     */
    public function update(UpdateEmployeeKpiMonthRequest $request, EmployeeKpiMonth $employeeKpiMonth)
    {
        // $this->authorize('update', $employeeKpiMonth);

        $employeeKpiMonth->load('employee');
        $oldData = [
            'employee' => $employeeKpiMonth->employee->full_name,
            'year' => $employeeKpiMonth->year,
            'month' => $employeeKpiMonth->month,
            'kpi_score' => $employeeKpiMonth->kpi_score,
        ];

        $data = $request->validated();

        // Check duplicate nếu thay đổi employee/year/month
        if (
            $data['employee_id'] != $employeeKpiMonth->employee_id ||
            $data['year'] != $employeeKpiMonth->year ||
            $data['month'] != $employeeKpiMonth->month
        ) {
            $exists = EmployeeKpiMonth::where('employee_id', $data['employee_id'])
                ->where('year', $data['year'])
                ->where('month', $data['month'])
                ->where('id', '!=', $employeeKpiMonth->id)
                ->exists();

            if ($exists) {
                return redirect()->back()
                    ->withErrors(['employee_id' => 'KPI tháng này của nhân viên đã tồn tại.'])
                    ->withInput();
            }
        }

        // Update input info
        $data['input_by'] = $request->user()->id;
        $data['input_at'] = now();

        $employeeKpiMonth->update($data);
        $employeeKpiMonth->refresh()->load('employee');

        $newData = [
            'employee' => $employeeKpiMonth->employee->full_name,
            'year' => $employeeKpiMonth->year,
            'month' => $employeeKpiMonth->month,
            'kpi_score' => $employeeKpiMonth->kpi_score,
        ];

        activity()
            ->performedOn($employeeKpiMonth)
            ->causedBy($request->user())
            ->withProperties([
                'old' => $oldData,
                'attributes' => $newData
            ])
            ->log('Cập nhật KPI tháng');

        return redirect()->route('employee-kpi-months.index')
            ->with([
                'message' => 'Cập nhật KPI tháng thành công.',
                'type' => 'success'
            ]);
    }

    /**
     * DELETE /employee-kpi-months/{employee_kpi_month}
     */
    public function destroy(EmployeeKpiMonth $employeeKpiMonth)
    {
        // $this->authorize('delete', $employeeKpiMonth);

        $employeeKpiMonth->load('employee');
        $oldData = [
            'employee' => $employeeKpiMonth->employee->full_name,
            'year' => $employeeKpiMonth->year,
            'month' => $employeeKpiMonth->month,
            'kpi_score' => $employeeKpiMonth->kpi_score,
        ];

        $employeeKpiMonth->delete();

        activity()
            ->performedOn($employeeKpiMonth)
            ->causedBy(request()->user())
            ->withProperties(['old' => $oldData])
            ->log('Xóa KPI tháng');

        return redirect()->route('employee-kpi-months.index')
            ->with([
                'message' => 'Đã xóa KPI tháng thành công.',
                'type' => 'success'
            ]);
    }

    /**
     * DELETE /employee-kpi-months/bulk-delete
     */
    public function bulkDelete(Request $request)
    {
        // $this->authorize('bulkDelete', EmployeeKpiMonth::class);

        $ids = (array) $request->get('ids', []);
        if (empty($ids)) {
            return redirect()->route('employee-kpi-months.index')
                ->with([
                    'message' => 'Không có mục nào được chọn để xóa.',
                    'type' => 'warning'
                ]);
        }

        $kpis = EmployeeKpiMonth::with('employee')->whereIn('id', $ids)->get();
        $deletedRecords = $kpis->map(function ($kpi) {
            return [
                'employee' => $kpi->employee->full_name,
                'year' => $kpi->year,
                'month' => $kpi->month,
                'kpi_score' => $kpi->kpi_score,
            ];
        })->toArray();

        $deletedCount = $kpis->count();
        EmployeeKpiMonth::whereIn('id', $ids)->delete();

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'count' => $deletedCount,
                'deleted_records' => $deletedRecords
            ])
            ->log('Xóa hàng loạt KPI tháng');

        return redirect()->route('employee-kpi-months.index')
            ->with([
                'message' => "Đã xóa {$deletedCount} KPI tháng thành công.",
                'type' => 'success'
            ]);
    }
}
