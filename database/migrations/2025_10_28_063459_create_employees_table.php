<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Khóa chính UUID của nhân viên

            // Liên kết với users (boilerplate đã có bảng users)
            // Mặc định Laravel users.id là big int (increments) -> dùng unsignedBigInteger cho an toàn.
            // Nếu users.id của bạn là UUID, hãy đổi kiểu dữ liệu cột này sang uuid cho đồng nhất.
            $table->unsignedBigInteger('user_id')->nullable()->index(); // ID user (nếu có tài khoản đăng nhập)

            $table->string('employee_code')->unique(); // Mã nhân viên (duy nhất)
            $table->string('full_name'); // Họ và tên đầy đủ
            $table->date('dob')->nullable(); // Ngày sinh
            $table->enum('gender', ['MALE', 'FEMALE', 'OTHER'])->nullable(); // Giới tính
            $table->enum('marital_status', ['SINGLE', 'MARRIED', 'DIVORCED', 'WIDOWED'])->nullable(); // Tình trạng hôn nhân

            $table->string('avatar')->nullable(); // Ảnh đại diện (đường dẫn file)

            $table->string('cccd')->nullable(); // CCCD/CMND
            $table->date('cccd_issued_on')->nullable(); // Ngày cấp CCCD
            $table->string('cccd_issued_by')->nullable(); // Nơi cấp CCCD

            // Địa chỉ thường trú (theo CCCD)
            $table->uuid('ward_id')->nullable()->index(); // ID xã/phường thường trú (nối với bảng wards)
            $table->string('address_street')->nullable(); // Số nhà/đường thường trú

            // Địa chỉ tạm trú (nếu khác với địa chỉ thường trú)
            $table->uuid('temp_ward_id')->nullable()->index(); // ID xã/phường tạm trú (nối với bảng wards)
            $table->string('temp_address_street')->nullable(); // Số nhà/đường tạm trú

            $table->string('phone')->nullable(); // SĐT liên hệ
            $table->string('emergency_contact_phone')->nullable(); // SĐT người thân
            $table->string('personal_email')->nullable(); // Email cá nhân
            $table->string('company_email')->nullable(); // Email công ty

            $table->date('hire_date')->nullable(); // Ngày vào làm
            $table->enum('status', ['ACTIVE','INACTIVE','ON_LEAVE','TERMINATED'])->default('ACTIVE')->index(); // Trạng thái nhân sự

            // Bảo hiểm (BHXH)
            $table->string('si_number')->nullable()->index(); // Mã số BHXH (nếu đã tham gia)

            $table->timestamps(); // created_at, updated_at

            // Performance indexes
            $table->index('employee_code', 'idx_employees_code');
            $table->index(['status', 'created_at'], 'idx_employees_status_created');
            $table->fullText(['employee_code', 'full_name', 'phone', 'company_email'], 'idx_employees_search');
        });

        // Gợi ý (tuỳ bạn bật FK hay không):
        // Schema::table('employees', function (Blueprint $table) {
        //     $table->foreign('user_id')->references('id')->on('users')->nullOnDelete(); // nếu users.id là BIGINT
        //     $table->foreign('ward_id')->references('id')->on('wards')->nullOnDelete();
        // });
    }

    public function down(): void {
        Schema::dropIfExists('employees');
    }
};
