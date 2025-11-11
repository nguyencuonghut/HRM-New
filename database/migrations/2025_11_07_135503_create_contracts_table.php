<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contracts', function (Blueprint $t) {
            $t->uuid('id')->primary();                   // PK
            $t->uuid('employee_id')->index();           // Nhân viên ký
            $t->uuid('department_id')->nullable()->index(); // Đơn vị tại thời điểm ký (snapshot)
            $t->uuid('position_id')->nullable()->index();   // Chức danh tại thời điểm ký (snapshot)

            $t->string('contract_number')->unique();    // Số HĐ
            $t->enum('contract_type', [                 // Loại HĐ
                'PROBATION','FIXED_TERM','INDEFINITE','SEASONAL','SERVICE','INTERNSHIP','PARTTIME'
            ])->index();
            $t->enum('status', [                        // Trạng thái
                'DRAFT','PENDING_APPROVAL','ACTIVE','SUSPENDED','TERMINATED','EXPIRED','CANCELLED'
            ])->default('DRAFT')->index();

            // Mốc thời gian
            $t->date('sign_date')->nullable();          // Ngày ký
            $t->date('start_date');                     // Ngày hiệu lực
            $t->date('end_date')->nullable();           // Ngày kết thúc (null nếu vô thời hạn)
            $t->date('probation_end_date')->nullable(); // Kết thúc thử việc (nếu có)

            // Thù lao (snapshot)
            $t->unsignedBigInteger('base_salary')->default(0);         // Lương cơ bản (VND)
            $t->unsignedBigInteger('insurance_salary')->default(0);    // Lương đóng BH
            $t->unsignedBigInteger('position_allowance')->default(0);  // Phụ cấp vị trí
            $t->json('other_allowances')->nullable();                  // Phụ cấp khác [{name, amount}]

            // Bảo hiểm & công
            $t->boolean('social_insurance')->default(true);  // Tham gia BHXH
            $t->boolean('health_insurance')->default(true);  // BHYT
            $t->boolean('unemployment_insurance')->default(true); // BHTN
            $t->string('work_location')->nullable();
            $t->string('working_time')->nullable();          // VD: "T2–T6, 8:00–17:00"

            // Phê duyệt
            $t->uuid('approver_id')->nullable()->index(); // Người duyệt cuối
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('rejected_at')->nullable();
            $t->text('approval_note')->nullable();

            // Chấm dứt
            $t->date('terminated_at')->nullable();
            $t->string('termination_reason')->nullable();

            // Ghi chú
            $t->text('note')->nullable();

            // Nguồn gốc & mẫu
            $t->enum('source', ['LEGACY','RECRUITMENT'])->default('LEGACY')->index();
            $t->uuid('source_id')->nullable()->index();                 // offers.id nếu từ tuyển dụng
            $t->uuid('template_id')->nullable()->index();            // contract_templates.id
            $t->string('generated_pdf_path')->nullable();          // storage path file PDF sinh ra
            $t->string('signed_file_path')->nullable();     // file scan/ký số (nếu có)
            $t->boolean('created_from_offer')->default(false)->index();

            $t->timestamps();
        });

        Schema::create('contract_attachments', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('contract_id')->index();
            $t->string('file_name');
            $t->string('file_path'); // storage path
            $t->unsignedBigInteger('file_size')->default(0);
            $t->string('mime_type')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('contract_attachments');
        Schema::dropIfExists('contracts');
    }
};
