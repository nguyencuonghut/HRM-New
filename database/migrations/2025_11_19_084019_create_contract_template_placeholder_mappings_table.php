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
        Schema::create('contract_template_placeholder_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id')->index();
            $table->foreign('template_id')->references('id')->on('contract_templates')->onDelete('cascade');

            $table->string('placeholder_key')->comment('Tên placeholder trong DOCX: employee_full_name');
            $table->enum('data_source', ['CONTRACT', 'COMPUTED', 'MANUAL', 'SYSTEM'])->default('CONTRACT')
                ->comment('CONTRACT: từ model | COMPUTED: tính toán | MANUAL: user nhập | SYSTEM: hệ thống');

            $table->string('source_path')->nullable()->comment('Dot notation path: employee.full_name, base_salary');
            $table->string('default_value')->nullable()->comment('Giá trị mặc định nếu không có data');
            $table->string('transformer')->nullable()->comment('Hàm transform: number_format, date_vn, uppercase');
            $table->text('formula')->nullable()->comment('Expression cho COMPUTED type');
            $table->json('validation_rules')->nullable()->comment('Rules cho MANUAL input');

            $table->boolean('is_required')->default(false);
            $table->integer('display_order')->default(0);

            $table->timestamps();

            $table->unique(['template_id', 'placeholder_key'], 'unique_template_placeholder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_template_placeholder_mappings');
    }
};
