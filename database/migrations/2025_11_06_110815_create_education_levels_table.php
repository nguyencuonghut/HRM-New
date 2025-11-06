<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('education_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();                           // Khóa chính UUID
            $table->string('code', 50)->nullable()->index();         // Mã (tùy chọn) - dùng để mapping nếu cần
            $table->string('name', 255);                             // Tên trình độ (VD: Đại học, Cao đẳng, ...)
            $table->integer('order_index')->default(0)->index();     // Thứ tự hiển thị
            $table->timestamps();

            // Tối ưu tìm kiếm
            $table->index('name');

            // Nếu muốn tránh trùng tên, có thể bật unique:
            // $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_levels');
    }
};
