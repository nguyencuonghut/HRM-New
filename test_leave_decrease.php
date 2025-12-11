<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Models\EmployeeAbsence;
use App\Services\InsuranceCalculationService;

echo "=== Checking Leave Request for Tạ Văn Toại ===\n\n";

$employee = Employee::where('full_name', 'LIKE', '%Toại%')->first();
if (!$employee) {
    echo "❌ Employee not found\n";
    exit;
}

echo "✓ Employee: {$employee->full_name} ({$employee->employee_code})\n";
echo "  Status: {$employee->status}\n\n";

// Check leave requests
$leaves = $employee->leaveRequests()->where('status', 'APPROVED')->get();
echo "Approved Leave Requests: {$leaves->count()}\n";
foreach ($leaves as $leave) {
    echo "  - ID: {$leave->id}\n";
    echo "    Start: {$leave->start_date->format('Y-m-d')}\n";
    echo "    End: {$leave->end_date->format('Y-m-d')}\n";
    echo "    Days: {$leave->days}\n";
    echo "    Type: {$leave->leaveType->code} ({$leave->leaveType->name})\n";
    echo "    Status: {$leave->status}\n\n";
}

// Check employee absences
$absences = EmployeeAbsence::where('employee_id', $employee->id)->get();
echo "Employee Absences: {$absences->count()}\n";
foreach ($absences as $absence) {
    echo "  - ID: {$absence->id}\n";
    echo "    Type: {$absence->absence_type}\n";
    echo "    Start: {$absence->start_date->format('Y-m-d')}\n";
    echo "    End: " . ($absence->end_date ? $absence->end_date->format('Y-m-d') : 'null') . "\n";
    echo "    Duration: {$absence->duration_days} days\n";
    echo "    Status: {$absence->status}\n";
    echo "    Leave Request ID: " . ($absence->leave_request_id ?? 'null') . "\n\n";
}

// Test calculate for Nov and Dec 2025
echo "\n=== Testing Calculate Nov 2025 ===\n";
$service = app(InsuranceCalculationService::class);
$changesNov = $service->calculateMonthlyChanges(2025, 11);

echo "INCREASE: {$changesNov['increase']->count()}\n";
echo "DECREASE: {$changesNov['decrease']->count()}\n";
echo "ADJUST: {$changesNov['adjust']->count()}\n\n";

$foundNov = $changesNov['decrease']->first(function($change) use ($employee) {
    return $change['employee']->id === $employee->id;
});

if ($foundNov) {
    echo "✅ Found in Nov DECREASE!\n";
    echo "   Reason: {$foundNov['auto_reason']}\n";
    echo "   Effective: {$foundNov['effective_date']->format('Y-m-d')}\n";
} else {
    echo "❌ NOT found in Nov DECREASE\n";
}

echo "\n=== Testing Calculate Dec 2025 ===\n";
$changesDec = $service->calculateMonthlyChanges(2025, 12);

echo "INCREASE: {$changesDec['increase']->count()}\n";
echo "DECREASE: {$changesDec['decrease']->count()}\n";
echo "ADJUST: {$changesDec['adjust']->count()}\n\n";

$foundDec = $changesDec['decrease']->first(function($change) use ($employee) {
    return $change['employee']->id === $employee->id;
});

if ($foundDec) {
    echo "✅ Found in Dec DECREASE!\n";
    echo "   Reason: {$foundDec['auto_reason']}\n";
    echo "   Effective: {$foundDec['effective_date']->format('Y-m-d')}\n";
} else {
    echo "❌ NOT found in Dec DECREASE\n";
}

echo "\n=== All DECREASE records for Nov ===\n";
foreach ($changesNov['decrease'] as $change) {
    echo "  - {$change['employee']->full_name}\n";
    echo "    Reason: {$change['auto_reason']}\n";
    echo "    Effective: {$change['effective_date']->format('Y-m-d')}\n";
}

echo "\n=== Test Complete ===\n";
