<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('positions', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Khóa chính UUID của vị trí/chức danh
            $table->uuid('department_id')->index(); // ID phòng/ban của vị trí
            $table->string('title'); // Tên vị trí/chức danh
            $table->string('level')->nullable(); // Cấp bậc/nhóm nghề (nếu có)
            $table->decimal('insurance_base_salary', 14, 2)->nullable(); // Lương đóng BHXH mặc định
            $table->decimal('position_salary', 14, 2)->nullable(); // Lương vị trí
            $table->decimal('competency_salary', 14, 2)->nullable(); // Lương năng lực/kỹ năng
            $table->timestamps(); // created_at, updated_at
        });
    }
    public function down(): void {
        Schema::dropIfExists('positions');
    }
};
