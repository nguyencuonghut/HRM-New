<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRelativeRequest;
use App\Http\Requests\UpdateEmployeeRelativeRequest;
use App\Http\Resources\EmployeeRelativeResource;
use App\Models\Employee;
use App\Models\EmployeeRelative;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class EmployeeRelativeController extends Controller
{
    use AuthorizesRequests;

    public function index(Employee $employee)
    {
        $this->authorize('viewProfile', $employee);

        $rows = $employee->relatives()->orderBy('full_name')->get();
        return response()->json(EmployeeRelativeResource::collection($rows));
    }

    public function store(StoreEmployeeRelativeRequest $request, Employee $employee)
    {
        $this->authorize('editProfile', $employee);

        $data = $request->validated();
        $data['employee_id'] = $employee->id;

        $row = EmployeeRelative::create($data);

        activity('employee-relative')
            ->performedOn($row)
            ->causedBy($request->user())
            ->withProperties([
                'employee_id' => $employee->id,
                'payload'     => $data,
                'created'     => (new EmployeeRelativeResource($row))->resolve(),
            ])->log('created');

        return redirect()->route('employees.profile', $employee->id)
            ->with('success', 'Đã thêm người thân.');
    }

    public function update(UpdateEmployeeRelativeRequest $request, Employee $employee, EmployeeRelative $relative)
    {
        $this->authorize('editProfile', $employee);
        $this->authorize('ownEmployeeItem', [$employee, $relative]);

        $before = $relative->getOriginal();
        $relative->update($request->validated());
        $after   = $relative->refresh()->getAttributes();
        $changed = array_keys($relative->getChanges());

        activity('employee-relative')
            ->performedOn($relative)
            ->causedBy($request->user())
            ->withProperties([
                'employee_id' => $employee->id,
                'before'      => $before,
                'after'       => $after,
                'changed'     => $changed,
            ])->log('updated');

        return redirect()->route('employees.profile', $employee->id)
            ->with('success', 'Đã cập nhật người thân.');
    }

    public function destroy(Request $request, Employee $employee, EmployeeRelative $relative)
    {
        $this->authorize('editProfile', $employee);
        $this->authorize('ownEmployeeItem', [$employee, $relative]);

        $snapshot = (new EmployeeRelativeResource($relative))->resolve();
        $relative->delete();

        activity('employee-relative')
            ->performedOn($relative)
            ->causedBy($request->user())
            ->withProperties([
                'employee_id' => $employee->id,
                'deleted'     => $snapshot,
            ])->log('deleted');

        return redirect()->route('employees.profile', $employee->id)
            ->with('success', 'Đã xoá người thân.');
    }

    public function bulkDelete(Request $request, Employee $employee)
    {
        $this->authorize('editProfile', $employee);

        $ids = (array) $request->input('ids', []);
        $rows = EmployeeRelative::where('employee_id', $employee->id)
                ->whereIn('id', $ids)->get();

        $snapshots = EmployeeRelativeResource::collection($rows)->resolve();
        EmployeeRelative::whereIn('id', $rows->pluck('id'))->delete();

        activity('employee-relative')
            ->causedBy($request->user())
            ->withProperties([
                'employee_id' => $employee->id,
                'ids'         => $ids,
                'deleted'     => $snapshots,
            ])->log('bulk-deleted');

        return redirect()->route('employees.profile', $employee->id)
            ->with('success', 'Đã xoá các người thân đã chọn.');
    }
}
