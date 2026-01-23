<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\CompanyHoliday;
use App\Models\EmployeeAnnualReview;
use App\Models\EmployeeBenefitPayout;
use App\Models\EmployeeRewardDiscipline;
use App\Models\EmployeeAssignment;
use App\Models\Department;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    /**
     * Show calendar page (Admin/Manager view)
     */
    public function index()
    {
        $user = auth()->user();

        // Check permissions
        if ($user->hasRole(['Super Admin', 'HR Admin', 'Payroll Admin'])) {
            $viewType = 'company-wide'; // All employees
        } elseif ($user->hasRole('Department Manager')) {
            $viewType = 'department'; // Own department only
        } elseif ($user->hasRole('Director')) {
            $viewType = 'executive'; // Critical events only
        } else {
            abort(403, 'Unauthorized access to calendar');
        }

        return Inertia::render('Calendar/Index', [
            'viewType' => $viewType,
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'positions' => Position::select('id', 'title')->orderBy('title')->get(),
            'employees' => Employee::select('id', 'employee_code', 'full_name')
                ->where('status', 'ACTIVE')
                ->orderBy('full_name')
                ->get()
                ->map(fn($e) => [
                    'id' => $e->id,
                    'label' => "{$e->employee_code} - {$e->full_name}",
                ]),
        ]);
    }

    /**
     * Get calendar events (Admin/Manager)
     * Supports filters: departments, positions, employees, event types
     */
    public function events(Request $request)
    {
        $user = auth()->user();
        $start = Carbon::parse($request->start);
        $end = Carbon::parse($request->end);

        // Get employees based on role
        $employees = $this->getAuthorizedEmployees($user, $request);

        $events = [];

        // 1. Leave events
        if ($request->get('filter_leaves', true)) {
            $events = array_merge($events, $this->getLeaveEvents($employees, $start, $end));
        }

        // 2. Contract events
        if ($request->get('filter_contracts', true)) {
            $events = array_merge($events, $this->getContractEvents($employees, $start, $end));
        }

        // 3. Birthdays
        if ($request->get('filter_birthdays', true)) {
            $events = array_merge($events, $this->getBirthdayEvents($employees, $start, $end));
        }

        // 4. Work anniversaries
        if ($request->get('filter_anniversaries', true)) {
            $events = array_merge($events, $this->getAnniversaryEvents($employees, $start, $end));
        }

        // 5. Company holidays
        if ($request->get('filter_holidays', true)) {
            $events = array_merge($events, $this->getCompanyHolidays($start, $end));
        }

        // 6. Performance reviews
        if ($request->get('filter_reviews', true)) {
            $events = array_merge($events, $this->getReviewEvents($employees, $start, $end));
        }

        // 7. Benefits
        if ($request->get('filter_benefits', true)) {
            $events = array_merge($events, $this->getBenefitEvents($employees, $start, $end));
        }

        // 8. Rewards/Disciplines
        if ($request->get('filter_rewards', true)) {
            $events = array_merge($events, $this->getRewardDisciplineEvents($employees, $start, $end));
        }

        return response()->json(['events' => $events]);
    }

    /**
     * Get authorized employees based on user role and filters
     */
    private function getAuthorizedEmployees($user, $request)
    {
        $query = Employee::query()->where('status', 'ACTIVE');

        // Role-based access control
        if ($user->hasRole(['Super Admin', 'HR Admin', 'Payroll Admin'])) {
            // Full access to all employees
        } elseif ($user->hasRole('Department Manager')) {
            // Only employees in departments they manage
            $managedDepartmentIds = EmployeeAssignment::where('employee_id', $user->employee->id)
                ->whereIn('position_type', ['HEAD', 'DEPUTY'])
                ->pluck('department_id');

            $query->whereHas('assignments', function ($q) use ($managedDepartmentIds) {
                $q->whereIn('department_id', $managedDepartmentIds);
            });
        } elseif ($user->hasRole('Director')) {
            // All employees (for executive view)
        } else {
            return collect(); // No access
        }

        // Apply filters
        if ($request->filled('departments')) {
            $departmentIds = is_array($request->departments)
                ? $request->departments
                : [$request->departments];

            $query->whereHas('assignments', function ($q) use ($departmentIds) {
                $q->whereIn('department_id', $departmentIds);
            });
        }

        if ($request->filled('positions')) {
            $positionIds = is_array($request->positions)
                ? $request->positions
                : [$request->positions];

            $query->whereHas('assignments', function ($q) use ($positionIds) {
                $q->whereIn('position_id', $positionIds);
            });
        }

        if ($request->filled('employees')) {
            $employeeIds = is_array($request->employees)
                ? $request->employees
                : [$request->employees];

            $query->whereIn('id', $employeeIds);
        }

        return $query->get();
    }

    /**
     * Get leave events for multiple employees
     */
    private function getLeaveEvents($employees, $start, $end)
    {
        $employeeIds = $employees->pluck('id');

        $leaves = LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'APPROVED')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                      ->orWhereBetween('end_date', [$start, $end])
                      ->orWhere(function ($q) use ($start, $end) {
                          $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                      });
            })
            ->with(['leaveType', 'employee'])
            ->get();

        return $leaves->map(function ($leave) {
            return [
                'id' => 'leave-' . $leave->id,
                'type' => 'leave',
                'title' => $leave->leaveType->name . ' - ' . $leave->employee->full_name,
                'start' => $leave->start_date->format('Y-m-d'),
                'end' => $leave->end_date->addDay()->format('Y-m-d'), // FullCalendar exclusive end
                'color' => $leave->leaveType->color,
                'allDay' => true,
                'extendedProps' => [
                    'employeeId' => $leave->employee->id,
                    'employeeCode' => $leave->employee->employee_code,
                    'employeeName' => $leave->employee->full_name,
                    'leaveType' => $leave->leaveType->code,
                    'days' => $leave->days,
                    'status' => $leave->status,
                    'reason' => $leave->reason,
                ],
            ];
        })->toArray();
    }

    /**
     * Get contract events (expiry warnings) for multiple employees
     */
    private function getContractEvents($employees, $start, $end)
    {
        $events = [];

        foreach ($employees as $employee) {
            $contract = Contract::where('employee_id', $employee->id)
                ->where('status', 'ACTIVE')
                ->whereNotNull('end_date')
                ->first();

            if (!$contract || !$contract->end_date) {
                continue;
            }

            $expiryDate = Carbon::parse($contract->end_date);
            $today = Carbon::today();

            // Warning 60 days before
            $warning60 = $expiryDate->copy()->subDays(60);
            if ($warning60->between($start, $end) && $today->lte($warning60)) {
                $events[] = [
                    'id' => 'contract-warning-60-' . $contract->id,
                    'type' => 'contract_expiry_warning',
                    'title' => '⚠️ HĐ hết hạn 60 ngày - ' . $employee->full_name,
                    'start' => $warning60->format('Y-m-d'),
                    'color' => '#f59e0b', // orange
                    'allDay' => true,
                    'extendedProps' => [
                        'employeeId' => $employee->id,
                        'employeeCode' => $employee->employee_code,
                        'employeeName' => $employee->full_name,
                        'contractNumber' => $contract->contract_number,
                        'contractType' => $contract->contract_type,
                        'expiryDate' => $expiryDate->format('Y-m-d'),
                        'daysUntilExpiry' => $today->diffInDays($expiryDate),
                    ],
                ];
            }

            // Urgent 15 days before
            $urgent15 = $expiryDate->copy()->subDays(15);
            if ($urgent15->between($start, $end) && $today->lte($urgent15)) {
                $events[] = [
                    'id' => 'contract-urgent-15-' . $contract->id,
                    'type' => 'contract_expiry_urgent',
                    'title' => '🚨 HĐ hết hạn 15 ngày - ' . $employee->full_name,
                    'start' => $urgent15->format('Y-m-d'),
                    'color' => '#ef4444', // red
                    'allDay' => true,
                    'extendedProps' => [
                        'employeeId' => $employee->id,
                        'employeeCode' => $employee->employee_code,
                        'employeeName' => $employee->full_name,
                        'contractNumber' => $contract->contract_number,
                        'contractType' => $contract->contract_type,
                        'expiryDate' => $expiryDate->format('Y-m-d'),
                        'daysUntilExpiry' => $today->diffInDays($expiryDate),
                    ],
                ];
            }
        }

        return $events;
    }

    /**
     * Get birthday events for multiple employees
     */
    private function getBirthdayEvents($employees, $start, $end)
    {
        $events = [];

        foreach ($employees as $employee) {
            if (!$employee->dob) {
                continue;
            }

            $dob = Carbon::parse($employee->dob);

            // Check each year in the range
            for ($year = $start->year; $year <= $end->year; $year++) {
                $birthday = Carbon::create($year, $dob->month, $dob->day);

                if ($birthday->between($start, $end)) {
                    $events[] = [
                        'id' => 'birthday-' . $employee->id . '-' . $year,
                        'type' => 'birthday',
                        'title' => '🎂 Sinh nhật - ' . $employee->full_name,
                        'start' => $birthday->format('Y-m-d'),
                        'color' => '#ec4899', // pink
                        'allDay' => true,
                        'extendedProps' => [
                            'employeeId' => $employee->id,
                            'employeeCode' => $employee->employee_code,
                            'employeeName' => $employee->full_name,
                            'age' => $year - $dob->year,
                            'recurring' => true,
                        ],
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * Get work anniversary events for multiple employees
     */
    private function getAnniversaryEvents($employees, $start, $end)
    {
        $events = [];

        foreach ($employees as $employee) {
            // Get first contract start date as hire date
            $firstContract = Contract::where('employee_id', $employee->id)
                ->orderBy('start_date', 'asc')
                ->first();

            if (!$firstContract) {
                continue;
            }

            $hireDate = Carbon::parse($firstContract->start_date);

            // Check each year in the range
            for ($year = $start->year; $year <= $end->year; $year++) {
                $anniversary = Carbon::create($year, $hireDate->month, $hireDate->day);

                if ($anniversary->between($start, $end) && $year > $hireDate->year) {
                    $yearsOfService = $year - $hireDate->year;

                    $events[] = [
                        'id' => 'anniversary-' . $employee->id . '-' . $year,
                        'type' => 'work_anniversary',
                        'title' => "🎉 {$yearsOfService} năm - {$employee->full_name}",
                        'start' => $anniversary->format('Y-m-d'),
                        'color' => '#8b5cf6', // purple
                        'allDay' => true,
                        'extendedProps' => [
                            'employeeId' => $employee->id,
                            'employeeCode' => $employee->employee_code,
                            'employeeName' => $employee->full_name,
                            'yearsOfService' => $yearsOfService,
                            'hireDate' => $hireDate->format('Y-m-d'),
                            'recurring' => true,
                        ],
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * Get company holidays
     */
    private function getCompanyHolidays($start, $end)
    {
        $holidays = CompanyHoliday::whereBetween('holiday_date', [$start, $end])
            ->orderBy('holiday_date')
            ->get();

        return $holidays->map(function ($holiday) {
            return [
                'id' => 'holiday-' . $holiday->id,
                'type' => 'company_holiday',
                'title' => '🎊 ' . $holiday->name,
                'start' => $holiday->holiday_date->format('Y-m-d'),
                'color' => '#dc2626', // red
                'allDay' => true,
                'display' => 'background', // Show as background event
                'extendedProps' => [
                    'isCompanyWide' => true,
                    'isRecurring' => $holiday->is_recurring,
                    'note' => $holiday->note,
                ],
            ];
        })->toArray();
    }

    /**
     * Get performance review events for multiple employees
     */
    private function getReviewEvents($employees, $start, $end)
    {
        $events = [];

        foreach ($employees as $employee) {
            // Review deadline (Dec 31 each year)
            for ($year = $start->year; $year <= $end->year; $year++) {
                $deadline = Carbon::create($year, 12, 31);

                if ($deadline->between($start, $end)) {
                    // Check if review already completed
                    $review = EmployeeAnnualReview::where('employee_id', $employee->id)
                        ->where('year', $year)
                        ->first();

                    if (!$review) {
                        // Reminder 15 days before deadline
                        $reminder = $deadline->copy()->subDays(15);
                        if ($reminder->between($start, $end)) {
                            $events[] = [
                                'id' => 'review-reminder-' . $employee->id . '-' . $year,
                                'type' => 'performance_review_reminder',
                                'title' => '📊 Đánh giá ' . $year . ' - ' . $employee->full_name,
                                'start' => $reminder->format('Y-m-d'),
                                'color' => '#f59e0b', // orange
                                'allDay' => true,
                                'extendedProps' => [
                                    'employeeId' => $employee->id,
                                    'employeeCode' => $employee->employee_code,
                                    'employeeName' => $employee->full_name,
                                    'year' => $year,
                                    'deadline' => $deadline->format('Y-m-d'),
                                ],
                            ];
                        }
                    }
                }
            }
        }

        return $events;
    }

    /**
     * Get benefit payout events for multiple employees
     */
    private function getBenefitEvents($employees, $start, $end)
    {
        $employeeIds = $employees->pluck('id');

        $payouts = EmployeeBenefitPayout::whereIn('employee_id', $employeeIds)
            ->whereBetween('paid_date', [$start, $end])
            ->with(['benefitType', 'employee'])
            ->get();

        return $payouts->map(function ($payout) {
            return [
                'id' => 'benefit-' . $payout->id,
                'type' => 'benefit_payout',
                'title' => '🎁 ' . $payout->benefitType->name . ' - ' . $payout->employee->full_name,
                'start' => $payout->paid_date->format('Y-m-d'),
                'color' => '#06b6d4', // cyan
                'allDay' => true,
                'extendedProps' => [
                    'employeeId' => $payout->employee->id,
                    'employeeCode' => $payout->employee->employee_code,
                    'employeeName' => $payout->employee->full_name,
                    'benefitType' => $payout->benefitType->code,
                    'amount' => $payout->amount,
                ],
            ];
        })->toArray();
    }

    /**
     * Get reward/discipline events for multiple employees
     */
    private function getRewardDisciplineEvents($employees, $start, $end)
    {
        $employeeIds = $employees->pluck('id');

        $records = EmployeeRewardDiscipline::whereIn('employee_id', $employeeIds)
            ->whereBetween('effective_date', [$start, $end])
            ->with('employee')
            ->get();

        return $records->map(function ($record) {
            $isReward = $record->type === 'REWARD';

            return [
                'id' => 'reward-discipline-' . $record->id,
                'type' => $isReward ? 'reward' : 'discipline',
                'title' => ($isReward ? '🏆 Khen thưởng' : '⚠️ Kỷ luật') . ' - ' . $record->employee->full_name,
                'start' => $record->effective_date->format('Y-m-d'),
                'color' => $isReward ? '#10b981' : '#ef4444', // green : red
                'allDay' => true,
                'extendedProps' => [
                    'employeeId' => $record->employee->id,
                    'employeeCode' => $record->employee->employee_code,
                    'employeeName' => $record->employee->full_name,
                    'category' => $record->category,
                    'amount' => $record->amount,
                    'decisionNo' => $record->decision_no,
                ],
            ];
        })->toArray();
    }

    /**
     * Get team summary statistics (for Department Manager)
     */
    public function teamSummary(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasRole('Department Manager')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get managed department IDs
        $managedDepartmentIds = EmployeeAssignment::where('employee_id', $user->employee->id)
            ->whereIn('position_type', ['HEAD', 'DEPUTY'])
            ->pluck('department_id');

        $teamEmployees = Employee::whereHas('assignments', function ($q) use ($managedDepartmentIds) {
            $q->whereIn('department_id', $managedDepartmentIds);
        })->where('status', 'ACTIVE')->get();

        $today = Carbon::today();
        $thisMonth = Carbon::now();

        // On leave today
        $onLeaveToday = LeaveRequest::whereIn('employee_id', $teamEmployees->pluck('id'))
            ->where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with(['employee', 'leaveType'])
            ->get();

        // Birthdays this month
        $birthdaysThisMonth = $teamEmployees->filter(function ($emp) use ($thisMonth) {
            return $emp->dob && Carbon::parse($emp->dob)->month === $thisMonth->month;
        });

        // Contracts expiring soon (60 days)
        $contractsExpiring = Contract::whereIn('employee_id', $teamEmployees->pluck('id'))
            ->where('status', 'ACTIVE')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$today, $today->copy()->addDays(60)])
            ->with('employee')
            ->get();

        // Team coverage
        $teamSize = $teamEmployees->count();
        $onLeaveCount = $onLeaveToday->count();
        $coveragePercent = $teamSize > 0 ? round((($teamSize - $onLeaveCount) / $teamSize) * 100) : 0;

        return response()->json([
            'onLeaveToday' => $onLeaveToday->map(fn($leave) => [
                'id' => $leave->employee->id,
                'name' => $leave->employee->full_name,
                'code' => $leave->employee->employee_code,
                'leaveType' => $leave->leaveType->name,
                'daysRemaining' => Carbon::today()->diffInDays(Carbon::parse($leave->end_date)),
            ]),
            'birthdaysThisMonth' => $birthdaysThisMonth->map(fn($emp) => [
                'id' => $emp->id,
                'name' => $emp->full_name,
                'code' => $emp->employee_code,
                'birthday' => Carbon::parse($emp->dob)->format('d/m'),
            ]),
            'contractsExpiring' => $contractsExpiring->map(fn($contract) => [
                'id' => $contract->employee->id,
                'name' => $contract->employee->full_name,
                'code' => $contract->employee->employee_code,
                'contractType' => $contract->contract_type,
                'expiryDate' => $contract->end_date->format('d/m/Y'),
                'daysUntilExpiry' => Carbon::today()->diffInDays($contract->end_date),
            ]),
            'teamCoverage' => [
                'teamSize' => $teamSize,
                'onLeave' => $onLeaveCount,
                'working' => $teamSize - $onLeaveCount,
                'coveragePercent' => $coveragePercent,
            ],
        ]);
    }
}
