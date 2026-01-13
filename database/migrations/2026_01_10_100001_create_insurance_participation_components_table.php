<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Purpose: Create detail table for participation components
     * Allows each participation to have 0-5 components with custom settings
     * Supports special cases:
     * - Only 0.5% TNLĐ-BNN: enable only OCC_ACCIDENT_DISEASE
     * - BHTN base 72M: enable UNEMPLOYMENT with base_type=FIXED_AMOUNT, base_amount=72000000
     */
    public function up(): void
    {
        Schema::create('insurance_participation_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('insurance_participation_id')->index('idx_ipc_participation_id')->comment('FK to insurance_participations');
            $table->unsignedBigInteger('component_id')->index('idx_ipc_component_id')->comment('FK to insurance_components.id');
            $table->boolean('is_enabled')->default(true)->index('idx_ipc_enabled')->comment('Component có được bật');
            $table->decimal('rate_total', 8, 5)->comment('Tỷ lệ đóng (có thể override từ default)');
            $table->enum('base_type', ['INSURANCE_SALARY', 'FIXED_AMOUNT'])->default('INSURANCE_SALARY')->comment('Base lương: từ HĐ/PLHĐ hoặc số cố định');
            $table->decimal('base_amount', 15, 2)->nullable()->comment('Số tiền cố định (dùng khi base_type=FIXED_AMOUNT)');
            $table->text('note')->nullable()->comment('Ghi chú');
            $table->timestamps();

            $table->foreign('insurance_participation_id', 'fk_ipc_participation')
                ->references('id')
                ->on('insurance_participations')
                ->onDelete('cascade');

            $table->foreign('component_id', 'fk_ipc_component')
                ->references('id')
                ->on('insurance_components')
                ->onDelete('cascade');

            $table->unique(['insurance_participation_id', 'component_id'], 'unique_participation_component');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('insurance_participation_components');
        Schema::enableForeignKeyConstraints();
    }
};
