<?php

namespace App\Http\Controllers;

use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveApprovalController extends Controller
{
    protected LeaveApprovalService $approvalService;

    public function __construct(LeaveApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Display pending leave requests for current user (as approver)
     */
    public function index()
    {
        $user = Auth::user();
        $pendingRequests = $this->approvalService->getPendingRequestsForApprover($user);

        return Inertia::render('LeaveApprovals/Index', [
            'pendingRequests' => LeaveRequestResource::collection($pendingRequests)->resolve(),
            'pendingCount' => $pendingRequests->count(),
        ]);
    }

    /**
     * Approve a leave request
     */
    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'comment' => 'nullable|string|max:500',
        ]);

        $result = $this->approvalService->processApproval(
            $leaveRequest,
            Auth::user(),
            'approve',
            $validated['comment'] ?? null
        );

        if (!$result['success']) {
            return redirect()->back()->with([
                'message' => 'Không thể phê duyệt đơn này',
                'type' => 'error'
            ]);
        }

        return redirect()->back()->with([
            'message' => 'Đã phê duyệt đơn nghỉ phép',
            'type' => 'success'
        ]);
    }

    /**
     * Reject a leave request
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:500',
        ]);

        $result = $this->approvalService->processApproval(
            $leaveRequest,
            Auth::user(),
            'reject',
            $validated['comment']
        );

        if (!$result['success']) {
            return redirect()->back()->with([
                'message' => 'Không thể từ chối đơn này',
                'type' => 'error'
            ]);
        }

        return redirect()->back()->with([
            'message' => 'Đơn nghỉ phép đã bị từ chối',
            'type' => 'success'
        ]);
    }

    /**
     * Get pending count for badge
     */
    public function pendingCount()
    {
        $user = Auth::user();
        $count = $this->approvalService->getPendingCountForApprover($user);

        return response()->json(['count' => $count]);
    }
}
