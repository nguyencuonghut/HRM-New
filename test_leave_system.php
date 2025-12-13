<?php

/**
 * Test script for Leave Management System
 *
 * Test scenarios:
 * 1. Annual leave pro-rata calculation
 * 2. Personal paid leave (event-based)
 * 3. Maternity leave calculation
 * 4. Sick leave validation
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Services\LeaveCalculationService;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(LeaveCalculationService::class);

echo "\n=== TEST LEAVE CALCULATION SYSTEM ===\n\n";

// Test 1: Check Annual Leave Balance for "Bùi Thị Nết"
echo "1. ANNUAL LEAVE - Pro-rata Calculation\n";
echo "----------------------------------------\n";

$employee = Employee::where('full_name', 'LIKE', '%Bùi Thị Nết%')->first();
if ($employee) {
    echo "Employee: {$employee->full_name} (Code: {$employee->employee_code})\n";

    $employment = $employee->currentEmployment();
    if ($employment) {
        echo "Employment Start: {$employment->start_date}\n";

        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->with('leaveType')
            ->first();

        if ($balance) {
            echo "Leave Type: {$balance->leaveType->name}\n";
            echo "Total Days: {$balance->total_days}\n";
            echo "Used Days: {$balance->used_days}\n";
            echo "Remaining: {$balance->remaining_days}\n";

            // Calculate expected days
            $startDate = \Carbon\Carbon::parse($employment->start_date);
            $endOfYear = \Carbon\Carbon::parse("{$balance->year}-12-31");
            $workingMonths = min($startDate->diffInMonths($endOfYear) + 1, 12);
            echo "Expected Days (working {$workingMonths} months): {$workingMonths} days\n";

            if ($balance->total_days == $workingMonths) {
                echo "✅ Pro-rata calculation CORRECT!\n";
            } else {
                echo "❌ Pro-rata calculation INCORRECT! Expected {$workingMonths}, got {$balance->total_days}\n";
            }
        } else {
            echo "❌ No leave balance found!\n";
        }
    } else {
        echo "❌ No employment record found!\n";
    }
} else {
    echo "❌ Employee not found!\n";
}

echo "\n";

// Test 2: Personal Paid Leave Reasons
echo "2. PERSONAL PAID LEAVE - Event-based Calculation\n";
echo "-----------------------------------------------\n";

$reasons = $service->getPersonalPaidLeaveReasons();
foreach ($reasons as $reason) {
    echo "{$reason['label']}: {$reason['days']} ngày\n";
}

echo "\nTest calculation:\n";
$marriageDays = $service->calculatePersonalPaidLeaveDays('MARRIAGE');
echo "MARRIAGE: {$marriageDays} ngày (Expected: 3)\n";
if ($marriageDays === 3) {
    echo "✅ Personal leave calculation CORRECT!\n";
} else {
    echo "❌ Personal leave calculation INCORRECT!\n";
}

echo "\n";

// Test 3: Maternity Leave Calculation
echo "3. MATERNITY LEAVE - Calculation\n";
echo "--------------------------------\n";

$conditions = [
    ['twins_count' => 1, 'is_caesarean' => false, 'children_under_36_months' => 0],
    ['twins_count' => 2, 'is_caesarean' => false, 'children_under_36_months' => 0],
    ['twins_count' => 1, 'is_caesarean' => true, 'children_under_36_months' => 0],
    ['twins_count' => 1, 'is_caesarean' => false, 'children_under_36_months' => 1],
    ['twins_count' => 2, 'is_caesarean' => true, 'children_under_36_months' => 1],
];

foreach ($conditions as $condition) {
    $days = $service->calculateMaternityLeaveDays($condition);
    $desc = "Twins: {$condition['twins_count']}, Caesarean: " . ($condition['is_caesarean'] ? 'Yes' : 'No') .
            ", Children <36m: {$condition['children_under_36_months']}";
    echo "{$desc} => {$days} ngày\n";
}

echo "\nExpected:\n";
echo "- Base (1 child, natural, no young children): 180 days\n";
echo "- Twins (+30): 210 days\n";
echo "- Caesarean (+15): 195 days\n";
echo "- Young children (+30): 210 days\n";
echo "- All conditions (+30+15+30): 255 days\n";

$expected = [180, 210, 195, 210, 255];
$allCorrect = true;
foreach ($conditions as $i => $condition) {
    $calculated = $service->calculateMaternityLeaveDays($condition);
    if ($calculated !== $expected[$i]) {
        echo "❌ Test " . ($i+1) . " FAILED: Expected {$expected[$i]}, got {$calculated}\n";
        $allCorrect = false;
    }
}

if ($allCorrect) {
    echo "✅ Maternity leave calculation CORRECT!\n";
}

echo "\n";

// Test 4: Sick Leave Validation
echo "4. SICK LEAVE - Validation\n";
echo "--------------------------\n";

$sickValidation1 = $service->validateSickLeave(['medical_certificate_path' => null]);
echo "Without medical certificate:\n";
echo "Valid: " . ($sickValidation1['valid'] ? 'Yes' : 'No') . "\n";
echo "Message: {$sickValidation1['message']}\n";

$sickValidation2 = $service->validateSickLeave(['medical_certificate_path' => 'some-file.pdf']);
echo "\nWith medical certificate:\n";
echo "Valid: " . ($sickValidation2['valid'] ? 'Yes' : 'No') . "\n";
echo "Message: {$sickValidation2['message']}\n";

echo "\n";

// Test 5: Leave Types Configuration
echo "5. LEAVE TYPES - Vietnamese Labor Law 2019\n";
echo "-------------------------------------------\n";

$leaveTypes = LeaveType::active()->ordered()->get();
foreach ($leaveTypes as $type) {
    echo "{$type->name} ({$type->code}):\n";
    echo "  Days per year: {$type->days_per_year}\n";
    echo "  Is paid: " . ($type->is_paid ? 'Yes' : 'No') . "\n";
    echo "  Description: {$type->description}\n";
    echo "\n";
}

echo "\n=== TESTS COMPLETE ===\n\n";
