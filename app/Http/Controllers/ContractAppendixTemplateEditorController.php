<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContractAppendixTemplateResource;
use App\Models\ContractAppendixTemplate;
use App\Services\DocxMergeService;
use App\Services\DynamicPlaceholderResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use PhpOffice\PhpWord\IOFactory;

class ContractAppendixTemplateEditorController extends Controller
{
    use AuthorizesRequests;

    public function editor(ContractAppendixTemplate $template)
    {
        $this->authorize('update', $template);

        return Inertia::render('ContractAppendixTemplateEditor', [
            'template'   => new ContractAppendixTemplateResource($template),
            'sampleData' => $this->getMockDataForAppendixType($template->appendix_type),
        ]);
    }

    /**
     * Preview PDF từ DOCX template với mock data
     */
    public function docxPreview(ContractAppendixTemplate $template, DocxMergeService $docxMerge)
    {
        $this->authorize('view', $template);

        if (!$template->body_path) {
            abort(404, 'Template chưa có file DOCX.');
        }

        $docxPath = Storage::disk('public')->path($template->body_path);
        if (!file_exists($docxPath)) {
            abort(404, 'File DOCX không tồn tại.');
        }

        // 1) Create mock ContractAppendix object with relationships
        $mockAppendix = $this->createMockAppendix($template);

        // 2) Use DynamicPlaceholderResolverService to resolve placeholders based on mappings
        $resolver = app(DynamicPlaceholderResolverService::class);
        $mergeData = $resolver->resolve($mockAppendix, $template);

        // Prepare output path for merged DOCX
        $mergedDocxPath = storage_path('app/temp/' . uniqid('appendix_preview_') . '.docx');
        if (!file_exists(dirname($mergedDocxPath))) {
            mkdir(dirname($mergedDocxPath), 0755, true);
        }

        // Merge DOCX với resolved placeholders
        $docxMerge->merge($docxPath, $mergeData, $mergedDocxPath);

        // Convert to PDF using LibreOffice
        $pdfPath = $this->convertToPdfViaLibreOffice($mergedDocxPath);

        // Cleanup merged DOCX file
        if (file_exists($mergedDocxPath)) {
            @unlink($mergedDocxPath);
        }

        if (!$pdfPath || !file_exists($pdfPath)) {
            abort(500, 'Không thể convert DOCX sang PDF.');
        }

        // Return PDF (will auto-delete after send)
        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Create mock ContractAppendix object with full relationships
     */
    private function createMockAppendix(ContractAppendixTemplate $template): object
    {
        // Mock Contract first
        $mockContract = new \stdClass();
        $mockContract->id = 'mock-contract-id';
        $mockContract->contract_number = 'HD-2025-001';
        $mockContract->contract_type = 'INDEFINITE';
        $mockContract->start_date = '2025-01-01';
        $mockContract->end_date = null;
        $mockContract->sign_date = now()->format('Y-m-d');
        $mockContract->probation_end_date = '2025-03-01';
        $mockContract->base_salary = 15000000;
        $mockContract->insurance_salary = 12000000;
        $mockContract->position_allowance = 2000000;
        $mockContract->social_insurance = true;
        $mockContract->health_insurance = true;
        $mockContract->unemployment_insurance = true;
        $mockContract->working_time = 'T2-T6, 08:00-17:00';
        $mockContract->work_location = 'Văn phòng Hà Nội';
        $mockContract->status = 'ACTIVE';
        $mockContract->source = 'SYSTEM';

        // Mock Employee with full info - match actual Employee model field names
        $mockEmployee = new \stdClass();
        $mockEmployee->full_name = 'Nguyễn Văn A';
        $mockEmployee->employee_code = 'NV001';
        $mockEmployee->phone = '0912345678';
        $mockEmployee->emergency_contact_phone = '0987654321';
        $mockEmployee->personal_email = 'nguyenvana@example.com';
        $mockEmployee->company_email = 'nguyenvana@hongha.com';
        $mockEmployee->cccd = '001234567890';
        $mockEmployee->cccd_issued_on = '2015-01-15';
        $mockEmployee->cccd_issued_by = 'Công an TP Hà Nội';
        $mockEmployee->si_number = 'SX123456789';
        $mockEmployee->dob = '1990-01-01';
        $mockEmployee->gender = 'MALE';
        $mockEmployee->marital_status = 'SINGLE';
        $mockEmployee->address_street = 'Số 10, Đường Láng Hạ';
        $mockEmployee->temp_address_street = '456 Đường Trần Duy Hưng';
        $mockEmployee->hire_date = '2020-01-15';
        $mockEmployee->status = 'ACTIVE';

        // Mock ward/province for addresses
        $mockProvince = new \stdClass();
        $mockProvince->name = 'Hà Nội';
        $mockProvince->full_name = 'Thành phố Hà Nội';

        $mockWard = new \stdClass();
        $mockWard->name = 'Đống Đa';
        $mockWard->full_name = 'Quận Đống Đa';
        $mockWard->province = $mockProvince;
        $mockEmployee->ward = $mockWard;

        $mockTempWard = new \stdClass();
        $mockTempWard->name = 'Cầu Giấy';
        $mockTempWard->full_name = 'Quận Cầu Giấy';
        $mockTempWard->province = $mockProvince;
        $mockEmployee->tempWard = $mockTempWard;

        // Mock Department
        $mockDepartment = new \stdClass();
        $mockDepartment->name = 'Phòng Kỹ Thuật';
        $mockDepartment->code = 'KT';
        $mockDepartment->type = 'DEPARTMENT';
        $mockDepartment->is_active = true;

        // Mock Position
        $mockPosition = new \stdClass();
        $mockPosition->title = 'Kỹ Sư Phần Mềm';
        $mockPosition->level = 'Senior';
        $mockPosition->insurance_base_salary = 12000000;
        $mockPosition->position_salary = 15000000;
        $mockPosition->competency_salary = 3000000;
        $mockPosition->allowance = 2000000;

        // Assign to contract
        $mockContract->employee = $mockEmployee;
        $mockContract->department = $mockDepartment;
        $mockContract->position = $mockPosition;

        // Mock ContractAppendix
        $mockAppendix = new \stdClass();
        $mockAppendix->id = 'mock-appendix-id';
        $mockAppendix->appendix_number = 'PL-2025-001';
        $mockAppendix->appendix_type = $template->appendix_type;
        $mockAppendix->effective_date = now()->addMonth()->format('Y-m-d');
        $mockAppendix->sign_date = now()->format('Y-m-d');
        $mockAppendix->contract = $mockContract;

        // Type-specific mock data in old_terms / new_terms
        switch ($template->appendix_type) {
            case 'SALARY':
                $mockAppendix->old_terms = (object)[
                    'base_salary' => 15000000,
                    'insurance_salary' => 12000000,
                    'position_allowance' => 2000000,
                ];
                $mockAppendix->new_terms = (object)[
                    'base_salary' => 18000000,
                    'insurance_salary' => 15000000,
                    'position_allowance' => 3000000,
                ];
                $mockAppendix->adjustment_reason = 'Tăng lương định kỳ theo quy định';
                break;

            case 'ALLOWANCE':
                $mockAppendix->old_terms = (object)['allowance_amount' => 1500000];
                $mockAppendix->new_terms = (object)['allowance_amount' => 2000000];
                $mockAppendix->allowance_name = 'Phụ cấp xăng xe';
                break;

            case 'POSITION':
                $mockAppendix->old_position = 'Kỹ sư phần mềm';
                $mockAppendix->new_position = 'Trưởng nhóm phát triển';
                break;

            case 'DEPARTMENT':
                $mockAppendix->old_department = 'Phòng Kỹ Thuật';
                $mockAppendix->new_department = 'Phòng R&D';
                break;
        }

        return $mockAppendix;
    }

    /**
     * Generate mock placeholder data cho preview (DEPRECATED - use createMockAppendix + resolver)
     */
    private function getMockPlaceholderData(ContractAppendixTemplate $template): array
    {
        // Base employee/contract data
        $baseData = [
            'employee_full_name' => 'Nguyễn Văn A',
            'employee_code' => 'NV001',
            'employee_phone' => '0912345678',
            'employee_cccd' => '001234567890',
            'employee_dob' => '01/01/1990',
            'employee_address' => 'Số 10, Đường Láng Hạ, Đống Đa, Hà Nội',
            'employee_position' => 'Kỹ sư phần mềm',
            'employee_department' => 'Phòng Công Nghệ',
            'contract_number' => 'HĐ-2025-001',
            'contract_type' => 'Hợp đồng không xác định thời hạn',
            'contract_start_date' => '01/01/2025',
            'contract_signed_date' => '01/01/2025',
            'appendix_number' => 'PL-2025-001',
            'effective_date' => '01/03/2025',
            'signed_date' => '01/03/2025',
            'current_date' => now()->format('d/m/Y'),
            'current_year' => now()->year,
            'company_name' => 'CÔNG TY CỔ PHẦN HỒNG HÀ',
            'company_representative' => 'Nguyễn Văn B',
            'company_representative_title' => 'GIÁM ĐỐC',
        ];

        // Type-specific mock data
        switch ($template->appendix_type) {
            case 'SALARY':
                $typeData = [
                    'old_base_salary' => '15.000.000',
                    'old_base_salary_words' => 'Mười lăm triệu đồng chẵn.',
                    'new_base_salary' => '18.000.000',
                    'new_base_salary_words' => 'Mười tám triệu đồng chẵn.',
                    'old_insurance_salary' => '12.000.000',
                    'new_insurance_salary' => '15.000.000',
                    'old_position_allowance' => '2.000.000',
                    'new_position_allowance' => '3.000.000',
                    'old_total_salary' => '17.000.000',
                    'new_total_salary' => '21.000.000',
                    'salary_increase_amount' => '4.000.000',
                    'salary_increase_percent' => '23,5',
                ];
                break;

            case 'ALLOWANCE':
                $typeData = [
                    'allowance_name' => 'Phụ cấp xăng xe',
                    'old_allowance_amount' => '1.500.000',
                    'old_allowance_amount_words' => 'Một triệu năm trăm nghìn đồng chẵn.',
                    'new_allowance_amount' => '2.000.000',
                    'new_allowance_amount_words' => 'Hai triệu đồng chẵn.',
                ];
                break;

            case 'POSITION':
                $typeData = [
                    'old_position' => 'Kỹ sư phần mềm',
                    'new_position' => 'Trưởng nhóm phát triển',
                    'position_change_reason' => 'Thăng tiến do hoàn thành xuất sắc nhiệm vụ',
                ];
                break;

            case 'DEPARTMENT':
                $typeData = [
                    'old_department' => 'Phòng Công Nghệ',
                    'new_department' => 'Phòng Nghiên Cứu & Phát Triển',
                    'department_change_reason' => 'Điều chuyển theo nhu cầu tổ chức',
                ];
                break;

            case 'WORKING_TERMS':
                $typeData = [
                    'old_working_time' => 'T2-T6, 08:00-17:00',
                    'new_working_time' => 'T2-T6, 09:00-18:00',
                    'old_work_location' => 'Văn phòng Hà Nội',
                    'new_work_location' => 'Văn phòng Ninh Bình',
                ];
                break;

            case 'EXTENSION':
                $typeData = [
                    'old_end_date' => '31/12/2025',
                    'new_end_date' => '31/12/2026',
                    'extension_duration_months' => '12',
                    'extension_reason' => 'Gia hạn theo nhu cầu công việc',
                ];
                break;

            default:
                $typeData = [
                    'notes' => 'Nội dung phụ lục khác',
                ];
        }

        return array_merge($baseData, $typeData);
    }

    /**
     * Get mock data cho Inertia page (structured data)
     */
    private function getMockDataForAppendixType(string $type): array
    {
        $baseData = [
            'employee' => [
                'full_name' => 'Nguyễn Văn A',
                'employee_code' => 'NV001',
                'phone' => '0912345678',
                'cccd' => '001234567890',
                'dob' => '01/01/1990',
            ],
            'contract' => [
                'contract_number' => 'HĐ-2025-001',
                'type' => 'INDEFINITE',
                'start_date' => '01/01/2025',
            ],
            'appendix' => [
                'appendix_number' => 'PL-2025-001',
                'effective_date' => '01/03/2025',
            ],
        ];

        switch ($type) {
            case 'SALARY':
                $baseData['changes'] = [
                    'old_base_salary' => 15000000,
                    'new_base_salary' => 18000000,
                    'old_insurance_salary' => 12000000,
                    'new_insurance_salary' => 15000000,
                ];
                break;

            case 'POSITION':
                $baseData['changes'] = [
                    'old_position' => 'Kỹ sư phần mềm',
                    'new_position' => 'Trưởng nhóm phát triển',
                ];
                break;

            case 'DEPARTMENT':
                $baseData['changes'] = [
                    'old_department' => 'Phòng Công Nghệ',
                    'new_department' => 'Phòng Nghiên Cứu & Phát Triển',
                ];
                break;
        }

        return $baseData;
    }

    /**
     * Convert DOCX to PDF using LibreOffice
     */
    private function convertToPdfViaLibreOffice(string $docxPath): ?string
    {
        $outputDir = dirname($docxPath);
        $pdfPath = $outputDir . '/' . pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';

        // Find LibreOffice binary
        $libreOfficePath = $this->findLibreOfficePath();
        if (!$libreOfficePath) {
            Log::error('LibreOffice not found');
            return null;
        }

        // Execute conversion
        $command = sprintf(
            '%s --headless --convert-to pdf:writer_pdf_Export --outdir %s %s 2>&1',
            escapeshellarg($libreOfficePath),
            escapeshellarg($outputDir),
            escapeshellarg($docxPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($pdfPath)) {
            Log::error('LibreOffice conversion failed', [
                'command' => $command,
                'output' => $output,
                'return_code' => $returnCode,
            ]);
            return null;
        }

        return $pdfPath;
    }

    /**
     * Auto-detect LibreOffice installation path
     */
    private function findLibreOfficePath(): ?string
    {
        $possiblePaths = [
            '/usr/bin/libreoffice',           // Linux standard
            '/usr/bin/soffice',                // Alternative Linux
            '/usr/local/bin/libreoffice',      // Linux custom install
            '/opt/libreoffice/program/soffice', // Custom install
            '/Applications/LibreOffice.app/Contents/MacOS/soffice', // macOS
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe', // Windows
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try using `which` command
        $which = trim(shell_exec('which libreoffice 2>/dev/null') ?? '');
        if ($which && file_exists($which)) {
            return $which;
        }

        return null;
    }
}
