<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_benefit_payouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('employee_id')->index()->comment('Nhân viên nhận phúc lợi');
            $table->unsignedBigInteger('benefit_type_id')->index()->comment('Loại phúc lợi');
            $table->date('paid_date')->index()->comment('Ngày chi trả');
            $table->decimal('amount', 15, 2)->comment('Số tiền');
            $table->string('currency', 3)->default('VND')->comment('Đơn vị tiền tệ');
            $table->text('note')->nullable()->comment('Ghi chú');
            $table->unsignedBigInteger('paid_by')->nullable()->comment('Admin ghi nhận');
            $table->string('payment_method')->nullable()->comment('Phương thức: Tiền mặt/Chuyển khoản');
            $table->string('reference_no')->nullable()->comment('Số chứng từ/phiếu chi');
            $table->enum('source', ['MANUAL', 'IMPORT'])->default('MANUAL')->comment('Nguồn nhập');
            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('benefit_type_id')->references('id')->on('benefit_types')->onDelete('restrict');
            $table->foreign('paid_by')->references('id')->on('users')->onDelete('set null');

            // Composite indexes for reporting
            $table->index(['employee_id', 'paid_date']);
            $table->index(['benefit_type_id', 'paid_date']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_benefit_payouts');
    }
};
