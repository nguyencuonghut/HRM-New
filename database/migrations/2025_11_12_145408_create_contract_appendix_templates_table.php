<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_appendix_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                // Tên template, ví dụ: Phụ lục điều chỉnh lương
            $table->string('code')->unique();      // Mã template, ví dụ: PL-LUONG-01

            // Loại phụ lục: match với enum appendix_type của ContractAppendix
            $table->enum('appendix_type', [
                'SALARY',          // Điều chỉnh lương
                'ALLOWANCE',       // Điều chỉnh phụ cấp
                'POSITION',        // Điều chỉnh chức danh
                'DEPARTMENT',      // Điều chuyển đơn vị
                'WORKING_TERMS',   // Thời gian/địa điểm làm việc
                'EXTENSION',       // Gia hạn HĐ
                'OTHER',           // Khác
            ])->default('SALARY');

            // Tên view Blade sẽ dùng để render PDF, ví dụ: 'contracts.appendixes.salary'
            $table->string('blade_view')->default('contracts.appendixes.default');

            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_appendix_templates');
    }
};
