<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Loại phép (Annual leave, Sick leave, Personal leave, etc.)
        Schema::create('leave_types', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');                        // Tên loại phép (VD: Phép năm, Phép ốm)
            $t->string('code')->unique();              // Mã (VD: ANNUAL, SICK, PERSONAL)
            $t->string('color')->default('#3B82F6');   // Màu hiển thị trên UI
            $t->unsignedInteger('days_per_year')->default(12);  // Số ngày phép/năm
            $t->boolean('requires_approval')->default(true);    // Có cần phê duyệt không
            $t->boolean('is_paid')->default(true);              // Có hưởng lương không
            $t->boolean('is_active')->default(true);            // Còn sử dụng không
            $t->text('description')->nullable();
            $t->unsignedInteger('order_index')->default(0);     // Thứ tự sắp xếp
            $t->timestamps();
        });

        // Yêu cầu nghỉ phép
        Schema::create('leave_requests', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('employee_id')->index();                    // Nhân viên nộp đơn
            $t->uuid('leave_type_id')->index();                  // Loại phép

            $t->date('start_date');                              // Ngày bắt đầu
            $t->date('end_date');                                // Ngày kết thúc
            $t->decimal('days', 5, 2)->default(0);               // Số ngày nghỉ (có thể 0.5 ngày)
            $t->text('reason')->nullable();                      // Lý do nghỉ

            $t->enum('status', [
                'DRAFT',           // Nháp
                'PENDING',         // Chờ phê duyệt
                'APPROVED',        // Đã phê duyệt
                'REJECTED',        // Từ chối
                'CANCELLED'        // Đã hủy
            ])->default('DRAFT')->index();

            $t->timestamp('submitted_at')->nullable();           // Thời gian nộp đơn
            $t->timestamp('approved_at')->nullable();            // Thời gian duyệt cuối cùng (fully approved)
            $t->timestamp('cancelled_at')->nullable();           // Thời gian hủy

            $t->text('note')->nullable();                        // Ghi chú thêm
            $t->uuid('created_by')->nullable()->index();         // User tạo đơn
            $t->timestamps();

            // Index để query hiệu quả
            $t->index(['employee_id', 'status']);
            $t->index(['start_date', 'end_date']);
        });

        // Quy trình phê duyệt nghỉ phép (multi-step approval)
        Schema::create('leave_approvals', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('leave_request_id')->index();               // Request được phê duyệt
            $t->uuid('approver_id')->nullable()->index();        // User phê duyệt

            $t->unsignedInteger('step')->default(1);             // Bước phê duyệt (1, 2, 3...)
            $t->string('approver_role')->nullable();             // Role của approver (LINE_MANAGER, DIRECTOR, HR)

            $t->enum('status', [
                'PENDING',         // Chờ duyệt
                'APPROVED',        // Đã duyệt
                'REJECTED'         // Từ chối
            ])->default('PENDING')->index();

            $t->text('comment')->nullable();                     // Ý kiến phê duyệt
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('rejected_at')->nullable();
            $t->timestamps();

            // Index để query approval workflow
            $t->index(['leave_request_id', 'step']);
            $t->index(['approver_id', 'status']);
        });

        // Số ngày phép còn lại của nhân viên (balance tracking)
        Schema::create('leave_balances', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('employee_id')->index();
            $t->uuid('leave_type_id')->index();
            $t->unsignedInteger('year')->index();                // Năm (2025, 2026...)

            $t->decimal('total_days', 5, 2)->default(0);         // Tổng số ngày phép trong năm
            $t->decimal('used_days', 5, 2)->default(0);          // Đã sử dụng
            $t->decimal('remaining_days', 5, 2)->default(0);     // Còn lại
            $t->decimal('carried_forward', 5, 2)->default(0);    // Số ngày chuyển từ năm trước

            $t->timestamps();

            // Unique: mỗi nhân viên chỉ có 1 balance/leave_type/year
            $t->unique(['employee_id', 'leave_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_approvals');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
    }
};
