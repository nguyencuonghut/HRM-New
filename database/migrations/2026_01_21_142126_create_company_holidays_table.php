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
        Schema::create('company_holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->date('holiday_date')->index();
            $table->unsignedInteger('year')->index();
            $table->boolean('is_compensated')->default(false)->comment('Có nghỉ bù hay không');
            $table->date('compensated_date')->nullable()->comment('Ngày nghỉ bù');
            $table->boolean('is_recurring')->default(false)->comment('Lặp lại hàng năm');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['year', 'holiday_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_holidays');
    }
};
