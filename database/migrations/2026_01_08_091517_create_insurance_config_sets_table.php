<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Insurance Config Sets (Versioning System)
 *
 * Bộ cấu hình insurance salary system với versioning.
 * Cho phép:
 * - Quản lý nhiều phiên bản config
 * - Activate/deactivate config theo thời gian
 * - Clone từ config cũ
 * - Audit trail đầy đủ
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insurance_config_sets', function (Blueprint $table) {
            $table->id();

            // Metadata
            $table->string('code', 50)->unique()->comment('Mã config set (VD: VN_INS_2024_07)');
            $table->string('name')->comment('Tên mô tả');
            $table->text('description')->nullable()->comment('Mô tả chi tiết');

            // Status & Versioning
            $table->enum('status', ['DRAFT', 'ACTIVE', 'ARCHIVED'])
                  ->default('DRAFT')
                  ->index()
                  ->comment('Trạng thái: DRAFT=Nháp, ACTIVE=Đang áp dụng, ARCHIVED=Đã lưu trữ');

            // Effective dates
            $table->date('effective_from')->index()->comment('Ngày bắt đầu hiệu lực');
            $table->date('effective_to')->nullable()->index()->comment('Ngày kết thúc (null = vô thời hạn)');

            // Clone tracking
            $table->unsignedBigInteger('based_on_set_id')->nullable()->comment('Clone từ config set nào (nếu có)');
            $table->foreign('based_on_set_id')
                  ->references('id')
                  ->on('insurance_config_sets')
                  ->nullOnDelete();

            // Audit trail
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->text('notes')->nullable()->comment('Ghi chú');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'effective_from', 'effective_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_config_sets');
    }
};
