<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeAssignmentRequest;
use App\Http\Requests\UpdateEmployeeAssignmentRequest;
use App\Http\Resources\EmployeeAssignmentResource;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Position;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class EmployeeAssignmentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', EmployeeAssignment::class);

        // Bộ lọc đơn giản từ query (tuỳ chọn)
        $departmentId = $request->get('department_id');

        $query = EmployeeAssignment::query()
            ->with([
                'employee:id,full_name,employee_code',
                'department:id,name,type',
                'position:id,title',
            ])
            ->join('employees', 'employee_assignments.employee_id', '=', 'employees.id')
            ->when($departmentId, fn($q) => $q->where('employee_assignments.department_id', $departmentId))
            ->orderBy('employees.full_name')
            ->select('employee_assignments.*');

        $assignments = EmployeeAssignmentResource::collection($query->get())->resolve();

        // Dữ liệu Select cho form
        $employees   = Employee::orderBy('full_name')->get(['id','full_name','employee_code']);
        $departments = Department::orderBy('order_index')->orderBy('name')->get(['id','name','type']);
        $positions   = Position::orderBy('title')->get(['id','title']);

        return Inertia::render('EmployeeAssignmentIndex', [
            'assignments' => $assignments,
            'employees'   => $employees,
            'departments' => $departments,
            'positions'   => $positions,
            'enums'       => [
                'role_types' => [
                    ['value' => 'HEAD',   'label' => 'Trưởng đơn vị'],
                    ['value' => 'DEPUTY', 'label' => 'Phó đơn vị'],
                    ['value' => 'MEMBER', 'label' => 'Nhân viên'],
                ],
                'statuses' => [
                    ['value' => 'ACTIVE',   'label' => 'Đang hiệu lực'],
                    ['value' => 'INACTIVE', 'label' => 'Ngừng hiệu lực'],
                ],
            ],
        ]);
    }

    public function store(StoreEmployeeAssignmentRequest $request)
    {
        $this->authorize('create', EmployeeAssignment::class);

        $data = $request->validated();

        // Lưu ý: unique primary ACTIVE đã được đảm bảo bởi constraint DB (active_primary_flag)
        try {
            $assignment = EmployeeAssignment::create($data);
            $assignment->load(['employee', 'department', 'position']);

            activity()
                ->performedOn($assignment)
                ->causedBy($request->user())
                ->withProperties([
                    'attributes' => [
                        'employee' => $assignment->employee?->full_name,
                        'department' => $assignment->department?->name,
                        'position' => $assignment->position?->title,
                        'is_primary' => $assignment->is_primary ? 'Chính' : 'Phụ',
                        'role_type' => $assignment->role_type,
                        'start_date' => $assignment->start_date?->toDateString(),
                        'end_date' => $assignment->end_date?->toDateString(),
                        'status' => $assignment->status,
                    ]
                ])
                ->log('Tạo phân công nhân sự');
        } catch (QueryException $e) {
            // Bắt lỗi ràng buộc "một primary ACTIVE duy nhất"
            return back()->withErrors([
                'is_primary' => 'Nhân viên này đã có phân công CHÍNH đang hoạt động.',
            ])->withInput();
        }

        return redirect()->route('employee-assignments.index')
            ->with('success', 'Đã tạo phân công nhân sự.');
    }

    public function update(UpdateEmployeeAssignmentRequest $request, EmployeeAssignment $employeeAssignment)
    {
        $this->authorize('update', $employeeAssignment);

        $data = $request->validated();

        try {
            // Load relationships before update for old values
            $employeeAssignment->load(['employee', 'department', 'position']);
            $oldData = [
                'employee' => $employeeAssignment->employee?->full_name,
                'department' => $employeeAssignment->department?->name,
                'position' => $employeeAssignment->position?->title,
                'is_primary' => $employeeAssignment->is_primary ? 'Chính' : 'Phụ',
                'role_type' => $employeeAssignment->role_type,
                'start_date' => $employeeAssignment->start_date?->toDateString(),
                'end_date' => $employeeAssignment->end_date?->toDateString(),
                'status' => $employeeAssignment->status,
            ];

            $employeeAssignment->update($data);
            $employeeAssignment->refresh()->load(['employee', 'department', 'position']);

            $newData = [
                'employee' => $employeeAssignment->employee?->full_name,
                'department' => $employeeAssignment->department?->name,
                'position' => $employeeAssignment->position?->title,
                'is_primary' => $employeeAssignment->is_primary ? 'Chính' : 'Phụ',
                'role_type' => $employeeAssignment->role_type,
                'start_date' => $employeeAssignment->start_date?->toDateString(),
                'end_date' => $employeeAssignment->end_date?->toDateString(),
                'status' => $employeeAssignment->status,
            ];

            activity()
                ->performedOn($employeeAssignment)
                ->causedBy($request->user())
                ->withProperties([
                    'old' => $oldData,
                    'attributes' => $newData
                ])
                ->log('Cập nhật phân công nhân sự');
        } catch (QueryException $e) {
            return back()->withErrors([
                'is_primary' => 'Nhân viên này đã có phân công CHÍNH đang hoạt động.',
            ])->withInput();
        }

        return redirect()->route('employee-assignments.index')
            ->with('success', 'Đã cập nhật phân công.');
    }

    public function destroy(EmployeeAssignment $employeeAssignment)
    {
        $this->authorize('delete', $employeeAssignment);

        $employeeAssignment->load(['employee', 'department', 'position']);
        $oldData = [
            'employee' => $employeeAssignment->employee?->full_name,
            'department' => $employeeAssignment->department?->name,
            'position' => $employeeAssignment->position?->title,
            'is_primary' => $employeeAssignment->is_primary ? 'Chính' : 'Phụ',
            'role_type' => $employeeAssignment->role_type,
            'start_date' => $employeeAssignment->start_date?->toDateString(),
            'end_date' => $employeeAssignment->end_date?->toDateString(),
            'status' => $employeeAssignment->status,
        ];

        $employeeAssignment->delete();

        activity()
            ->performedOn($employeeAssignment)
            ->causedBy(request()->user())
            ->withProperties(['old' => $oldData])
            ->log('Xóa phân công nhân sự');

        return redirect()->route('employee-assignments.index')
            ->with('success', 'Đã xoá phân công.');
    }

    public function bulkDestroy(Request $request)
    {
        $this->authorize('delete', EmployeeAssignment::class);

        $ids = (array) $request->get('ids', []);
        if (!empty($ids)) {
            $assignments = EmployeeAssignment::with(['employee', 'department', 'position'])
                ->whereIn('id', $ids)
                ->get();

            $deletedRecords = $assignments->map(function ($assignment) {
                return [
                    'employee' => $assignment->employee?->full_name,
                    'department' => $assignment->department?->name,
                    'position' => $assignment->position?->title,
                    'is_primary' => $assignment->is_primary ? 'Chính' : 'Phụ',
                    'role_type' => $assignment->role_type,
                ];
            })->toArray();

            EmployeeAssignment::whereIn('id', $ids)->delete();

            activity()
                ->causedBy($request->user())
                ->withProperties([
                    'count' => count($ids),
                    'deleted_records' => $deletedRecords
                ])
                ->log('Xóa hàng loạt phân công nhân sự');
        }

        return redirect()->route('employee-assignments.index')
            ->with('success', 'Đã xoá các phân công đã chọn.');
    }
}
