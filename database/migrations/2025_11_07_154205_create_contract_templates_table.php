<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contract_templates', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->enum('type', ['PROBATION','FIXED_TERM','INDEFINITE','SEASONAL','SERVICE','INTERNSHIP','PARTTIME']);
            $t->enum('engine', ['LIQUID','BLADE','HTML_TO_PDF','DOCX_MERGE'])->default('DOCX_MERGE');
            $t->string('body_path')->nullable();                 // ví dụ: 'contracts/templates/probation.blade.php'
            $t->longText('content')->nullable(); // Nội dung template dạng Liquid (user chỉnh sửa trên UI)
            $t->json('placeholders_json')->nullable(); // danh sách biến hỗ trợ render
            $t->boolean('is_default')->default(false); // Mặc định theo loại
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('version')->default(1);
            $t->unsignedBigInteger('updated_by')->nullable(); // Ai sửa lần cuối (optional, có thể dùng cho audit)
            $t->timestamps();

            $t->index(['type','is_active']);
            $t->index('engine');
        });
    }

    public function down(): void {
        Schema::dropIfExists('contract_templates');
    }
};
