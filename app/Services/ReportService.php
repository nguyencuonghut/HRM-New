<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Report Service
 * Core service for all HRM reports
 * Provides reusable methods for as-of-date logic, date calculations, and common queries
 */
class ReportService
{
    /**
     * Get active assignment for an employee as of a specific date
     *
     * Assignment is considered effective at as_of_date if:
     * - status = ACTIVE
     * - start_date <= as_of_date OR start_date IS NULL
     * - end_date >= as_of_date OR end_date IS NULL
     * - Prefer is_primary = true
     *
     * @param int $employeeId
     * @param string|Carbon $asOfDate
     * @return EmployeeAssignment|null
     */
    public function getActiveAssignmentAsOf(int $employeeId, $asOfDate): ?EmployeeAssignment
    {
        $date = $asOfDate instanceof Carbon ? $asOfDate : Carbon::parse($asOfDate);

        return EmployeeAssignment::where('employee_id', $employeeId)
            ->where('status', 'ACTIVE')
            ->where(function ($q) use ($date) {
                $q->where('start_date', '<=', $date)
                    ->orWhereNull('start_date');
            })
            ->where(function ($q) use ($date) {
                $q->where('end_date', '>=', $date)
                    ->orWhereNull('end_date');
            })
            ->orderBy('is_primary', 'desc')
            ->orderBy('start_date', 'desc')
            ->first();
    }

    /**
     * Get all active employees with their assignments as of a specific date
     *
     * @param string|Carbon $asOfDate
     * @param array $filters Optional filters (department_id, position_id, etc.)
     * @return Collection
     */
    public function 
    getEmployeesWithAssignmentsAsOf($asOfDate, array $filters = []): Collection
    {
        $date = $asOfDate instanceof Carbon ? $asOfDate : Carbon::parse($asOfDate);

        $query = Employee::query()
            ->where('status', 'ACTIVE')
            ->with(['assignments' => function ($q) use ($date) {
                $q->where('status', 'ACTIVE')
                    ->where(function ($query) use ($date) {
                        $query->where('start_date', '<=', $date)
                            ->orWhereNull('start_date');
                    })
                    ->where(function ($query) use ($date) {
                        $query->where('end_date', '>=', $date)
                            ->orWhereNull('end_date');
                    })
                    ->orderBy('is_primary', 'desc')
                    ->orderBy('start_date', 'desc');
            }]);

        // Apply filters
        if (isset($filters['department_id'])) {
            $query->whereHas('assignments', function ($q) use ($date, $filters) {
                $q->where('status', 'ACTIVE')
                    ->where('department_id', $filters['department_id'])
                    ->where(function ($query) use ($date) {
                        $query->where('start_date', '<=', $date)
                            ->orWhereNull('start_date');
                    })
                    ->where(function ($query) use ($date) {
                        $query->where('end_date', '>=', $date)
                            ->orWhereNull('end_date');
                    });
            });
        }

        if (isset($filters['position_id'])) {
            $query->whereHas('assignments', function ($q) use ($date, $filters) {
                $q->where('status', 'ACTIVE')
                    ->where('position_id', $filters['position_id'])
                    ->where(function ($query) use ($date) {
                        $query->where('start_date', '<=', $date)
                            ->orWhereNull('start_date');
                    })
                    ->where(function ($query) use ($date) {
                        $query->where('end_date', '>=', $date)
                            ->orWhereNull('end_date');
                    });
            });
        }

        return $query->get();
    }

    /**
     * Count employees by department as of a specific date
     *
     * @param string|Carbon $asOfDate
     * @return Collection [department_id => count]
     */
    public function countEmployeesByDepartmentAsOf($asOfDate): Collection
    {
        $date = $asOfDate instanceof Carbon ? $asOfDate : Carbon::parse($asOfDate);

        $employees = $this->getEmployeesWithAssignmentsAsOf($date);

        $counts = [];
        foreach ($employees as $employee) {
            $assignment = $employee->assignments->first();
            if ($assignment && $assignment->department_id) {
                $deptId = $assignment->department_id;
                $counts[$deptId] = ($counts[$deptId] ?? 0) + 1;
            }
        }

        return collect($counts);
    }

