<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Thang lương/hệ số theo Position
 *
 * Mục đích:
 * - Lưu trữ 7 bậc lương (1-7) cho mỗi Position
 * - Mỗi bậc có hệ số riêng (coefficient)
 * - Hỗ trợ lịch sử thay đổi hệ số theo thời gian
 *
 * Công thức tính:
 * Lương BHXH = Lương tối thiểu vùng × Hệ số bậc
 *
 * Nghiệp vụ:
 * - Cứ mỗi 3 năm thâm niên ở cùng vị trí → tăng 1 bậc
 * - Tối đa bậc 7
 * - Khi điều chỉnh hệ số → INSERT record mới với effective_from mới
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('position_salary_grades', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Position áp dụng
            $table->uuid('position_id')->index()->comment('ID vị trí/chức danh');

            // Bậc lương (1-7)
            $table->unsignedTinyInteger('grade')->index()->comment('Bậc lương (1-7)');

            // Hệ số lương
            $table->decimal('coefficient', 6, 2)->comment('Hệ số nhân với lương tối thiểu vùng');

            // Thời gian hiệu lực
            $table->date('effective_from')->index()->comment('Ngày bắt đầu hiệu lực');
            $table->date('effective_to')->nullable()->index()->comment('Ngày kết thúc hiệu lực (null = vô thời hạn)');

            // Trạng thái
            $table->boolean('is_active')->default(true)->index()->comment('Đang hiệu lực?');

            // Ghi chú
            $table->text('note')->nullable()->comment('Ghi chú về điều chỉnh hệ số');

            $table->timestamps();

            // Ràng buộc: 1 position + 1 grade chỉ có 1 record effective tại 1 thời điểm
            $table->unique(['position_id', 'grade', 'effective_from'], 'uq_pos_grade_from');

            // Foreign key
            $table->foreign('position_id')
                  ->references('id')
                  ->on('positions')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('position_salary_grades');
    }
};
