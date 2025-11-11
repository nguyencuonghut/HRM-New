<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contract_templates', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->enum('type', ['PROBATION','FIXED_TERM','INDEFINITE','SERVICE','INTERNSHIP','PARTTIME']);
            $t->enum('engine', ['BLADE','HTML_TO_PDF','DOCX_MERGE'])->default('BLADE'); // trước mắt dùng Blade
            $t->string('body_path');                 // ví dụ: 'contracts/templates/probation.blade.php'
            $t->json('placeholders_json')->nullable(); // danh sách biến hỗ trợ render
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();

            $t->index(['type','is_active']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('contract_templates');
    }
};
