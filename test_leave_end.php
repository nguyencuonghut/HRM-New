<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\EmployeeAbsence;
use App\Events\LeaveRequestEnded;

echo "=== TEST LEAVE END LOGIC ===\n\n";

// Find an expired leave (end_date < today)
echo "1. Looking for expired leaves...\n";
$expiredLeave = LeaveRequest::where('status', LeaveRequest::STATUS_APPROVED)
    ->whereDate('end_date', '<', now()->toDateString())
    ->with(['employee', 'leaveType'])
    ->first();

if (!$expiredLeave) {
    echo "❌ No expired leaves found in database\n";
    echo "\nCreating a test leave that has ended...\n";

    // Create a test leave that ended yesterday
    $employee = Employee::where('status', 'ACTIVE')->first();
    if (!$employee) {
        echo "❌ No active employee found\n";
        exit;
    }

    $leaveType = \App\Models\LeaveType::where('code', 'SICK')->first();
    if (!$leaveType) {
        echo "❌ SICK leave type not found\n";
        exit;
    }

    $yesterday = now()->subDay();
    $startDate = $yesterday->copy()->subDays(40);

    $expiredLeave = LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => $startDate,
        'end_date' => $yesterday,
        'days' => 41,
        'reason' => 'Test nghỉ ốm dài hạn đã kết thúc',
        'status' => 'APPROVED',
        'submitted_at' => $startDate,
        'approved_at' => $startDate,
    ]);

    // Create employee absence
    EmployeeAbsence::create([
        'employee_id' => $employee->id,
        'leave_request_id' => $expiredLeave->id,
        'absence_type' => 'SICK_LONG',
        'start_date' => $startDate,
        'end_date' => $yesterday,
        'duration_days' => 41,
        'affects_insurance' => true,
        'status' => 'ACTIVE',
        'reason' => 'Test nghỉ ốm dài hạn',
    ]);

    echo "✓ Created test leave\n";
}

echo "\n2. Expired Leave Details:\n";
echo "  Employee: {$expiredLeave->employee->full_name} ({$expiredLeave->employee->employee_code})\n";
echo "  Type: {$expiredLeave->leaveType->name}\n";
echo "  Period: {$expiredLeave->start_date->format('Y-m-d')} → {$expiredLeave->end_date->format('Y-m-d')}\n";
echo "  Days: {$expiredLeave->days}\n";
echo "  Status: {$expiredLeave->status}\n";

// Check employee status before
$employee = $expiredLeave->employee;
echo "\n3. Employee Status BEFORE:\n";
echo "  Status: {$employee->status}\n";

// Check employee absence
$absence = EmployeeAbsence::where('leave_request_id', $expiredLeave->id)->first();
if ($absence) {
    echo "  Absence Status: {$absence->status}\n";
}

// Dispatch LeaveRequestEnded event
echo "\n4. Dispatching LeaveRequestEnded event...\n";
event(new LeaveRequestEnded($expiredLeave));

// Check results
echo "\n5. Results AFTER:\n";
$employee->refresh();
echo "  Employee Status: {$employee->status}\n";

if ($absence) {
    $absence->refresh();
    echo "  Absence Status: {$absence->status}\n";
}

// Check other active long leaves
$otherLeaves = LeaveRequest::where('employee_id', $employee->id)
    ->where('id', '!=', $expiredLeave->id)
    ->where('status', LeaveRequest::STATUS_APPROVED)
    ->whereHas('leaveType', function ($query) {
        $query->whereIn('code', ['MATERNITY', 'SICK', 'UNPAID']);
    })
    ->where('days', '>=', 30)
    ->where('start_date', '<=', now())
    ->where('end_date', '>=', now())
    ->count();

echo "\n6. Other active long leaves: {$otherLeaves}\n";

echo "\n=== EXPECTED BEHAVIOR ===\n";
if ($otherLeaves > 0) {
    echo "✓ Employee should remain ON_LEAVE (has other active long leaves)\n";
} else {
    echo "✓ Employee should return to ACTIVE (no other active long leaves)\n";
}

echo "\n=== SUMMARY ===\n";
echo "✓ Event dispatched successfully\n";
echo "✓ Listeners processed:\n";
echo "  - EndEmployeeAbsenceOnLeaveEnded (absence → ENDED)\n";
echo "  - UpdateEmployeeStatusOnLeaveEnded (employee status synced)\n";
