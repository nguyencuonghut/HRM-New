<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('role_scopes', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Khóa chính UUID
            $table->unsignedBigInteger('role_id')->index(); // spatie roles.id
            $table->uuid('employee_id')->nullable()->index(); // Giới hạn theo nhân viên (nếu cần)
            $table->uuid('department_id')->nullable()->index(); // Giới hạn theo phòng/ban
            $table->timestamps(); // created_at, updated_at
        });
    }
    public function down(): void {
        Schema::dropIfExists('role_scopes');
    }
};
