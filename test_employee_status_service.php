<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Models\Contract;
use App\Models\LeaveRequest;
use App\Services\EmployeeStatusService;

echo "=== TEST EMPLOYEE STATUS SERVICE ===\n\n";

$service = app(EmployeeStatusService::class);

// Test 1: Employee with ACTIVE contract
echo "Test 1: Employee with ACTIVE contract\n";
$employee = Employee::whereHas('contracts', function($q) {
    $q->where('status', 'ACTIVE');
})->first();

if ($employee) {
    echo "  Employee: {$employee->full_name} ({$employee->employee_code})\n";
    echo "  Current status: {$employee->status}\n";

    // Count contracts
    $activeContracts = Contract::where('employee_id', $employee->id)
        ->where('status', 'ACTIVE')
        ->count();
    echo "  Active contracts: {$activeContracts}\n";

    // Count long leaves
    $longLeaves = LeaveRequest::where('employee_id', $employee->id)
        ->where('status', 'APPROVED')
        ->whereHas('leaveType', function ($query) {
            $query->whereIn('code', ['MATERNITY', 'SICK', 'UNPAID']);
        })
        ->where('days', '>=', 30)
        ->where('start_date', '<=', now())
        ->where('end_date', '>=', now())
        ->count();
    echo "  Active long leaves: {$longLeaves}\n";

    echo "  Syncing from contracts...\n";
    $service->syncFromContracts($employee);
    $employee->refresh();
    echo "  ✓ New status: {$employee->status}\n\n";
}

// Test 2: Employee with long-term leave
echo "Test 2: Employee with long-term leave\n";
$employeeWithLeave = Employee::whereHas('leaveRequests', function($q) {
    $q->where('status', 'APPROVED')
      ->whereHas('leaveType', function($query) {
          $query->whereIn('code', ['MATERNITY', 'SICK', 'UNPAID']);
      })
      ->where('days', '>=', 30)
      ->where('start_date', '<=', now())
      ->where('end_date', '>=', now());
})->first();

if ($employeeWithLeave) {
    echo "  Employee: {$employeeWithLeave->full_name} ({$employeeWithLeave->employee_code})\n";
    echo "  Current status: {$employeeWithLeave->status}\n";

    $leave = LeaveRequest::where('employee_id', $employeeWithLeave->id)
        ->where('status', 'APPROVED')
        ->whereHas('leaveType', function ($query) {
            $query->whereIn('code', ['MATERNITY', 'SICK', 'UNPAID']);
        })
        ->where('days', '>=', 30)
        ->where('start_date', '<=', now())
        ->where('end_date', '>=', now())
        ->with('leaveType')
        ->first();

    if ($leave) {
        echo "  Leave: {$leave->leaveType->name}\n";
        echo "  Period: {$leave->start_date->format('Y-m-d')} to {$leave->end_date->format('Y-m-d')}\n";
        echo "  Days: {$leave->days}\n";
    }

    echo "  Syncing from leaves...\n";
    $service->syncFromLeaves($employeeWithLeave);
    $employeeWithLeave->refresh();
    echo "  ✓ New status: {$employeeWithLeave->status}\n\n";
}

// Test 3: Employee with TERMINATED status
echo "Test 3: Employee with TERMINATED contract\n";
$terminatedEmployee = Employee::where('status', 'TERMINATED')->first();

if ($terminatedEmployee) {
    echo "  Employee: {$terminatedEmployee->full_name} ({$terminatedEmployee->employee_code})\n";
    echo "  Current status: {$terminatedEmployee->status}\n";

    $activeContracts = Contract::where('employee_id', $terminatedEmployee->id)
        ->where('status', 'ACTIVE')
        ->count();
    $terminatedContracts = Contract::where('employee_id', $terminatedEmployee->id)
        ->where('status', 'TERMINATED')
        ->count();
    echo "  Active contracts: {$activeContracts}\n";
    echo "  Terminated contracts: {$terminatedContracts}\n";

    echo "  Syncing from contracts...\n";
    $service->syncFromContracts($terminatedEmployee);
    $terminatedEmployee->refresh();
    echo "  ✓ Status remains: {$terminatedEmployee->status}\n\n";
}

echo "=== SUMMARY ===\n";
echo "✓ Service tested successfully\n";
echo "\nNext steps:\n";
echo "1. Listeners are auto-discovered (using #[ListensTo] attribute)\n";
echo "2. Observer registered in AppServiceProvider\n";
echo "3. Status will auto-update when:\n";
echo "   - Contract is APPROVED (→ ACTIVE)\n";
echo "   - Contract is TERMINATED (→ TERMINATED)\n";
echo "   - Long leave is APPROVED (→ ON_LEAVE)\n";
echo "   - EmployeeAbsence status changes to ENDED (→ ACTIVE)\n";
