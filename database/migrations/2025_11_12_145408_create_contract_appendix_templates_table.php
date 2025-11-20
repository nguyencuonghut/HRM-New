<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_appendix_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                // Tên template, ví dụ: Phụ lục điều chỉnh lương
            $table->string('code')->unique();      // Mã template, ví dụ: PL-LUONG-01

            // Loại phụ lục: match với enum appendix_type của ContractAppendix
            $table->enum('appendix_type', [
                'SALARY',          // Điều chỉnh lương
                'ALLOWANCE',       // Điều chỉnh phụ cấp
                'POSITION',        // Điều chỉnh chức danh
                'DEPARTMENT',      // Điều chuyển đơn vị
                'WORKING_TERMS',   // Thời gian/địa điểm làm việc
                'EXTENSION',       // Gia hạn HĐ
                'OTHER',           // Khác
            ])->default('SALARY');

            // Engine - chỉ sử dụng DOCX_MERGE cho appendix templates
            $table->enum('engine', ['DOCX_MERGE'])->default('DOCX_MERGE');
            
            // Đường dẫn file DOCX template
            $table->string('body_path')->nullable(); // ví dụ: 'templates/appendixes/salary.docx'
            
            // Nội dung template (có thể dùng cho các engine khác trong tương lai)
            $table->longText('content')->nullable();
            
            // Danh sách biến hỗ trợ render (extracted placeholders)
            $table->json('placeholders_json')->nullable();
            
            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable(); // Ai sửa lần cuối

            $table->timestamps();

            $table->index(['appendix_type', 'is_active']);
            $table->index('engine');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_appendix_templates');
    }
};
