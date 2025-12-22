<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Employee;
use App\Models\Contract;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use Illuminate\Support\Facades\Log;

// Enable all logging
Log::info("=== TEST OBSERVER START ===");

// Tìm employee
$employee = Employee::where('full_name', 'Nguyễn Thị Thiết')->first();
echo "✅ Employee: {$employee->full_name}\n\n";

echo "🔄 Tạo Contract với logging...\n";

$contract = new Contract([
    'employee_id' => $employee->id,
    'contract_number' => 'TEST-LOG-' . time(),
    'type' => ContractType::PROBATION,
    'status' => ContractStatus::ACTIVE,
    'start_date' => now()->subDays(10),
    'end_date' => now()->addMonths(6),
    'created_by' => 1,
]);

echo "Contract employee_id: {$contract->employee_id}\n";
echo "Contract start_date: {$contract->start_date}\n";
echo "Contract status: {$contract->status->value}\n";
echo "Contract type: {$contract->type->value}\n\n";

echo "📝 Saving contract...\n";
$contract->save();

echo "✅ Contract saved: {$contract->id}\n";
echo "Employment ID: " . ($contract->employment_id ?? 'NULL') . "\n\n";

Log::info("=== TEST OBSERVER END ===");

// Check log
echo "\n📋 Checking recent logs:\n";
$logFile = storage_path('logs/laravel.log');
$logs = shell_exec("tail -50 {$logFile} | grep -A 3 -B 3 'TEST OBSERVER'");
echo $logs ?: "No logs found\n";
