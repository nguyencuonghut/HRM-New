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

            $table->date('start_date')->comment('Ngày bắt đầu đợt làm việc này');
            $table->date('end_date')->nullable()->comment('Ngày kết thúc (null = đang làm)');

            $table->enum('end_reason', [
                'RESIGN',           // Nghỉ việc tự nguyện
                'TERMINATION',      // Sa thải
                'CONTRACT_END',     // Hết hạn hợp đồng
                'LAYOFF',           // Cho thôi việc
                'RETIREMENT',       // Nghỉ hưu
                'MATERNITY_LEAVE',  // Nghỉ sinh
                'REHIRE',           // Tái tuyển dụng (end_date của đợt cũ)
                'OTHER'
            ])->nullable()->comment('Lý do kết thúc');

            $table->boolean('is_current')->default(true)->index()->comment('Đợt làm việc hiện tại?');

            $table->text('note')->nullable()->comment('Ghi chú');

            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');

            // Indexes
            $table->index(['employee_id', 'is_current']);
            $table->index(['employee_id', 'start_date']);

            // Constraint: only one current employment per employee
            $table->unique(['employee_id', 'is_current'], 'unique_current_employment')
                ->where('is_current', true);
        });

        // Add optional foreign key to contracts
        Schema::table('contracts', function (Blueprint $table) {
            $table->uuid('employment_id')->nullable()->after('employee_id')->comment('Thuộc đợt làm việc nào');

            $table->foreign('employment_id')
                ->references('id')
                ->on('employee_employments')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['employment_id']);
            $table->dropColumn('employment_id');
        });

        Schema::dropIfExists('employee_employments');
    }
};
