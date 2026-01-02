<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tạo bảng Employee Annual Reviews - Đánh giá cuối năm
     *
     * Annual review = 1 record / employee / năm + file form đã điền (attachment)
     * - Annual review đã chốt → không cần form template, không cần item-level scoring
     * - File form đánh giá đã điền sẽ dùng bảng polymorphic attachments (nếu có)
     * - kpi_avg_score tự tính từ KPI tháng, nhưng vẫn lưu để "đóng băng" theo năm
     * - Status/approval bỏ luôn vì admin nhập là đã chốt, chỉ cần input_at/input_by để dấu vết
     */
    public function up(): void
    {
        Schema::create('employee_annual_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id')->index();
            $table->integer('year')->index();

            // KPI average score từ các tháng trong năm (đóng băng)
            $table->decimal('kpi_avg_score', 5, 2)->comment('Điểm KPI trung bình năm (đóng băng)');

            // Final rating và score
            $table->enum('final_rating', ['A', 'B', 'C', 'D'])
                  ->comment('Xếp loại cuối cùng: Xuất sắc, Tốt, Đạt, Cần cải thiện');
            $table->decimal('final_score', 5, 2)->nullable()
                  ->comment('Điểm tổng cuối cùng (nếu công ty có)');

            $table->text('note')->nullable()->comment('Nhận xét đánh giá');

            // Tracking người nhập và thời gian nhập (dấu vết đã chốt)
            $table->unsignedBigInteger('input_by')->nullable()->comment('User ID người nhập');
            $table->timestamp('input_at')->nullable()->comment('Thời điểm nhập');

            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('input_by')->references('id')->on('users')->onDelete('set null');

            // Unique constraint: 1 annual review per employee per year
            $table->unique(['employee_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_annual_reviews');
    }
};