    /**
     * Count employees by position as of a specific date
     *
     * @param string|Carbon $asOfDate
     * @return Collection [position_id => count]
     */
    public function countEmployeesByPositionAsOf($asOfDate): Collection
    {
        $date = $asOfDate instanceof Carbon ? $asOfDate : Carbon::parse($asOfDate);

        $employees = $this->getEmployeesWithAssignmentsAsOf($date);

        $counts = [];
        foreach ($employees as $employee) {
            $assignment = $employee->assignments->first();
            if ($assignment && $assignment->position_id) {
                $posId = $assignment->position_id;
                $counts[$posId] = ($counts[$posId] ?? 0) + 1;
            }
        }

        return collect($counts);
    }

    /**
     * Get employees who joined between two dates
     *
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @return Collection
     */
    public function getNewHiresBetween($startDate, $endDate): Collection
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        return Employee::whereBetween('hire_date', [$start, $end])
            ->with(['assignments' => function ($q) use ($end) {
                $q->where('status', 'ACTIVE')
                    ->where(function ($query) use ($end) {
                        $query->where('start_date', '<=', $end)
                            ->orWhereNull('start_date');
                    })
                    ->orderBy('is_primary', 'desc')
                    ->orderBy('start_date', 'desc');
            }])
            ->orderBy('hire_date', 'desc')
            ->get();
    }

    /**
     * Get employees who left between two dates
     *
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @return Collection
     */
    public function getTerminationsBetween($startDate, $endDate): Collection
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        return Employee::whereHas('employments', function ($q) use ($start, $end) {
                $q->whereNotNull('end_date')
                    ->whereBetween('end_date', [$start, $end])
                    ->whereIn('end_reason', ['RESIGN', 'TERMINATION', 'CONTRACT_END', 'LAYOFF', 'RETIREMENT']);
            })
            ->with([
                'assignments' => function ($q) {
                    $q->orderBy('end_date', 'desc')
                        ->orderBy('is_primary', 'desc');
                },
                'employments' => function ($q) use ($start, $end) {
                    $q->whereNotNull('end_date')
                        ->whereBetween('end_date', [$start, $end])
                        ->orderBy('end_date', 'desc');
                }
            ])
            ->get()
            ->sortByDesc(function ($employee) {
                return $employee->employments->first()?->end_date;
            });
    }

    /**
     * Get employees with assignment changes between two dates
     * (Department or Position transfers)
     *
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @return Collection
     */
    public function getTransfersBetween($startDate, $endDate): Collection
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        // Get assignments that started in the period
        $newAssignments = EmployeeAssignment::whereBetween('start_date', [$start, $end])
            ->where('status', 'ACTIVE')
            ->with(['employee', 'department', 'position'])
            ->orderBy('start_date', 'desc')
            ->get();

        // Filter out first assignments (new hires)
        $transfers = $newAssignments->filter(function ($assignment) {
            // Check if employee has older assignments
            $olderAssignments = EmployeeAssignment::where('employee_id', $assignment->employee_id)
                ->where('id', '!=', $assignment->id)
                ->where('start_date', '<', $assignment->start_date)
                ->count();

            return $olderAssignments > 0;
        });

        return $transfers->values();
    }

    /**
     * Calculate date range for common periods
     *
     * @param string $period 'this_month', 'last_month', 'this_quarter', 'this_year', etc.
     * @return array ['start' => Carbon, 'end' => Carbon]
     */
    public function getDateRangeForPeriod(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'yesterday' => [
                'start' => $now->copy()->subDay()->startOfDay(),
                'end' => $now->copy()->subDay()->endOfDay(),
            ],
            'this_week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'last_week' => [
                'start' => $now->copy()->subWeek()->startOfWeek(),
                'end' => $now->copy()->subWeek()->endOfWeek(),
            ],
            'this_month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'last_month' => [
                'start' => $now->copy()->subMonth()->startOfMonth(),
                'end' => $now->copy()->subMonth()->endOfMonth(),
            ],
            'this_quarter' => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
            ],
            'last_quarter' => [
                'start' => $now->copy()->subQuarter()->startOfQuarter(),
                'end' => $now->copy()->subQuarter()->endOfQuarter(),
            ],
            'this_year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            'last_year' => [
                'start' => $now->copy()->subYear()->startOfYear(),
                'end' => $now->copy()->subYear()->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
        };
    }

    /**
     * Format export metadata
     *
     * @param string $reportName
     * @param array $filters
     * @return array
     */
    public function getExportMetadata(string $reportName, array $filters = []): array
    {
        return [
            'report_name' => $reportName,
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'generated_by' => auth()->user()->name ?? 'System',
            'filters' => $filters,
        ];
    }

    /**
     * Apply common filters to employee query
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function applyEmployeeFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('company_email', 'like', "%{$search}%")
                    ->orWhere('personal_email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
