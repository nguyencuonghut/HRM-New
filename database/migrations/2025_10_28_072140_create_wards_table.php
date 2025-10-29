<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('wards', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Khóa chính UUID
            $table->uuid('province_id')->index(); // Tỉnh/Thành phố cha
            $table->string('code')->unique(); // Mã xã/phường
            $table->string('name'); // Tên xã/phường
            $table->timestamps(); // created_at, updated_at
        });

        // Nếu muốn, có thể thêm khóa ngoại:
        // Schema::table('wards', function (Blueprint $table) {
        //     $table->foreign('province_id')->references('id')->on('provinces')->cascadeOnDelete();
        // });
    }

    public function down(): void {
        Schema::dropIfExists('wards');
    }
};
