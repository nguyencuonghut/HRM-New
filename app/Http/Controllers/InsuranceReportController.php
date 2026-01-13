<?php

namespace App\Http\Controllers;

use App\Http\Resources\InsuranceMonthlyReportResource;
use App\Http\Resources\InsuranceChangeRecordResource;
use App\Models\InsuranceMonthlyReport;
use App\Models\InsuranceChangeRecord;
use App\Services\InsuranceReportService;
use App\Services\InsuranceExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class InsuranceReportController extends Controller
{
    use AuthorizesRequests;

    protected InsuranceReportService $reportService;

    public function __construct(InsuranceReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display a listing of insurance reports
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', InsuranceMonthlyReport::class);

        $query = InsuranceMonthlyReport::query()->with('changeRecords');

        // Filter by year
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'year');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder)
            ->orderBy('month', 'desc');

        $reports = $query->paginate($request->input('per_page', 15));

        return Inertia::render('Insurance/Reports/Index', [
            'reports' => [
                'data' => InsuranceMonthlyReportResource::collection($reports->items())->resolve(),
                'current_page' => $reports->currentPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'last_page' => $reports->lastPage(),
            ],
            'filters' => $request->only(['year', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new report
     */
    public function create()
    {
        $this->authorize('create', InsuranceMonthlyReport::class);

        return Inertia::render('Insurance/Reports/Create', [
            'currentYear' => now()->year,
            'currentMonth' => now()->month,
        ]);
    }

    /**
     * Store a newly created report (generate it)
     */
    public function store(Request $request)
    {
        $this->authorize('create', InsuranceMonthlyReport::class);

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        try {
            $report = $this->reportService->generateMonthlyReport(
                $validated['year'],
                $validated['month']
            );

            return redirect()->route('insurance-reports.show', $report)->with([
                'message' => 'Tạo báo cáo thành công',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Display the specified report with all change records
     */
    public function show(InsuranceMonthlyReport $insuranceReport)
    {
        $this->authorize('view', $insuranceReport);

        $insuranceReport->load(['changeRecords.employee', 'changeRecords.approvedBy']);

        // Group records by type
        $increaseRecords = $insuranceReport->increaseRecords()->with(['employee', 'approvedBy'])->get();
        $decreaseRecords = $insuranceReport->decreaseRecords()->with(['employee', 'approvedBy'])->get();
        $adjustRecords = $insuranceReport->adjustRecords()->with(['employee', 'approvedBy'])->get();

        return Inertia::render('Insurance/Reports/Detail', [
            'report' => (new InsuranceMonthlyReportResource($insuranceReport))->resolve(),
            'increaseRecords' => InsuranceChangeRecordResource::collection($increaseRecords)->resolve(),
            'decreaseRecords' => InsuranceChangeRecordResource::collection($decreaseRecords)->resolve(),
            'adjustRecords' => InsuranceChangeRecordResource::collection($adjustRecords)->resolve(),
            'canApprove' => Auth::user()->hasAnyRole(['Admin', 'Super Admin']),
        ]);
    }

    /**
     * Approve a change record
     */
    public function approve(Request $request, InsuranceChangeRecord $record)
    {
        $this->authorize('update', $record->report);

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->reportService->approveRecord(
                $record,
                Auth::user(),
                $validated['admin_notes'] ?? null
            );

            return redirect()->back()->with([
                'message' => 'Duyệt thay đổi BH thành công',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Reject a change record
     */
    public function reject(Request $request, InsuranceChangeRecord $record)
    {
        $this->authorize('update', $record->report);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $this->reportService->rejectRecord(
                $record,
                Auth::user(),
                $validated['reason']
            );

            return redirect()->back()->with([
                'message' => 'Từ chối thay đổi BH thành công',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Adjust a change record (modify salary)
     */
    public function adjust(Request $request, InsuranceChangeRecord $record)
    {
        $this->authorize('update', $record->report);

        $validated = $request->validate([
            'adjusted_salary' => 'required|numeric|min:0',
            'adjustment_reason' => 'required|string|max:1000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->reportService->adjustRecord(
                $record,
                Auth::user(),
                $validated['adjusted_salary'],
                $validated['adjustment_reason'],
                $validated['admin_notes'] ?? null
            );

            return redirect()->back()->with([
                'message' => 'Điều chỉnh thay đổi BH thành công',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Update declaration month for a change record
     * Allows reviewer to override suggested declaration month
     */
    public function updateDeclarationMonth(Request $request, InsuranceChangeRecord $record)
    {
        $this->authorize('update', $record->report);

        $validated = $request->validate([
            'declaration_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'override_reason' => 'nullable|string|max:1000',
        ]);

        // If declaration_month differs from suggested, reason is required
        if ($validated['declaration_month'] !== $record->suggested_declaration_month) {
            $request->validate([
                'override_reason' => 'required|string|max:1000',
            ]);
        }

        try {
            $this->reportService->updateDeclarationMonth(
                $record,
                Auth::user(),
                $validated['declaration_month'],
                $validated['override_reason'] ?? null
            );

            return redirect()->back()->with([
                'message' => 'Cập nhật tháng đóng BHXH thành công',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Finalize report (lock it)
     */
    public function finalize(InsuranceMonthlyReport $insuranceReport)
    {
        $this->authorize('update', $insuranceReport);

        try {
            $this->reportService->finalizeReport($insuranceReport, Auth::user());

            return redirect()->back()->with([
                'message' => 'Hoàn tất báo cáo thành công',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Export report to Excel
     */
    public function export(InsuranceMonthlyReport $insuranceReport)
    {
        $this->authorize('view', $insuranceReport);

        try {
            $filePath = InsuranceExportService::exportToFile($insuranceReport);

            return Storage::download($filePath);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Remove the specified report
     */
    public function destroy(InsuranceMonthlyReport $insuranceReport)
    {
        $this->authorize('delete', $insuranceReport);

        try {
            $this->reportService->deleteReport($insuranceReport);

            return redirect()->route('insurance-reports.index')->with([
                'message' => 'Xóa báo cáo thành công',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Get snapshot contributions for finalized report
     */
    public function getSnapshot(InsuranceMonthlyReport $insuranceReport)
    {
        $this->authorize('view', $insuranceReport);

        // Check if report is finalized
        if ($insuranceReport->status !== 'FINALIZED') {
            return response()->json([
                'message' => 'Báo cáo chưa được hoàn tất'
            ], 422);
        }

        // Load snapshot contributions with items
        $contributions = \App\Models\InsuranceMonthlyContribution::where('report_id', $insuranceReport->id)
            ->with([
                'employee:id,employee_code,full_name',
                'items' => function ($query) {
                    $query->orderBy('component_code');
                }
            ])
            ->orderBy('employee_id')
            ->get();

        return response()->json([
            'contributions' => $contributions->map(function ($contrib) {
                return [
                    'id' => $contrib->id,
                    'employee' => $contrib->employee ? [
                        'id' => $contrib->employee->id,
                        'employee_code' => $contrib->employee->employee_code,
                        'full_name' => $contrib->employee->full_name,
                    ] : null,
                    'base_insurance_salary' => $contrib->base_insurance_salary,
                    'total_amount' => $contrib->total_amount,
                    'items' => $contrib->items->map(function ($item) {
                        return [
                            'component_id' => $item->component_id,
                            'component_code' => $item->component_code,
                            'component_name' => $item->component_name,
                            'base_type' => $item->base_type,
                            'base_used' => $item->base_used,
                            'rate_total' => $item->rate_total,
                            'amount' => $item->amount,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Export snapshot to Excel file
     */
    public function exportToExcel(InsuranceMonthlyReport $insuranceReport)
    {
        $this->authorize('view', $insuranceReport);

        try {
            // Use existing export service to generate Excel
            $filePath = InsuranceExportService::exportToFile($insuranceReport);

            return Storage::download($filePath, "BaoCao_BHXH_{$insuranceReport->year}_{$insuranceReport->month}.xlsx");
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
}

