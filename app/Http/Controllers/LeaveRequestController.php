<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveApprovalService;
use App\Services\LeaveCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LeaveRequestController extends Controller
{
    protected LeaveApprovalService $approvalService;
    protected LeaveCalculationService $calculationService;

    public function __construct(
        LeaveApprovalService $approvalService,
        LeaveCalculationService $calculationService
    ) {
        $this->approvalService = $approvalService;
        $this->calculationService = $calculationService;
    }

    /**
     * Display a listing of leave requests
     */
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['employee', 'leaveType', 'approvals.approver']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by leave type
        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        // Filter by employee (for managers viewing their team)
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Search by employee name or reason
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%");
                })->orWhere('reason', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $leaveRequests = $query->paginate($request->input('per_page', 15));

        return Inertia::render('LeaveRequests/Index', [
            'leaveRequests' => LeaveRequestResource::collection($leaveRequests),
            'leaveTypes' => LeaveType::active()->ordered()->get(),
            'filters' => $request->only(['status', 'leave_type_id', 'start_date', 'end_date', 'employee_id', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new leave request
     */
    public function create()
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['Admin', 'Super Admin']);

        $data = [
            'leaveTypes' => LeaveType::active()->ordered()->get(),
            'personalLeaveReasons' => $this->calculationService->getPersonalPaidLeaveReasons(),
            'mode' => 'create',
            'isAdmin' => $isAdmin,
        ];

        // If Admin or Super Admin, provide ALL employees list for selection
        if ($isAdmin) {
            $data['employees'] = Employee::orderBy('full_name')
                ->get(['id', 'full_name', 'employee_code']);
        } else {
            // Non-Admin: provide their own employee info
            $employee = Employee::where('user_id', $user->id)->first();
            if (!$employee) {
                return redirect()->back()->with([
                    'message' => 'Không tìm thấy thông tin nhân viên',
                    'type' => 'error'
                ]);
            }
            $data['employee'] = $employee;
        }

        return Inertia::render('LeaveRequests/Form', $data);
    }

    /**
     * Store a newly created leave request
     */
    public function store(StoreLeaveRequestRequest $request)
    {
        \Log::info("Store Leave Request", $request->all());
        $user = Auth::user();
        $validated = $request->validated();

        // If not Admin or Super Admin, use authenticated user's employee_id
        if (!$user->hasAnyRole(['Admin', 'Super Admin'])) {
            $employee = Employee::where('user_id', $user->id)->first();
            if (!$employee) {
                return redirect()->back()->with([
                    'message' => 'Không tìm thấy thông tin nhân viên',
                    'type' => 'error'
                ]);
            }
            $validated['employee_id'] = $employee->id;
        }

        // Get employee and leave type
        $employee = Employee::findOrFail($validated['employee_id']);
        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);

        // Additional validations based on leave type
        if ($leaveType->code === 'PERSONAL_PAID') {
            $validation = $this->calculationService->validatePersonalPaidLeave($validated);
            if (!$validation['valid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['personal_leave_reason' => $validation['message']]);
            }
        } elseif ($leaveType->code === 'SICK') {
            $validation = $this->calculationService->validateSickLeave($validated);
            if (!$validation['valid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['medical_certificate_path' => $validation['message']]);
            }
        } elseif ($leaveType->code === 'MATERNITY') {
            $validation = $this->calculationService->validateMaternityLeave($employee, $validated);
            if (!$validation['valid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['expected_due_date' => $validation['message']]);
            }
        }

        // For ANNUAL leave, check balance
        if ($leaveType->code === 'ANNUAL') {
            $year = \Carbon\Carbon::parse($validated['start_date'])->year;
            $balance = LeaveBalance::where('employee_id', $validated['employee_id'])
                ->where('leave_type_id', $validated['leave_type_id'])
                ->where('year', $year)
                ->first();

            $days = $validated['days'] ?? 0;
            if (!$balance || $balance->remaining_days < $days) {
                return redirect()->back()
                    ->withInput()
                    ->with([
                        'message' => 'Số dư phép năm không đủ. Còn lại: ' . ($balance->remaining_days ?? 0) . ' ngày',
                        'type' => 'error'
                    ]);
            }
        }

        // Handle file upload for medical certificate
        if ($request->hasFile('medical_certificate_path')) {
            $path = $request->file('medical_certificate_path')->store('medical-certificates', 'public');
            $validated['medical_certificate_path'] = $path;
        }

        // Create leave request
        $leaveRequest = new LeaveRequest([
            'employee_id' => $validated['employee_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days' => $validated['days'],
            'reason' => $validated['reason'] ?? null,
            'note' => $validated['note'] ?? null,
            'personal_leave_reason' => $validated['personal_leave_reason'] ?? null,
            'expected_due_date' => $validated['expected_due_date'] ?? null,
            'twins_count' => $validated['twins_count'] ?? null,
            'is_caesarean' => $validated['is_caesarean'] ?? false,
            'children_under_36_months' => $validated['children_under_36_months'] ?? null,
            'medical_certificate_path' => $validated['medical_certificate_path'] ?? null,
            'status' => LeaveRequest::STATUS_DRAFT,
            'created_by' => Auth::id(),
        ]);

        // Check for overlapping leave requests
        if ($overlapping = $leaveRequest->hasOverlappingLeave()) {
            $statusLabels = [
                LeaveRequest::STATUS_DRAFT => 'Nháp',
                LeaveRequest::STATUS_PENDING => 'Chờ duyệt',
                LeaveRequest::STATUS_APPROVED => 'Đã duyệt',
            ];
            $statusText = $statusLabels[$overlapping->status] ?? $overlapping->status;

            return redirect()->back()
                ->withInput()
                ->with([
                    'message' => "Khoảng thời gian này đã có đơn nghỉ phép ({$statusText}). Vui lòng chọn ngày khác.",
                    'type' => 'error'
                ]);
        }

        // Save to database
        $leaveRequest->save();

        // Log activity
        activity()
            ->useLog('leave-request')
            ->performedOn($leaveRequest)
            ->causedBy(Auth::user())
            ->withProperties([
                'employee_name' => $leaveRequest->employee->full_name,
                'leave_type' => $leaveRequest->leaveType->name,
                'days' => $leaveRequest->days,
            ])
            ->log('Tạo đơn nghỉ phép');

        // Submit for approval if requested
        if ($request->boolean('submit')) {
            $result = $this->approvalService->submitForApproval($leaveRequest, Auth::user());

            if (!$result['success']) {
                return redirect()->back()->with([
                    'message' => 'Gửi đơn nghỉ phép thất bại',
                    'type' => 'error'
                ]);
            }

            return redirect()->route('leave-requests.show', $leaveRequest)->with([
                'message' => 'Gửi đơn nghỉ phép thành công',
                'type' => 'success'
            ]);
        }

        return redirect()->route('leave-requests.show', $leaveRequest)->with([
            'message' => 'Tạo đơn nghỉ phép thành công',
            'type' => 'success'
        ]);
    }

    /**
     * Display the specified leave request
     */
    public function show(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load([
            'employee',
            'leaveType',
            'approvals' => function ($query) {
                $query->with('approver')->orderBy('step');
            }
        ]);

        return Inertia::render('LeaveRequests/Detail', [
            'leaveRequest' => (new LeaveRequestResource($leaveRequest))->resolve(),
            'canApprove' => $leaveRequest->canApprove(Auth::id()),
        ]);
    }

    /**
     * Show the form for editing the leave request
     */
    public function edit(LeaveRequest $leaveRequest)
    {
        // Can only edit DRAFT requests
        if ($leaveRequest->status !== LeaveRequest::STATUS_DRAFT) {
            return redirect()->back()->with([
                'message' => 'Không thể chỉnh sửa đơn đã gửi duyệt',
                'type' => 'error'
            ]);
        }

        $user = Auth::user();
        $data = [
            'leaveRequest' => new LeaveRequestResource($leaveRequest->load(['employee', 'leaveType'])),
            'leaveTypes' => LeaveType::active()->ordered()->get(),
            'personalLeaveReasons' => $this->calculationService->getPersonalPaidLeaveReasons(),
            'mode' => 'edit',
            'isAdmin' => $user->hasAnyRole(['Admin', 'Super Admin']),
        ];

        // If Admin or Super Admin, provide ALL employees list for selection
        if ($user->hasAnyRole(['Admin', 'Super Admin'])) {
            $data['employees'] = Employee::orderBy('full_name')
                ->get(['id', 'full_name', 'employee_code']);
        }

        return Inertia::render('LeaveRequests/Form', $data);
    }

    /**
     * Update the specified leave request
     */
    public function update(StoreLeaveRequestRequest $request, LeaveRequest $leaveRequest)
    {
        // Can only update DRAFT requests
        if ($leaveRequest->status !== LeaveRequest::STATUS_DRAFT) {
            return redirect()->back()->with([
                'message' => 'Không thể cập nhật đơn đã gửi duyệt',
                'type' => 'error'
            ]);
        }

        $validated = $request->validated();
        $employee = $leaveRequest->employee;
        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);

        // Additional validations based on leave type
        if ($leaveType->code === 'PERSONAL_PAID') {
            $validation = $this->calculationService->validatePersonalPaidLeave($validated);
            if (!$validation['valid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['personal_leave_reason' => $validation['message']]);
            }
        } elseif ($leaveType->code === 'SICK') {
            $validation = $this->calculationService->validateSickLeave($validated);
            if (!$validation['valid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['medical_certificate_path' => $validation['message']]);
            }
        } elseif ($leaveType->code === 'MATERNITY') {
            $validation = $this->calculationService->validateMaternityLeave($employee, $validated);
            if (!$validation['valid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['expected_due_date' => $validation['message']]);
            }
        }

        // For ANNUAL leave, check balance
        if ($leaveType->code === 'ANNUAL') {
            $year = now()->parse($validated['start_date'])->year;
            $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $validated['leave_type_id'])
                ->where('year', $year)
                ->first();

            // Calculate used days (excluding current request)
            $usedDays = LeaveRequest::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $validated['leave_type_id'])
                ->where('id', '!=', $leaveRequest->id)
                ->whereIn('status', [LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_PENDING])
                ->whereYear('start_date', $year)
                ->sum('days');

            $availableDays = ($balance->total ?? 0) - $usedDays;

            if ($availableDays < $validated['days']) {
                return redirect()->back()
                    ->withInput()
                    ->with([
                        'message' => 'Số dư phép năm không đủ. Còn lại: ' . $availableDays . ' ngày',
                        'type' => 'error'
                    ]);
            }
        }

        // Handle file upload for medical certificate
        if ($request->hasFile('medical_certificate_path')) {
            // Delete old file if exists
            if ($leaveRequest->medical_certificate_path) {
                Storage::disk('public')->delete($leaveRequest->medical_certificate_path);
            }
            $path = $request->file('medical_certificate_path')->store('medical-certificates', 'public');
            $validated['medical_certificate_path'] = $path;
        }

        // Set new values for overlap check
        $leaveRequest->leave_type_id = $validated['leave_type_id'];
        $leaveRequest->start_date = $validated['start_date'];
        $leaveRequest->end_date = $validated['end_date'];

        // Check for overlapping leave requests
        if ($overlapping = $leaveRequest->hasOverlappingLeave()) {
            $statusLabels = [
                LeaveRequest::STATUS_DRAFT => 'Nháp',
                LeaveRequest::STATUS_PENDING => 'Chờ duyệt',
                LeaveRequest::STATUS_APPROVED => 'Đã duyệt',
            ];
            $statusText = $statusLabels[$overlapping->status] ?? $overlapping->status;

            return redirect()->back()
                ->withInput()
                ->with([
                    'message' => "Khoảng thời gian này đã có đơn nghỉ phép ({$statusText}). Vui lòng chọn ngày khác.",
                    'type' => 'error'
                ]);
        }

        // Update with validated data
        $leaveRequest->fill([
            'days' => $validated['days'],
            'reason' => $validated['reason'] ?? null,
            'note' => $validated['note'] ?? null,
            'personal_leave_reason' => $validated['personal_leave_reason'] ?? null,
            'expected_due_date' => $validated['expected_due_date'] ?? null,
            'twins_count' => $validated['twins_count'] ?? null,
            'is_caesarean' => $validated['is_caesarean'] ?? false,
            'children_under_36_months' => $validated['children_under_36_months'] ?? null,
            'medical_certificate_path' => $validated['medical_certificate_path'] ?? $leaveRequest->medical_certificate_path,
        ]);
        $leaveRequest->save();

        // Log activity
        activity()
            ->useLog('leave-request')
            ->performedOn($leaveRequest)
            ->causedBy(Auth::user())
            ->log('Cập nhật đơn nghỉ phép');

        // Submit if requested
        if ($request->boolean('submit')) {
            $result = $this->approvalService->submitForApproval($leaveRequest, Auth::user());

            if (!$result['success']) {
                return redirect()->back()->with([
                    'message' => 'Gửi đơn nghỉ phép thất bại',
                    'type' => 'error'
                ]);
            }

            return redirect()->route('leave-requests.show', $leaveRequest)->with([
                'message' => 'Gửi đơn nghỉ phép thành công',
                'type' => 'success'
            ]);
        }

        return redirect()->route('leave-requests.show', $leaveRequest)->with([
            'message' => 'Cập nhật đơn nghỉ phép thành công',
            'type' => 'success'
        ]);
    }

    /**
     * Submit draft request for approval
     */
    public function submit(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== LeaveRequest::STATUS_DRAFT) {
            return redirect()->back()->with([
                'message' => 'Đơn không ở trạng thái nháp',
                'type' => 'error'
            ]);
        }

        $result = $this->approvalService->submitForApproval($leaveRequest, Auth::user());

        if (!$result['success']) {
            return redirect()->back()->with([
                'message' => 'Gửi đơn nghỉ phép thất bại',
                'type' => 'error'
            ]);
        }

        return redirect()->route('leave-requests.show', $leaveRequest)->with([
            'message' => 'Gửi đơn nghỉ phép thành công',
            'type' => 'success'
        ]);
    }

    /**
     * Cancel a leave request
     */
    public function cancel(LeaveRequest $leaveRequest)
    {
        // Can cancel DRAFT or PENDING requests
        if (!in_array($leaveRequest->status, [LeaveRequest::STATUS_DRAFT, LeaveRequest::STATUS_PENDING])) {
            return redirect()->back()->with([
                'message' => 'Không thể hủy đơn này',
                'type' => 'error'
            ]);
        }

        $leaveRequest->update([
            'status' => LeaveRequest::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        // Log activity
        activity()
            ->useLog('leave-request')
            ->performedOn($leaveRequest)
            ->causedBy(Auth::user())
            ->log('Hủy đơn nghỉ phép');

        return redirect()->route('leave-requests.index')->with([
            'message' => 'Hủy đơn nghỉ phép thành công',
            'type' => 'success'
        ]);
    }

    /**
     * Remove the specified leave request (soft delete)
     */
    public function destroy(LeaveRequest $leaveRequest)
    {
        // Can only delete DRAFT or CANCELLED requests
        if (!in_array($leaveRequest->status, [LeaveRequest::STATUS_DRAFT, LeaveRequest::STATUS_CANCELLED])) {
            return redirect()->back()->with([
                'message' => 'Không thể xóa đơn này',
                'type' => 'error'
            ]);
        }

        // Log before delete
        activity()
            ->useLog('leave-request')
            ->performedOn($leaveRequest)
            ->causedBy(Auth::user())
            ->log('Xóa đơn nghỉ phép');

        $leaveRequest->delete();

        return redirect()->route('leave-requests.index')->with([
            'message' => 'Xóa đơn nghỉ phép thành công',
            'type' => 'success'
        ]);
    }

    /**
     * Get leave balance for an employee
     */
    public function balance(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $year = $request->input('year', now()->year);

        $employee = Employee::findOrFail($employeeId);

        $balances = $employee->leaveBalances()
            ->where('year', $year)
            ->with('leaveType')
            ->get();

        return response()->json($balances);
    }
}
