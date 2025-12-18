<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EducationLevel;
use App\Models\School;
use App\Models\Department;
use App\Models\Position;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\EmployeeSkill;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeEducationResource;
use App\Http\Resources\EmployeeExperienceResource;
use App\Http\Resources\EmployeeRelativeResource;
use App\Http\Resources\EmployeeSkillResource;
use App\Http\Resources\EmployeeAssignmentResource;
use App\Http\Resources\SkillResource;
use App\Http\Resources\SkillCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    use AuthorizesRequests;

    // Trang tổng Profile (tabs) – nạp sẵn lists dùng chung
    public function profile(Employee $employee)
    {
        $this->authorize('viewProfile', $employee);

        // Load relationships for completion calculation
        $employee->load(['assignments', 'educations', 'relatives', 'experiences', 'employeeSkills', 'employments']);

        // Lấy employment hiện tại (nếu có)
        $currentEmployment = $employee->currentEmployment();
        $activeContractQuery = $employee->contracts()->active()->orderByDesc('start_date');
        if ($currentEmployment) {
            $activeContractQuery->where('employment_id', $currentEmployment->id);
        }
        $currentContract = $activeContractQuery->with(['appendixes'])->first();

        $currentPayroll = null;
        if ($currentContract) {
            // Lấy phụ lục ACTIVE mới nhất (nếu có)
            $activeAppendix = $currentContract->appendixes()
                ->where('status', 'ACTIVE')
                ->where(function($q){
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->orderByDesc('effective_date')
                ->first();
            if ($activeAppendix) {
                $currentPayroll = [
                    'type' => 'appendix',
                    'source_id' => $activeAppendix->id,
                    'number' => $activeAppendix->appendix_no,
                    'effective_date' => optional($activeAppendix->effective_date)->toDateString(),
                    'base_salary' => $activeAppendix->base_salary,
                    'insurance_salary' => $activeAppendix->insurance_salary,
                    'position_allowance' => $activeAppendix->position_allowance,
                    'other_allowances' => $activeAppendix->other_allowances,
                    'status' => $activeAppendix->status,
                    'status_label' => method_exists($activeAppendix, 'getStatusLabel') ? $activeAppendix->getStatusLabel() : $activeAppendix->status,
                    'title' => $activeAppendix->title,
                ];
            } else {
                $currentPayroll = [
                    'type' => 'contract',
                    'source_id' => $currentContract->id,
                    'number' => $currentContract->contract_number,
                    'effective_date' => optional($currentContract->start_date)->toDateString(),
                    'base_salary' => $currentContract->base_salary,
                    'insurance_salary' => $currentContract->insurance_salary,
                    'position_allowance' => $currentContract->position_allowance,
                    'other_allowances' => $currentContract->other_allowances,
                    'status' => $currentContract->status,
                    'status_label' => method_exists($currentContract, 'getStatusLabel') ? $currentContract->getStatusLabel() : $currentContract->status,
                    'title' => $currentContract->contract_type_label,
                ];
            }
        }

        return Inertia::render('EmployeeProfile', [
            'employee'          => (new EmployeeResource($employee))->resolve(),
            'education_levels'  => EducationLevel::orderBy('order_index')->get(['id','name']),
            'schools'           => School::orderBy('name')->get(['id','name']),
            'departments'       => Department::orderBy('name')->get(['id','name','type']),
            'positions'         => Position::orderBy('title')->get(['id','title','department_id']),
            // skill categories
            'skill_categories'  => SkillCategoryResource::collection(
                SkillCategory::where('is_active', true)->orderBy('order_index')->get()
            )->resolve(),
            // master skill list
            'skills'           => SkillResource::collection(
                Skill::with('category')->orderBy('name')->get()
            )->resolve(),
            // Nạp data tab Education (mặc định tab đầu)
            'educations'        => EmployeeEducationResource::collection(
                $employee->educations()->with(['educationLevel','school'])->orderByDesc('start_year')->get()
            )->resolve(),
            // 3 tab còn lại load lazy bởi route riêng (index) hoặc bạn có thể nạp luôn tại đây
            'relatives'        => EmployeeRelativeResource::collection(
                $employee->relatives()->orderBy('full_name')->get()
            )->resolve(),
            'experiences'      => EmployeeExperienceResource::collection(
                $employee->experiences()->orderByDesc('start_date')->get()
            )->resolve(),
            // gán kỹ năng của nhân viên
            'employee_skills'  => EmployeeSkillResource::collection(
                EmployeeSkill::with('skill:id,name')
                    ->where('employee_id', $employee->id)
                    ->get()
            )->resolve(),
            // Phân công của nhân viên
            'assignments'      => EmployeeAssignmentResource::collection(
                $employee->assignments()
                    ->with(['department:id,name,type', 'position:id,title'])
                    ->orderByDesc('is_primary')
                    ->orderByDesc('start_date')
                    ->get()
            )->resolve(),
            // Hợp đồng của nhân viên
            'contracts'        => \App\Http\Resources\ContractResource::collection(
                $employee->contracts()
                    ->with(['department:id,name', 'position:id,title', 'appendixes'])
                    ->orderByDesc('start_date')
                    ->get()
            )->resolve(),
            // Lương hiện tại
            'current_payroll' => $currentPayroll,
        ]);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Employee::class);

        $search = trim((string)$request->get('search',''));
        $status = $request->get('status', null);

        $query = Employee::query()
            ->with(['assignments', 'educations', 'relatives', 'experiences', 'employeeSkills', 'employments'])
            ->when($search !== '', function($q) use ($search) {
                $q->where(function($qq) use ($search){
                    $qq->where('full_name','like',"%{$search}%")
                       ->orWhere('employee_code','like',"%{$search}%")
                       ->orWhere('phone','like',"%{$search}%")
                       ->orWhere('company_email','like',"%{$search}%");
                });
            })
            ->when(!is_null($status) && $status !== '', fn($q)=> $q->where('status', $status))
            ->orderBy('full_name');

        // Trả mảng (không paginate) giống style RoleIndex.vue
        $employees = EmployeeResource::collection($query->get())->resolve();

        // Dùng cho filter trạng thái
        $statusOptions = [
            ['label'=>'Đang làm việc','value'=>'ACTIVE'],
            ['label'=>'Ngừng hoạt động','value'=>'INACTIVE'],
            ['label'=>'Đang nghỉ dài ngày','value'=>'ON_LEAVE'],
            ['label'=>'Đã nghỉ việc','value'=>'TERMINATED'],
        ];

        return Inertia::render('EmployeeIndex', [
            'employees'     => $employees,
            'statusOptions' => $statusOptions,
        ]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $this->authorize('create', Employee::class);

        $data = $request->validated();
        $employee = Employee::create($data);
        $employee->load(['ward.province', 'tempWard.province']);

        activity()
            ->performedOn($employee)
            ->causedBy($request->user())
            ->withProperties([
                'attributes' => [
                    'employee_code' => $employee->employee_code,
                    'full_name' => $employee->full_name,
                    'gender' => $employee->gender,
                    'phone' => $employee->phone,
                    'company_email' => $employee->company_email,
                    'ward' => $employee->ward ? $employee->ward->province->name . ' - ' . $employee->ward->name : null,
                    'hire_date' => $employee->hire_date?->toDateString(),
                    'status' => $employee->status,
                ]
            ])
            ->log('Tạo nhân viên');

        return redirect()->route('employees.index')
            ->with('success', 'Tạo nhân viên thành công!');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $employee->load(['ward.province', 'tempWard.province']);
        $oldData = [
            'employee_code' => $employee->employee_code,
            'full_name' => $employee->full_name,
            'gender' => $employee->gender,
            'phone' => $employee->phone,
            'company_email' => $employee->company_email,
            'ward' => $employee->ward ? $employee->ward->province->name . ' - ' . $employee->ward->name : null,
            'hire_date' => $employee->hire_date?->toDateString(),
            'status' => $employee->status,
        ];

        $data = $request->validated();
        $employee->update($data);
        $employee->refresh()->load(['ward.province', 'tempWard.province']);

        $newData = [
            'employee_code' => $employee->employee_code,
            'full_name' => $employee->full_name,
            'gender' => $employee->gender,
            'phone' => $employee->phone,
            'company_email' => $employee->company_email,
            'ward' => $employee->ward ? $employee->ward->province->name . ' - ' . $employee->ward->name : null,
            'hire_date' => $employee->hire_date?->toDateString(),
            'status' => $employee->status,
        ];

        activity()
            ->performedOn($employee)
            ->causedBy($request->user())
            ->withProperties([
                'old' => $oldData,
                'attributes' => $newData
            ])
            ->log('Cập nhật nhân viên');

        return redirect()->route('employees.index')
            ->with('success', 'Cập nhật nhân viên thành công!');
    }

    public function destroy(Employee $employee)
    {
        $this->authorize('delete', $employee);

        $employee->load(['ward.province', 'tempWard.province']);
        $oldData = [
            'employee_code' => $employee->employee_code,
            'full_name' => $employee->full_name,
            'gender' => $employee->gender,
            'phone' => $employee->phone,
            'company_email' => $employee->company_email,
            'ward' => $employee->ward ? $employee->ward->province->name . ' - ' . $employee->ward->name : null,
            'hire_date' => $employee->hire_date?->toDateString(),
            'status' => $employee->status,
        ];

        // Không dùng soft delete theo quyết định của bạn
        $employee->delete();

        activity()
            ->performedOn($employee)
            ->causedBy(request()->user())
            ->withProperties(['old' => $oldData])
            ->log('Xóa nhân viên');

        return redirect()->route('employees.index')
            ->with('success', 'Đã xóa nhân viên!');
    }

    public function show(Employee $employee)
    {
        $this->authorize('view', $employee);

        return Inertia::render('Employees/Show', [
            'employee' => new EmployeeResource($employee)
        ]);
    }
}
