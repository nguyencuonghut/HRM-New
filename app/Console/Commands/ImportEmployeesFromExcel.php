<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeeImport;

class ImportEmployeesFromExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example: php artisan employees:import {path-to-file}
     */
    protected $signature = 'employees:import {file : Đường dẫn file Excel}';

    /**
     * The console command description.
     */
    protected $description = 'Import employees from an Excel file (Laravel 12, maatwebsite/excel 3.x)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');
        if (!file_exists($file)) {
            $this->error('File không tồn tại: ' . $file);
            return 1;
        }

        $this->info('Bắt đầu import nhân viên từ file: ' . $file);
        $import = new EmployeeImport();
        Excel::import($import, $file);

        $this->info('Import hoàn thành.');
        $this->info('Số bản ghi thành công: ' . EmployeeImport::$successCount);
        $this->info('Số bản ghi lỗi: ' . EmployeeImport::$errorCount);
        if (EmployeeImport::$errorCount > 0) {
            $this->warn('Chi tiết lỗi:');
            foreach (EmployeeImport::$errors as $err) {
                $this->line($err);
            }
        }
        return 0;
    }
}
