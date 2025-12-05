<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Kỳ lương (payroll periods) - tháng/năm
        Schema::create('payroll_periods', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->unsignedInteger('month');                        // Tháng (1-12)
            $t->unsignedInteger('year');                         // Năm
            $t->string('name');                                  // Tên kỳ lương (VD: "Lương tháng 11/2025")
            $t->date('payment_date')->nullable();                // Ngày trả lương
            $t->enum('status', [
                'DRAFT',           // Nháp - đang tính toán
                'PROCESSING',      // Đang xử lý
                'APPROVED',        // Đã phê duyệt
                'PAID',            // Đã thanh toán
                'CANCELLED'        // Đã hủy
            ])->default('DRAFT')->index();
            $t->text('note')->nullable();                        // Ghi chú
            $t->uuid('created_by')->nullable()->index();         // User tạo
            $t->uuid('approved_by')->nullable()->index();        // User phê duyệt
            $t->timestamp('approved_at')->nullable();            // Thời gian phê duyệt
            $t->timestamps();

            // Unique constraint: Không trùng tháng/năm
            $t->unique(['month', 'year']);
            $t->index(['year', 'month']);
        });

        // Bảng lương chi tiết cho từng nhân viên
        Schema::create('payroll_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('payroll_period_id')->index();              // Kỳ lương
            $t->uuid('employee_id')->index();                    // Nhân viên

            // Thông tin hợp đồng (snapshot tại thời điểm tính lương)
            $t->uuid('contract_id')->nullable()->index();        // Hợp đồng hiện tại
            $t->decimal('base_salary', 15, 2)->default(0);       // Lương cơ bản từ Contract

            // Thông tin phân công (snapshot)
            $t->uuid('assignment_id')->nullable()->index();      // Assignment PRIMARY
            $t->string('department_name')->nullable();           // Snapshot: tên phòng ban
            $t->string('position_title')->nullable();            // Snapshot: chức vụ
            $t->string('role_type')->nullable();                 // HEAD/DEPUTY/MEMBER

            // Phụ cấp (allowances) - tách riêng để dễ tracking
            $t->decimal('position_allowance', 15, 2)->default(0);     // Phụ cấp chức vụ (HEAD/DEPUTY)
            $t->decimal('responsibility_allowance', 15, 2)->default(0); // Phụ cấp trách nhiệm
            $t->decimal('other_allowances', 15, 2)->default(0);        // Phụ cấp khác
            $t->decimal('total_allowances', 15, 2)->default(0);        // Tổng phụ cấp

            // Khấu trừ (deductions)
            $t->decimal('social_insurance', 15, 2)->default(0);   // BHXH (8%)
            $t->decimal('health_insurance', 15, 2)->default(0);   // BHYT (1.5%)
            $t->decimal('unemployment_insurance', 15, 2)->default(0); // BHTN (1%)
            $t->decimal('income_tax', 15, 2)->default(0);         // Thuế TNCN
            $t->decimal('other_deductions', 15, 2)->default(0);   // Khấu trừ khác
            $t->decimal('total_deductions', 15, 2)->default(0);   // Tổng khấu trừ

            // Tính toán cuối cùng
            $t->decimal('gross_salary', 15, 2)->default(0);       // Tổng thu nhập = base + allowances
            $t->decimal('net_salary', 15, 2)->default(0);         // Thực lĩnh = gross - deductions

            // Số ngày công
            $t->unsignedInteger('working_days')->default(0);      // Số ngày làm việc thực tế
            $t->unsignedInteger('standard_days')->default(22);    // Số ngày chuẩn (22 ngày)
            $t->decimal('attendance_rate', 5, 2)->default(100);   // Tỷ lệ đi làm (%)

            // Metadata
            $t->text('note')->nullable();                         // Ghi chú
            $t->json('calculation_details')->nullable();          // Chi tiết tính toán (JSON)
            $t->timestamps();

            // Unique constraint: 1 employee chỉ có 1 record per period
            $t->unique(['payroll_period_id', 'employee_id']);
            $t->index(['employee_id', 'payroll_period_id']);

            // Foreign keys
            $t->foreign('payroll_period_id')->references('id')->on('payroll_periods')->onDelete('cascade');
            $t->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $t->foreign('contract_id')->references('id')->on('contracts')->onDelete('set null');
            $t->foreign('assignment_id')->references('id')->on('employee_assignments')->onDelete('set null');
        });

        // Bảng điều chỉnh lương (adjustments) - bonus, penalty, advance...
        Schema::create('payroll_adjustments', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('payroll_item_id')->index();                // Payroll item được điều chỉnh
            $t->enum('type', [
                'BONUS',           // Thưởng
                'PENALTY',         // Phạt
                'ADVANCE',         // Tạm ứng
                'OVERTIME',        // Làm thêm giờ
                'OTHER'            // Khác
            ])->index();
            $t->decimal('amount', 15, 2)->default(0);            // Số tiền (+ hoặc -)
            $t->string('reason')->nullable();                    // Lý do
            $t->text('description')->nullable();                 // Mô tả chi tiết
            $t->uuid('created_by')->nullable()->index();         // User tạo
            $t->timestamps();

            $t->foreign('payroll_item_id')->references('id')->on('payroll_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_periods');
    }
};
