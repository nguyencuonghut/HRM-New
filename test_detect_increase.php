<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\InsuranceCalculationService;
use App\Models\Employee;

echo "=== Test Detect INCREASE cho Employee Bùi Thị Nết ===\n\n";

$employee = Employee::where('full_name', 'LIKE', '%Nết%')->first();
if (!$employee) {
    echo "❌ Employee not found\n";
    exit;
}

echo "✓ Employee: {$employee->full_name} ({$employee->employee_code})\n";
echo "  Status: {$employee->status}\n";
echo "  Hire Date: " . ($employee->hire_date ? $employee->hire_date->format('Y-m-d') : 'null') . "\n\n";

// Check contracts
$contracts = $employee->contracts()->where('status', 'ACTIVE')->get();
echo "Active Contracts: {$contracts->count()}\n";
foreach ($contracts as $contract) {
    echo "  - {$contract->contract_number}\n";
    echo "    Start: {$contract->start_date->format('Y-m-d')}\n";
    echo "    End: {$contract->end_date->format('Y-m-d')}\n";
    echo "    Insurance Salary: " . number_format($contract->insurance_salary) . "\n";
}

// Check insurance participations
$participations = $employee->insuranceParticipations()->where('status', 'ACTIVE')->get();
echo "\nActive Insurance Participations: {$participations->count()}\n";

// Test calculate for October 2025
echo "\n=== Testing Calculate Oct 2025 ===\n";
$service = app(InsuranceCalculationService::class);
$changes = $service->calculateMonthlyChanges(2025, 10);

echo "INCREASE: {$changes['increase']->count()}\n";
echo "DECREASE: {$changes['decrease']->count()}\n";
echo "ADJUST: {$changes['adjust']->count()}\n\n";

// Find employee in INCREASE
$found = $changes['increase']->first(function($change) use ($employee) {
    return $change['employee']->id === $employee->id;
});

if ($found) {
    echo "✅ Found employee in INCREASE!\n";
    echo "   Reason: {$found['auto_reason']}\n";
    echo "   Salary: " . number_format($found['insurance_salary']) . "\n";
    echo "   Effective: {$found['effective_date']->format('Y-m-d')}\n";
    echo "   Notes: {$found['system_notes']}\n";
} else {
    echo "❌ Employee NOT found in INCREASE\n\n";
    echo "Debugging info:\n";
    echo "All INCREASE records:\n";
    foreach ($changes['increase'] as $change) {
        echo "  - {$change['employee']->full_name} ({$change['employee']->employee_code})\n";
        echo "    Reason: {$change['auto_reason']}\n";
        echo "    Effective: {$change['effective_date']->format('Y-m-d')}\n";
    }
}

echo "\n=== Test Complete ===\n";
