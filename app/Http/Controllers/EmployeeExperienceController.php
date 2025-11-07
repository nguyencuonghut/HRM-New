<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeExperienceRequest;
use App\Http\Requests\UpdateEmployeeExperienceRequest;
use App\Http\Resources\EmployeeExperienceResource;
use App\Models\Employee;
use App\Models\EmployeeExperience;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class EmployeeExperienceController extends Controller
{
    use AuthorizesRequests;

    public function index(Employee $employee)
    {
        $this->authorize('viewProfile', $employee);

        $rows = $employee->experiences()->orderByDesc('start_date')->get();
        return response()->json(EmployeeExperienceResource::collection($rows));
    }

    public function store(StoreEmployeeExperienceRequest $request, Employee $employee)
    {
        $this->authorize('editProfile', $employee);

        $data = $request->validated();
        $data['employee_id'] = $employee->id;

        // Nếu is_current = true thì end_date nên để null
        if (($data['is_current'] ?? false) && !empty($data['end_date'])) {
            $data['end_date'] = null;
        }

        $row = EmployeeExperience::create($data);

        activity('employee-experience')
            ->performedOn($row)
            ->causedBy($request->user())
            ->withProperties([
                'employee_id' => $employee->id,
                'payload'     => $data,
                'created'     => (new EmployeeExperienceResource($row))->resolve(),
            ])->log('created');

        return redirect()->route('employees.profile', $employee->id)
            ->with('success', 'Đã thêm kinh nghiệm.');
    }

    public function update(UpdateEmployeeExperienceRequest $request, Employee $employee, EmployeeExperience $experience)
    {
        $this->authorize('editProfile', $employee);
        $this->authorize('ownEmployeeItem', [$employee, $experience]);

        $payload = $request->validated();
        if (($payload['is_current'] ?? false) && !empty($payload['end_date'])) {
            $payload['end_date'] = null;
        }

        $before = $experience->getOriginal();
        $experience->update($payload);
        $after   = $experience->refresh()->getAttributes();
        $changed = array_keys($experience->getChanges());

        activity('employee-experience')
            ->performedOn($experience)
            ->causedBy($request->user())
            ->withProperties([
                'employee_id' => $employee->id,
                'before'      => $before,
                'after'       => $after,
                'changed'     => $changed,
            ])->log('updated');

        return redirect()->route('employees.profile', $employee->id)
            ->with('success', 'Đã cập nhật kinh nghiệm.');
    }

    public function destroy(Request $request, Employee $employee, EmployeeExperience $experience)
    {
        $this->authorize('editProfile', $employee);
        $this->authorize('ownEmployeeItem', [$employee, $experience]);

        $snapshot = (new EmployeeExperienceResource($experience))->resolve();
        $experience->delete();

        activity('employee-experience')
            ->performedOn($experience)
            ->causedBy($request->user())
            ->withProperties([
                'employee_id' => $employee->id,
                'deleted'     => $snapshot,
            ])->log('deleted');

        return redirect()->route('employees.profile', $employee->id)
            ->with('success', 'Đã xoá kinh nghiệm.');
    }

    public function bulkDelete(Request $request, Employee $employee)
    {
        $this->authorize('editProfile', $employee);

        $ids = (array) $request->input('ids', []);
        $rows = EmployeeExperience::where('employee_id', $employee->id)
                ->whereIn('id', $ids)->get();

        $snapshots = EmployeeExperienceResource::collection($rows)->resolve();
        EmployeeExperience::whereIn('id', $rows->pluck('id'))->delete();

        activity('employee-experience')
            ->causedBy($request->user())
            ->withProperties([
                'employee_id' => $employee->id,
                'ids'         => $ids,
                'deleted'     => $snapshots,
            ])->log('bulk-deleted');

        return redirect()->route('employees.profile', $employee->id)
            ->with('success', 'Đã xoá các kinh nghiệm đã chọn.');
    }
}
