<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeSkillRequest;
use App\Http\Requests\UpdateEmployeeSkillRequest;
use App\Http\Resources\EmployeeSkillResource;
use App\Models\Employee;
use App\Models\EmployeeSkill;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmployeeSkillController extends Controller
{
    use AuthorizesRequests;

    public function index(Employee $employee)
    {
        $this->authorize('viewProfile', $employee);

        $rows = EmployeeSkill::with('skill:id,name')
            ->where('employee_id', $employee->id)
            ->get();

        return response()->json(EmployeeSkillResource::collection($rows));
    }

    public function store(StoreEmployeeSkillRequest $request, Employee $employee)
    {
        $this->authorize('editProfile', $employee);

        $data = $request->validated();
        $data['employee_id'] = $employee->id;

        // Đảm bảo mỗi kỹ năng chỉ gán 1 lần cho NV
        $exists = EmployeeSkill::where('employee_id', $employee->id)
                    ->where('skill_id', $data['skill_id'])
                    ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'skill_id' => 'Kỹ năng này đã được gán cho nhân viên.',
            ]);
        }

        // Bảo toàn level/years trong khoảng
        $data['level'] = max(0, min(5, (int)($data['level'] ?? 0)));
        $data['years'] = max(0, (int)($data['years'] ?? 0));

        $row = EmployeeSkill::create($data)->load('skill:id,name');

        activity('employee-skill')
            ->performedOn($row)
            ->causedBy($request->user())
            ->withProperties([
                'attributes' => [
                    'employee' => $employee->full_name,
                    'skill' => $row->skill?->name,
                    'level' => $row->level,
                    'years' => $row->years,
                    'note' => $row->note,
                ]
            ])->log('created');

        return redirect()->route('employees.profile', $employee->id)
            ->with(['message' => 'Đã thêm kỹ năng cho nhân viên.', 'type' => 'success']);
    }

    public function update(UpdateEmployeeSkillRequest $request, Employee $employee, EmployeeSkill $employeeSkill)
    {
        $this->authorize('editProfile', $employee);
        $this->authorize('ownEmployeeItem', [$employee, $employeeSkill]);

        $payload = $request->validated();

        // Nếu đổi skill_id, cần đảm bảo không trùng
        if (isset($payload['skill_id']) && $payload['skill_id'] !== $employeeSkill->skill_id) {
            $exists = EmployeeSkill::where('employee_id', $employee->id)
                        ->where('skill_id', $payload['skill_id'])
                        ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'skill_id' => 'Kỹ năng này đã được gán cho nhân viên.',
                ]);
            }
        }

        if (isset($payload['level'])) $payload['level'] = max(0, min(5, (int)$payload['level']));
        if (isset($payload['years'])) $payload['years'] = max(0, (int)$payload['years']);

        // Load old skill name before update
        $employeeSkill->load('skill:id,name');
        $oldAttributes = [
            'employee' => $employee->full_name,
            'skill' => $employeeSkill->skill?->name,
            'level' => $employeeSkill->level,
            'years' => $employeeSkill->years,
            'note' => $employeeSkill->note,
        ];

        $employeeSkill->update($payload);
        $employeeSkill->refresh()->load('skill:id,name');

        activity('employee-skill')
            ->performedOn($employeeSkill)
            ->causedBy($request->user())
            ->withProperties([
                'old' => $oldAttributes,
                'attributes' => [
                    'employee' => $employee->full_name,
                    'skill' => $employeeSkill->skill?->name,
                    'level' => $employeeSkill->level,
                    'years' => $employeeSkill->years,
                    'note' => $employeeSkill->note,
                ]
            ])->log('updated');

        return redirect()->route('employees.profile', $employee->id)
            ->with(['message' => 'Đã cập nhật kỹ năng.', 'type' => 'success']);
    }

    public function destroy(Request $request, Employee $employee, EmployeeSkill $employeeSkill)
    {
        $this->authorize('editProfile', $employee);
        $this->authorize('ownEmployeeItem', [$employee, $employeeSkill]);

        $employeeSkill->load('skill:id,name');

        activity('employee-skill')
            ->performedOn($employeeSkill)
            ->causedBy($request->user())
            ->withProperties([
                'old' => [
                    'employee' => $employee->full_name,
                    'skill' => $employeeSkill->skill?->name,
                    'level' => $employeeSkill->level,
                    'years' => $employeeSkill->years,
                    'note' => $employeeSkill->note,
                ]
            ])->log('deleted');

        $employeeSkill->delete();

        return redirect()->route('employees.profile', $employee->id)
            ->with(['message' => 'Đã xoá kỹ năng.', 'type' => 'success']);
    }

    public function bulkDelete(Request $request, Employee $employee)
    {
        $this->authorize('editProfile', $employee);

        $ids = (array) $request->input('ids', []);
        $rows = EmployeeSkill::with('skill:id,name')
            ->where('employee_id', $employee->id)
            ->whereIn('id', $ids)->get();

        $deletedRecords = $rows->map(fn($item) => [
            'employee' => $employee->full_name,
            'skill' => $item->skill?->name,
            'level' => $item->level,
            'years' => $item->years,
        ])->toArray();

        EmployeeSkill::whereIn('id', $rows->pluck('id'))->delete();

        activity('employee-skill')
            ->causedBy($request->user())
            ->withProperties([
                'deleted_count' => count($ids),
                'deleted_records' => $deletedRecords
            ])->log('bulk-deleted');

        return redirect()->route('employees.profile', $employee->id)
            ->with(['message' => 'Đã xoá các kỹ năng đã chọn.', 'type' => 'success']);
    }

    // ==== (tuỳ chọn) CRUD danh mục Skill riêng ====
    public function skillIndex()
    {
        $this->authorize('view employees'); // hoặc permission manage skills
        return response()->json(Skill::orderBy('name')->get(['id','name','code']));
    }
    public function skillStore(Request $request)
    {
        $this->authorize('edit employees');
        $data = $request->validate(['name'=>'required|string|max:255','code'=>'nullable|string|max:100']);
        $skill = Skill::create($data);
        activity('skill')->performedOn($skill)->causedBy($request->user())
            ->withProperties(['payload'=>$data])->log('created');
        return back()->with('success','Đã tạo kỹ năng.');
    }
    public function skillUpdate(Request $request, Skill $skill)
    {
        $this->authorize('edit employees');
        $data = $request->validate(['name'=>'required|string|max:255','code'=>'nullable|string|max:100']);
        $before = $skill->getOriginal();
        $skill->update($data);
        activity('skill')->performedOn($skill)->causedBy($request->user())
            ->withProperties(['before'=>$before,'after'=>$skill->getAttributes(),'changed'=>array_keys($skill->getChanges())])->log('updated');
        return back()->with('success','Đã cập nhật kỹ năng.');
    }
    public function skillDestroy(Request $request, Skill $skill)
    {
        $this->authorize('edit employees');
        $snapshot = $skill->toArray();
        $skill->delete();
        activity('skill')->performedOn($skill)->causedBy($request->user())
            ->withProperties(['deleted'=>$snapshot])->log('deleted');
        return back()->with('success','Đã xoá kỹ năng.');
    }
}
