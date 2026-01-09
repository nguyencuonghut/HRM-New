<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Insurance Salary Grade Configs
 *
 * Thang hệ số lương BHXH (7 bậc) trong từng config set.
 * Mỗi config set PHẢI có đủ 7 bậc (1-7).
 *
 * Công thức: Lương BHXH = Lương tối thiểu vùng × Hệ số bậc
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insurance_salary_grade_configs', function (Blueprint $table) {
            $table->id();

            // Link to config set
            $table->unsignedBigInteger('config_set_id')->index();
            $table->foreign('config_set_id')
                  ->references('id')
                  ->on('insurance_config_sets')
                  ->cascadeOnDelete();

            // Grade data
            $table->unsignedTinyInteger('grade')
                  ->comment('Bậc lương (1-7)');

            $table->string('name', 100)->comment('Tên bậc (VD: Bậc 1, Bậc 2...)');

            $table->decimal('coefficient', 8, 4)
                  ->comment('Hệ số nhân với lương tối thiểu vùng');

            $table->text('description')->nullable()->comment('Mô tả bậc');

            $table->boolean('is_active')->default(true)->comment('Bậc này có hiệu lực không');

            $table->timestamps();

            // Unique constraint: 1 config_set chỉ có 1 record cho mỗi grade
            $table->unique(['config_set_id', 'grade'], 'uq_config_grade');

            // Index
            $table->index(['config_set_id', 'grade']);
            $table->index(['config_set_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_salary_grade_configs');
    }
};
