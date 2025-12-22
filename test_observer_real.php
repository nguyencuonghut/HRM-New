<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Employee;
use App\Models\Contract;
use App\Enums\ContractStatus;
use App\Enums\ContractType;

// Tìm employee
$employee = Employee::where('full_name', 'Nguyễn Thị Thiết')->first();
if (!$employee) {
    echo "❌ Không tìm thấy Nguyễn Thị Thiết\n";
    exit(1);
}

echo "✅ Tìm thấy Employee: {$employee->full_name} (ID: {$employee->id})\n\n";

// Đếm employments hiện tại
$employmentsBefore = $employee->employments()->count();
echo "📊 Employments trước khi tạo: {$employmentsBefore}\n\n";

// Tạo contract mới (sẽ trigger observer)
echo "🔄 Đang tạo Contract TEST mới...\n";

try {
    $contract = Contract::create([
        'employee_id' => $employee->id,
        'contract_number' => 'TEST-OBSERVER-' . time(),
        'type' => ContractType::PROBATION,
        'status' => ContractStatus::ACTIVE,
        'start_date' => now()->subDays(10),
        'end_date' => now()->addMonths(6),
        'created_by' => 1,
    ]);
    
    echo "✅ Contract đã tạo: {$contract->contract_number} (ID: {$contract->id})\n";
    echo "   Employment ID: " . ($contract->employment_id ?? 'NULL') . "\n\n";
    
    // Đếm lại employments
    $employmentsAfter = $employee->employments()->count();
    echo "📊 Employments sau khi tạo: {$employmentsAfter}\n";
    
    if ($employmentsAfter > $employmentsBefore) {
        echo "✅ Observer đã chạy! Số employment tăng: " . ($employmentsAfter - $employmentsBefore) . "\n";
    } else {
        echo "❌ Observer KHÔNG chạy! Số employment không đổi\n";
    }
    
    // Kiểm tra contract có được gắn employment không
    $contract->refresh();
    if ($contract->employment_id) {
        echo "✅ Contract đã được gắn với employment_id: {$contract->employment_id}\n";
    } else {
        echo "❌ Contract KHÔNG được gắn với employment_id\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Lỗi khi tạo contract: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
