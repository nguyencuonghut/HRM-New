<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Bảng đề xuất tăng bậc BHXH
 *
 * Mục đích:
 * - Lưu trữ đề xuất tăng bậc từ hệ thống (cronjob hàng tháng)
 * - HR duyệt/từ chối
 * - Tracking workflow: PENDING → APPROVED/REJECTED
 *
 * Flow:
 * 1. Cronjob quét nhân viên đủ điều kiện (3 năm thâm niên)
 * 2. Tạo suggestion với status = PENDING
 * 3. HR xem danh sách suggestion
 * 4. HR duyệt → tạo Appendix SALARY → cập nhật insurance_profile
 * 5. Suggestion status = APPROVED
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('insurance_grade_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Nhân viên
            $table->uuid('employee_id')->index()->comment('ID nhân viên');

            // Đề xuất
            $table->unsignedTinyInteger('current_grade')->comment('Bậc hiện tại (1-7)');
            $table->unsignedTinyInteger('suggested_grade')->comment('Bậc đề xuất (1-7)');

            // Lý do
            $table->decimal('tenure_years', 5, 2)->comment('Số năm thâm niên tại vị trí');
            $table->text('reason')->nullable()->comment('Lý do chi tiết');

            // Trạng thái
            $table->enum('status', [
                'PENDING',      // Chờ duyệt
                'APPROVED',     // Đã duyệt
                'REJECTED',     // Từ chối
                'EXPIRED',      // Quá hạn (nếu không xử lý sau 90 ngày)
            ])->default('PENDING')->index()->comment('Trạng thái đề xuất');

            // Workflow
            $table->uuid('processed_by')->nullable()->index()->comment('Người xử lý (HR)');
            $table->timestamp('processed_at')->nullable()->comment('Thời điểm xử lý');
            $table->text('process_note')->nullable()->comment('Ghi chú khi xử lý');

            // Kết quả (nếu approved)
            $table->uuid('created_appendix_id')->nullable()->index()
                  ->comment('ID phụ lục HĐLĐ được tạo (nếu duyệt)');

            // Metadata
            $table->date('suggested_at')->index()->comment('Ngày đề xuất');
            $table->date('expires_at')->nullable()->index()->comment('Ngày hết hạn đề xuất (suggested_at + 90 days)');

            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')
                  ->references('id')
                  ->on('employees')
                  ->cascadeOnDelete();

            // Index composite
            $table->index(['employee_id', 'status'], 'idx_emp_suggestion_status');
            $table->index(['status', 'suggested_at'], 'idx_suggestion_pending');
        });
    }

    public function down(): void {
        Schema::dropIfExists('insurance_grade_suggestions');
    }
};
