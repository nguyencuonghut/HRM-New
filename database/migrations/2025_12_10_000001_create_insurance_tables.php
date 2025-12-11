<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bảng insurance_participations (Lịch sử tham gia BH)
        Schema::create('insurance_participations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id')->index();

            // Thông tin tham gia
            $table->date('participation_start_date'); // Ngày bắt đầu tham gia
            $table->date('participation_end_date')->nullable(); // Ngày kết thúc (null = đang tham gia)

            // Loại BH tham gia
            $table->boolean('has_social_insurance')->default(false); // BHXH
            $table->boolean('has_health_insurance')->default(false); // BHYT
            $table->boolean('has_unemployment_insurance')->default(false); // BHTN

            // Mức lương BH (snapshot từ Contract/Appendix)
            $table->decimal('insurance_salary', 15, 2); // Lương đóng BH
            $table->uuid('contract_id')->nullable()->index(); // Contract gốc
            $table->uuid('contract_appendix_id')->nullable()->index(); // Appendix (nếu có thay đổi)

            // Trạng thái
            $table->enum('status', ['ACTIVE', 'SUSPENDED', 'TERMINATED'])->default('ACTIVE')->index();

            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('contract_id')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('contract_appendix_id')->references('id')->on('contract_appendixes')->nullOnDelete();
        });

        // 2. Bảng insurance_monthly_reports (Báo cáo tháng)
        Schema::create('insurance_monthly_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->integer('year')->index();
            $table->integer('month')->index();

            // Summary data (tự động tính)
            $table->integer('total_increase')->default(0); // Tổng TĂNG
            $table->integer('total_decrease')->default(0); // Tổng GIẢM
            $table->integer('total_adjust')->default(0); // Tổng ĐIỀU CHỈNH

            $table->integer('approved_increase')->default(0); // Đã duyệt TĂNG
            $table->integer('approved_decrease')->default(0); // Đã duyệt GIẢM
            $table->integer('approved_adjust')->default(0); // Đã duyệt ĐIỀU CHỈNH

            $table->decimal('total_insurance_salary', 15, 2)->default(0);

            // File exports
            $table->string('export_file_path')->nullable(); // Đường dẫn file Excel
            $table->timestamp('exported_at')->nullable();
            $table->unsignedBigInteger('exported_by')->nullable();

            // Trạng thái báo cáo
            $table->enum('status', ['DRAFT', 'FINALIZED'])->default('DRAFT')->index();
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();

            $table->text('notes')->nullable(); // Ghi chú chung

            $table->timestamps();

            $table->unique(['year', 'month']); // Mỗi tháng chỉ có 1 báo cáo
        });

        // 3. Bảng insurance_change_records (Chi tiết từng thay đổi - phải duyệt)
        Schema::create('insurance_change_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id')->index(); // Thuộc báo cáo tháng nào
            $table->uuid('employee_id')->index();

            // Loại thay đổi
            $table->enum('change_type', ['INCREASE', 'DECREASE', 'ADJUST'])->index();

            // Thông tin BH
            $table->decimal('insurance_salary', 15, 2); // Lương BH
            $table->boolean('has_social_insurance')->default(false);
            $table->boolean('has_health_insurance')->default(false);
            $table->boolean('has_unemployment_insurance')->default(false);

            // Lý do thay đổi (tự động phát hiện)
            $table->enum('auto_reason', [
                'NEW_HIRE', // Nhân viên mới
                'TERMINATION', // Nghỉ việc
                'LONG_ABSENCE', // Nghỉ dài >30 ngày
                'SALARY_CHANGE', // Thay đổi lương
                'RETURN_TO_WORK', // Quay lại làm việc
                'OTHER'
            ])->nullable();

            $table->text('system_notes')->nullable(); // Ghi chú tự động của hệ thống

            // Ngày có hiệu lực
            $table->date('effective_date');

            // Liên kết nguồn
            $table->uuid('contract_id')->nullable();
            $table->uuid('contract_appendix_id')->nullable();
            $table->uuid('leave_request_id')->nullable(); // Nếu do nghỉ phép

            // DUYỆT BỞI ADMIN
            $table->enum('approval_status', ['PENDING', 'APPROVED', 'REJECTED', 'ADJUSTED'])->default('PENDING')->index();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_notes')->nullable(); // Admin ghi chú khi duyệt

            // Admin có thể SỬA số liệu
            $table->decimal('adjusted_salary', 15, 2)->nullable(); // Lương sau khi Admin sửa
            $table->text('adjustment_reason')->nullable(); // Lý do Admin sửa

            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('insurance_monthly_reports')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('contract_id')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('contract_appendix_id')->references('id')->on('contract_appendixes')->nullOnDelete();
            $table->foreign('leave_request_id')->references('id')->on('leave_requests')->nullOnDelete();
        });

        // 4. Bảng employee_absences (Nghỉ dài hạn ảnh hưởng BH)
        Schema::create('employee_absences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id')->index();

            $table->enum('absence_type', [
                'MATERNITY', // Thai sản (auto từ Leave)
                'SICK_LONG', // Ốm dài hạn >30 ngày
                'UNPAID_LONG', // Nghỉ không lương >30 ngày
                'MILITARY', // Nghĩa vụ quân sự
                'STUDY', // Học tập dài hạn
                'OTHER'
            ])->index();

            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = chưa xác định
            $table->integer('duration_days')->nullable(); // Số ngày nghỉ

            $table->boolean('affects_insurance')->default(true); // Ảnh hưởng BH (>30 ngày)
            $table->text('reason')->nullable();

            // Liên kết với Leave Request (nếu có)
            $table->uuid('leave_request_id')->nullable()->index();

            $table->enum('status', ['PENDING', 'APPROVED', 'ACTIVE', 'ENDED'])->default('PENDING')->index();

            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('leave_request_id')->references('id')->on('leave_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_absences');
        Schema::dropIfExists('insurance_change_records');
        Schema::dropIfExists('insurance_monthly_reports');
        Schema::dropIfExists('insurance_participations');
    }
};
