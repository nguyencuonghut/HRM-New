<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Purpose: Add declaration month fields to insurance_change_records
     * Supports:
     * - Rule 1-14 → same month, 15-31 → next month (suggested)
     * - Reviewer can override declaration_month
     * - Track who/when/why overridden
     */
    public function up(): void
    {
        Schema::table('insurance_change_records', function (Blueprint $table) {
            $table->string('suggested_declaration_month', 7)->nullable()->index()->after('effective_date')->comment('Tháng kê khai gợi ý (YYYY-MM)');
            $table->string('declaration_month', 7)->nullable()->index()->after('suggested_declaration_month')->comment('Tháng kê khai thực tế (YYYY-MM)');
            $table->unsignedBigInteger('declaration_set_by')->nullable()->after('declaration_month')->comment('User ID người chọn declaration_month');
            $table->timestamp('declaration_set_at')->nullable()->after('declaration_set_by')->comment('Thời điểm chọn declaration_month');
            $table->text('declaration_override_reason')->nullable()->after('declaration_set_at')->comment('Lý do thay đổi declaration_month (nếu khác suggested)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_change_records', function (Blueprint $table) {
            $table->dropColumn([
                'suggested_declaration_month',
                'declaration_month',
                'declaration_set_by',
                'declaration_set_at',
                'declaration_override_reason',
            ]);
        });
    }
};
