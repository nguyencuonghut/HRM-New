<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add performance indexes for insurance module
     */
    public function up(): void
    {
        // Contracts table indexes
        Schema::table('contracts', function (Blueprint $table) {
            $table->index(['status', 'start_date'], 'idx_contracts_status_start');
            $table->index(['status', 'end_date'], 'idx_contracts_status_end');
            $table->index(['employee_id', 'status'], 'idx_contracts_employee_status');
        });

        // Insurance Participations indexes
        Schema::table('insurance_participations', function (Blueprint $table) {
            $table->index('status', 'idx_participations_status');
            $table->index(['employee_id', 'status'], 'idx_participations_employee_status');
            $table->index('updated_at', 'idx_participations_updated');
        });

        // Participation Components indexes
        Schema::table('insurance_participation_components', function (Blueprint $table) {
            $table->index('insurance_participation_id', 'idx_components_participation');
            $table->index('is_enabled', 'idx_components_enabled');
            $table->index('component_id', 'idx_components_component');
        });

        // Change Records indexes
        Schema::table('insurance_change_records', function (Blueprint $table) {
            $table->index(['report_id', 'approval_status'], 'idx_records_report_status');
            $table->index('created_at', 'idx_records_created');
            $table->index('change_type', 'idx_records_type');
        });

        // Monthly Contributions indexes
        Schema::table('insurance_monthly_contributions', function (Blueprint $table) {
            $table->index('report_id', 'idx_contributions_report');
            $table->index('employee_id', 'idx_contributions_employee');
        });

        // Monthly Contribution Items indexes
        Schema::table('insurance_monthly_contribution_items', function (Blueprint $table) {
            $table->index('contribution_id', 'idx_items_contribution');
            $table->index('component_id', 'idx_items_component');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop contracts indexes
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('idx_contracts_status_start');
            $table->dropIndex('idx_contracts_status_end');
            $table->dropIndex('idx_contracts_employee_status');
        });

        // Drop insurance_participations indexes
        Schema::table('insurance_participations', function (Blueprint $table) {
            $table->dropIndex('idx_participations_status');
            $table->dropIndex('idx_participations_employee_status');
            $table->dropIndex('idx_participations_updated');
        });

        // Drop insurance_participation_components indexes
        Schema::table('insurance_participation_components', function (Blueprint $table) {
            $table->dropIndex('idx_components_participation');
            $table->dropIndex('idx_components_enabled');
            $table->dropIndex('idx_components_component');
        });

        // Drop insurance_change_records indexes
        Schema::table('insurance_change_records', function (Blueprint $table) {
            $table->dropIndex('idx_records_report_status');
            $table->dropIndex('idx_records_created');
            $table->dropIndex('idx_records_type');
        });

        // Drop insurance_monthly_contributions indexes
        Schema::table('insurance_monthly_contributions', function (Blueprint $table) {
            $table->dropIndex('idx_contributions_report');
            $table->dropIndex('idx_contributions_employee');
        });

        // Drop insurance_monthly_contribution_items indexes
        Schema::table('insurance_monthly_contribution_items', function (Blueprint $table) {
            $table->dropIndex('idx_items_contribution');
            $table->dropIndex('idx_items_component');
        });
    }
};
