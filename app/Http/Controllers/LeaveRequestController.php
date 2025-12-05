<?php

namespace App\Http\Controllers;

use App\Http\Resources\LeaveRequestResource;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveRequestController extends Controller
{
    protected LeaveApprovalService $approvalService;

    public function __construct(LeaveApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
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
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return redirect()->back()->with('error', 'Employee profile not found');
        }

        return Inertia::render('LeaveRequests/Form', [
            'leaveTypes' => LeaveType::active()->ordered()->get(),
            'employee' => $employee,
            'mode' => 'create',
        ]);
    }

    /**
     * Store a newly created leave request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
            'submit' => 'boolean',
        ]);

        // Create temporary instance for overlap check
        $leaveRequest = new LeaveRequest([
            'employee_id' => $validated['employee_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'] ?? null,
            'status' => LeaveRequest::STATUS_DRAFT,
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
        $leaveRequest->created_by = Auth::id();
        $leaveRequest->save();

        // Calculate days
        $leaveRequest->days = $leaveRequest->calculateDays();
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
            $result = $this->approvalService->submitForApproval($leaveRequest);

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

        return Inertia::render('LeaveRequests/Form', [
            'leaveRequest' => new LeaveRequestResource($leaveRequest->load(['employee', 'leaveType'])),
            'leaveTypes' => LeaveType::active()->ordered()->get(),
            'mode' => 'edit',
        ]);
    }

    /**
     * Update the specified leave request
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        // Can only update DRAFT requests
        if ($leaveRequest->status !== LeaveRequest::STATUS_DRAFT) {
            return redirect()->back()->with([
                'message' => 'Không thể cập nhật đơn đã gửi duyệt',
                'type' => 'error'
            ]);
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
            'submit' => 'boolean',
        ]);

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
        $leaveRequest->reason = $validated['reason'] ?? null;
        $leaveRequest->save();

        // Recalculate days
        $leaveRequest->days = $leaveRequest->calculateDays();
        $leaveRequest->save();

        // Log activity
        activity()
            ->useLog('leave-request')
            ->performedOn($leaveRequest)
            ->causedBy(Auth::user())
            ->log('Cập nhật đơn nghỉ phép');

        // Submit if requested
        if ($request->boolean('submit')) {
            $result = $this->approvalService->submitForApproval($leaveRequest);

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

        $result = $this->approvalService->submitForApproval($leaveRequest);

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
