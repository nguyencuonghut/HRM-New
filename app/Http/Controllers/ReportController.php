<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeStatus;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Services\ProfileCompletionService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    protected ReportService $reportService;
    protected ProfileCompletionService $profileCompletionService;

    public function __construct(
        ReportService $reportService,
        ProfileCompletionService $profileCompletionService
    ) {
        $this->reportService = $reportService;
        $this->profileCompletionService = $profileCompletionService;
    }

    /**
     * Display Reports Hub - catalog of all reports
     */
    public function hub(): Response
    {
        return Inertia::render('Reports/Index', [
            'reportCategories' => $this->getReportCategories(),
        ]);
    }

    /**
     * RPT-001: Headcount Snapshot
     * Total headcount by department/position at a specific date
     */
    public function headcount(Request $request): Response
    {
        $asOfDate = $request->input('as_of_date', Carbon::today()->format('Y-m-d'));

        // Get all employees with assignments as of date
        $employees = $this->reportService->getEmployeesWithAssignmentsAsOf($asOfDate);
        $employeeAssignments = $employees->map(function($e) {
            $a = $e->assignments->first();
            return $a ? [
                'employee_id' => $e->id,
                'department_id' => $a->department_id,
            ] : null;
        })->filter()->values();

        // Helper: get all department IDs that have at least one employee (direct or descendant)
        $allDeptIdsWithEmp = $employeeAssignments->pluck('department_id')->unique()->all();
        $descendantMap = [];
        $getAllDescendants = null;
        $getAllDescendants = function($deptId) use (&$descendantMap, &$getAllDescendants) {
            if (isset($descendantMap[$deptId])) return $descendantMap[$deptId];
            $descendants = Department::where('parent_id', $deptId)->pluck('id')->all();
            $all = [];
            foreach ($descendants as $childId) {
                $all[] = $childId;
                $all = array_merge($all, $getAllDescendants($childId));
            }
            $descendantMap[$deptId] = $all;
            return $all;
        };

        // Filter root departments: only those (or their descendants) with employees
        $rootDepartments = Department::whereNull('parent_id')
            ->orderByRaw('CASE WHEN order_index IS NULL THEN 1 ELSE 0 END, order_index ASC')
            ->orderBy('name')
            ->get(['id','parent_id','type','name','code','is_active']);

        $rootDepartments = $rootDepartments->filter(function($dept) use ($allDeptIdsWithEmp, $getAllDescendants) {
            $ids = array_merge([$dept->id], $getAllDescendants($dept->id));
            return count(array_intersect($ids, $allDeptIdsWithEmp)) > 0;
        })->values();

        // Recursive build tree, only keep children with employees
        $buildTree = function($dept) use (&$buildTree, $allDeptIdsWithEmp, $getAllDescendants, $employeeAssignments) {
            $ids = array_merge([$dept->id], $getAllDescendants($dept->id));
            if (count(array_intersect($ids, $allDeptIdsWithEmp)) === 0) return null;
            $children = Department::where('parent_id', $dept->id)
                ->orderByRaw('CASE WHEN order_index IS NULL THEN 1 ELSE 0 END, order_index ASC')
                ->orderBy('name')
                ->get(['id','parent_id','type','name','code','is_active']);
            $childNodes = $children->map(fn($c) => $buildTree($c))->filter()->values();
            // Count employees in this dept and all descendants
            $count = collect($ids)->sum(fn($id) => $employeeAssignments->where('department_id', $id)->count());
            return [
                'key' => $dept->id,
                'label' => $dept->name,
                'data' => [
                    'id' => $dept->id,
                    'type' => $dept->type,
                    'code' => $dept->code,
                    'is_active' => (bool)$dept->is_active,
                    'headcount' => $count,
                ],
                'children' => $childNodes,
                'leaf' => $childNodes->isEmpty(),
            ];
        };
        $departmentTree = $rootDepartments->map(fn($dept) => $buildTree($dept))->filter()->values()->toArray();

        // Total headcount = sum of all employees in filtered tree
        $totalHeadcount = $employeeAssignments->count();


        // byPosition: only count employees in filtered tree
        // First, ensure we have position_id in each assignment (add to $employeeAssignments above if missing)
        $employeeAssignmentsWithPosition = $employees->map(function($e) {
            $a = $e->assignments->first();
            return $a ? [
                'employee_id' => $e->id,
                'department_id' => $a->department_id,
                'employment_type' => $a->employment_type,
                'position_id' => $a->position_id,
            ] : null;
        })->filter()->values();

        $byPosition = $employeeAssignmentsWithPosition->groupBy('position_id')->map->count();
        $positions = Position::whereIn('id', $byPosition->keys())->get()->keyBy('id');
        $positionBreakdown = $byPosition->map(function ($count, $posId) use ($positions) {
            $position = $positions->get($posId);
            $title = $position && !empty(trim($position->title)) ? $position->title : 'Chưa xác định';
            return [
                'position_id' => $posId,
                'position_name' => $title,
                'count' => $count,
            ];
        })->values();

        // byContractType: count employees by contract_type of ACTIVE contract at as_of_date
        $asOf = Carbon::parse($asOfDate);

        // Assumption: $employees already includes contracts relationship.
        // If not, you MUST eager load it in getEmployeesWithAssignmentsAsOf or here:
        // $employees->load('contracts');

        $contractTypeCounts = $employees->map(function ($e) use ($asOf) {
            // Find contract effective at as_of_date
            $activeContract = $e->contracts
                ->filter(function ($c) use ($asOf) {
                    $startOk = $c->start_date && Carbon::parse($c->start_date)->lte($asOf);
                    $endOk = !$c->end_date || Carbon::parse($c->end_date)->gte($asOf);
                    return $startOk && $endOk;
                })
                ->sortByDesc('start_date')
                ->first();

            // Return enum value or special bucket
            return $activeContract?->contract_type ?: '__NO_ACTIVE_CONTRACT__';
        })
        ->groupBy(fn ($t) => $t)
        ->map->count()
        ->toArray();

        // Map enum value -> Vietnamese label (using ContractType enum)
        $byEmploymentType = []; // keep prop name to avoid FE changes
        foreach ($contractTypeCounts as $typeValue => $count) {
            if ($typeValue === '__NO_ACTIVE_CONTRACT__') {
                $label = 'Chưa có HĐ hiệu lực';
            } else {
                try {
                    $label = ContractType::from($typeValue)->label();
                } catch (\ValueError $e) {
                    // In case DB has unexpected value
                    $label = $typeValue;
                }
            }

            $byEmploymentType[$label] = $count;
        }

        return Inertia::render('Reports/Headcount', [
            'asOfDate' => $asOfDate,
            'totalHeadcount' => $totalHeadcount,
            'departmentTree' => $departmentTree,
            'byPosition' => $positionBreakdown,
            'byEmploymentType' => $byEmploymentType,
            'filters' => $request->only(['as_of_date']),
        ]);
    }

    /**
     * RPT-010: Employee List
     * Detailed employee list with filters
     */
    public function employeeList(Request $request): Response
    {
        $query = Employee::query()->with(['assignments' => function ($q) {
            $q->where('status', 'ACTIVE')
                ->where('is_primary', true)
                ->with(['department', 'position']);
        }]);

        // Apply filters
        $query = $this->reportService->applyEmployeeFilters($query, $request->all());

        if ($request->filled('department_id')) {
            $query->whereHas('assignments', function ($q) use ($request) {
                $q->where('department_id', $request->department_id)
                    ->where('status', 'ACTIVE');
            });
        }

        if ($request->filled('position_id')) {
            $positionIds = is_array($request->position_id) ? $request->position_id : [$request->position_id];
            $query->whereHas('assignments', function ($q) use ($positionIds) {
                $q->whereIn('position_id', $positionIds)
                    ->where('status', 'ACTIVE');
            });
        }

        // Pagination
        $employees = $query->orderBy('employee_code')->paginate($request->input('per_page', 20));

        return Inertia::render('Reports/EmployeeList', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'positions' => Position::with('department:id,name')->orderBy('title')->get(['id', 'title', 'department_id']),
            'employeeStatuses' => collect(EmployeeStatus::cases())->map(fn($status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'severity' => $status->severity(),
            ])->all(),
            'filters' => $request->all(),
        ]);
    }

    /**
     * RPT-011: Data Completeness
     * Profile completion analysis
     */
    public function dataCompleteness(Request $request): Response
    {
        $query = Employee::query()->where('status', 'ACTIVE');

        if ($request->filled('department_id')) {
            $query->whereHas('assignments', function ($q) use ($request) {
                $q->where('department_id', $request->department_id)
                    ->where('status', 'ACTIVE');
            });
        }

        $employees = $query->with(['assignments' => function ($q) {
            $q->where('status', 'ACTIVE')
                ->where('is_primary', true)
                ->with('department');
        }])->get();

        // Calculate completion for each employee
        $completionData = $employees->map(function ($employee) {
            $result = $this->profileCompletionService->calculateScore($employee);
            $assignment = $employee->assignments->first();

            return [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => $employee->full_name,
                'department_name' => $assignment?->department?->name,
                'position_name' => $assignment?->position?->title,
                'completion_percentage' => $result['score'],
                'missing_count' => count($result['missing']),
                'missing_items' => collect($result['missing'])->pluck('item')->toArray(),
                'details' => $result['details'],
            ];
        })->sortBy('completion_percentage');

        // Summary statistics
        $avgCompletion = $completionData->avg('completion_percentage');
        $completeProfiles = $completionData->where('completion_percentage', 100)->count();
        $incompleteProfiles = $completionData->where('completion_percentage', '<', 100)->count();

        return Inertia::render('Reports/DataCompleteness', [
            'employees' => $completionData->values(),
            'summary' => [
                'total_employees' => $employees->count(),
                'average_completion' => round($avgCompletion, 2),
                'complete_100' => $completeProfiles,
                'incomplete' => $incompleteProfiles,
            ],
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['department_id']),
        ]);
    }

    /**
     * RPT-002: Employee Movement
     * New hires, terminations, transfers in date range
     */
    public function employeeMovement(Request $request): Response
    {
        $dateRange = $this->getDateRange($request);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        // Get movements
        $newHires = $this->reportService->getNewHiresBetween($startDate, $endDate);
        $terminations = $this->reportService->getTerminationsBetween($startDate, $endDate);
        $transfers = $this->reportService->getTransfersBetween($startDate, $endDate);

        // Apply department filter if provided (including child departments)
        if ($request->filled('department_id')) {
            $departmentIds = $this->getAllDepartmentIds($request->department_id);

            $newHires = $newHires->filter(function ($employee) use ($departmentIds) {
                return $employee->assignments->whereIn('department_id', $departmentIds)->where('status', 'ACTIVE')->isNotEmpty();
            });

            $terminations = $terminations->filter(function ($employee) use ($departmentIds) {
                return $employee->assignments->whereIn('department_id', $departmentIds)->isNotEmpty();
            });

            $transfers = $transfers->filter(function ($assignment) use ($departmentIds) {
                return in_array($assignment->department_id, $departmentIds);
            });
        }

        return Inertia::render('Reports/EmployeeMovement', [
            'newHires' => $newHires->map(fn($e) => $this->formatEmployeeForMovement($e))->values()->all(),
            'terminations' => $terminations->map(fn($e) => $this->formatEmployeeForMovement($e))->values()->all(),
            'transfers' => $transfers->map(fn($t) => $this->formatTransfer($t))->values()->all(),
            'summary' => [
                'new_hires' => $newHires->count(),
                'terminations' => $terminations->count(),
                'transfers' => $transfers->count(),
                'net_change' => $newHires->count() - $terminations->count(),
            ],
            'dateRange' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['start_date', 'end_date', 'period', 'department_id']),
        ]);
    }

    /**
     * RPT-020: Contracts by Status
     * Contract distribution by status
     */
    public function contractsStatus(Request $request): Response
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', Carbon::today()->format('Y-m-d')));

        // Get contracts that exist at the as_of_date
        // A contract exists at as_of_date if:
        // - It has started (start_date <= as_of_date)
        $contracts = Contract::with(['employee', 'template', 'department'])
            ->whereDate('start_date', '<=', $asOfDate)
            ->get();

        // Filter by status if provided
        if ($request->filled('status')) {
            $contracts = $contracts->where('status', $request->status);
        }

        // Filter by department if provided
        if ($request->filled('department_id')) {
            $contracts = $contracts->filter(function ($contract) use ($request) {
                return $contract->department_id === $request->department_id;
            });
        }

        $total = $contracts->count();
        $byStatus = $contracts->groupBy('status')->map->count();

        // Calculate KPIs
        $summary = [
            'total_contracts' => $total,
            'active_contracts' => $byStatus->get('ACTIVE', 0),
            'expiring_soon' => $contracts->filter(function ($contract) use ($asOfDate) {
                if ($contract->status !== 'ACTIVE' || !$contract->end_date) return false;
                $endDate = Carbon::parse($contract->end_date);
                $daysUntil = $asOfDate->diffInDays($endDate, false);
                return $daysUntil > 0 && $daysUntil <= 30;
            })->count(),
            'expired_contracts' => $byStatus->get('EXPIRED', 0),
        ];

        // Status breakdown for chart
        $statusBreakdown = $byStatus->map(function ($count, $status) use ($total) {
            return [
                'status' => $status,
                'status_label' => ContractStatus::from($status)->label(),
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        })->values();

        return Inertia::render('Reports/ContractsStatus', [
            'contracts' => $contracts->map(fn($c) => $this->formatContractForStatus($c, $asOfDate))->values()->all(),
            'summary' => $summary,
            'statusBreakdown' => $statusBreakdown->all(),
            'statusOptions' => ContractStatus::toArray(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['as_of_date', 'status', 'department_id']),
        ]);
    }

    /**
     * RPT-021: Contracts Expiring
     * Contracts expiring in date range
     */
    public function contractsExpiring(Request $request): Response
    {
        $fromDate = Carbon::parse($request->input('from_date', Carbon::today()->format('Y-m-d')));
        $toDate = Carbon::parse($request->input('to_date', Carbon::today()->addDays(30)->format('Y-m-d')));
        $warningDays = $request->input('warning_days', 30);

        $contracts = Contract::with(['employee', 'template'])
            ->where('status', 'ACTIVE')
            ->whereBetween('end_date', [$fromDate, $toDate])
            ->orderBy('end_date')
            ->get();

        $contractsData = $contracts->map(function ($contract) use ($fromDate) {
            $endDate = Carbon::parse($contract->end_date);
            $daysUntilExpiry = $fromDate->diffInDays($endDate, false);

            return [
                'id' => $contract->id,
                'employee_code' => $contract->employee->employee_code,
                'employee_name' => $contract->employee->full_name,
                'contract_type' => ContractType::from($contract->contract_type)->label(),
                'start_date' => $contract->start_date,
                'end_date' => $contract->end_date,
                'days_until_expiry' => (int) round($daysUntilExpiry),
                'urgency' => $daysUntilExpiry <= 15 ? 'critical' : ($daysUntilExpiry <= 30 ? 'warning' : 'normal'),
            ];
        });

        return Inertia::render('Reports/ContractsExpiring', [
            'contracts' => $contractsData,
            'summary' => [
                'total' => $contracts->count(),
                'critical' => $contractsData->where('urgency', 'critical')->count(),
                'warning' => $contractsData->where('urgency', 'warning')->count(),
            ],
            'dateRange' => [
                'from' => $fromDate->format('Y-m-d'),
                'to' => $toDate->format('Y-m-d'),
            ],
            'warningDays' => $warningDays,
            'filters' => $request->only(['from_date', 'to_date', 'warning_days']),
        ]);
    }

    /**
     * RPT-022: Contract Approval SLA
     * Contract approval turnaround time analysis
     */
    public function contractApprovalSla(Request $request): Response
    {
        $dateRange = $this->getDateRange($request);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        // Only include contracts that went through approval workflow (exclude backfilled contracts)
        $contracts = Contract::with(['employee', 'template', 'approvals.approver'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('approvals') // Only contracts with at least one approval record
            ->get();

        $approvalData = $contracts->map(function ($contract) {
            $approvalTime = null;
            $status = 'pending';

            if ($contract->approvals->isNotEmpty()) {
                $finalApproval = $contract->approvals->sortByDesc('approved_at')->first();
                if ($finalApproval && $finalApproval->approved_at) {
                    $createdAt = Carbon::parse($contract->created_at);
                    $approvedAt = Carbon::parse($finalApproval->approved_at);
                    $approvalTime = $createdAt->diffInHours($approvedAt);
                    $status = 'approved';
                }
            }

            return [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'employee_code' => $contract->employee->employee_code,
                'employee_name' => $contract->employee->full_name,
                'contract_type' => ContractType::from($contract->contract_type)->label(),
                'created_at' => $contract->created_at->format('Y-m-d'),
                'approval_time_hours' => $approvalTime,
                'approval_time_days' => $approvalTime ? round($approvalTime / 24, 1) : null,
                'status' => $status,
                'status_label' => $status === 'approved' ? 'Đã duyệt' : 'Đang chờ',
            ];
        });

        $approved = $approvalData->where('status', 'approved');
        $avgApprovalTime = $approved->avg('approval_time_hours');
        $slaCompliance = $approved->where('approval_time_hours', '<=', 48)->count();
        $slaCompliancePercent = $approved->count() > 0 ? ($slaCompliance / $approved->count() * 100) : 0;

        return Inertia::render('Reports/ContractApprovalSla', [
            'contracts' => $approvalData->values(),
            'summary' => [
                'total' => $contracts->count(),
                'approved' => $approved->count(),
                'pending' => $approvalData->where('status', 'pending')->count(),
                'avg_approval_hours' => $avgApprovalTime ? round($avgApprovalTime, 2) : null,
                'avg_approval_days' => $avgApprovalTime ? round($avgApprovalTime / 24, 2) : null,
                'sla_compliance_percent' => round($slaCompliancePercent, 2),
            ],
            'dateRange' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'filters' => $request->only(['start_date', 'end_date', 'period']),
        ]);
    }

    /**
     * RPT-030: Monthly Leave Summary
     * Leave summary for a specific month
     */
    public function leaveMonthly(Request $request): Response
    {
        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $leaveRequests = LeaveRequest::with(['employee.assignments.department', 'leaveType'])
            ->where('status', 'APPROVED')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($query) use ($startDate, $endDate) {
                        $query->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();

        $byLeaveType = $leaveRequests->groupBy('leave_type_id')->map(function ($requests, $typeId) {
            $leaveType = $requests->first()->leaveType;
            return [
                'type_id' => $typeId,
                'type_name' => $leaveType->name ?? 'Unknown',
                'request_count' => $requests->count(),
                'total_days' => $requests->sum('days'),
            ];
        })->values();

        $byDepartment = $leaveRequests->groupBy(function ($request) {
            return $request->employee->assignments->first()?->department_id;
        })->map(function ($requests, $deptId) {
            $dept = $requests->first()->employee->assignments->first()?->department;
            return [
                'department_id' => $deptId,
                'department_name' => $dept->name ?? 'Unknown',
                'request_count' => $requests->count(),
                'total_days' => $requests->sum('days'),
            ];
        })->values();

        return Inertia::render('Reports/LeaveMonthly', [
            'year' => $year,
            'month' => $month,
            'summary' => [
                'total_requests' => $leaveRequests->count(),
                'total_days' => $leaveRequests->sum('days'),
            ],
            'byLeaveType' => $byLeaveType,
            'byDepartment' => $byDepartment,
            'filters' => $request->only(['year', 'month']),
        ]);
    }

    /**
     * RPT-031: Leave Balances
     * Leave balance report for employees
     */
    public function leaveBalances(Request $request): Response
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', Carbon::today()->format('Y-m-d')));
        $asOfYear = $asOfDate->year;

        $query = Employee::query()
            ->where('status', 'ACTIVE')
            ->with([
                'leaveBalances' => function ($q) use ($asOfYear) {
                    $q->where('year', '<=', $asOfYear);
                },
                'leaveBalances.leaveType',
                'assignments' => function ($q) {
                    $q->where('status', 'ACTIVE')
                        ->where('is_primary', true)
                        ->with('department');
                },
            ]);

        if ($request->filled('department_id')) {
            $query->whereHas('assignments', function ($q) use ($request) {
                $q->where('department_id', $request->department_id)
                    ->where('status', 'ACTIVE');
            });
        }

        $employees = $query->get();

        $balanceData = [];
        foreach ($employees as $employee) {
            foreach ($employee->leaveBalances as $balance) {
                $expiryDate = Carbon::create($balance->year, 12, 31);
                $daysUntilExpiry = Carbon::today()->diffInDays($expiryDate, false);

                $balanceData[] = [
                    'employee_id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'employee_name' => $employee->full_name,
                    'department' => $employee->assignments->first()?->department?->name,
                    'leave_type' => $balance->leaveType->name,
                    'year' => $balance->year,
                    'allowance' => $balance->total_days,
                    'used' => $balance->used_days,
                    'remaining' => $balance->remaining_days,
                    'expiry_date' => $expiryDate->format('Y-m-d'),
                    'days_until_expiry' => $daysUntilExpiry,
                    'is_expiring_soon' => $daysUntilExpiry <= 30 && $daysUntilExpiry >= 0,
                ];
            }
        }

        return Inertia::render('Reports/LeaveBalances', [
            'balances' => $balanceData,
            'asOfDate' => $asOfDate->format('Y-m-d'),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['as_of_date', 'department_id']),
        ]);
    }

    /**
     * Export report to Excel
     */
    public function export(Request $request, string $reportCode)
    {
        // TODO: Implement Excel export
        // Use Laravel Excel or similar package
        // Include metadata from $this->reportService->getExportMetadata()

        return response()->json([
            'message' => 'Export functionality coming soon',
            'report_code' => $reportCode,
        ]);
    }

    // ========== Helper Methods ==========

    private function getReportCategories(): array
    {
        return [
            [
                'name' => 'Quản trị & Nhân sự',
                'icon' => 'pi-users',
                'reports' => [
                    ['code' => 'headcount', 'name' => 'Biên chế nhân sự', 'description' => 'Tổng số nhân viên theo bộ phận/vị trí tại thời điểm cụ thể'],
                    ['code' => 'employee-list', 'name' => 'Danh sách nhân viên', 'description' => 'Danh sách chi tiết thông tin nhân viên với bộ lọc đa dạng'],
                    ['code' => 'data-completeness', 'name' => 'Độ hoàn thiện hồ sơ', 'description' => 'Đánh giá mức độ đầy đủ thông tin hồ sơ nhân viên'],
                    ['code' => 'employee-movement', 'name' => 'Biến động nhân sự', 'description' => 'Theo dõi tuyển mới, nghỉ việc và điều chuyển nhân sự'],
                ],
            ],
            [
                'name' => 'Hợp đồng',
                'icon' => 'pi-file-edit',
                'reports' => [
                    ['code' => 'contracts-status', 'name' => 'Tình trạng hợp đồng', 'description' => 'Phân bổ và thống kê hợp đồng theo trạng thái'],
                    ['code' => 'contracts-expiring', 'name' => 'Hợp đồng sắp hết hạn', 'description' => 'Danh sách hợp đồng cần gia hạn hoặc ký mới'],
                    ['code' => 'contract-approval-sla', 'name' => 'Thời gian phê duyệt', 'description' => 'Phân tích hiệu suất phê duyệt hợp đồng'],
                ],
            ],
            [
                'name' => 'Nghỉ phép',
                'icon' => 'pi-calendar',
                'reports' => [
                    ['code' => 'leave-monthly', 'name' => 'Tổng hợp nghỉ phép', 'description' => 'Báo cáo tổng hợp nghỉ phép theo tháng'],
                    ['code' => 'leave-balances', 'name' => 'Số dư phép', 'description' => 'Theo dõi số ngày phép còn lại của nhân viên'],
                ],
            ],
            [
                'name' => 'Kiểm soát & Vận hành',
                'icon' => 'pi-shield',
                'reports' => [
                    ['code' => 'activity-log', 'name' => 'Nhật ký hoạt động', 'description' => 'Theo dõi lịch sử thao tác và thay đổi dữ liệu', 'external' => '/activity-logs'],
                    ['code' => 'backup-health', 'name' => 'Trạng thái sao lưu', 'description' => 'Kiểm tra tình trạng sao lưu dữ liệu hệ thống', 'external' => '/backup'],
                ],
            ],
        ];
    }

    /**
     * Get all descendant department IDs (including the parent itself)
     */
    private function getAllDepartmentIds($departmentId): array
    {
        $ids = [$departmentId];
        $children = Department::where('parent_id', $departmentId)->pluck('id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getAllDepartmentIds($childId));
        }

        return $ids;
    }

    private function getDateRange(Request $request): array
    {
        // Handle period-based date range
        if ($request->filled('period') && !$request->filled('year')) {
            return $this->reportService->getDateRangeForPeriod($request->period);
        }

        // Handle year/month/quarter parameters
        if ($request->filled('year')) {
            $year = (int) $request->year;

            if ($request->filled('month')) {
                // Specific month of year
                $month = (int) $request->month;
                return [
                    'start' => Carbon::create($year, $month, 1)->startOfMonth(),
                    'end' => Carbon::create($year, $month, 1)->endOfMonth(),
                ];
            }

            if ($request->filled('quarter')) {
                // Specific quarter of year
                $quarter = (int) $request->quarter;
                $startMonth = ($quarter - 1) * 3 + 1;
                return [
                    'start' => Carbon::create($year, $startMonth, 1)->startOfMonth(),
                    'end' => Carbon::create($year, $startMonth, 1)->addMonths(2)->endOfMonth(),
                ];
            }

            // Whole year
            return [
                'start' => Carbon::create($year, 1, 1)->startOfYear(),
                'end' => Carbon::create($year, 12, 31)->endOfYear(),
            ];
        }

        // Handle explicit start_date and end_date
        // If no filters provided, use a very wide range to get all data (All)
        if (!$request->filled('start_date') && !$request->filled('end_date') &&
            !$request->filled('period') && !$request->filled('year')) {
            return [
                'start' => Carbon::create(2000, 1, 1),
                'end' => Carbon::create(2100, 12, 31),
            ];
        }

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

        return ['start' => $startDate, 'end' => $endDate];
    }

    private function formatEmployeeForMovement($employee): array
    {
        $assignment = $employee->assignments->first();
        $employment = $employee->employments->first();

        return [
            'id' => $employee->id,
            'code' => $employee->employee_code,
            'full_name' => $employee->full_name,
            'email' => $employee->company_email,
            'hire_date' => $employee->hire_date,
            'termination_date' => $employment?->end_date,
            'termination_reason' => $employment?->end_reason,
            'department' => $assignment?->department?->name,
            'position' => $assignment?->position?->title,
        ];
    }

    private function formatTransfer($assignment): array
    {
        return [
            'employee_id' => $assignment->employee_id,
            'employee_code' => $assignment->employee->employee_code,
            'employee_name' => $assignment->employee->full_name,
            'department' => $assignment->department->name ?? 'N/A',
            'position' => $assignment->position->title ?? 'N/A',
            'start_date' => $assignment->start_date,
            'is_primary' => $assignment->is_primary,
        ];
    }

    private function formatContract($contract): array
    {
        return [
            'id' => $contract->id,
            'employee_code' => $contract->employee->employee_code,
            'employee_name' => $contract->employee->full_name,
            'contract_type' => $contract->template->name ?? 'N/A',
            'status' => $contract->status,
            'start_date' => $contract->start_date,
            'end_date' => $contract->end_date,
            'created_at' => $contract->created_at->format('Y-m-d'),
        ];
    }

    private function formatContractForStatus($contract, $asOfDate): array
    {
        // Calculate days until expiry
        $daysUntilExpiry = null;
        if ($contract->end_date && in_array($contract->status, ['ACTIVE', 'SUSPENDED'])) {
            $endDate = Carbon::parse($contract->end_date);
            $diff = $asOfDate->diffInDays($endDate, false);
            // Only show positive values (future dates), round to integer
            $daysUntilExpiry = $diff > 0 ? (int) round($diff) : 0;
        }

        return [
            'id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'employee_code' => $contract->employee->employee_code,
            'employee_name' => $contract->employee->full_name,
            'department_name' => $contract->snapshot_department_name ?? ($contract->department->name ?? 'N/A'),
            'contract_type' => ContractType::from($contract->contract_type)->label(),
            'status' => $contract->status,
            'status_label' => ContractStatus::from($contract->status)->label(),
            'start_date' => $contract->start_date,
            'end_date' => $contract->end_date,
            'days_until_expiry' => $daysUntilExpiry,
        ];
    }
}
