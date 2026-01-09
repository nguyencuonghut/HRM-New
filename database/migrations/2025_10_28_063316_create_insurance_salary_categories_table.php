<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create insurance_salary_categories table
     *
     * Purpose: Standardize insurance salary category management for positions
     * - Prevents typos and inconsistencies
     * - Provides dropdown options in UI
     * - Enables proper grouping and reporting
     *
     * Relationships:
     * - Has many positions
     */
    public function up(): void
    {
        Schema::create('insurance_salary_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Code: unique identifier (giam_doc, truong_phong, etc.)
            $table->string('code', 50)->unique()->index()
                  ->comment('Mã định danh: giam_doc, pho_giam_doc, truong_phong, etc.');

            // Name: display name
            $table->string('name', 100)->unique()
                  ->comment('Tên hiển thị: Giám đốc, Phó Giám đốc, Trưởng phòng, etc.');

            // Description: detailed explanation
            $table->text('description')->nullable()
                  ->comment('Mô tả chi tiết về nhóm chức danh này');

            // Display order: for sorting in UI
            $table->integer('display_order')->default(0)
                  ->comment('Thứ tự hiển thị (số càng nhỏ càng ưu tiên)');

            // Active status
            $table->boolean('is_active')->default(true)->index()
                  ->comment('Trạng thái hoạt động');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_salary_categories');
    }
};
