<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Khóa chính UUID của phân công/kiêm nhiệm
            $table->uuid('employee_id')->index(); // ID nhân viên
            $table->uuid('department_id')->index(); // Gán vào phòng/ban nào
            $table->uuid('position_id')->nullable()->index(); // Vị trí/chức danh (có thể null)
            $table->boolean('is_primary')->default(false)->index(); // Đánh dấu assignment chính
            $table->enum('role_type', ['HEAD','DEPUTY','MEMBER'])->default('MEMBER')->index(); // Vai trò trong đơn vị
            $table->date('start_date')->nullable()->index(); // Ngày bắt đầu hiệu lực
            $table->date('end_date')->nullable()->index(); // Ngày kết thúc hiệu lực
            $table->enum('status', ['ACTIVE','INACTIVE'])->default('ACTIVE')->index(); // Trạng thái phân công
            $table->unsignedTinyInteger('active_primary_flag')->storedAs("CASE WHEN is_primary = 1 AND status = 'ACTIVE' THEN 1 ELSE NULL END"); // 1 nếu là assignment chính đang ACTIVE
            $table->timestamps(); // created_at, updated_at
        });

        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->unique(['employee_id', 'active_primary_flag'], 'uq_emp_one_active_primary'); // Ràng buộc chỉ 1 primary ACTIVE / nhân viên
        });
    }
    public function down(): void {
        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->dropUnique('uq_emp_one_active_primary'); // Gỡ constraint trước khi drop table
        });
        Schema::dropIfExists('employee_assignments');
    }
};
