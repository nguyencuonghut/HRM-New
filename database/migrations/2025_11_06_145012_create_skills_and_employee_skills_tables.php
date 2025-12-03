<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Tạo bảng skill_categories trước
        Schema::create('skill_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                                       // Tên nhóm (VD: Tin học văn phòng, Lập trình)
            $table->text('description')->nullable();                      // Mô tả nhóm
            $table->unsignedSmallInteger('order_index')->default(0);      // Thứ tự hiển thị
            $table->boolean('is_active')->default(true);                  // Trạng thái
            $table->timestamps();
            $table->unique('name');
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id')->nullable()->index();             // Nhóm kỹ năng
            $table->string('code', 100)->nullable()->index();             // Mã kỹ năng (nếu cần)
            $table->string('name');                                       // Tên kỹ năng (VD: Excel, SQL, Quản lý dự án)
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('skill_categories')->nullOnDelete();
            $table->unique('name');                                       // Tránh trùng tên
        });

        Schema::create('employee_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id')->index();
            $table->uuid('skill_id')->index();
            $table->unsignedTinyInteger('level')->default(0)->index();    // 0-5: mức thành thạo
            $table->unsignedSmallInteger('years')->default(0);            // Số năm kinh nghiệm
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('skill_id')->references('id')->on('skills')->cascadeOnDelete();
            $table->unique(['employee_id','skill_id']);                   // 1 kỹ năng / 1 NV
        });
    }
    public function down(): void {
        Schema::dropIfExists('employee_skills');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('skill_categories');
    }
};
