<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contract_appendix_template_placeholder_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appendix_template_id');
            
            // Index với tên ngắn gọn
            $table->index('appendix_template_id', 'catpm_template_idx');
            
            // Foreign key với tên ngắn gọn
            $table->foreign('appendix_template_id', 'catpm_template_fk')
                ->references('id')
                ->on('contract_appendix_templates')
                ->onDelete('cascade');

            $table->string('placeholder_key')->comment('Tên placeholder trong DOCX: old_salary, new_position');
            $table->enum('data_source', ['CONTRACT', 'COMPUTED', 'MANUAL', 'SYSTEM'])->default('MANUAL')
                ->comment('CONTRACT: từ appendix model | COMPUTED: tính toán | MANUAL: user nhập | SYSTEM: hệ thống');

            $table->string('source_path')->nullable()->comment('Dot notation path: old_terms.base_salary, effective_date');
            $table->string('default_value')->nullable()->comment('Giá trị mặc định nếu không có data');
            $table->string('transformer')->nullable()->comment('Hàm transform: number_format, date_vn, uppercase');
            $table->text('formula')->nullable()->comment('Expression cho COMPUTED type');
            $table->json('validation_rules')->nullable()->comment('Rules cho MANUAL input');

            $table->boolean('is_required')->default(false);
            $table->integer('display_order')->default(0);

            $table->timestamps();

            $table->unique(['appendix_template_id', 'placeholder_key'], 'unique_appendix_template_placeholder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_appendix_template_placeholder_mappings');
    }
};

