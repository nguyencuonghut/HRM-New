<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_experiences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id')->index();
            $table->string('company_name');                               // Tên công ty
            $table->string('position_title');                             // Chức danh
            $table->date('start_date')->nullable();                       // Ngày bắt đầu
            $table->date('end_date')->nullable();                         // Ngày kết thúc
            $table->boolean('is_current')->default(false)->index();       // Đang làm hiện tại
            $table->text('responsibilities')->nullable();                 // Mô tả công việc
            $table->text('achievements')->nullable();                     // Thành tích
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }
    public function down(): void {
        Schema::dropIfExists('employee_experiences');
    }
};
