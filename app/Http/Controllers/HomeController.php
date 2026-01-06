<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class HomeController extends Controller
{
    public function index()
    {
        // 1. Stats Cards
        $stats = [
            'totalEmployees' => Employee::where('status', 'ACTIVE')->count(),
            'newEmployeesThisMonth' => Employee::whereMonth('hire_date', Carbon::now()->month)
                ->whereYear('hire_date', Carbon::now()->year)
                ->count(),
            'onLeaveToday' => LeaveRequest::where('status', LeaveRequest::STATUS_APPROVED)
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->count(),
            'pendingLeave' => LeaveRequest::where('status', LeaveRequest::STATUS_PENDING)->count(),
            'expiringContracts' => Contract::where('status', 'ACTIVE')
                ->whereBetween('end_date', [today(), today()->addDays(30)])
                ->count(),
            'pendingApprovals' => Contract::where('status', 'PENDING_APPROVAL')->count() +
                LeaveRequest::where('status', LeaveRequest::STATUS_PENDING)->count(),
        ];

        // 2. Priority Items - Cần xử lý hôm nay
        $priorityItems = collect();

        // Hợp đồng sắp hết hạn trong 7 ngày (khẩn cấp hơn)
        $urgentContracts = Contract::where('status', 'ACTIVE')
            ->whereBetween('end_date', [today(), today()->addDays(7)])
            ->count();
        if ($urgentContracts > 0) {
            $priorityItems->push([
                'id' => 'urgent_contracts',
                'type' => 'contract_expiring_soon',
                'title' => 'Hợp đồng sắp hết hạn',
                'description' => "{$urgentContracts} hợp đồng hết hạn trong 7 ngày tới",
                'count' => $urgentContracts,
            ]);
        }

        // Đơn nghỉ phép chờ duyệt
        $pendingLeaves = LeaveRequest::where('status', LeaveRequest::STATUS_PENDING)->count();
        if ($pendingLeaves > 0) {
            $priorityItems->push([
                'id' => 'pending_leaves',
                'type' => 'pending_leave',
                'title' => 'Đơn nghỉ phép chờ duyệt',
                'description' => "{$pendingLeaves} đơn đang chờ bạn xử lý",
                'count' => $pendingLeaves,
            ]);
        }

        // Hợp đồng chờ phê duyệt
        $pendingContracts = Contract::where('status', 'PENDING_APPROVAL')->count();
        if ($pendingContracts > 0) {
            $priorityItems->push([
                'id' => 'pending_contracts',
                'type' => 'pending_contract',
                'title' => 'Hợp đồng chờ phê duyệt',
                'description' => "{$pendingContracts} hợp đồng đang chờ duyệt",
                'count' => $pendingContracts,
            ]);
        }

        // Nhân viên chưa có SI number (chưa đủ hồ sơ BHXH)
        $incompleteProfiles = Employee::where('status', 'ACTIVE')
            ->whereNull('si_number')
            ->count();
        if ($incompleteProfiles > 0) {
            $priorityItems->push([
                'id' => 'incomplete_profiles',
                'type' => 'incomplete_profile',
                'title' => 'Hồ sơ chưa hoàn thiện',
                'description' => "{$incompleteProfiles} nhân viên chưa có mã BHXH",
                'count' => $incompleteProfiles,
            ]);
        }

        // 3. Department Statistics for Chart (chỉ departments cấp cao nhất - parent_id = NULL)
        // Đếm cả nhân viên của tất cả departments con-cháu-chắt

        // Lấy tất cả root departments
        $rootDepartments = Department::whereNull('parent_id')->get();

        $departmentStats = $rootDepartments->map(function($rootDept) {
            // Lấy tất cả department IDs trong cây con (bao gồm cả root)
            $descendantIds = $this->getAllDescendantDepartmentIds($rootDept->id);

            // Đếm nhân viên có primary assignment trong bất kỳ department nào trong cây
            $count = Employee::where('employees.status', 'ACTIVE')
                ->join('employee_assignments', 'employees.id', '=', 'employee_assignments.employee_id')
                ->where('employee_assignments.status', 'ACTIVE')
                ->where('employee_assignments.is_primary', true)
                ->whereIn('employee_assignments.department_id', $descendantIds)
                ->distinct()
                ->count('employees.id');

            return [
                'name' => $rootDept->name,
                'count' => $count
            ];
        })->sortByDesc('count')->values();

        // 4. Recent Activities (from activity_log table)
        $recentActivities = Activity::with('causer')
            ->whereIn('log_name', ['employee', 'contract', 'leave-request'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                // Map description to Vietnamese-friendly types
                $typeMap = [
                    'created' => 'employee_created',
                    'updated' => 'employee_updated',
                    'deleted' => 'employee_deleted',
                ];

                $type = 'default';
                $description = $activity->description;

                if ($activity->log_name === 'employee' && $activity->description === 'created') {
                    $type = 'employee_created';
                    $description = 'Thêm nhân viên mới';
                } elseif ($activity->log_name === 'contract' && $activity->description === 'created') {
                    $type = 'contract_created';
                    $description = 'Tạo hợp đồng mới';
                } elseif ($activity->log_name === 'leave-request' && str_contains($activity->description, 'approved')) {
                    $type = 'leave_approved';
                    $description = 'Phê duyệt đơn nghỉ phép';
                } elseif ($activity->log_name === 'leave-request' && str_contains($activity->description, 'rejected')) {
                    $type = 'leave_rejected';
                    $description = 'Từ chối đơn nghỉ phép';
                }

                return [
                    'id' => $activity->id,
                    'type' => $type,
                    'description' => $description,
                    'user' => $activity->causer->name ?? 'Hệ thống',
                    'created_at' => $activity->created_at,
                ];
            });

        // 5. Upcoming Events
        $upcomingEvents = collect();

        // Birthday events trong tháng này
        $birthdays = Employee::where('status', 'ACTIVE')
            ->whereNotNull('dob')
            ->whereRaw('MONTH(dob) = ?', [Carbon::now()->month])
            ->whereRaw('DAY(dob) >= ?', [Carbon::now()->day])
            ->orderByRaw('DAY(dob)')
            ->limit(5)
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => 'birthday_' . $employee->id,
                    'date' => Carbon::now()->year . '-' .
                        str_pad(Carbon::now()->month, 2, '0', STR_PAD_LEFT) . '-' .
                        str_pad(Carbon::parse($employee->dob)->day, 2, '0', STR_PAD_LEFT),
                    'title' => 'Sinh nhật',
                    'description' => $employee->full_name,
                    'type' => 'birthday'
                ];
            });

        // Contract expiry events trong 30 ngày tới
        $expiringContracts = Contract::with('employee')
            ->where('status', 'ACTIVE')
            ->whereBetween('end_date', [today(), today()->addDays(30)])
            ->orderBy('end_date')
            ->limit(5)
            ->get()
            ->map(function ($contract) {
                return [
                    'id' => 'contract_' . $contract->id,
                    'date' => $contract->end_date->format('Y-m-d'),
                    'title' => 'Hợp đồng hết hạn',
                    'description' => $contract->employee->full_name,
                    'type' => 'contract_expiry'
                ];
            });

        $upcomingEvents = $birthdays->concat($expiringContracts)
            ->sortBy('date')
            ->values()
            ->take(5);

        return Inertia::render('Home', [
            'stats' => $stats,
            'priorityItems' => $priorityItems->values(),
            'departmentStats' => $departmentStats,
            'recentActivities' => $recentActivities,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }

    /**
     * Lấy tất cả department IDs trong cây con (đệ quy)
     * Bao gồm cả department hiện tại và tất cả con-cháu-chắt
     */
    private function getAllDescendantDepartmentIds($departmentId)
    {
        $ids = [$departmentId];

        // Lấy tất cả departments con trực tiếp
        $children = Department::where('parent_id', $departmentId)->pluck('id');

        // Đệ quy cho mỗi department con
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getAllDescendantDepartmentIds($childId));
        }

        return $ids;
    }
}
