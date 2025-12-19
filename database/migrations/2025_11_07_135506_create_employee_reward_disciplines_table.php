<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_reward_disciplines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('type'); // REWARD | DISCIPLINE
            $table->string('category'); // BONUS, COMMENDATION, WARNING, etc.
            $table->string('decision_no')->unique(); // Số quyết định
            $table->date('decision_date'); // Ngày ra quyết định
            $table->date('effective_date'); // Ngày có hiệu lực
            $table->decimal('amount', 15, 2)->nullable(); // Số tiền thưởng/phạt
            $table->text('description'); // Mô tả chi tiết
            $table->text('note')->nullable(); // Ghi chú thêm
            $table->json('evidence_files')->nullable(); // Files đính kèm
            $table->foreignUuid('issued_by')->nullable()->constrained('employees')->onDelete('set null'); // Người ký quyết định (HEAD/DEPUTY)
            $table->string('status')->default('DRAFT'); // DRAFT | ACTIVE
            $table->foreignUuid('related_contract_id')->nullable()->constrained('contracts')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['employee_id', 'type']);
            $table->index(['employee_id', 'effective_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_reward_disciplines');
    }
};
