<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Employee;
use App\Models\Contract;
use App\Models\LeaveRequest;
use App\Models\EmployeeEmployment;
use App\Enums\EmployeeStatus;

$employee = Employee::where('full_name', 'like', '%Bùi Thế Tuyến%')->first();

if (!$employee) {
    echo "Không tìm thấy nhân viên Bùi Thế Tuyến\n";
    exit;
}

echo "=== THÔNG TIN NHÂN VIÊN ===\n";
echo "ID: {$employee->id}\n";
echo "Tên: {$employee->name}\n";
echo "Status hiện tại: {$employee->status} (" . EmployeeStatus::from($employee->status)->label() . ")\n";
echo "Ngày tạo: {$employee->created_at}\n";
echo "\n";

echo "=== CONTRACTS ===\n";
$contracts = Contract::where('employee_id', $employee->id)->get();
echo "Tổng số contract: {$contracts->count()}\n";
if ($contracts->count() > 0) {
    foreach ($contracts as $contract) {
        echo "  - ID: {$contract->id}, Status: {$contract->status}, Type: {$contract->contract_type_id}\n";
        echo "    Start: {$contract->start_date}, End: " . ($contract->end_date ?? 'N/A') . "\n";
    }
} else {
    echo "  (Không có contract nào)\n";
}
echo "\n";

echo "=== LEAVE REQUESTS ===\n";
$leaves = LeaveRequest::where('employee_id', $employee->id)->get();
echo "Tổng số leave: {$leaves->count()}\n";
if ($leaves->count() > 0) {
    foreach ($leaves as $leave) {
        echo "  - ID: {$leave->id}, Status: {$leave->status}, Days: {$leave->days}\n";
        echo "    Start: {$leave->start_date}, End: {$leave->end_date}\n";
    }
}
echo "\n";

echo "=== EMPLOYMENTS ===\n";
$employments = EmployeeEmployment::where('employee_id', $employee->id)->get();
echo "Tổng số employment: {$employments->count()}\n";
if ($employments->count() > 0) {
    foreach ($employments as $emp) {
        echo "  - ID: {$emp->id}, Status: {$emp->employment_status}\n";
        echo "    Start: {$emp->start_date}, End: " . ($emp->end_date ?? 'N/A') . "\n";
    }
}
