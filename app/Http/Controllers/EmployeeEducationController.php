<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeEducationRequest;
use App\Http\Requests\UpdateEmployeeEducationRequest;
use App\Http\Resources\EmployeeEducationResource;
use App\Http\Resources\EmployeeExperienceResource;
use App\Http\Resources\EmployeeRelativeResource;
use App\Http\Resources\EmployeeSkillResource;
use App\Http\Resources\EmployeeAssignmentResource;
use App\Http\Resources\SkillResource;
use App\Http\Resources\SkillCategoryResource;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\EmployeeSkill;
use App\Models\EmployeeAssignment;
use App\Models\Department;
use App\Models\Position;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class EmployeeEducationController extends Controller
{
    use AuthorizesRequests;

    // Trang tổng Profile (tabs) – nạp sẵn lists dùng chung
    public function profile(Employee $employee)
    {
        $this->authorize('viewProfile', $employee);

        // Load relationships for completion calculation
        $employee->load(['assignments', 'educations', 'relatives', 'experiences', 'employeeSkills', 'employments']);

        return Inertia::render('EmployeeProfile', [
            'employee'          => (new \App\Http\Resources\EmployeeResource($employee))->resolve(),
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
        ]);
    }

    public function index(Employee $employee)
    {
        $this->authorize('viewProfile', $employee);
        $rows = $employee->educations()->with(['educationLevel','school'])->orderByDesc('start_year')->get();
        return response()->json(EmployeeEducationResource::collection($rows));
    }

    public function store(StoreEmployeeEducationRequest $request, Employee $employee)
    {
        $this->authorize('editProfile', $employee);
        $data = $request->validated();
        $data['employee_id'] = $employee->id;

        $row = EmployeeEducation::create($data);
        $row->load(['educationLevel', 'school']);

        activity('employee-education')
            ->performedOn($row)
            ->causedBy($request->user())
            ->withProperties([
                'attributes' => [
                    'employee' => $employee->full_name,
                    'education_level' => $row->educationLevel?->name,
                    'school' => $row->school?->name,
                    'major' => $row->major,
                    'start_year' => $row->start_year,
                    'end_year' => $row->end_year,
                    'study_form' => $row->study_form,
                    'certificate_no' => $row->certificate_no,
                    'graduation_date' => $row->graduation_date?->toDateString(),
                    'grade' => $row->grade,
                    'note' => $row->note,
                ]
            ])
            ->log('created');

        return redirect()->route('employees.profile', $employee->id)
            ->with(['message' => 'Đã thêm học vấn.', 'type' => 'success']);
    }

    public function update(UpdateEmployeeEducationRequest $request, Employee $employee, EmployeeEducation $education)
    {
        $this->authorize('editProfile', $employee);
        $this->authorize('ownEmployeeItem', [$employee, $education]);

        // Load relationships for old values
        $education->load(['educationLevel', 'school']);
        $oldAttributes = [
            'employee' => $employee->full_name,
            'education_level' => $education->educationLevel?->name,
            'school' => $education->school?->name,
            'major' => $education->major,
            'start_year' => $education->start_year,
            'end_year' => $education->end_year,
            'study_form' => $education->study_form,
            'certificate_no' => $education->certificate_no,
            'graduation_date' => $education->graduation_date?->toDateString(),
            'grade' => $education->grade,
            'note' => $education->note,
        ];

        $data = $request->validated();
        $education->update($data);
        $education->refresh()->load(['educationLevel', 'school']);

        activity('employee-education')
            ->performedOn($education)
            ->causedBy($request->user())
            ->withProperties([
                'old' => $oldAttributes,
                'attributes' => [
                    'employee' => $employee->full_name,
                    'education_level' => $education->educationLevel?->name,
                    'school' => $education->school?->name,
                    'major' => $education->major,
                    'start_year' => $education->start_year,
                    'end_year' => $education->end_year,
                    'study_form' => $education->study_form,
                    'certificate_no' => $education->certificate_no,
                    'graduation_date' => $education->graduation_date?->toDateString(),
                    'grade' => $education->grade,
                    'note' => $education->note,
                ]
            ])
            ->log('updated');

        return redirect()->route('employees.profile', $employee->id)
            ->with(['message' => 'Đã cập nhật học vấn.', 'type' => 'success']);
    }

    public function destroy(Employee $employee, EmployeeEducation $education)
    {
        $this->authorize('editProfile', $employee);
        $this->authorize('ownEmployeeItem', [$employee, $education]);

        $education->load(['educationLevel', 'school']);

        activity('employee-education')
            ->performedOn($education)
            ->causedBy(request()->user())
            ->withProperties([
                'old' => [
                    'employee' => $employee->full_name,
                    'education_level' => $education->educationLevel?->name,
                    'school' => $education->school?->name,
                    'major' => $education->major,
                    'start_year' => $education->start_year,
                    'end_year' => $education->end_year,
                    'study_form' => $education->study_form,
                    'certificate_no' => $education->certificate_no,
                    'graduation_date' => $education->graduation_date?->toDateString(),
                    'grade' => $education->grade,
                    'note' => $education->note,
                ]
            ])
            ->log('deleted');

        $education->delete();

        return redirect()->route('employees.profile', $employee->id)
            ->with(['message' => 'Đã xoá học vấn.', 'type' => 'success']);
    }

    public function bulkDelete(Request $request, Employee $employee)
    {
        $this->authorize('editProfile', $employee);
        $ids = (array) $request->input('ids', []);
        if ($ids) {
            $rows = EmployeeEducation::where('employee_id', $employee->id)
                ->whereIn('id', $ids)
                ->with(['educationLevel', 'school'])
                ->get();

            $deletedRecords = $rows->map(fn($edu) => [
                'employee' => $employee->full_name,
                'education_level' => $edu->educationLevel?->name,
                'school' => $edu->school?->name,
                'major' => $edu->major,
                'start_year' => $edu->start_year,
                'end_year' => $edu->end_year,
            ])->toArray();

            EmployeeEducation::where('employee_id', $employee->id)->whereIn('id', $ids)->delete();

            activity('employee-education')
                ->causedBy($request->user())
                ->withProperties([
                    'deleted_count' => count($ids),
                    'deleted_records' => $deletedRecords
                ])
                ->log('bulk-deleted');
        }
        return redirect()->route('employees.profile', $employee->id)
            ->with(['message' => 'Đã xoá các bản ghi đã chọn.', 'type' => 'success']);
    }

    /**
     * Get activity timeline for employee
     */
    public function activities(Request $request, Employee $employee)
    {
        $this->authorize('viewProfile', $employee);

        $query = \Spatie\Activitylog\Models\Activity::query();

        // Filter by module if provided
        if ($request->has('module') && $request->module) {
            $module = $request->module;

            // All modules now use log_name directly (including employee-assignment after fix)
            // Also support old logs with log_name="default" for backward compatibility
            if ($module === 'employee-assignment') {
                $query->where(function($q) {
                    // New logs: log_name = "employee-assignment"
                    $q->where('log_name', 'employee-assignment')
                      // Old logs: log_name = "default" + description pattern
                      ->orWhere(function($qq) {
                          $qq->where('log_name', 'default')
                             ->where('description', 'LIKE', 'EMPLOYEE_ASSIGNMENT_%');
                      });
                });
            } else {
                // For other modules: filter by log_name directly
                $query->where('log_name', $module);
            }
        } else {
            // Get all employee-related logs (both old and new)
            $query->where(function($q) {
                $q->where('log_name', 'LIKE', 'employee-%')
                  ->orWhere(function($qq) {
                      $qq->where('log_name', 'default')
                         ->where('description', 'LIKE', 'EMPLOYEE_%');
                  });
            });
        }

        // Filter by employee using multiple strategies based on activity log structure
        $query->where(function($q) use ($employee) {
            // Strategy 1: employee-skill uses properties->attributes->employee (full_name)
            $q->where('properties->attributes->employee', $employee->full_name)
              ->orWhere('properties->old->employee', $employee->full_name);

            // Strategy 2: employee-relative & employee-experience use properties->employee_id (UUID)
            $q->orWhere('properties->employee_id', $employee->id);

            // Strategy 3: Direct activities on Employee model
            $q->orWhere(function($qq) use ($employee) {
                $qq->where('subject_type', Employee::class)
                   ->where('subject_id', $employee->id);
            });
        });

        $activities = $query->with('causer:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($activities);
    }
}
