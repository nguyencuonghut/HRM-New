<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->uuid('id')->primary();                   // Khóa chính UUID
            $table->string('code', 50)->nullable()->index(); // Mã trường (tùy chọn)
            $table->string('name', 255);                     // Tên trường
            $table->timestamps();

            // Tối ưu tìm kiếm
            $table->index('name');

            // Nếu muốn tránh trùng tên, có thể bật unique:
            // $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
