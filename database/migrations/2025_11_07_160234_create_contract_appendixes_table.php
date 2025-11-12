<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contract_appendixes', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('contract_id')->index();
            $t->string('appendix_no')->index();           // Số PL (duy nhất theo contract)
            $t->enum('appendix_type', [                   // Nhóm thay đổi
                'SALARY', 'ALLOWANCE', 'POSITION', 'DEPARTMENT',
                'WORKING_TERMS', 'EXTENSION', 'OTHER'
            ])->index();
            $t->enum('source', ['LEGACY','WORKFLOW'])->default('WORKFLOW')->index();
            $t->string('title')->nullable();              // Tiêu đề ngắn gọn
            $t->text('summary')->nullable();              // Mô tả thay đổi

            // Thời hạn hiệu lực
            $t->date('effective_date');                   // Ngày hiệu lực
            $t->date('end_date')->nullable();             // Nếu có thời hạn

            // Trạng thái
            $t->enum('status', ['DRAFT','PENDING_APPROVAL','ACTIVE','REJECTED','CANCELLED'])
              ->default('DRAFT')->index();
            $t->uuid('approver_id')->nullable()->index();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('rejected_at')->nullable();
            $t->string('approval_note')->nullable();

            // Payload thay đổi (snapshot từng phần, để generic)
            $t->unsignedBigInteger('base_salary')->nullable();
            $t->unsignedBigInteger('insurance_salary')->nullable();
            $t->unsignedBigInteger('position_allowance')->nullable();
            $t->json('other_allowances')->nullable();     // [{name,amount}]

            $t->uuid('department_id')->nullable()->index(); // Điều chuyển đơn vị
            $t->uuid('position_id')->nullable()->index();   // Điều chỉnh chức danh
            $t->string('working_time')->nullable();         // Ví dụ: "T2–T6 8:00–17:00"
            $t->string('work_location')->nullable();

            $t->string('generated_pdf_path')->nullable();          // storage path file PDF sinh ra

            $t->text('note')->nullable();

            $t->timestamps();

            $t->unique(['contract_id','appendix_no'], 'uq_contract_appendix_no');
        });

        Schema::create('contract_appendix_attachments', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('appendix_id')->index();
            $t->string('file_name');
            $t->string('file_path');
            $t->unsignedBigInteger('file_size')->default(0);
            $t->string('mime_type')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('contract_appendix_attachments');
        Schema::dropIfExists('contract_appendixes');
    }
};
