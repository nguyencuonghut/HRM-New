<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAnnualReview;
use App\Models\Employee;
use App\Models\EmployeeKpiMonth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Http\Requests\StoreEmployeeAnnualReviewRequest;
use App\Http\Requests\UpdateEmployeeAnnualReviewRequest;
use App\Http\Resources\EmployeeAnnualReviewResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmployeeAnnualReviewController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /employee-annual-reviews
     * Trả về danh sách đánh giá cuối năm với filter
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', EmployeeAnnualReview::class);

        $search = trim((string) $request->get('search', ''));
        $year = $request->get('year');
        $rating = $request->get('rating');
        $employeeId = $request->get('employee_id');

        $query = EmployeeAnnualReview::query()
            ->with(['employee:id,employee_code,full_name', 'inputBy:id,name'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('employee', function ($qq) use ($search) {
                    $qq->where('full_name', 'like', "%{$search}%")
                       ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($year, fn($q) => $q->where('year', $year))
            ->when($rating, fn($q) => $q->where('final_rating', $rating))
            ->when($employeeId, fn($q) => $q->where('employee_id', $employeeId))
            ->orderBy('year', 'desc')
            ->orderBy('created_at', 'desc');

        $reviews = $query->get();

        // Lấy danh sách nhân viên để dropdown
        $employees = Employee::query()
            ->orderBy('full_name')
            ->get(['id', 'employee_code', 'full_name']);

        // Lấy danh sách năm để filter (từ dữ liệu có sẵn)
        $years = EmployeeAnnualReview::query()
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Nếu không có năm nào, thêm năm hiện tại
        if (empty($years)) {
            $years = [date('Y')];
        }

        return Inertia::render('EmployeeAnnualReview/Index', [
            'reviews' => EmployeeAnnualReviewResource::collection($reviews)->resolve(),
            'employees' => $employees,
            'years' => $years,
            'enums' => [
                'ratings' => [
                    ['value' => 'A', 'label' => 'Xuất sắc'],
                    ['value' => 'B', 'label' => 'Tốt'],
                    ['value' => 'C', 'label' => 'Đạt'],
                    ['value' => 'D', 'label' => 'Cần cải thiện'],
                ],
            ],
        ]);
    }

    /**
     * POST /employee-annual-reviews
     */
    public function store(StoreEmployeeAnnualReviewRequest $request)
    {
        // $this->authorize('create', EmployeeAnnualReview::class);

        $data = $request->validated();

        // Check duplicate
        $exists = EmployeeAnnualReview::where('employee_id', $data['employee_id'])
            ->where('year', $data['year'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['employee_id' => 'Đánh giá năm này của nhân viên đã tồn tại.'])
                ->withInput();
        }

        // Thêm thông tin người nhập
        $data['input_by'] = $request->user()->id;
        $data['input_at'] = now();

        $review = EmployeeAnnualReview::create($data);
        $review->load('employee');

        activity()
            ->performedOn($review)
            ->causedBy($request->user())
            ->withProperties([
                'attributes' => [
                    'employee' => $review->employee->full_name,
                    'year' => $review->year,
                    'kpi_avg_score' => $review->kpi_avg_score,
                    'final_rating' => $review->final_rating,
                ]
            ])
            ->log('Tạo đánh giá cuối năm');

        return redirect()->route('employee-annual-reviews.index')
            ->with([
                'message' => 'Tạo đánh giá cuối năm thành công.',
                'type' => 'success'
            ]);
    }

    /**
     * PUT /employee-annual-reviews/{employee_annual_review}
     */
    public function update(UpdateEmployeeAnnualReviewRequest $request, EmployeeAnnualReview $employeeAnnualReview)
    {
        // $this->authorize('update', $employeeAnnualReview);

        $employeeAnnualReview->load('employee');
        $oldData = [
            'employee' => $employeeAnnualReview->employee->full_name,
            'year' => $employeeAnnualReview->year,
            'kpi_avg_score' => $employeeAnnualReview->kpi_avg_score,
            'final_rating' => $employeeAnnualReview->final_rating,
        ];

        $data = $request->validated();

        // Check duplicate nếu thay đổi employee/year
        if (
            $data['employee_id'] != $employeeAnnualReview->employee_id ||
            $data['year'] != $employeeAnnualReview->year
        ) {
            $exists = EmployeeAnnualReview::where('employee_id', $data['employee_id'])
                ->where('year', $data['year'])
                ->where('id', '!=', $employeeAnnualReview->id)
                ->exists();

            if ($exists) {
                return redirect()->back()
                    ->withErrors(['employee_id' => 'Đánh giá năm này của nhân viên đã tồn tại.'])
                    ->withInput();
            }
        }

        // Update input info
        $data['input_by'] = $request->user()->id;
        $data['input_at'] = now();

        $employeeAnnualReview->update($data);
        $employeeAnnualReview->refresh()->load('employee');

        $newData = [
            'employee' => $employeeAnnualReview->employee->full_name,
            'year' => $employeeAnnualReview->year,
            'kpi_avg_score' => $employeeAnnualReview->kpi_avg_score,
            'final_rating' => $employeeAnnualReview->final_rating,
        ];

        activity()
            ->performedOn($employeeAnnualReview)
            ->causedBy($request->user())
            ->withProperties([
                'old' => $oldData,
                'attributes' => $newData
            ])
            ->log('Cập nhật đánh giá cuối năm');

        return redirect()->route('employee-annual-reviews.index')
            ->with([
                'message' => 'Cập nhật đánh giá cuối năm thành công.',
                'type' => 'success'
            ]);
    }

    /**
     * DELETE /employee-annual-reviews/{employee_annual_review}
     */
    public function destroy(EmployeeAnnualReview $employeeAnnualReview)
    {
        // $this->authorize('delete', $employeeAnnualReview);

        $employeeAnnualReview->load('employee');
        $oldData = [
            'employee' => $employeeAnnualReview->employee->full_name,
            'year' => $employeeAnnualReview->year,
            'kpi_avg_score' => $employeeAnnualReview->kpi_avg_score,
            'final_rating' => $employeeAnnualReview->final_rating,
        ];

        $employeeAnnualReview->delete();

        activity()
            ->performedOn($employeeAnnualReview)
            ->causedBy(request()->user())
            ->withProperties(['old' => $oldData])
            ->log('Xóa đánh giá cuối năm');

        return redirect()->route('employee-annual-reviews.index')
            ->with([
                'message' => 'Đã xóa đánh giá cuối năm thành công.',
                'type' => 'success'
            ]);
    }

    /**
     * DELETE /employee-annual-reviews/bulk-delete
     */
    public function bulkDelete(Request $request)
    {
        // $this->authorize('bulkDelete', EmployeeAnnualReview::class);

        $ids = (array) $request->get('ids', []);
        if (empty($ids)) {
            return redirect()->route('employee-annual-reviews.index')
                ->with([
                    'message' => 'Không có mục nào được chọn để xóa.',
                    'type' => 'warning'
                ]);
        }

        $reviews = EmployeeAnnualReview::with('employee')->whereIn('id', $ids)->get();
        $deletedRecords = $reviews->map(function ($review) {
            return [
                'employee' => $review->employee->full_name,
                'year' => $review->year,
                'kpi_avg_score' => $review->kpi_avg_score,
                'final_rating' => $review->final_rating,
            ];
        })->toArray();

        $deletedCount = $reviews->count();
        EmployeeAnnualReview::whereIn('id', $ids)->delete();

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'count' => $deletedCount,
                'deleted_records' => $deletedRecords
            ])
            ->log('Xóa hàng loạt đánh giá cuối năm');

        return redirect()->route('employee-annual-reviews.index')
            ->with([
                'message' => "Đã xóa {$deletedCount} đánh giá cuối năm thành công.",
                'type' => 'success'
            ]);
    }

    /**
     * GET /employee-annual-reviews/{employee_id}/calculate-kpi
     * API để tính điểm KPI trung bình từ các tháng
     */
    public function calculateKpiAverage(Request $request, $employeeId, $year)
    {
        $avgScore = EmployeeKpiMonth::where('employee_id', $employeeId)
            ->where('year', $year)
            ->avg('kpi_score');

        return response()->json([
            'kpi_avg_score' => $avgScore ? round($avgScore, 2) : 0,
            'months_count' => EmployeeKpiMonth::where('employee_id', $employeeId)
                ->where('year', $year)
                ->count()
        ]);
    }
}
