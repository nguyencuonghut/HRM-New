<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Purpose: Create snapshot tables for insurance contributions
     * Stores calculated contributions when report is FINALIZED
     * Prevents data changes after approval
     */
    public function up(): void
    {
        // Parent table: Contribution per employee per report
        Schema::create('insurance_monthly_contributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id')->index()->comment('FK to insurance_monthly_reports');
            $table->uuid('employee_id')->index()->comment('FK to employees');
            $table->uuid('change_record_id')->nullable()->index()->comment('FK to insurance_change_records');
            $table->decimal('base_insurance_salary', 15, 2)->comment('Lương BH base của tháng');
            $table->decimal('total_amount', 15, 2)->comment('Tổng tiền phải đóng');
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('insurance_monthly_reports')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('change_record_id')->references('id')->on('insurance_change_records')->nullOnDelete();
            $table->unique(['report_id', 'employee_id'], 'unique_report_employee');
        });

        // Detail table: Breakdown by component
        Schema::create('insurance_monthly_contribution_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contribution_id')->index()->comment('FK to insurance_monthly_contributions');
            $table->unsignedBigInteger('component_id')->index()->comment('FK to insurance_components.id');
            $table->string('component_code', 50)->index()->comment('Snapshot of component code');
            $table->string('component_name', 255)->comment('Snapshot of component name');
            $table->enum('base_type', ['INSURANCE_SALARY', 'FIXED_AMOUNT'])->default('INSURANCE_SALARY')->comment('Snapshot of base type');
            $table->decimal('base_used', 15, 2)->comment('Base được dùng để tính (có thể khác base_insurance_salary nếu FIXED_AMOUNT)');
            $table->decimal('rate_total', 8, 5)->comment('Tỷ lệ đóng tại thời điểm tính');
            $table->decimal('amount', 15, 2)->comment('Số tiền = base_used × rate_total');
            $table->timestamps();

            $table->foreign('contribution_id')->references('id')->on('insurance_monthly_contributions')->onDelete('cascade');
            $table->foreign('component_id')->references('id')->on('insurance_components')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('insurance_monthly_contribution_items');
        Schema::dropIfExists('insurance_monthly_contributions');
        Schema::enableForeignKeyConstraints();
    }
};
