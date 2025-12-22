<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Employee;
use App\Models\Contract;
use Illuminate\Support\Facades\Log;

// Test xem observer có chạy không
Log::info("=== START OBSERVER TEST ===");

$employee = App\Models\Employee::where('full_name', 'Nguyễn Thị Thiết')->first();

echo "Creating new contract...\n";

$contract = new Contract();
$contract->employee_id = $employee->id;
$contract->contract_number = 'TEST-' . time();
$contract->contract_type = 'PROBATION';
$contract->status = 'ACTIVE';
$contract->source = 'LEGACY';
$contract->start_date = now()->subDays(5);
$contract->end_date = now()->addMonths(3);
$contract->created_by = 1;

echo "Before save - employment_id: " . ($contract->employment_id ?? 'NULL') . "\n";

$saved = $contract->save();

echo "After save - saved: " . ($saved ? 'YES' : 'NO') . "\n";
echo "After save - employment_id: " . ($contract->employment_id ?? 'NULL') . "\n";
echo "Contract ID: {$contract->id}\n";

Log::info("=== END OBSERVER TEST ===");

// Check log
echo "\n=== Log entries ===\n";
$logs = shell_exec("tail -50 storage/logs/laravel.log | grep -A 2 'OBSERVER TEST'");
echo $logs ?: "No logs found\n";
