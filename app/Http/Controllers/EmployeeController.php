<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Employee::class);

        $search = trim((string)$request->get('search',''));
        $status = $request->get('status', null);

        $query = Employee::query()
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
        Employee::create($data);

        return redirect()->route('employees.index')
            ->with('success', 'Tạo nhân viên thành công!');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $data = $request->validated();
        $employee->update($data);

        return redirect()->route('employees.index')
            ->with('success', 'Cập nhật nhân viên thành công!');
    }

    public function destroy(Employee $employee)
    {
        $this->authorize('delete', $employee);

        // Không dùng soft delete theo quyết định của bạn
        $employee->delete();

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
