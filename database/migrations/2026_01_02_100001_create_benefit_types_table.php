<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefit_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Mã loại phúc lợi (BIRTHDAY, WEDDING...)');
            $table->string('name')->comment('Tên loại phúc lợi (Sinh nhật, Hiếu, Hỷ...)');
            $table->text('description')->nullable()->comment('Mô tả chi tiết');
            $table->boolean('is_active')->default(true)->comment('Còn sử dụng hay không');
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benefit_types');
    }
};
