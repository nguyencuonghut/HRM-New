<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\Employee;
use App\Models\EmployeeInsuranceProfile;
use App\Models\PositionSalaryGrade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service: Quản lý lifecycle của EmployeeInsuranceProfile
 *
 * CRUD gián tiếp qua Contract & ContractAppendix (KHÔNG CRUD trực tiếp)
 *
 * Luồng tạo/cập nhật:
 * 1. Contract ACTIVE → createProfileFromContract()
 * 2. Appendix SALARY ACTIVE → updateProfileFromSalaryAppendix()
 * 3. Appendix POSITION ACTIVE → updateProfileFromPositionAppendix()
 * 4. Contract END → closeProfileOnContractEnd()
 * 5. Backfill legacy → backfillProfileFromLegacyContract()
 */
class EmployeeInsuranceProfileService
{
    /**
     * Tạo insurance profile từ Contract khi chuyển sang ACTIVE
     *
     * Trigger: Contract status → ACTIVE (via ContractApproved event)
     *
     * @param Contract $contract
     * @return EmployeeInsuranceProfile|null
     */
    public function createProfileFromContract(Contract $contract): ?EmployeeInsuranceProfile
    {
        // Skip nếu contract không có thông tin BHXH
        if (!$contract->insurance_salary || !$contract->position_id || !$contract->employee_id) {
            Log::warning("Cannot create insurance profile - missing required fields", [
                'contract_id' => $contract->id,
                'has_insurance_salary' => (bool) $contract->insurance_salary,
                'has_position_id' => (bool) $contract->position_id,
                'has_employee_id' => (bool) $contract->employee_id,
            ]);
            return null;
        }

        // Skip nếu employee đã có profile active cho contract này
        $existingProfile = EmployeeInsuranceProfile::where('employee_id', $contract->employee_id)
            ->whereNull('applied_to')
            ->first();

        if ($existingProfile) {
            Log::info("Employee already has active insurance profile", [
                'contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
                'existing_profile_id' => $existingProfile->id,
            ]);
            return $existingProfile;
        }

        // Detect grade từ insurance_salary và position grades
        $grade = $this->detectGradeFromSalary(
            $contract->insurance_salary,
            $contract->position_id,
            $contract->start_date
        );

        if (!$grade) {
            Log::warning("Cannot detect grade from insurance_salary", [
                'contract_id' => $contract->id,
                'insurance_salary' => $contract->insurance_salary,
                'position_id' => $contract->position_id,
            ]);
            // Fallback to grade 1
            $grade = 1;
        }

        // Xác định reason: INITIAL nếu là contract đầu tiên, POSITION_CHANGE nếu không
        $previousContracts = Contract::where('employee_id', $contract->employee_id)
            ->where('id', '!=', $contract->id)
            ->where('status', 'ACTIVE')
            ->count();

        $reason = $previousContracts > 0 ? 'POSITION_CHANGE' : 'INITIAL';

        // Tạo profile mới
        try {
            $profile = EmployeeInsuranceProfile::create([
                'employee_id' => $contract->employee_id,
                'position_id' => $contract->position_id,
                'grade' => $grade,
                'applied_from' => $contract->start_date,
                'applied_to' => null, // Đang active
                'reason' => $reason,
                'source_appendix_id' => null, // Từ contract chính, không có appendix
                'note' => "Tạo từ hợp đồng {$contract->contract_number}",
                'created_by' => auth()->id() ?? null,
            ]);

            Log::info("Created insurance profile from contract", [
                'contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
                'profile_id' => $profile->id,
                'grade' => $grade,
                'reason' => $reason,
            ]);

            return $profile;
        } catch (\Exception $e) {
            Log::error("Failed to create insurance profile from contract", [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Cập nhật insurance profile từ Appendix SALARY khi ACTIVE
     *
     * Trigger: Appendix (type=SALARY) status → ACTIVE
     *
     * @param ContractAppendix $appendix
     * @return EmployeeInsuranceProfile|null
     */
    public function updateProfileFromSalaryAppendix(ContractAppendix $appendix): ?EmployeeInsuranceProfile
    {
        // Validate appendix type
        if ($appendix->appendix_type->value !== 'SALARY') {
            Log::warning("Appendix is not SALARY type", [
                'appendix_id' => $appendix->id,
                'appendix_type' => $appendix->appendix_type->value,
            ]);
            return null;
        }

        // Skip nếu không có insurance_salary mới
        if (!$appendix->insurance_salary) {
            Log::warning("Appendix does not have insurance_salary", [
                'appendix_id' => $appendix->id,
            ]);
            return null;
        }

        $contract = $appendix->contract;
        if (!$contract || !$contract->employee_id) {
            Log::warning("Cannot get contract or employee from appendix", [
                'appendix_id' => $appendix->id,
            ]);
            return null;
        }

        // Detect new grade từ insurance_salary mới
        $newGrade = $this->detectGradeFromSalary(
            $appendix->insurance_salary,
            $contract->position_id,
            $appendix->effective_date
        );

        if (!$newGrade) {
            Log::warning("Cannot detect grade from appendix insurance_salary", [
                'appendix_id' => $appendix->id,
                'insurance_salary' => $appendix->insurance_salary,
                'position_id' => $contract->position_id,
            ]);
            return null;
        }

        // Transaction: Close current profile + Create new profile
        return DB::transaction(function () use ($contract, $appendix, $newGrade) {
            // Close current profile
            $currentProfile = EmployeeInsuranceProfile::where('employee_id', $contract->employee_id)
                ->whereNull('applied_to')
                ->first();

            if ($currentProfile) {
                $currentProfile->applied_to = Carbon::parse($appendix->effective_date)->subDay()->format('Y-m-d');
                $currentProfile->save();

                Log::info("Closed current insurance profile", [
                    'profile_id' => $currentProfile->id,
                    'closed_at' => $currentProfile->applied_to,
                ]);
            }

            // Determine reason: SENIORITY hoặc ADJUSTMENT
            $reason = 'ADJUSTMENT'; // Default
            if ($currentProfile && $newGrade > $currentProfile->grade) {
                $reason = 'SENIORITY'; // Có thể là tăng bậc theo thâm niên
            }

            // Create new profile
            $newProfile = EmployeeInsuranceProfile::create([
                'employee_id' => $contract->employee_id,
                'position_id' => $contract->position_id,
                'grade' => $newGrade,
                'applied_from' => $appendix->effective_date,
                'applied_to' => null,
                'reason' => $reason,
                'source_appendix_id' => $appendix->id,
                'note' => "Tạo từ phụ lục {$appendix->appendix_no} - Thay đổi lương BHXH",
                'created_by' => auth()->id() ?? $appendix->approver_id ?? null,
            ]);

            Log::info("Created new insurance profile from salary appendix", [
                'appendix_id' => $appendix->id,
                'profile_id' => $newProfile->id,
                'old_grade' => $currentProfile?->grade,
                'new_grade' => $newGrade,
                'reason' => $reason,
            ]);

            return $newProfile;
        });
    }

    /**
     * Cập nhật insurance profile từ Appendix POSITION khi ACTIVE
     *
     * Trigger: Appendix (type=POSITION) status → ACTIVE
     *
     * @param ContractAppendix $appendix
     * @return EmployeeInsuranceProfile|null
     */
    public function updateProfileFromPositionAppendix(ContractAppendix $appendix): ?EmployeeInsuranceProfile
    {
        // Validate appendix type
        if ($appendix->appendix_type->value !== 'POSITION') {
            Log::warning("Appendix is not POSITION type", [
                'appendix_id' => $appendix->id,
                'appendix_type' => $appendix->appendix_type->value,
            ]);
            return null;
        }

        // Skip nếu không có position_id mới
        if (!$appendix->position_id) {
            Log::warning("Appendix does not have position_id", [
                'appendix_id' => $appendix->id,
            ]);
            return null;
        }

        $contract = $appendix->contract;
        if (!$contract || !$contract->employee_id) {
            Log::warning("Cannot get contract or employee from appendix", [
                'appendix_id' => $appendix->id,
            ]);
            return null;
        }

        // Get current profile để xác định grade strategy
        $currentProfile = EmployeeInsuranceProfile::where('employee_id', $contract->employee_id)
            ->whereNull('applied_to')
            ->first();

        // Xác định grade cho position mới:
        // Option 1: Nếu appendix có insurance_salary → detect từ salary
        // Option 2: Nếu không → giữ grade hiện tại (chuyển ngang bậc)
        // Option 3: Nếu không có profile cũ → grade 1
        $newGrade = 1; // Default

        if ($appendix->insurance_salary) {
            // Option 1: Detect from salary
            $detectedGrade = $this->detectGradeFromSalary(
                $appendix->insurance_salary,
                $appendix->position_id,
                $appendix->effective_date
            );
            $newGrade = $detectedGrade ?? 1;
        } elseif ($currentProfile) {
            // Option 2: Keep current grade
            $newGrade = $currentProfile->grade;
        }

        // Determine reason: PROMOTION nếu có insurance_salary cao hơn, POSITION_CHANGE nếu không
        $reason = 'POSITION_CHANGE';
        if ($appendix->insurance_salary && $currentProfile) {
            $oldSalary = $this->calculateInsuranceSalary($currentProfile);
            if ($appendix->insurance_salary > $oldSalary) {
                $reason = 'PROMOTION';
            }
        }

        // Transaction: Close current profile + Create new profile
        return DB::transaction(function () use ($contract, $appendix, $newGrade, $reason, $currentProfile) {
            // Close current profile
            if ($currentProfile) {
                $currentProfile->applied_to = Carbon::parse($appendix->effective_date)->subDay()->format('Y-m-d');
                $currentProfile->save();

                Log::info("Closed current insurance profile", [
                    'profile_id' => $currentProfile->id,
                    'closed_at' => $currentProfile->applied_to,
                ]);
            }

            // Create new profile with new position
            $newProfile = EmployeeInsuranceProfile::create([
                'employee_id' => $contract->employee_id,
                'position_id' => $appendix->position_id,
                'grade' => $newGrade,
                'applied_from' => $appendix->effective_date,
                'applied_to' => null,
                'reason' => $reason,
                'source_appendix_id' => $appendix->id,
                'note' => "Tạo từ phụ lục {$appendix->appendix_no} - Chuyển đổi chức danh",
                'created_by' => auth()->id() ?? $appendix->approver_id ?? null,
            ]);

            Log::info("Created new insurance profile from position appendix", [
                'appendix_id' => $appendix->id,
                'profile_id' => $newProfile->id,
                'old_position_id' => $currentProfile?->position_id,
                'new_position_id' => $appendix->position_id,
                'grade' => $newGrade,
                'reason' => $reason,
            ]);

            return $newProfile;
        });
    }

    /**
     * Đóng insurance profile khi Contract kết thúc
     *
     * Trigger: Contract status → EXPIRED/CANCELLED
     *
     * @param Contract $contract
     * @return void
     */
    public function closeProfileOnContractEnd(Contract $contract): void
    {
        // Tìm profile đang active của employee
        $currentProfile = EmployeeInsuranceProfile::where('employee_id', $contract->employee_id)
            ->whereNull('applied_to')
            ->first();

        if (!$currentProfile) {
            Log::info("No active insurance profile to close", [
                'contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
            ]);
            return;
        }

        // Set applied_to = end_date hoặc terminated_at
        $endDate = $contract->terminated_at ?? $contract->end_date;
        if (!$endDate) {
            Log::warning("Contract has no end_date or terminated_at", [
                'contract_id' => $contract->id,
            ]);
            return;
        }

        try {
            $currentProfile->applied_to = $endDate;
            $currentProfile->note = ($currentProfile->note ?? '') . "\n[Đóng do hợp đồng {$contract->contract_number} kết thúc]";
            $currentProfile->save();

            Log::info("Closed insurance profile on contract end", [
                'contract_id' => $contract->id,
                'profile_id' => $currentProfile->id,
                'closed_at' => $endDate,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to close insurance profile", [
                'contract_id' => $contract->id,
                'profile_id' => $currentProfile->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Backfill insurance profile từ Contract LEGACY
     *
     * Trigger: Manual command hoặc backfill script
     *
     * @param Contract $contract
     * @return EmployeeInsuranceProfile|null
     */
    public function backfillProfileFromLegacyContract(Contract $contract): ?EmployeeInsuranceProfile
    {
        // Skip nếu contract không có thông tin BHXH
        if (!$contract->insurance_salary || !$contract->position_id || !$contract->employee_id) {
            Log::warning("Cannot backfill - missing required fields", [
                'contract_id' => $contract->id,
            ]);
            return null;
        }

        // Check nếu đã có profile cho khoảng thời gian này
        $existingProfile = EmployeeInsuranceProfile::where('employee_id', $contract->employee_id)
            ->where('applied_from', $contract->start_date)
            ->first();

        if ($existingProfile) {
            Log::info("Profile already exists for this period", [
                'contract_id' => $contract->id,
                'profile_id' => $existingProfile->id,
            ]);
            return $existingProfile;
        }

        // Detect grade
        $grade = $this->detectGradeFromSalary(
            $contract->insurance_salary,
            $contract->position_id,
            $contract->start_date
        ) ?? 1;

        // Xác định applied_to
        $appliedTo = null;
        if (in_array($contract->status, ['EXPIRED', 'CANCELLED', 'TERMINATED'])) {
            $appliedTo = $contract->terminated_at ?? $contract->end_date;
        }

        // Tạo profile với reason = BACKFILL
        try {
            $profile = EmployeeInsuranceProfile::create([
                'employee_id' => $contract->employee_id,
                'position_id' => $contract->position_id,
                'grade' => $grade,
                'applied_from' => $contract->start_date,
                'applied_to' => $appliedTo,
                'reason' => 'BACKFILL',
                'source_appendix_id' => null,
                'note' => "Backfill từ hợp đồng legacy {$contract->contract_number}",
                'created_by' => auth()->id() ?? null,
            ]);

            Log::info("Backfilled insurance profile from legacy contract", [
                'contract_id' => $contract->id,
                'profile_id' => $profile->id,
                'grade' => $grade,
                'applied_from' => $contract->start_date,
                'applied_to' => $appliedTo,
            ]);

            return $profile;
        } catch (\Exception $e) {
            Log::error("Failed to backfill insurance profile", [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Helper: Detect grade từ insurance_salary và position grades
     *
     * @param int $insuranceSalary
     * @param string $positionId
     * @param string $date
     * @return int|null Grade (1-7) hoặc null nếu không detect được
     */
    protected function detectGradeFromSalary(int $insuranceSalary, string $positionId, string $date): ?int
    {
        // Get all grades for position tại thời điểm
        $grades = PositionSalaryGrade::where('position_id', $positionId)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhereDate('effective_to', '>=', $date);
            })
            ->orderBy('grade')
            ->get();

        if ($grades->isEmpty()) {
            return null;
        }

        // Get minimum wage (assume region 2 - có thể enhance later)
        $minWage = \App\Models\MinimumWage::where('region', 2)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhereDate('effective_to', '>=', $date);
            })
            ->first();

        if (!$minWage) {
            return null;
        }

        // Find grade có salary gần nhất với insurance_salary
        $bestMatch = null;
        $minDiff = PHP_INT_MAX;

        foreach ($grades as $gradeData) {
            $calculatedSalary = $minWage->amount * $gradeData->coefficient;
            $diff = abs($calculatedSalary - $insuranceSalary);

            if ($diff < $minDiff) {
                $minDiff = $diff;
                $bestMatch = $gradeData->grade;
            }
        }

        return $bestMatch;
    }

    /**
     * Helper: Calculate insurance salary từ profile
     *
     * @param EmployeeInsuranceProfile $profile
     * @return int|null
     */
    protected function calculateInsuranceSalary(EmployeeInsuranceProfile $profile): ?int
    {
        $grade = PositionSalaryGrade::where('position_id', $profile->position_id)
            ->where('grade', $profile->grade)
            ->where('is_active', true)
            ->first();

        if (!$grade) {
            return null;
        }

        $minWage = \App\Models\MinimumWage::where('region', 2)
            ->where('is_active', true)
            ->first();

        if (!$minWage) {
            return null;
        }

        return (int) round($minWage->amount * $grade->coefficient);
    }
}
