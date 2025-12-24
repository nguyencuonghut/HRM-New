<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Http\Resources\ContractAppendixResource;
use App\Http\Resources\ContractTimelineResource;
use App\Models\{Contract, ContractTemplate, Employee, Department, Position};
use App\Enums\{ContractType, ContractStatus, ContractSource, AppendixType, ActivityLogDescription};
use App\Services\ContractApprovalService;
use App\Services\ContractTerminationService;
use App\Enums\ContractTerminationReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ContractController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Contract::class);

        $contracts = Contract::with([
                'employee:id,full_name,employee_code',
                'department:id,name,code',
                'position:id,title',
                'approvals.approver:id,name,email',
                'attachments'
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
        $payload['created_by'] = $request->user()->id;

        // Auto-fill snapshot from PRIMARY assignment if not provided
        if (!isset($payload['snapshot_department_name']) && isset($payload['employee_id'])) {
            $primaryAssignment = \App\Models\EmployeeAssignment::where('employee_id', $payload['employee_id'])
                ->where('is_primary', true)
                ->where('status', 'ACTIVE')
                ->with(['department', 'position'])
                ->first();

            if ($primaryAssignment) {
                $payload['department_id'] = $primaryAssignment->department_id;
                $payload['position_id'] = $primaryAssignment->position_id;
                $payload['snapshot_department_name'] = $primaryAssignment->department?->name;
                $payload['snapshot_position_title'] = $primaryAssignment->position?->title;
                $payload['snapshot_role_type'] = $primaryAssignment->role_type;
            }
        }

        // Rule: chặn overlap với hợp đồng ACTIVE
        // Kiểm tra cho mọi hợp đồng mới để tránh tạo hợp đồng sẽ overlap khi activate
        $this->ensureNoActiveOverlap($payload['employee_id'], $payload['start_date'] ?? null, $payload['end_date'] ?? null);

        $row = Contract::create($payload);

        // Refresh to get employment_id set by Observer
        $row->refresh();

        // Upload attachments nếu có
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('contracts/' . $row->id . '/attachments', $fileName, 'public');

                $row->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

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
            ->log(ActivityLogDescription::CONTRACT_CREATED->value);

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

        // Upload attachments mới nếu có
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('contracts/' . $contract->id . '/attachments', $fileName, 'public');

                $contract->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

        // Xóa attachments đã chọn (nếu có)
        if ($request->has('delete_attachments')) {
            $deleteIds = $request->input('delete_attachments', []);
            $attachmentsToDelete = $contract->attachments()->whereIn('id', $deleteIds)->get();

            foreach ($attachmentsToDelete as $attachment) {
                if (\Storage::disk('public')->exists($attachment->file_path)) {
                    \Storage::disk('public')->delete($attachment->file_path);
                }
                $attachment->delete();
            }
        }

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
            ])->log(ActivityLogDescription::CONTRACT_UPDATED->value);

        return redirect()->route('contracts.index')->with([
            'message' => 'Đã cập nhật hợp đồng.',
            'type'    => 'success'
        ]);
    }

    public function show(Request $request, Contract $contract)
    {
        $this->authorize('view', $contract);

        // Load các quan hệ cần cho header hồ sơ HĐ
        $contract->load(['employee', 'department', 'position', 'approvals.approver:id,name,email', 'attachments']);

        // Lấy danh sách phụ lục theo HĐ
        $appendixes = $contract->appendixes()
            ->with(['attachments', 'department:id,name,code', 'position:id,title,department_id'])
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->get();

        // Load activity log timeline - approval workflow for contract AND appendixes
        $contractActivities = Activity::forSubject($contract)
            ->with('causer:id,name,email')
            ->get();

        // Get all appendix activities
        $appendixActivities = collect();
        foreach ($contract->appendixes as $appendix) {
            $appendixActivities = $appendixActivities->merge(
                Activity::forSubject($appendix)
                    ->with('causer:id,name,email')
                    ->get()
            );
        }

        // Merge and sort all approval activities
        $timeline = $contractActivities
            ->merge($appendixActivities)
            ->sortBy('created_at')
            ->values();

        // Build contract timeline (merge contract events + appendixes)
        $contractTimeline = $this->buildContractTimeline($contract);

        $activeTab = request('tab', 'general'); // default nếu không truyền

        // Load data for appendix form
        $departments = Department::select('id','name','code')->orderBy('name')->get();
        $positions = Position::select('id','title','department_id')->orderBy('title')->get();
        $appendixTemplates = \App\Models\ContractAppendixTemplate::where('is_active', true)
            ->select('id','name','code','appendix_type','is_default')
            ->orderBy('name')
            ->get();

        // Check if user can backfill (for status field visibility)
        $canBackfill = $request->user()->can('create', \App\Models\ContractAppendix::class);

        return \Inertia\Inertia::render('ContractDetail', [
            'contract'   => new ContractResource($contract)->resolve(),
            'appendixes' => ContractAppendixResource::collection($appendixes)->resolve(),
            'timeline'   => ContractTimelineResource::collection($timeline)->resolve(),
            'contractTimeline' => $contractTimeline,
            'activeTab'   => $activeTab,
            'departments' => $departments,
            'positions'   => $positions,
            'appendixTemplates' => $appendixTemplates,
            'canBackfill' => $canBackfill,
        ]);
    }

    /**
     * Build contract timeline with all events
     */
    private function buildContractTimeline(Contract $contract)
    {
        $events = collect();

        // 1. Contract created event
        $events->push([
            'id' => 'contract_created',
            'event_type' => 'contract_created',
            'created_at' => $contract->created_at,
            'actor' => $contract->creator ? [
                'id' => $contract->creator->id,
                'name' => $contract->creator->name,
            ] : null,
            'status' => $contract->status,
            'details' => [
                'contract_type_label' => ContractType::tryFrom($contract->contract_type)?->label(),
                'start_date' => $contract->start_date,
                'end_date' => $contract->end_date,
            ],
        ]);

        // 2. Appendixes (including renewals)
        foreach ($contract->appendixes as $appendix) {
            $eventType = $appendix->appendix_type === AppendixType::EXTENSION ? 'contract_renewal' : 'appendix_created';

            $details = [
                'appendix_no' => $appendix->appendix_no,
                'type_label' => $appendix->appendix_type->label(),
                'effective_date' => $appendix->effective_date,
                'description' => $appendix->content,
            ];

            // Add renewal-specific details
            if ($appendix->appendix_type === AppendixType::EXTENSION) {
                $activities = Activity::forSubject($contract)
                    ->where('properties->appendix_id', $appendix->id)
                    ->first();

                $details['old_end_date'] = $activities?->properties['old_end_date'] ?? null;
                $details['new_end_date'] = $activities?->properties['new_end_date'] ?? $appendix->effective_date;

                if ($appendix->status === 'ACTIVE' && $appendix->approver) {
                    $details['approved_at'] = $appendix->approved_at;
                    $details['approver_name'] = $appendix->approver->name;
                }

                if ($appendix->status === 'REJECTED' && $appendix->approver) {
                    $details['rejected_at'] = $appendix->rejected_at;
                    $details['approver_name'] = $appendix->approver->name;
                    $details['approval_note'] = $appendix->approval_note;
                }
            }

            $events->push([
                'id' => 'appendix_' . $appendix->id,
                'event_type' => $eventType,
                'created_at' => $appendix->created_at,
                'actor' => $appendix->creator ? [
                    'id' => $appendix->creator->id,
                    'name' => $appendix->creator->name,
                ] : null,
                'status' => $appendix->status,
                'details' => $details,
            ]);
        }

        // 3. Contract terminated event
        if ($contract->status === 'TERMINATED' && $contract->terminated_at) {
            $events->push([
                'id' => 'contract_terminated',
                'event_type' => 'contract_terminated',
                'created_at' => $contract->terminated_at,
                'actor' => null, // TODO: Add terminator if available
                'status' => 'TERMINATED',
                'details' => [
                    'terminated_at' => $contract->terminated_at,
                    'termination_reason_label' => ContractTerminationReason::tryFrom($contract->termination_reason)?->label(),
                    'termination_note' => $contract->termination_note,
                ],
            ]);
        }

        // Sort by created_at descending (newest first)
        return $events->sortByDesc('created_at')->values()->all();
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
            ->withProperties(['deleted' => $snapshot])->log(ActivityLogDescription::CONTRACT_DELETED->value);

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
            ])->log(ActivityLogDescription::CONTRACT_BULK_DELETED->value);

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
        // Loại trừ các hợp đồng đã TERMINATED (không tính overlap)

        $exists = Contract::where('employee_id', $employeeId)
            ->where('status', '!=', 'TERMINATED') // Loại trừ hợp đồng đã chấm dứt
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

    // ==================== APPROVAL WORKFLOW ====================

    /**
     * Gửi hợp đồng để phê duyệt
     */
    public function submitForApproval(Request $request, Contract $contract, ContractApprovalService $approvalService)
    {
        $this->authorize('submit', $contract);

        try {
            $approvalService->submitForApproval($contract);

            return redirect()->route('contracts.index')->with([
                'message' => 'Đã gửi hợp đồng để phê duyệt.',
                'type'    => 'success'
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Phê duyệt hợp đồng (workflow mới)
     */
    public function approve(Request $request, Contract $contract, ContractApprovalService $approvalService)
    {
        $this->authorize('approve', $contract);

        $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        try {
            // Kiểm tra overlap trước khi approve (chỉ ở bước cuối)
            $currentStep = $contract->getCurrentApprovalStep();
            if ($currentStep && $currentStep->level->value === 'DIRECTOR') {
                $this->ensureNoActiveOverlap(
                    $contract->employee_id,
                    $contract->start_date?->format('Y-m-d'),
                    $contract->end_date?->format('Y-m-d'),
                    $contract->id
                );
            }

            $approvalService->approve($contract, $request->user(), $request->input('comments'));

            return redirect()->route('contracts.index')->with([
                'message' => 'Đã phê duyệt hợp đồng.',
                'type'    => 'success'
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Từ chối hợp đồng (workflow mới)
     */
    public function reject(Request $request, Contract $contract, ContractApprovalService $approvalService)
    {
        $this->authorize('approve', $contract);

        $request->validate([
            'comments' => 'required|string|max:1000',
        ], [
            'comments.required' => 'Vui lòng nhập lý do từ chối.'
        ]);

        try {
            $approvalService->reject($contract, $request->user(), $request->input('comments'));

            return redirect()->route('contracts.index')->with([
                'message' => 'Đã từ chối hợp đồng.',
                'type'    => 'success'
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Thu hồi yêu cầu phê duyệt
     */
    public function recall(Request $request, Contract $contract, ContractApprovalService $approvalService)
    {
        $this->authorize('recall', $contract);

        try {
            $approvalService->recall($contract);

            return redirect()->route('contracts.index')->with([
                'message' => 'Đã thu hồi yêu cầu phê duyệt.',
                'type'    => 'success'
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Lấy danh sách hợp đồng chờ phê duyệt của user hiện tại
     */
    public function pendingApprovals(Request $request, ContractApprovalService $approvalService)
    {
        $contracts = $approvalService->getPendingContractsForUser($request->user());

        return response()->json([
            'data' => ContractResource::collection($contracts),
            'count' => $contracts->count(),
        ]);
    }

    /**
     * Chấm dứt hợp đồng
     */
    public function terminate(Request $request, Contract $contract, ContractTerminationService $service)
    {
        $this->authorize('update', $contract);
        \Log::info('Terminating contract', ['contract_id' => $contract->id, 'user_id' => $request->user()->id]);

        $validated = $request->validate([
            'terminated_at' => 'required|date',
            'termination_reason' => 'required|string|in:' . implode(',', array_column(ContractTerminationReason::cases(), 'value')),
            'termination_note' => 'nullable|string|max:1000',
        ]);

        try {
            $service->terminateContract($contract, $validated, $request->user());

            return redirect()->back()->with([
                'message' => 'Hợp đồng đã được chấm dứt thành công',
                'type' => 'success'
            ]);
        } catch (\InvalidArgumentException $e) {
            \Log::error('Contract termination validation failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors([
                'contract' => $e->getMessage(),
            ])->with([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        } catch (\Exception $e) {
            \Log::error('Contract termination failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors([
                'contract' => 'Có lỗi xảy ra khi chấm dứt hợp đồng: ' . $e->getMessage(),
            ])->with([
                'message' => 'Không thể chấm dứt hợp đồng. Vui lòng kiểm tra lại.',
                'type' => 'error'
            ]);
        }
    }

    /**
     * Lấy danh sách các lý do chấm dứt hợp đồng (API cho modal)
     */
    public function terminationReasons()
    {
        return response()->json([
            'data' => ContractTerminationReason::options(),
        ]);
    }

    /**
     * Tính toán trợ cấp thôi việc (API cho modal)
     */
    public function calculateSeverancePay(Request $request, Contract $contract, ContractTerminationService $service)
    {
        $this->authorize('view', $contract);

        $reason = $request->input('reason');

        if (!$reason) {
            return response()->json([
                'data' => [
                    'eligible' => false,
                    'amount' => 0,
                    'note' => 'Vui lòng chọn lý do chấm dứt để tính trợ cấp',
                ]
            ]);
        }

        return response()->json([
            'data' => $service->calculateSeverancePay($contract, $reason),
        ]);
    }

    /**
     * Lấy danh sách hợp đồng đã chấm dứt
     */
    public function terminated(Request $request, ContractTerminationService $service)
    {
        $this->authorize('viewAny', Contract::class);

        $filters = $request->only(['reason', 'from_date', 'to_date', 'department_id']);
        $contracts = $service->getTerminatedContracts($filters)->paginate(20);

        return response()->json([
            'data' => ContractResource::collection($contracts),
            'meta' => [
                'current_page' => $contracts->currentPage(),
                'last_page' => $contracts->lastPage(),
                'total' => $contracts->total(),
            ],
        ]);
    }

    /**
     * Thống kê chấm dứt hợp đồng
     */
    public function terminationStatistics(Request $request, ContractTerminationService $service)
    {
        $this->authorize('viewAny', Contract::class);

        $year = $request->input('year', now()->year);
        $stats = $service->getTerminationStatistics($year);

        return response()->json(['data' => $stats]);
    }

    /**
     * Gia hạn hợp đồng
     */
    public function renew(Request $request, Contract $contract, \App\Services\ContractRenewalService $renewalService)
    {
        $this->authorize('update', $contract);

        try {
            $validated = $request->validate([
                'new_end_date' => 'required|date|after:' . ($contract->end_date ?? $contract->start_date),
                'title' => 'nullable|string|max:255',
                'summary' => 'nullable|string',
                'note' => 'nullable|string',
                'base_salary' => 'nullable|integer|min:0',
                'insurance_salary' => 'nullable|integer|min:0',
                'position_allowance' => 'nullable|integer|min:0',
                'other_allowances' => 'nullable|array',
                'department_id' => 'nullable|uuid|exists:departments,id',
                'position_id' => 'nullable|uuid|exists:positions,id',
                'working_time' => 'nullable|string|max:255',
                'work_location' => 'nullable|string|max:500',
            ]);

            $appendix = $renewalService->renewContract($contract, $validated, $request->user());

            return redirect()->back()->with('success', 'Yêu cầu gia hạn hợp đồng đã được tạo và đang chờ phê duyệt');
        } catch (\Exception $e) {
            Log::error('Contract renewal failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Lấy danh sách hợp đồng sắp hết hạn
     */
    public function expiring(Request $request, \App\Services\ContractRenewalService $renewalService)
    {
        $this->authorize('viewAny', Contract::class);

        $daysThreshold = $request->input('days', 30);
        $contracts = $renewalService->getExpiringContracts($daysThreshold);

        return response()->json([
            'success' => true,
            'data' => ContractResource::collection($contracts),
        ]);
    }

    /**
     * Phê duyệt phụ lục
     */
    public function approveAppendix(Request $request, Contract $contract, $appendixId, \App\Services\ContractRenewalService $renewalService)
    {
        try {
            $appendix = \App\Models\ContractAppendix::findOrFail($appendixId);

            if ($appendix->contract_id !== $contract->id) {
                throw new \Exception('Phụ lục không thuộc về hợp đồng này');
            }

            $this->authorize('approve', $appendix);

            $validated = $request->validate([
                'note' => 'nullable|string',
            ]);

            $renewalService->approveRenewal($appendix, $request->user(), $validated['note'] ?? null);

            return redirect()->back()->with('success', 'Phụ lục đã được phê duyệt thành công');
        } catch (\Exception $e) {
            Log::error('Appendix approval failed', [
                'contract_id' => $contract->id,
                'appendix_id' => $appendixId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Từ chối phụ lục
     */
    public function rejectAppendix(Request $request, Contract $contract, $appendixId, \App\Services\ContractRenewalService $renewalService)
    {
        try {
            $appendix = \App\Models\ContractAppendix::findOrFail($appendixId);

            if ($appendix->contract_id !== $contract->id) {
                throw new \Exception('Phụ lục không thuộc về hợp đồng này');
            }

            $this->authorize('approve', $appendix);

            $validated = $request->validate([
                'note' => 'required|string',
            ]);

            $renewalService->rejectRenewal($appendix, $request->user(), $validated['note']);

            return redirect()->back()->with('success', 'Phụ lục đã bị từ chối');
        } catch (\Exception $e) {
            Log::error('Appendix rejection failed', [
                'contract_id' => $contract->id,
                'appendix_id' => $appendixId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Download contract attachment
     */
    public function downloadAttachment(\App\Models\ContractAttachment $attachment)
    {
        $contract = $attachment->contract;
        $this->authorize('view', $contract);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File không tồn tại');
        }

        $path = Storage::disk('public')->path($attachment->file_path);

        // Return file inline (browser will display if possible, otherwise download)
        return response()->file($path, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="' . $attachment->file_name . '"'
        ]);
    }
}
