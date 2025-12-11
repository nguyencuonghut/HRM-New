<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Services\LeaveApprovalService;

echo "=== Test Auto-Sync Employee Absence ===\n\n";

$employee = Employee::where('employee_code', '2142')->first();
$leaveType = LeaveType::where('code', 'SICK')->first();

if (!$employee || !$leaveType) {
    echo "❌ Employee or LeaveType not found\n";
    exit;
}

echo "✓ Employee: {$employee->full_name} ({$employee->employee_code})\n";
echo "✓ Leave Type: {$leaveType->name}\n\n";

// Create leave request
$leave = LeaveRequest::create([
    'employee_id' => $employee->id,
    'leave_type_id' => $leaveType->id,
    'start_date' => '2025-12-15',
    'end_date' => '2026-01-25',
    'days' => 42,
    'reason' => 'Test nghỉ ốm dài hạn',
    'status' => 'DRAFT',
]);

echo "✓ Created Leave Request: {$leave->id}\n";
echo "  - Start: {$leave->start_date->format('Y-m-d')}\n";
echo "  - End: {$leave->end_date->format('Y-m-d')}\n";
echo "  - Days: {$leave->days}\n\n";

// Auto-approve as Admin (simulate admin login)
echo "Processing auto-approve...\n";
$service = app(LeaveApprovalService::class);
$service->autoApprove($leave);

$leave->refresh();
echo "✓ Leave Status: {$leave->status}\n\n";

// Check employee_absences
echo "Checking employee_absences...\n";
$absence = \App\Models\EmployeeAbsence::where('leave_request_id', $leave->id)->first();

if ($absence) {
    echo "✅ Employee Absence Created Successfully!\n";
    echo "  - ID: {$absence->id}\n";
    echo "  - Type: {$absence->absence_type}\n";
    echo "  - Duration: {$absence->duration_days} days\n";
    echo "  - Status: {$absence->status}\n";
    echo "  - Start: {$absence->start_date->format('Y-m-d')}\n";
    echo "  - End: {$absence->end_date->format('Y-m-d')}\n";
} else {
    echo "❌ No employee absence created!\n";
    echo "   This should have been created automatically.\n";
}

echo "\n=== Test Complete ===\n";
