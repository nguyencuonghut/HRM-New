<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LeaveRequest;
use App\Events\LeaveRequestApproved;

echo "=== Sync Employee Absences for Approved Leave Requests ===\n\n";

// Find approved leave requests that should have absences but don't
$approvedLeaves = LeaveRequest::where('status', 'APPROVED')
    ->whereHas('leaveType', function($query) {
        $query->whereIn('code', ['MATERNITY', 'SICK', 'UNPAID']);
    })
    ->where(function($query) {
        $query->where('days', '>=', 30)
            ->orWhereHas('leaveType', function($q) {
                $q->where('code', 'MATERNITY');
            });
    })
    ->doesntHave('employeeAbsence')
    ->with('employee', 'leaveType')
    ->get();

echo "Found {$approvedLeaves->count()} approved leaves without absences\n\n";

foreach ($approvedLeaves as $leave) {
    echo "Processing: {$leave->employee->full_name}\n";
    echo "  Leave: {$leave->leaveType->name}\n";
    echo "  Period: {$leave->start_date->format('Y-m-d')} to {$leave->end_date->format('Y-m-d')}\n";
    echo "  Days: {$leave->days}\n";

    // Dispatch event to trigger listener
    event(new LeaveRequestApproved($leave, null));

    echo "  ✓ Event dispatched\n\n";
}

// Verify
$absences = \App\Models\EmployeeAbsence::whereIn('leave_request_id', $approvedLeaves->pluck('id'))
    ->count();

echo "\n=== Result ===\n";
echo "Employee Absences created: {$absences}\n";
echo "Expected: {$approvedLeaves->count()}\n";

if ($absences === $approvedLeaves->count()) {
    echo "✅ All absences created successfully!\n";
} else {
    echo "⚠ Some absences may not have been created\n";
}
