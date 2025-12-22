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
        Schema::create('employee_employments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id')->index();

            $table->date('start_date')->index()->comment('Ngày bắt đầu đợt làm việc');
            $table->date('end_date')->nullable()->index()->comment('Ngày kết thúc (null = đang làm)');

            $table->enum('end_reason', [
                'RESIGN',           // Nghỉ việc tự nguyện
                'TERMINATION',      // Sa thải
                'CONTRACT_END',     // Hết hạn hợp đồng
                'LAYOFF',           // Cho thôi việc
                'RETIREMENT',       // Nghỉ hưu
                'MATERNITY_LEAVE',  // Nghỉ sinh
                'REHIRE',           // Tái tuyển dụng (end_date của đợt cũ)
                'OTHER'
            ])->nullable()->index()->comment('Lý do kết thúc');

            $table->text('note')->nullable();

            // Flag is_current để query nhanh (có thể đồng bộ = resolver)
            $table->boolean('is_current')->default(false)->index();

            /**
             * MySQL-safe unique current:
             * - current_unique_flag = 1 nếu end_date IS NULL (đang làm)
             * - NULL nếu đã kết thúc
             * => UNIQUE(employee_id, current_unique_flag) đảm bảo mỗi employee chỉ có 1 row end_date NULL.
             */
            $table->unsignedTinyInteger('current_unique_flag')
                ->storedAs("CASE WHEN end_date IS NULL THEN 1 ELSE NULL END");

            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');

            // Unique constraint: only one current employment per employee (MySQL-safe)
            $table->unique(['employee_id', 'current_unique_flag'], 'uq_employee_one_current_employment');
        });

        // Add optional foreign key to contracts
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'employment_id')) {
                $table->uuid('employment_id')->nullable()->after('employee_id')->index()->comment('Thuộc đợt làm việc nào');

                $table->foreign('employment_id')
                    ->references('id')
                    ->on('employee_employments')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'employment_id')) {
                $table->dropForeign(['employment_id']);
                $table->dropColumn('employment_id');
            }
        });

        Schema::dropIfExists('employee_employments');
    }
};
