<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Position;
use App\Models\Department;
use App\Models\InsuranceSalaryCategory;
use App\Http\Resources\PositionResource;
use App\Http\Resources\DepartmentResource;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PositionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Position::class);

        $positions = PositionResource::collection(
            Position::with([
                'department',
                'insuranceSalaryCategory',
                'salaryGrades' => function ($query) {
                    $query->where('is_active', true)
                          ->orderBy('grade', 'asc');
                }
            ])->orderBy('department_id')->latest()->get()
        )->resolve();

        $departments = DepartmentResource::collection(
            Department::where('is_active', true)->orderBy('name')->get()
        )->resolve();

        $insuranceSalaryCategories = InsuranceSalaryCategory::where('is_active', true)
            ->ordered()
            ->get(['id', 'code', 'name'])
            ->toArray();

        return inertia('PositionIndex', compact('positions', 'departments', 'insuranceSalaryCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePositionRequest $request)
    {
        $this->authorize('create', Position::class);

        $validated = $request->validated();

        $position = Position::create($validated);

        // Log activity
        activity()
            ->performedOn($position)
            ->causedBy(Auth::user())
            ->withProperties([
                'position_title' => $position->title,
                'department' => $position->department?->name
            ])
            ->log('Tạo chức vụ mới: ' . $position->title);

        return redirect()->route('positions.index')->with([
            'message' => 'Tạo chức vụ thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePositionRequest $request, Position $position)
    {
        $this->authorize('update', $position);

        $validated = $request->validated();

        $position->update($validated);

        activity()
            ->performedOn($position)
            ->causedBy(Auth::user())
            ->withProperties([
                'position_title' => $position->title,
                'department' => $position->department?->name
            ])
            ->log('Sửa chức vụ: ' . $position->title);

        return redirect()->route('positions.index')->with([
            'message' => 'Cập nhật chức vụ thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position)
    {
        $this->authorize('delete', $position);

        // Check if position is being used in employee assignments
        if ($position->employeeAssignments()->count() > 0) {
            return redirect()->route('positions.index')->with([
                'message' => 'Không thể xóa chức vụ này vì đang có nhân viên được gán!',
                'type' => 'error'
            ]);
        }

        $positionTitle = $position->title;
        $position->delete();

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'position_title' => $positionTitle
            ])
            ->log('Xóa chức vụ: ' . $positionTitle);

        return redirect()->route('positions.index')->with([
            'message' => 'Xóa chức vụ thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', Position::class);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:positions,id'
        ]);

        $positionsToDelete = Position::whereIn('id', $request->ids)->get();

        // Check if any positions are being used in employee assignments
        $positionsInUse = [];
        foreach ($positionsToDelete as $position) {
            if ($position->employeeAssignments()->count() > 0) {
                $positionsInUse[] = $position->title;
            }
        }

        if (!empty($positionsInUse)) {
            return redirect()->route('positions.index')->with([
                'message' => 'Không thể xóa các chức vụ sau vì đang có nhân viên được gán: ' . implode(', ', $positionsInUse),
                'type' => 'error'
            ]);
        }

        foreach ($positionsToDelete as $position) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'position_title' => $position->title
                ])
                ->log('Xóa chức vụ: ' . $position->title);
        }

        $positionsToDelete->each->delete();

        return redirect()->route('positions.index')->with([
            'message' => 'Xóa các chức vụ đã chọn thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Get insurance salary suggestion for a position
     *
     * @param Position $position
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function insuranceSuggestion(Position $position, Request $request)
    {
        $employeeId = $request->query('employee_id');
        $date = $request->query('date', now());

        // Get region from company configuration at the specific date
        $companyRegion = \App\Models\CompanyRegion::getRegionAtDate($date);
        $defaultRegion = $companyRegion ? $companyRegion->region : 3; // Fallback to region 3 if not configured

        // Get employee's current insurance profile to determine grade
        $employeeProfile = null;
        $grade = 1; // Default to grade 1
        $gradeSource = 'bậc 1 (mặc định)';

        if ($employeeId) {
            $employeeProfile = \App\Models\EmployeeInsuranceProfile::where('employee_id', $employeeId)
                ->where('position_id', $position->id)
                ->whereNull('applied_to')
                ->first();

            if ($employeeProfile) {
                $grade = $employeeProfile->grade;
                $gradeSource = "bậc {$grade} (hiện tại)";
            }
        }

        // Get coefficient for the grade
        $gradeData = \App\Models\PositionSalaryGrade::where('position_id', $position->id)
            ->where('grade', $grade)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhereDate('effective_to', '>=', $date);
            })
            ->first();

        if (!$gradeData) {
            return response()->json([
                'suggested_insurance_salary' => null,
                'minimum_wage' => null,
                'region' => null,
                'grade' => null,
                'coefficient' => null,
                'explain' => 'Chưa có dữ liệu gợi ý cho vị trí này'
            ]);
        }

        $coefficient = (float) $gradeData->coefficient;

        // Get minimum wage for region from InsuranceConfigResolver
        $resolver = app(\App\Services\InsuranceConfigResolver::class);
        $minWage = $resolver->getMinimumWage($defaultRegion, $date);

        if (!$minWage) {
            return response()->json([
                'suggested_insurance_salary' => null,
                'minimum_wage' => null,
                'region' => null,
                'grade' => $grade,
                'coefficient' => $coefficient,
                'explain' => 'Không tìm thấy lương tối thiểu vùng'
            ]);
        }
        $suggestedSalary = (int) round($minWage * (float) $coefficient);

        // Format region as Roman numeral
        $regionMap = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];
        $regionDisplay = $regionMap[$defaultRegion] ?? $defaultRegion;

        return response()->json([
            'suggested_insurance_salary' => $suggestedSalary,
            'minimum_wage' => (float) $minWage,
            'region' => $regionDisplay,
            'grade' => $grade,
            'coefficient' => $coefficient,
            'explain' => number_format((float) $minWage, 0, ',', '.') . ' × ' . $coefficient . ' (Bậc ' . $grade . ' - ' . $position->title . ')'
        ]);
    }
}
