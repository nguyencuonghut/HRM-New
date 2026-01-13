<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Purpose: Create master table for 5 insurance components
     * Components:
     * - RETIREMENT_SURVIVOR: Hưu trí và tử tuất (22%)
     * - SICKNESS_MATERNITY: Ốm đau và thai sản (3%)
     * - OCC_ACCIDENT_DISEASE: TNLĐ-BNN (0.5%)
     * - UNEMPLOYMENT: Bảo hiểm thất nghiệp (2%)
     * - HEALTH: Bảo hiểm y tế (4.5%)
     */
    public function up(): void
    {
        Schema::create('insurance_components', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->index()->comment('RETIREMENT_SURVIVOR, SICKNESS_MATERNITY, etc.');
            $table->string('name_vi')->comment('Tên tiếng Việt');
            $table->decimal('default_rate_total', 8, 5)->comment('Tỷ lệ đóng mặc định (0.22000 = 22%)');
            $table->boolean('is_active')->default(true)->index()->comment('Component có đang hoạt động');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('insurance_components');
        Schema::enableForeignKeyConstraints();
    }
};
