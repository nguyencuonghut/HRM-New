<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Http\Resources\ContractAppendixResource;
use App\Models\{Contract, ContractTemplate, Employee, Department, Position};
use App\Enums\{ContractType, ContractStatus, ContractSource};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class ContractController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Contract::class);

        $contracts = Contract::with([
                'employee:id,full_name,employee_code',
                'department:id,name,code',
                'position:id,title'
            ])
            ->latest('created_at')
            ->get();

        $employees   = Employee::select('id','full_name','employee_code')->orderBy('full_name')->get();
        $departments = Department::select('id','name')->orderBy('name')->get();
        $positions   = Position::select('id','title','department_id')->orderBy('title')->get();

        return Inertia::render('ContractIndex', [
            'contracts'  => ContractResource::collection($contracts)->resolve(),
            'employees'  => $employees,
            'departments'=> $departments,
            'positions'  => $positions,
            'contractTypeOptions' => collect(ContractType::cases())->map(fn($c)=>['value'=>$c->value,'label'=>$c->label()])->values(),
            'statusOptions' => collect(ContractStatus::cases())->map(fn($c)=>['value'=>$c->value,'label'=>$c->label()])->values(),
            'sourceOptions' => collect(ContractSource::cases())->map(fn($c)=>['value'=>$c->value,'label'=>$c->label()])->values(),
        ]);
    }

    public function store(StoreContractRequest $request)
    {
        $this->authorize('create', Contract::class);

        $payload = $request->validated();

        // Rule: chặn overlap với hợp đồng ACTIVE
        // Kiểm tra cho mọi hợp đồng mới để tránh tạo hợp đồng sẽ overlap khi activate
        $this->ensureNoActiveOverlap($payload['employee_id'], $payload['start_date'] ?? null, $payload['end_date'] ?? null);

        $row = Contract::create($payload);
        $row->load(['employee:id,full_name,employee_code', 'department:id,name', 'position:id,title', 'template:id,name']);

        $employee = $row->employee;
        $department = $row->department;
        $position = $row->position;
        $template = $row->template;

        activity('contract')->performedOn($row)->causedBy($request->user())
            ->withProperties([
                'contract_number' => $row->contract_number,
                'employee' => $employee ? $employee->full_name . ' (' . $employee->employee_code . ')' : null,
                'department' => $department?->name,
                'position' => $position?->title,
                'contract_type' => ContractType::tryFrom($row->contract_type)?->label(),
                'status' => ContractStatus::tryFrom($row->status)?->label(),
                'start_date' => $row->start_date?->format('d/m/Y'),
                'end_date' => $row->end_date?->format('d/m/Y'),
                'base_salary' => number_format($row->base_salary ?? 0, 0, ',', '.') . ' VNĐ',
                'template' => $template?->name,
            ])
            ->log('created');

        return redirect()->route('contracts.index')->with([
            'message' => 'Đã tạo hợp đồng.',
            'type'    => 'success'
        ]);
    }

    public function update(UpdateContractRequest $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        $payload = $request->validated();

        // Rule: chặn overlap với hợp đồng ACTIVE
        // Kiểm tra cho mọi update để tránh tạo hợp đồng sẽ overlap khi activate
        // Sử dụng employee_id từ contract hiện tại vì không cho phép đổi employee
        $this->ensureNoActiveOverlap($contract->employee_id, $payload['start_date'] ?? null, $payload['end_date'] ?? null, $contract->id);

        // Lưu thông tin cũ (tường minh)
        $contract->load(['employee:id,full_name,employee_code', 'department:id,name', 'position:id,title', 'template:id,name']);
        $oldEmployee = $contract->employee;
        $oldDepartment = $contract->department;
        $oldPosition = $contract->position;
        $oldTemplate = $contract->template;

        $old = [
            'contract_number' => $contract->contract_number,
            'employee' => $oldEmployee ? $oldEmployee->full_name . ' (' . $oldEmployee->employee_code . ')' : null,
            'department' => $oldDepartment?->name,
            'position' => $oldPosition?->title,
            'contract_type' => ContractType::tryFrom($contract->contract_type)?->label(),
            'status' => ContractStatus::tryFrom($contract->status)?->label(),
            'start_date' => $contract->start_date?->format('d/m/Y'),
            'end_date' => $contract->end_date?->format('d/m/Y'),
            'base_salary' => number_format($contract->base_salary ?? 0, 0, ',', '.') . ' VNĐ',
            'template' => $oldTemplate?->name,
        ];

        $contract->update($payload);

        // Load lại relationships sau update
        $contract->load(['employee:id,full_name,employee_code', 'department:id,name', 'position:id,title', 'template:id,name']);
        $newEmployee = $contract->employee;
        $newDepartment = $contract->department;
        $newPosition = $contract->position;
        $newTemplate = $contract->template;

        $new = [
            'contract_number' => $contract->contract_number,
            'employee' => $newEmployee ? $newEmployee->full_name . ' (' . $newEmployee->employee_code . ')' : null,
            'department' => $newDepartment?->name,
            'position' => $newPosition?->title,
            'contract_type' => ContractType::tryFrom($contract->contract_type)?->label(),
            'status' => ContractStatus::tryFrom($contract->status)?->label(),
            'start_date' => $contract->start_date?->format('d/m/Y'),
            'end_date' => $contract->end_date?->format('d/m/Y'),
            'base_salary' => number_format($contract->base_salary ?? 0, 0, ',', '.') . ' VNĐ',
            'template' => $newTemplate?->name,
        ];

        activity('contract')->performedOn($contract)->causedBy($request->user())
            ->withProperties([
                'old' => $old,
                'attributes' => $new,
            ])->log('updated');

        return redirect()->route('contracts.index')->with([
            'message' => 'Đã cập nhật hợp đồng.',
            'type'    => 'success'
        ]);
    }

    public function show(Contract $contract)
    {
        $this->authorize('view', $contract);

        // Load các quan hệ cần cho header hồ sơ HĐ
        $contract->load(['employee', 'department', 'position']);

        // Lấy danh sách phụ lục theo HĐ
        $appendixes = $contract->appendixes()
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->get();

        $activeTab = request('tab', 'general'); // default nếu không truyền

        return \Inertia\Inertia::render('ContractDetail', [
            'contract'   => new ContractResource($contract)->resolve(),
            'appendixes' => ContractAppendixResource::collection($appendixes)->resolve(),
            'activeTab'   => $activeTab,
        ]);
    }


    public function destroy(Request $request, Contract $contract)
    {
        $this->authorize('delete', $contract);

        $contract->load(['employee:id,full_name,employee_code', 'department:id,name', 'position:id,title', 'template:id,name']);
        $employee = $contract->employee;
        $department = $contract->department;
        $position = $contract->position;
        $template = $contract->template;

        $snapshot = [
            'contract_number' => $contract->contract_number,
            'employee' => $employee ? $employee->full_name . ' (' . $employee->employee_code . ')' : null,
            'department' => $department?->name,
            'position' => $position?->title,
            'contract_type' => ContractType::tryFrom($contract->contract_type)?->label(),
            'status' => ContractStatus::tryFrom($contract->status)?->label(),
            'start_date' => $contract->start_date?->format('d/m/Y'),
            'end_date' => $contract->end_date?->format('d/m/Y'),
            'base_salary' => number_format($contract->base_salary ?? 0, 0, ',', '.') . ' VNĐ',
            'template' => $template?->name,
        ];

        // Xóa file PDF nếu có
        if ($contract->generated_pdf_path && Storage::disk('public')->exists($contract->generated_pdf_path)) {
            Storage::disk('public')->delete($contract->generated_pdf_path);
        }

        $contract->delete();

        activity('contract')->performedOn($contract)->causedBy($request->user())
            ->withProperties(['deleted' => $snapshot])->log('deleted');

        return redirect()->route('contracts.index')->with([
            'message' => 'Đã xóa hợp đồng.',
            'type'    => 'success'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', Contract::class);

        $ids = (array) $request->input('ids', []);
        $rows = Contract::with(['employee:id,full_name,employee_code', 'department:id,name', 'position:id,title'])->whereIn('id',$ids)->get();

        $snapshots = $rows->map(function($contract) {
            $employee = $contract->employee;
            $department = $contract->department;
            $position = $contract->position;

            return [
                'contract_number' => $contract->contract_number,
                'employee' => $employee ? $employee->full_name . ' (' . $employee->employee_code . ')' : null,
                'department' => $department?->name,
                'position' => $position?->title,
                'contract_type' => ContractType::tryFrom($contract->contract_type)?->label(),
                'status' => ContractStatus::tryFrom($contract->status)?->label(),
            ];
        })->toArray();

        // Xóa các file PDF nếu có
        foreach ($rows as $contract) {
            if ($contract->generated_pdf_path && Storage::disk('public')->exists($contract->generated_pdf_path)) {
                Storage::disk('public')->delete($contract->generated_pdf_path);
            }
        }

        Contract::whereIn('id',$ids)->delete();

        activity('contract')->causedBy($request->user())
            ->withProperties([
                'count' => count($ids),
                'deleted' => $snapshots
            ])->log('bulk-deleted');

        return redirect()->route('contracts.index')->with([
            'message' => 'Đã xóa nhiều hợp đồng.',
            'type'    => 'success'
        ]);
    }

    /** Kiểm tra không có hợp đồng nào chồng thời gian với (start,end) truyền vào */
    protected function ensureNoActiveOverlap(string $employeeId, ?string $start, ?string $end, ?string $ignoreId = null): void
    {
        // Overlap khi: existing.start <= new.end AND new.start <= existing.end
        // Với xử lý null (end_date = null nghĩa là vô thời hạn)

        $exists = Contract::where('employee_id', $employeeId)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where(function($query) use ($start, $end) {
                // Điều kiện 1: existing.start_date <= new.end_date (hoặc new.end_date là null/vô hạn)
                $query->where(function($q1) use ($end) {
                    if ($end) {
                        // Nếu new.end_date có giá trị, kiểm tra existing.start_date <= new.end_date
                        $q1->whereDate('start_date', '<=', $end);
                    }
                    // Nếu new.end_date = null (vô hạn), luôn overlap với mọi existing contract
                });

                // Điều kiện 2: new.start_date <= existing.end_date (hoặc existing.end_date là null/vô hạn)
                $query->where(function($q2) use ($start) {
                    // existing.end_date >= new.start_date HOẶC existing.end_date là null (vô hạn)
                    $q2->whereDate('end_date', '>=', $start)
                       ->orWhereNull('end_date');
                });
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'start_date' => 'Đã tồn tại hợp đồng của nhân viên này trong khoảng thời gian trùng lặp.',
            ]);
        }
    }

    // Phê duyệt
    public function approve(Request $request, Contract $contract)
    {
        $this->authorize('approve', $contract);

        // Kiểm tra overlap trước khi approve
        $this->ensureNoActiveOverlap(
            $contract->employee_id,
            $contract->start_date?->format('Y-m-d'),
            $contract->end_date?->format('Y-m-d'),
            $contract->id
        );

        $contract->load(['employee:id,full_name,employee_code']);
        $employee = $contract->employee;

        $contract->update([
            'status' => 'ACTIVE',
            'approver_id' => $request->user()->id,
            'approved_at' => now(),
            'rejected_at' => null,
            'approval_note' => $request->input('approval_note')
        ]);

        activity('contract')
            ->performedOn($contract)
            ->causedBy($request->user())
            ->withProperties([
                'contract_number' => $contract->contract_number,
                'employee' => $employee ? $employee->full_name . ' (' . $employee->employee_code . ')' : null,
                'action' => 'approved',
                'status' => 'Hiệu lực',
                'approval_note' => $request->input('approval_note'),
            ])->log('approved');

        return redirect()->route('contracts.index')->with([
            'message' => 'Đã phê duyệt hợp đồng.',
            'type'    => 'success'
        ]);
    }

    public function reject(Request $request, Contract $contract)
    {
        $this->authorize('approve', $contract);

        $contract->load(['employee:id,full_name,employee_code']);
        $employee = $contract->employee;

        $contract->update([
            'status' => 'DRAFT',
            'approver_id' => $request->user()->id,
            'rejected_at' => now(),
            'approval_note' => $request->input('approval_note')
        ]);

        activity('contract')
            ->performedOn($contract)
            ->causedBy($request->user())
            ->withProperties([
                'contract_number' => $contract->contract_number,
                'employee' => $employee ? $employee->full_name . ' (' . $employee->employee_code . ')' : null,
                'action' => 'rejected',
                'status' => 'Nháp',
                'approval_note' => $request->input('approval_note'),
            ])->log('rejected');

        return redirect()->route('contracts.index')->with([
            'message' => 'Đã từ chối hợp đồng.',
            'type'    => 'success'
        ]);
    }
}
