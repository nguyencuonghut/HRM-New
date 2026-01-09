<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Insurance Minimum Wage Configs
 *
 * Lương tối thiểu vùng trong từng config set.
 * Mỗi config set PHẢI có đủ 4 vùng (1-4).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insurance_minimum_wage_configs', function (Blueprint $table) {
            $table->id();

            // Link to config set
            $table->unsignedBigInteger('config_set_id')->index();
            $table->foreign('config_set_id')
                  ->references('id')
                  ->on('insurance_config_sets')
                  ->cascadeOnDelete();

            // Region data
            $table->unsignedTinyInteger('region')
                  ->comment('Vùng (1=Vùng I, 2=Vùng II, 3=Vùng III, 4=Vùng IV)');

            $table->decimal('amount', 15, 2)->comment('Lương tối thiểu vùng (VNĐ)');

            // Effective dates (optional override từ config_set)
            $table->date('effective_from')->nullable()->comment('Ngày hiệu lực (null = dùng của config_set)');
            $table->date('effective_to')->nullable()->comment('Ngày kết thúc');

            $table->text('note')->nullable()->comment('Ghi chú (VD: Nghị định số...)');

            $table->timestamps();

            // Unique constraint: 1 config_set chỉ có 1 record cho mỗi region
            $table->unique(['config_set_id', 'region'], 'uq_config_region');

            // Index
            $table->index(['config_set_id', 'region']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_minimum_wage_configs');
    }
};
