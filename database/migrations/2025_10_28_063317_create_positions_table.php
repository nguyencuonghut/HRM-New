<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tạo bảng Positions (Vị trí/Chức danh)
     *
     * LƯU Ý QUAN TRỌNG về Lương BHXH:
     * ================================
     * - Trường 'insurance_base_salary' KHÔNG nên dùng trực tiếp để tính BHXH
     * - Lương BHXH thực tế = Lương tối thiểu vùng × Hệ số bậc (từ bảng position_salary_grades)
     * - Trường này chỉ dùng làm DEFAULT GỢI Ý khi tạo hợp đồng/phụ lục
     *
     * Hệ thống lương BHXH chuẩn:
     * - minimum_wages: Lương tối thiểu vùng theo thời gian
     * - position_salary_grades: 7 bậc với hệ số cho mỗi position
     * - employee_insurance_profiles: Bậc hiện tại của từng nhân viên
     * - contract_appendixes: Quyết định pháp lý về lương BHXH
     *
     * Xem thêm:
     * - database/migrations/2025_12_25_000001_create_minimum_wages_table.php
     * - database/migrations/2025_12_25_000002_create_position_salary_grades_table.php
     * - database/migrations/2025_12_25_000003_create_employee_insurance_profiles_table.php
     */
    public function up(): void {
        Schema::create('positions', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Khóa chính UUID của vị trí/chức danh
            $table->uuid('department_id')->index(); // ID phòng/ban của vị trí
            $table->string('title'); // Tên vị trí/chức danh
            $table->string('level')->nullable(); // Cấp bậc/nhóm nghề (nếu có)

            // ⚠️ DEPRECATED: Chỉ dùng làm default gợi ý, KHÔNG dùng để tính BHXH chính thức
            // Lương BHXH thực tế phải tính từ: minimum_wage × coefficient (position_salary_grades)
            $table->decimal('insurance_base_salary', 14, 2)->nullable()
                  ->comment('DEPRECATED: Chỉ làm default gợi ý. Dùng position_salary_grades để tính BHXH thực tế');

            $table->decimal('position_salary', 14, 2)->nullable(); // Lương vị trí
            $table->decimal('competency_salary', 14, 2)->nullable(); // Lương năng lực/kỹ năng
            $table->decimal('allowance', 14, 2)->nullable(); // Phụ cấp (nếu có)
            $table->timestamps(); // created_at, updated_at
        });
    }
    public function down(): void {
        Schema::dropIfExists('positions');
    }
};
