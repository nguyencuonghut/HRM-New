<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_educations', function (Blueprint $table) {
            $table->uuid('id')->primary();                               // Khóa chính
            $table->uuid('employee_id')->index();                         // FK nhân viên
            $table->uuid('education_level_id')->nullable()->index();      // FK trình độ học vấn
            $table->uuid('school_id')->nullable()->index();               // FK trường
            $table->string('major')->nullable();                          // Chuyên ngành
            $table->year('start_year')->nullable();                       // Năm bắt đầu
            $table->year('end_year')->nullable();                         // Năm kết thúc
            $table->enum('study_form', ['FULLTIME','PARTTIME','ONLINE'])->nullable()->index(); // Hình thức học
            $table->string('certificate_no')->nullable();                 // Số hiệu văn bằng
            $table->date('graduation_date')->nullable();                  // Ngày tốt nghiệp
            $table->string('grade')->nullable();                          // Xếp loại
            $table->text('note')->nullable();                             // Ghi chú
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('education_level_id')->references('id')->on('education_levels')->nullOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::dropIfExists('employee_educations');
    }
};
