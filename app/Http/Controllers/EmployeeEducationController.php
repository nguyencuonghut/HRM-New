<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeEducationRequest;
use App\Http\Requests\UpdateEmployeeEducationRequest;
use App\Http\Resources\EmployeeEducationResource;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\EmployeeEducation;
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

        return Inertia::render('EmployeeProfile', [
            'employee'          => $employee->only(['id','full_name','employee_code']),
            'education_levels'  => EducationLevel::orderBy('order_index')->get(['id','name']),
            'schools'           => School::orderBy('name')->get(['id','name']),
            // Nạp data tab Education (mặc định tab đầu)
            'educations'        => EmployeeEducationResource::collection(
                $employee->educations()->with(['educationLevel','school'])->orderByDesc('start_year')->get()
            )->resolve(),
            // 3 tab còn lại load lazy bởi route riêng (index) hoặc bạn có thể nạp luôn tại đây
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
}
