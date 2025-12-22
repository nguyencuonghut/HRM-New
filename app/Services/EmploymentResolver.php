<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\EmployeeEmployment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmploymentResolver
{
    /**
     * Check if contract should trigger employment creation/update
     *
     * Rules:
     * - LEGACY: Create employment if status represents actual work period
     *   (ACTIVE, EXPIRED, TERMINATED, SUSPENDED, CANCELLED - not DRAFT)
     * - RECRUITMENT: Create employment when approved or ACTIVE
     */
    public function shouldCreateEmployment(Contract $contract): bool
    {
        // LEGACY contracts: skip DRAFT and PENDING_APPROVAL
        if ($contract->source === 'LEGACY') {
            return !in_array($contract->status, ['DRAFT', 'PENDING_APPROVAL']);
        }

        // RECRUITMENT contracts: only when approved/active
        if ($contract->source === 'RECRUITMENT') {
            return in_array($contract->status, ['ACTIVE', 'SUSPENDED', 'TERMINATED', 'EXPIRED']);
        }

        return false;
    }

    /**
     * Resolve + attach employment_id for a contract.
     * Call this at:
     * - ContractObserver (automatic on save)
     * - ContractController@approve (manual trigger)
     */
    public function attachEmploymentForContract(Contract $contract): ?EmployeeEmployment
    {
        // Check if this contract should trigger employment creation
        if (!$this->shouldCreateEmployment($contract)) {
            return null;
        }

        return DB::transaction(function () use ($contract) {
            $contract->loadMissing('employee');

            $employeeId = $contract->employee_id;
            $startDate  = $this->asDate($contract->start_date);
            $endDate    = $this->asDateOrNull($contract->end_date);

            // Lock existing employments for this employee to avoid race
            $employments = EmployeeEmployment::where('employee_id', $employeeId)
                ->orderBy('start_date')
                ->lockForUpdate()
                ->get();

            // 1) If contract already has employment_id and exists -> update dates if needed
            if ($contract->employment_id) {
                $current = $employments->firstWhere('id', $contract->employment_id);
                if ($current) {
                    $this->mergeEmploymentDates($current, $startDate, $endDate);
                    $this->syncIsCurrentFlags($employeeId);
                    return $current->fresh();
                }
            }

            // 2) Find an employment that contains contract.start_date
            //    Priority: current employment (end_date null) > overlapping employment
            $matched = $employments->first(function (EmployeeEmployment $e) use ($startDate) {
                if ($e->start_date && $startDate->lt($e->start_date)) return false;
                if (is_null($e->end_date)) return true;
                return $startDate->lte($e->end_date);
            });

            // If no matched employment, check if there's a current employment we can extend
            if (!$matched) {
                $matched = $employments->first(function (EmployeeEmployment $e) {
                    return is_null($e->end_date);
                });
            }

            if ($matched) {
                $this->mergeEmploymentDates($matched, $startDate, $endDate);

                $contract->employment_id = $matched->id;
                $contract->save();

                $this->syncIsCurrentFlags($employeeId);
                return $matched->fresh();
            }

            // 3) No matched employment -> create new employment
            $employment = EmployeeEmployment::create([
                'employee_id' => $employeeId,
                'start_date'  => $startDate->toDateString(),
                'end_date'    => $endDate?->toDateString(),
                'end_reason'  => null,
                'note'        => $this->buildAutoNote($contract),
                'is_current'  => $endDate ? false : true,
            ]);

            $contract->employment_id = $employment->id;
            $contract->save();

            // Ensure only 1 current row (unique constraint will enforce)
            $this->syncIsCurrentFlags($employeeId);

            return $employment->fresh();
        });
    }

    /**
     * Optional: when contract is terminated/cancelled and you want to end current employment.
     * You can call this from terminate flow if you want.
     */
    public function endCurrentEmployment(string $employeeId, string $endDate, ?string $reason = null, ?string $note = null): void
    {
        DB::transaction(function () use ($employeeId, $endDate, $reason, $note) {
            $current = EmployeeEmployment::where('employee_id', $employeeId)
                ->whereNull('end_date')
                ->lockForUpdate()
                ->first();

            if (!$current) return;

            $current->end_date   = $endDate;
            $current->end_reason = $reason ?: $current->end_reason;
            if ($note) $current->note = trim(($current->note ?? '') . "\n" . $note);
            $current->is_current = false;
            $current->save();

            $this->syncIsCurrentFlags($employeeId);
        });
    }

    /* ---------------- Internals ---------------- */

    private function mergeEmploymentDates(EmployeeEmployment $employment, Carbon $startDate, ?Carbon $endDate): void
    {
        $dirty = false;

        // start_date = min(existing.start_date, contract.start_date)
        if ($employment->start_date && $startDate->lt($employment->start_date)) {
            $employment->start_date = $startDate->toDateString();
            $dirty = true;
        }

        // If employment already ended, and contract has later end_date, extend it
        if (!is_null($endDate)) {
            if (is_null($employment->end_date) || $endDate->gt($employment->end_date)) {
                $employment->end_date = $endDate->toDateString();
                $dirty = true;
            }
        } else {
            // Contract has no end_date => employment should be current
            if (!is_null($employment->end_date)) {
                // Only unset end_date if you want "current wins".
                // In many cases, a later ACTIVE indefinite contract means employment continues.
                $employment->end_date = null;
                $dirty = true;
            }
        }

        // Sync is_current (fast flag)
        $newIsCurrent = is_null($employment->end_date);
        if ($employment->is_current !== $newIsCurrent) {
            $employment->is_current = $newIsCurrent;
            $dirty = true;
        }

        if ($dirty) $employment->save();
    }

    /**
     * Keep is_current boolean consistent with end_date.
     * Unique constraint is enforced by generated column anyway.
     */
    private function syncIsCurrentFlags(string $employeeId): void
    {
        EmployeeEmployment::where('employee_id', $employeeId)->update([
            'is_current' => DB::raw("CASE WHEN end_date IS NULL THEN 1 ELSE 0 END")
        ]);
    }

    private function asDate($value): Carbon
    {
        return $value instanceof Carbon ? $value->copy() : Carbon::parse($value);
    }

    private function asDateOrNull($value): ?Carbon
    {
        if (!$value) return null;
        return $this->asDate($value);
    }

    private function buildAutoNote(Contract $contract): string
    {
        $parts = [
            "Auto tạo từ Contract",
            "contract_id={$contract->id}",
            "contract_number={$contract->contract_number}",
            "source={$contract->source}",
            "status={$contract->status}",
        ];
        return implode(' | ', $parts);
    }
}
