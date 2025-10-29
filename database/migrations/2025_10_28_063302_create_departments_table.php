<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Khóa chính UUID của phòng/ban/bộ phận
            // Loại đơn vị: DEPARTMENT = Phòng/Ban, UNIT = Bộ phận, TEAM = Nhóm
            $table->enum('type', ['DEPARTMENT','UNIT','TEAM'])
                ->default('DEPARTMENT')
                ->index(); // Phân loại node trong cây
            $table->uuid('parent_id')->nullable()->index(); // ID node cha (null = node gốc đại diện công ty)
            $table->string('name'); // Tên phòng/ban/bộ phận
            $table->string('code')->unique(); // Mã đơn vị (duy nhất)
            $table->uuid('head_assignment_id')->nullable()->index(); // employee_assignments.id của Trưởng đơn vị
            $table->uuid('deputy_assignment_id')->nullable()->index(); // employee_assignments.id của Phó đơn vị
            $table->integer('order_index')->default(0); // Thứ tự hiển thị trong cây tổ chức
            $table->boolean('is_active')->default(true); // Trạng thái hoạt động
            $table->timestamps(); // created_at, updated_at
        });
    }
    public function down(): void {
        Schema::dropIfExists('departments');
    }
};
