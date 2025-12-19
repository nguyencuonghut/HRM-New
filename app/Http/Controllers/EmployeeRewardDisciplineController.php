<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeRewardDiscipline;
use App\Http\Resources\EmployeeRewardDisciplineResource;
use App\Http\Requests\StoreEmployeeRewardDisciplineRequest;
use App\Http\Requests\UpdateEmployeeRewardDisciplineRequest;
use App\Services\RewardDisciplineService;
use App\Enums\RewardDisciplineCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeRewardDisciplineController extends Controller
{
    protected RewardDisciplineService $service;

    public function __construct(RewardDisciplineService $service)
    {
        $this->service = $service;
    }

    /**
     * Get data for profile page (called from EmployeeController@profile)
     */
    public static function getProfileData(Employee $employee): array
    {
        $records = EmployeeRewardDiscipline::where('employee_id', $employee->id)
            ->with(['contract', 'issuedBy'])
            ->latest()
            ->get();

        $service = new RewardDisciplineService();
        $stats = $service->getSummaryStats($employee->id);

        return [
            'records' => EmployeeRewardDisciplineResource::collection($records)->resolve(),
            'stats' => $stats,
            'categoryOptions' => static::getCategoryOptionsStatic(),
            'headDeputyEmployees' => static::getHeadDeputyEmployees(),
        ];
    }

    /**
     * Display list of rewards & disciplines for an employee (API endpoint)
     */
    public function index(Employee $employee)
    {
        $records = EmployeeRewardDiscipline::where('employee_id', $employee->id)
            ->with(['contract', 'issuedBy'])
            ->latest()
            ->get();

        $stats = $this->service->getSummaryStats($employee->id);

        return response()->json([
            'records' => EmployeeRewardDisciplineResource::collection($records)->resolve(),
            'stats' => $stats,
            'categoryOptions' => $this->getCategoryOptions(),
            'headDeputyEmployees' => static::getHeadDeputyEmployees(),
        ]);
    }

    /**
     * Store a new reward/discipline record
     */
    public function store(StoreEmployeeRewardDisciplineRequest $request, Employee $employee)
    {
        $validated = $request->validated();
        $validated['employee_id'] = $employee->id;

        $record = EmployeeRewardDiscipline::create($validated);

        return redirect()->back()->with([
            'message' => 'Lưu thành công',
            'type' => 'success'
        ]);
    }

    /**
     * Update an existing record
     */
    public function update(UpdateEmployeeRewardDisciplineRequest $request, Employee $employee, EmployeeRewardDiscipline $rewardDiscipline)
    {
        $validated = $request->validated();
        $rewardDiscipline->update($validated);

        return redirect()->back()->with([
            'message' => 'Cập nhật thành công',
            'type' => 'success'
        ]);
    }

    /**
     * Delete a record (soft delete)
     */
    public function destroy(Employee $employee, EmployeeRewardDiscipline $rewardDiscipline)
    {
        // Check ownership
        if ($rewardDiscipline->employee_id !== $employee->id) {
            abort(403, 'Không có quyền');
        }

        $rewardDiscipline->delete();

        return redirect()->back()->with([
            'type' => 'success',
            'message' => 'Đã xóa'
        ]);
    }

    /**
     * Get category options grouped by type
     */
    protected function getCategoryOptions(): array
    {
        return static::getCategoryOptionsStatic();
    }

    /**
     * Get category options (static version for profile)
     */
    protected static function getCategoryOptionsStatic(): array
    {
        return [
            'rewards' => collect(RewardDisciplineCategory::rewardCategories())
                ->map(fn($cat) => [
                    'value' => $cat->value,
                    'label' => $cat->label(),
                ])
                ->values()
                ->toArray(),
            'disciplines' => collect(RewardDisciplineCategory::disciplineCategories())
                ->map(fn($cat) => [
                    'value' => $cat->value,
                    'label' => $cat->label(),
                ])
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Get employees who are HEAD or DEPUTY
     */
    protected static function getHeadDeputyEmployees(): array
    {
        return Employee::whereHas('assignments', function($query) {
            $query->whereIn('role_type', ['HEAD', 'DEPUTY'])
                  ->where('status', 'ACTIVE');
        })
        ->where('status', 'ACTIVE')
        ->orderBy('full_name')
        ->get()
        ->map(fn($emp) => [
            'value' => $emp->id,
            'label' => $emp->full_name . ' - ' . $emp->employee_code,
        ])
        ->toArray();
    }
}
