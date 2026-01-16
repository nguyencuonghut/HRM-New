<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Notifications\EmployeeImportCompletedNotification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportEmployeesFromExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $disk;
    protected $filePath;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($disk, $filePath, $userId)
    {
        $this->disk = $disk;
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $user = User::find($this->userId);

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        try {
            $disk = Storage::disk($this->disk);

            Log::info('Starting import', [
                'disk' => $this->disk,
                'disk_root' => config("filesystems.disks.{$this->disk}.root"),
                'filePath' => $this->filePath,
                'exists' => $disk->exists($this->filePath),
                'resolved_path' => $disk->path($this->filePath),
            ]);

            if (!$disk->exists($this->filePath)) {
                throw new \Exception(
                    "File [{$disk->path($this->filePath)}] does not exist and can therefore not be imported."
                );
            }

            $import = new \App\Imports\EmployeeImport();
            Excel::import($import, $disk->path($this->filePath));

            $successCount = $import->successCount ?? \App\Imports\EmployeeImport::$successCount ?? 0;
            $errorCount   = $import->errorCount ?? \App\Imports\EmployeeImport::$errorCount ?? 0;
            $errors       = $import->errors ?? \App\Imports\EmployeeImport::$errors ?? [];
        } catch (\Exception $e) {
            Log::error('ImportEmployeesFromExcelJob error: ' . $e->getMessage());
            $errorCount++;
            $errors[] = $e->getMessage();
        }

        $total = $successCount + $errorCount;

        $user->notify(
            new EmployeeImportCompletedNotification(
                $total,
                $successCount,
                $errorCount
            )
        );
    }

}
