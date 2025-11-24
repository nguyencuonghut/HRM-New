<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contract_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contract_id')->index();
            $table->enum('level', ['MANAGER', 'DIRECTOR'])->index(); // Cấp phê duyệt
            $table->unsignedTinyInteger('order')->default(1); // Thứ tự: 1=Manager, 2=Director
            $table->uuid('approver_id')->nullable()->index(); // Người được giao duyệt (null = chưa xác định)
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING')->index();
            $table->text('comments')->nullable(); // Ý kiến phê duyệt
            $table->timestamp('approved_at')->nullable(); // Thời điểm duyệt/từ chối
            $table->timestamps();

            // Index tổ hợp để query nhanh
            $table->index(['contract_id', 'level', 'status']);
            $table->index(['approver_id', 'status']); // Query: contracts đang chờ tôi duyệt
        });
    }

    public function down(): void {
        Schema::dropIfExists('contract_approvals');
    }
};
