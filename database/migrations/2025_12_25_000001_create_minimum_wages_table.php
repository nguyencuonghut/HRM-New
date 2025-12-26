<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Bảng lương tối thiểu vùng theo thời gian
 *
 * Mục đích:
 * - Lưu trữ lương tối thiểu vùng (1,2,3,4) theo quy định nhà nước
 * - Hỗ trợ lịch sử thay đổi theo thời gian (effective_from/to)
 * - Đảm bảo tính đúng lương BHXH theo mức hiệu lực tại thời điểm
 *
 * Nghiệp vụ:
 * - Khi nhà nước điều chỉnh lương tối thiểu → INSERT record mới
 * - KHÔNG update record cũ (để giữ lịch sử)
 * - Payroll/BHXH report phải lấy đúng mức theo effective date
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('minimum_wages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Vùng lương tối thiểu (1: Vùng I, 2: Vùng II, 3: Vùng III, 4: Vùng IV)
            $table->unsignedTinyInteger('region')->index()->comment('1=Vùng I, 2=Vùng II, 3=Vùng III, 4=Vùng IV');

            // Mức lương tối thiểu (VND)
            $table->unsignedBigInteger('amount')->comment('Lương tối thiểu vùng (VND)');

            // Thời gian hiệu lực
            $table->date('effective_from')->index()->comment('Ngày bắt đầu hiệu lực');
            $table->date('effective_to')->nullable()->index()->comment('Ngày kết thúc hiệu lực (null = vô thời hạn)');

            // Trạng thái
            $table->boolean('is_active')->default(true)->index()->comment('Đang hiệu lực?');

            // Ghi chú bổ sung
            $table->text('note')->nullable()->comment('Ghi chú (số QĐ, văn bản pháp lý...)');

            $table->timestamps();

            // Ràng buộc: 1 vùng chỉ có 1 record effective tại 1 thời điểm
            $table->unique(['region', 'effective_from'], 'uq_minwage_region_from');
        });
    }

    public function down(): void {
        Schema::dropIfExists('minimum_wages');
    }
};
