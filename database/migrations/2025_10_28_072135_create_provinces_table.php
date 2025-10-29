<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('provinces', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Khóa chính UUID
            $table->string('code')->unique(); // Mã tỉnh/thành (ví dụ: 79)
            $table->string('name'); // Tên tỉnh/thành phố
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void {
        Schema::dropIfExists('provinces');
    }
};
