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
            $table->string('cccd')->nullable(); // CCCD/CMND
            $table->date('cccd_issued_on')->nullable(); // Ngày cấp CCCD
            $table->string('cccd_issued_by')->nullable(); // Nơi cấp CCCD

            // Địa chỉ hành chính theo danh mục Tỉnh/Thành & Xã/Phường
            $table->uuid('ward_id')->nullable()->index(); // ID xã/phường (nối với bảng wards)
            $table->string('address_street')->nullable(); // Số nhà/đường
            $table->string('phone')->nullable(); // SĐT liên hệ
            $table->string('personal_email')->nullable(); // Email cá nhân

            $table->date('hire_date')->nullable(); // Ngày vào làm
            $table->enum('status', ['ACTIVE','INACTIVE','ON_LEAVE','TERMINATED'])->default('ACTIVE')->index(); // Trạng thái nhân sự

            // Bảo hiểm (BHXH)
            $table->string('si_number')->nullable()->index(); // Mã số BHXH (nếu đã tham gia)

            $table->timestamps(); // created_at, updated_at
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
