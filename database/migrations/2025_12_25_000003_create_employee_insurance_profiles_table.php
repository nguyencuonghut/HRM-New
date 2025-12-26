<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Hồ sơ BHXH của nhân viên
 *
 * Mục đích:
 * - Lưu trữ bậc lương BHXH hiện tại của nhân viên
 * - Lịch sử thay đổi bậc (tăng bậc, điều chỉnh, chuyển vị trí...)
 * - Liên kết với phụ lục/quyết định pháp lý (source_appendix_id)
 *
 * Nghiệp vụ:
 * - Mọi thay đổi bậc phải có event rõ ràng (KHÔNG tự động)
 * - Cứ mỗi 3 năm thâm niên → hệ thống GỢI Ý tăng bậc
 * - HR duyệt → tạo Appendix SALARY → cập nhật record này
 * - Record có applied_to = NULL là bậc hiện tại
 *
 * Reason codes:
 * - INITIAL: Khởi tạo ban đầu (backfill)
 * - SENIORITY: Tăng bậc theo thâm niên (3 năm)
 * - PROMOTION: Tăng bậc do thăng chức
 * - ADJUSTMENT: Điều chỉnh đặc biệt (quyết định của ban lãnh đạo)
 * - POSITION_CHANGE: Chuyển vị trí (reset bậc hoặc điều chỉnh)
 * - BACKFILL: Bổ sung dữ liệu lịch sử
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('employee_insurance_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Nhân viên
            $table->uuid('employee_id')->index()->comment('ID nhân viên');

            // Vị trí áp dụng để tính bậc (có thể null nếu không liên quan position cụ thể)
            $table->uuid('position_id')->nullable()->index()->comment('ID vị trí/chức danh áp dụng');

            // Bậc BHXH (1-7)
            $table->unsignedTinyInteger('grade')->default(1)->index()->comment('Bậc lương BHXH (1-7)');

            // Thời gian áp dụng
            $table->date('applied_from')->index()->comment('Ngày bắt đầu áp dụng');
            $table->date('applied_to')->nullable()->index()->comment('Ngày kết thúc áp dụng (null = đang áp dụng)');

            // Lý do thay đổi
            $table->enum('reason', [
                'INITIAL',           // Khởi tạo ban đầu
                'SENIORITY',         // Tăng bậc theo thâm niên (3 năm)
                'PROMOTION',         // Tăng bậc do thăng chức
                'ADJUSTMENT',        // Điều chỉnh đặc biệt
                'POSITION_CHANGE',   // Chuyển vị trí
                'BACKFILL'          // Bổ sung dữ liệu lịch sử
            ])->nullable()->comment('Lý do thay đổi bậc');

            // Nguồn pháp lý (Phụ lục/Quyết định)
            $table->uuid('source_appendix_id')->nullable()->index()
                  ->comment('ID phụ lục HĐLĐ làm căn cứ pháp lý (nếu có)');

            // Ghi chú
            $table->text('note')->nullable()->comment('Ghi chú chi tiết');

            // Audit
            $table->uuid('created_by')->nullable()->index()->comment('Người tạo record');
            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')
                  ->references('id')
                  ->on('employees')
                  ->cascadeOnDelete();

            $table->foreign('position_id')
                  ->references('id')
                  ->on('positions')
                  ->nullOnDelete();

            // Nếu muốn ràng buộc FK với contract_appendixes:
            $table->foreign('source_appendix_id')
                  ->references('id')
                  ->on('contract_appendixes')
                  ->nullOnDelete();

            // Index composite cho query performance
            $table->index(['employee_id', 'applied_to'], 'idx_emp_insurance_current');
            $table->index(['applied_from', 'applied_to'], 'idx_insurance_date_range');
        });
    }

    public function down(): void {
        Schema::dropIfExists('employee_insurance_profiles');
    }
};
