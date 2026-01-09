<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Company Regions - Vùng BHXH của công ty theo thời gian
 *
 * Mục đích:
 * - Lưu trữ vùng BHXH (1-4) của công ty theo thời gian
 * - Hỗ trợ thay đổi vùng khi công ty chuyển địa điểm
 * - Đảm bảo tính đúng lương BHXH theo vùng hiệu lực tại thời điểm
 *
 * Nghiệp vụ:
 * - Khi công ty chuyển vùng → INSERT record mới với effective_from mới
 * - KHÔNG update record cũ (để giữ lịch sử)
 * - Payroll/BHXH report phải lấy đúng vùng theo effective date
 *
 * Ví dụ:
 * - 2024-01-01 → 2024-12-31: Vùng 3
 * - 2025-01-01 → null: Vùng 2 (công ty chuyển trụ sở)
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('company_regions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Vùng BHXH (1: Vùng I, 2: Vùng II, 3: Vùng III, 4: Vùng IV)
            $table->unsignedTinyInteger('region')->index()->comment('1=Vùng I, 2=Vùng II, 3=Vùng III, 4=Vùng IV');

            // Thời gian hiệu lực
            $table->date('effective_from')->index()->comment('Ngày bắt đầu hiệu lực');
            $table->date('effective_to')->nullable()->index()->comment('Ngày kết thúc hiệu lực (null = vô thời hạn)');

            // Ghi chú
            $table->text('note')->nullable()->comment('Lý do thay đổi vùng, địa chỉ mới, văn bản pháp lý...');

            $table->timestamps();

            // Ràng buộc: Không được có 2 record cùng effective_from
            $table->unique(['effective_from'], 'uq_company_region_from');
        });
    }

    public function down(): void {
        Schema::dropIfExists('company_regions');
    }
};
