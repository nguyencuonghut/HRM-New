<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_relatives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id')->index();
            $table->string('full_name');                                  // Họ tên người thân
            $table->enum('relation', ['FATHER','MOTHER','SPOUSE','CHILD','SIBLING','OTHER'])->index(); // Quan hệ
            $table->date('dob')->nullable();                              // Ngày sinh
            $table->string('phone')->nullable();
            $table->string('occupation')->nullable();                     // Nghề nghiệp
            $table->string('address')->nullable();
            $table->boolean('is_emergency_contact')->default(false)->index(); // Liên hệ khẩn cấp
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }
    public function down(): void {
        Schema::dropIfExists('employee_relatives');
    }
};
