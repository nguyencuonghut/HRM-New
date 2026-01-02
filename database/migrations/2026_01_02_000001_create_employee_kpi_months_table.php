<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tạo bảng Employee KPI Months - KPI tháng của nhân viên
     *
     * KPI tháng = 1 record / employee / tháng (không gắn vào position)
     * - KPI là kết quả tổng hợp của nhân viên, không phụ thuộc số lượng position
     * - Gắn KPI vào position sẽ tạo rủi ro:
     *   + Admin phải chọn "position nào" → dễ sai, khó backfill
     *   + Thay đổi position trong tháng làm dữ liệu KPI không nhất quán
     * - HRM chuẩn thường tách: Assignment/Position history (nhiều) vs Performance result (một kết quả theo period)
     * - Status/approval bỏ luôn vì admin nhập là đã chốt, chỉ cần input_at/input_by để dấu vết
     */
    public function up(): void
    {
        Schema::create('employee_kpi_months', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id')->index();
            $table->integer('year');
            $table->tinyInteger('month')->comment('1-12');
            $table->decimal('kpi_score', 5, 2)->comment('Điểm KPI tháng');
            $table->text('note')->nullable();

            // Tracking người nhập và thời gian nhập (dấu vết đã chốt)
            $table->unsignedBigInteger('input_by')->nullable()->comment('User ID người nhập');
            $table->timestamp('input_at')->nullable()->comment('Thời điểm nhập');

            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('input_by')->references('id')->on('users')->onDelete('set null');

            // Unique constraint: 1 KPI record per employee per month
            $table->unique(['employee_id', 'year', 'month']);

            // Additional indexes for queries
            $table->index(['year', 'month']);
            $table->index(['employee_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_kpi_months');
    }
};
