<?php

namespace App\Services;

use App\Models\InsuranceChangeRecord;
use App\Models\InsuranceMonthlyReport;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Storage;

class InsuranceExportService implements WithMultipleSheets
{
    protected InsuranceMonthlyReport $report;
    protected array $increaseRecords;
    protected array $decreaseRecords;

    public function __construct(InsuranceMonthlyReport $report)
    {
        $this->report = $report;

        // Get approved + adjusted records for export
        $this->increaseRecords = InsuranceChangeRecord::where('report_id', $report->id)
            ->where('change_type', InsuranceChangeRecord::TYPE_INCREASE)
            ->forExport()
            ->with(['employee'])
            ->orderBy('effective_date')
            ->get()
            ->toArray();

        $this->decreaseRecords = InsuranceChangeRecord::where('report_id', $report->id)
            ->where('change_type', InsuranceChangeRecord::TYPE_DECREASE)
            ->forExport()
            ->with(['employee'])
            ->orderBy('effective_date')
            ->get()
            ->toArray();
    }

    /**
     * Return sheets array
     */
    public function sheets(): array
    {
        return [
            new IncreaseSheet($this->report, $this->increaseRecords),
            new DecreaseSheet($this->report, $this->decreaseRecords),
        ];
    }

    /**
     * Export to Excel file and save
     */
    public static function exportToFile(InsuranceMonthlyReport $report): string
    {
        if (!$report->isFinalized()) {
            throw new \Exception('Chỉ có thể xuất báo cáo đã hoàn tất');
        }

        $fileName = "BaoCao_BHXH_{$report->month}_{$report->year}_" . Carbon::now()->format('YmdHis') . ".xlsx";
        $filePath = "exports/insurance/{$report->year}/{$fileName}";

        // Use Laravel Excel to generate file
        \Maatwebsite\Excel\Facades\Excel::store(
            new self($report),
            $filePath,
            'local'
        );

        // Update report with export info
        $report->update([
            'export_file_path' => $filePath,
            'exported_at' => now(),
            'exported_by' => auth()->id(),
        ]);

        return $filePath;
    }
}

/**
 * Sheet 1: TĂNG LAO ĐỘNG
 */
class IncreaseSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected InsuranceMonthlyReport $report;
    protected array $records;

    public function __construct(InsuranceMonthlyReport $report, array $records)
    {
        $this->report = $report;
        $this->records = $records;
    }

    public function collection()
    {
        $data = collect();

        foreach ($this->records as $index => $record) {
            $employee = $record['employee'];

            $data->push([
                $index + 1, // STT
                $employee['employee_code'] ?? '', // Mã NV
                $employee['full_name'] ?? '', // Họ và tên
                $employee['si_number'] ?? '', // Mã số BHXH
                $this->getPosition($employee), // Cấp bậc, chức vụ
                number_format($record['insurance_salary'], 0, ',', '.'), // Lương BHXH
                '', // Phụ cấp (empty for now)
                Carbon::parse($record['effective_date'])->format('m/Y'), // Từ tháng năm
                $record['admin_notes'] ?? $record['system_notes'] ?? '', // Ghi chú
            ]);
        }

        // Add summary row
        $totalSalary = collect($this->records)->sum(function ($record) {
            return $record['adjusted_salary'] ?? $record['insurance_salary'];
        });

        $data->push([
            '',
            '',
            '',
            '',
            'TỔNG CỘNG',
            number_format($totalSalary, 0, ',', '.'),
            '',
            '',
            '',
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            ['DANH SÁCH LAO ĐỘNG THAM GIA BHXH, BHYT, BHTN, BHTNLĐ, BNN'],
            ["Số 1 tháng {$this->report->month} năm {$this->report->year}"],
            [],
            [
                'STT',
                'Mã NV',
                'Họ và tên',
                'Mã số BHXH',
                'Cấp bậc, chức vụ, chức danh nghề của lao động viêc',
                'Tiền lương',
                '',
                'Từ tháng năm',
                'Ghi chú',
            ],
            [
                '',
                '',
                '',
                '',
                '',
                'Lương BHXH',
                'Phụ cấp',
                '',
                '',
            ],
            ['', '', '', '', '', 'TĂNG LAO ĐỘNG', '', '', ''],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge cells for title
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A4:A5');
        $sheet->mergeCells('B4:B5');
        $sheet->mergeCells('C4:C5');
        $sheet->mergeCells('D4:D5');
        $sheet->mergeCells('E4:E5');
        $sheet->mergeCells('F4:G4');
        $sheet->mergeCells('H4:H5');
        $sheet->mergeCells('I4:I5');
        $sheet->mergeCells('A6:I6');

        // Center align
        $sheet->getStyle('A1:I2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:I5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:I6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Bold headers
        $sheet->getStyle('A1:I6')->getFont()->setBold(true);

        // Borders
        $lastRow = 6 + count($this->records) + 1;
        $sheet->getStyle("A4:I{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Auto size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }

    public function title(): string
    {
        return 'TĂNG';
    }

    protected function getPosition($employee): string
    {
        // Get employee's position/title
        // This would come from EmployeeAssignment or similar
        return ''; // TODO: Implement based on your employee structure
    }
}

/**
 * Sheet 2: GIẢM
 */
class DecreaseSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected InsuranceMonthlyReport $report;
    protected array $records;

    public function __construct(InsuranceMonthlyReport $report, array $records)
    {
        $this->report = $report;
        $this->records = $records;
    }

    public function collection()
    {
        $data = collect();

        foreach ($this->records as $index => $record) {
            $employee = $record['employee'];

            $data->push([
                $index + 1, // STT
                $employee['employee_code'] ?? '', // Mã NV
                $employee['full_name'] ?? '', // Họ và tên
                $employee['si_number'] ?? '', // Mã số BHXH
                $this->getPosition($employee), // Cấp bậc, chức vụ
                number_format($record['insurance_salary'], 0, ',', '.'), // Lương BHXH
                '', // Phụ cấp (empty for now)
                Carbon::parse($record['effective_date'])->format('m/Y'), // Từ tháng năm
                $record['admin_notes'] ?? $record['system_notes'] ?? '', // Ghi chú
            ]);
        }

        // Add summary row
        $totalSalary = collect($this->records)->sum(function ($record) {
            return $record['adjusted_salary'] ?? $record['insurance_salary'];
        });

        $data->push([
            '',
            '',
            '',
            '',
            'TỔNG GIẢM',
            number_format($totalSalary, 0, ',', '.'),
            '',
            '',
            '',
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            ['DANH SÁCH LAO ĐỘNG THAM GIA BHXH, BHYT, BHTN, BHTNLĐ, BNN'],
            ["Số 1 tháng {$this->report->month} năm {$this->report->year}"],
            [],
            [
                'STT',
                'Mã NV',
                'Họ và tên',
                'Mã số BHXH',
                'Cấp bậc, chức vụ, chức danh nghề của lao động viêc',
                'Tiền lương',
                '',
                'Từ tháng năm',
                'Ghi chú',
            ],
            [
                '',
                '',
                '',
                '',
                '',
                'Lương BHXH',
                'Phụ cấp',
                '',
                '',
            ],
            ['', '', '', '', '', 'GIẢM', '', '', ''],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge cells for title
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A4:A5');
        $sheet->mergeCells('B4:B5');
        $sheet->mergeCells('C4:C5');
        $sheet->mergeCells('D4:D5');
        $sheet->mergeCells('E4:E5');
        $sheet->mergeCells('F4:G4');
        $sheet->mergeCells('H4:H5');
        $sheet->mergeCells('I4:I5');
        $sheet->mergeCells('A6:I6');

        // Center align
        $sheet->getStyle('A1:I2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:I5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:I6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Bold headers
        $sheet->getStyle('A1:I6')->getFont()->setBold(true);

        // Borders
        $lastRow = 6 + count($this->records) + 1;
        $sheet->getStyle("A4:I{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Auto size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }

    public function title(): string
    {
        return 'GIẢM';
    }

    protected function getPosition($employee): string
    {
        // Get employee's position/title
        return ''; // TODO: Implement based on your employee structure
    }
}
