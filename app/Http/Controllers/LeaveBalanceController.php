<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveBalanceController extends Controller
{
    /**
     * Display a listing of all employees' leave balances (for HR/Admin)
     */
    public function index(Request $request)
    {
        $year = $request->input('year', now()->year);
        $leaveTypeId = $request->input('leave_type_id');
        $departmentId = $request->input('department_id');
        $search = $request->input('search');
        $employeeId = $request->input('employee_id');

        // Build base query for detailed view
        $balancesQuery = LeaveBalance::query()->where('year', $year);

        // Filter by employee (for detail dialog)
        if ($employeeId) {
            $balancesQuery->where('employee_id', $employeeId);
        }

        // Filter by leave type
        if ($leaveTypeId) {
            $balancesQuery->where('leave_type_id', $leaveTypeId);
        }

        // Filter by department
        if ($departmentId) {
            $balancesQuery->whereHas('employee.primaryAssignment', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        // Search by employee name or code
        if ($search) {
            $balancesQuery->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        // Get detailed balances with relationships
        $balances = $balancesQuery->orderBy('employee_id')
            ->orderBy('leave_type_id')
            ->paginate(50);

        // Load relationships from the collection inside paginator - load separately to avoid nested issues
        $balances->getCollection()->load(['employee', 'leaveType']);

        // Build summary data using raw query with department info
        $summaryBaseQuery = \DB::table('leave_balances as lb')
            ->join('employees as e', 'lb.employee_id', '=', 'e.id')
            ->leftJoin('employee_assignments as ea', function ($join) {
                $join->on('e.id', '=', 'ea.employee_id')
                    ->where('ea.is_primary', true)
                    ->where('ea.status', 'ACTIVE');
            })
            ->leftJoin('departments as d', 'ea.department_id', '=', 'd.id')
            ->select(
                'lb.employee_id',
                'e.employee_code',
                'e.full_name',
                'd.name as department_name',
                \DB::raw('SUM(lb.total_days) as total_all'),
                \DB::raw('SUM(lb.used_days) as used_all'),
                \DB::raw('SUM(lb.remaining_days) as remaining_all')
            )
            ->where('lb.year', $year);

        // Apply filters to summary
        if ($departmentId) {
            $summaryBaseQuery->where('ea.department_id', $departmentId);
        }

        if ($search) {
            $summaryBaseQuery->where(function ($q) use ($search) {
                $q->where('e.full_name', 'like', "%{$search}%")
                    ->orWhere('e.employee_code', 'like', "%{$search}%");
            });
        }

        $summaryData = $summaryBaseQuery->groupBy('lb.employee_id', 'e.employee_code', 'e.full_name', 'd.name')->get();

        // Transform summary data
        $summaryCollection = $summaryData->map(function ($item) {
            return (object) [
                'employee_id' => $item->employee_id,
                'employee' => (object) [
                    'id' => $item->employee_id,
                    'employee_code' => $item->employee_code,
                    'full_name' => $item->full_name,
                    'department_name' => $item->department_name,
                ],
                'total_all' => (float) $item->total_all,
                'used_all' => (float) $item->used_all,
                'remaining_all' => (float) $item->remaining_all,
            ];
        });

        // Manual pagination for summary
        $summaryPage = $request->input('summary_page', 1);
        $perPage = 50;
        $total = $summaryCollection->count();
        $summaryPaginated = $summaryCollection->slice(($summaryPage - 1) * $perPage, $perPage)->values();

        $summary = new \Illuminate\Pagination\LengthAwarePaginator(
            $summaryPaginated,
            $total,
            $perPage,
            $summaryPage,
            ['path' => $request->url(), 'pageName' => 'summary_page']
        );

        // Get filter options
        $leaveTypes = LeaveType::where('is_active', true)
            ->orderBy('order_index')
            ->get(['id', 'name', 'code', 'color']);

        $departments = \App\Models\Department::orderBy('name')->get(['id', 'name']);

        return Inertia::render('LeaveBalances/Index', [
            'balances' => $balances,
            'summary' => $summary,
            'leaveTypes' => $leaveTypes,
            'departments' => $departments,
            'filters' => [
                'year' => $year,
                'leave_type_id' => $leaveTypeId,
                'department_id' => $departmentId,
                'search' => $search,
            ],
            'years' => range(now()->year - 2, now()->year + 1), // Last 2 years + current + next
        ]);
    }

    /**
     * Display leave balances for a specific employee (for Profile page)
     */
    public function show(Employee $employee, Request $request)
    {
        $year = $request->input('year', now()->year);

        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get()
            ->map(function ($balance) {
                return [
                    'id' => $balance->id,
                    'leave_type' => [
                        'id' => $balance->leaveType->id,
                        'name' => $balance->leaveType->name,
                        'code' => $balance->leaveType->code,
                        'color' => $balance->leaveType->color,
                        'days_per_year' => $balance->leaveType->days_per_year,
                    ],
                    'year' => $balance->year,
                    'total_days' => (float) $balance->total_days,
                    'used_days' => (float) $balance->used_days,
                    'remaining_days' => (float) $balance->remaining_days,
                    'carried_forward' => (float) $balance->carried_forward,
                    'usage_percentage' => $balance->total_days > 0
                        ? round(($balance->used_days / $balance->total_days) * 100, 1)
                        : 0,
                ];
            });

        // Get recent leave requests for this year
        $recentLeaves = $employee->leaveRequests()
            ->whereYear('start_date', $year)
            ->whereIn('status', ['PENDING', 'APPROVED'])
            ->with('leaveType')
            ->orderBy('start_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'leave_type' => $leave->leaveType->name,
                    'start_date' => $leave->start_date->format('d/m/Y'),
                    'end_date' => $leave->end_date->format('d/m/Y'),
                    'days' => (float) $leave->days,
                    'status' => $leave->status,
                ];
            });

        return response()->json([
            'balances' => $balances,
            'recent_leaves' => $recentLeaves,
            'year' => $year,
        ]);
    }

    /**
     * Initialize balances for a specific year (Admin only)
     */
    public function initialize(Request $request)
    {
        $year = $request->input('year', now()->year);

        $result = \Artisan::call('leave:initialize-balances', ['year' => $year]);

        return redirect()->back()->with([
            'message' => 'Leave balances initialized successfully',
            'type' => 'success'
        ]);
    }
}
