<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\User;
use App\Enums\ContractTerminationReason;
use App\Enums\ActivityLogDescription;
use App\Events\ContractTerminated;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContractTerminationService
{
    /**
     * Chấm dứt hợp đồng
     */
    public function terminateContract(Contract $contract, array $data, User $terminator): Contract
    {
        // Validate
        $this->validateTermination($contract);

        DB::beginTransaction();
        try {
            $terminatedAt = Carbon::parse($data['terminated_at']);

            // Update contract status
            $contract->update([
                'status' => 'TERMINATED',
                'terminated_at' => $terminatedAt,
                'termination_reason' => $data['termination_reason'],
                'note' => $data['termination_note'] ?? $contract->note,
            ]);

            // Xử lý các phụ lục (appendixes) của hợp đồng
            $this->handleAppendixesOnTermination($contract, $terminatedAt);

            // Log activity
            activity()
                ->performedOn($contract)
                ->causedBy($terminator)
                ->withProperties([
                    'reason' => $data['termination_reason'],
                    'terminated_at' => $data['terminated_at'],
                    'note' => $data['termination_note'] ?? null,
                ])
                ->log(ActivityLogDescription::CONTRACT_TERMINATED->value);

            // Dispatch event
            event(new ContractTerminated(
                contract: $contract->fresh(),
                terminator: $terminator,
                reason: $data['termination_reason'],
                note: $data['termination_note'] ?? null
            ));

            DB::commit();

            return $contract->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Xử lý các phụ lục khi hợp đồng bị chấm dứt
     */
    protected function handleAppendixesOnTermination(Contract $contract, Carbon $terminatedAt): void
    {
        $appendixes = $contract->appendixes;

        foreach ($appendixes as $appendix) {
            // Phụ lục ACTIVE: Set end_date = terminated_at và chuyển sang CANCELLED
            if ($appendix->status === 'ACTIVE') {
                $appendix->update([
                    'status' => 'CANCELLED',
                    'end_date' => $terminatedAt,
                    'note' => ($appendix->note ?? '') . "\n[Tự động hủy do hợp đồng chính bị chấm dứt]",
                ]);

                activity()
                    ->performedOn($appendix)
                    ->log(ActivityLogDescription::APPENDIX_CANCELLED->value);
            }

            // Phụ lục PENDING_APPROVAL: Tự động REJECT
            if ($appendix->status === 'PENDING_APPROVAL') {
                $appendix->update([
                    'status' => 'REJECTED',
                    'rejected_at' => now(),
                    'approval_note' => 'Tự động từ chối do hợp đồng chính bị chấm dứt',
                ]);

                activity()
                    ->performedOn($appendix)
                    ->log(ActivityLogDescription::APPENDIX_REJECTED->value);
            }

            // Phụ lục DRAFT: Chuyển sang CANCELLED
            if ($appendix->status === 'DRAFT') {
                $appendix->update([
                    'status' => 'CANCELLED',
                    'note' => ($appendix->note ?? '') . "\n[Tự động hủy do hợp đồng chính bị chấm dứt]",
                ]);

                activity()
                    ->performedOn($appendix)
                    ->log(ActivityLogDescription::APPENDIX_CANCELLED->value);
            }
        }
    }

    /**
     * Validate có thể chấm dứt hợp đồng không
     */
    public function validateTermination(Contract $contract): void
    {
        // Không cho phép terminate các trạng thái này
        $invalidStatuses = ['DRAFT', 'PENDING_APPROVAL', 'REJECTED'];

        if (in_array($contract->status, $invalidStatuses)) {
            throw new \InvalidArgumentException(
                'Không thể chấm dứt hợp đồng ở trạng thái ' . $contract->status . '. Chỉ có thể chấm dứt hợp đồng đã ACTIVE.'
            );
        }

        // Nếu status là ACTIVE hoặc TERMINATED (do sửa DB), đều cho phép terminate
        // Chỉ cảnh báo nếu đã có terminated_at (re-terminate case)
        if ($contract->terminated_at !== null && $contract->status === 'TERMINATED') {
            \Log::warning('Re-terminating contract that was already terminated', [
                'contract_id' => $contract->id,
                'previous_terminated_at' => $contract->terminated_at,
                'status' => $contract->status,
            ]);
        }
    }

    /**
     * Tính toán trợ cấp thôi việc (nếu có)
     * Logic đơn giản: 1/2 tháng lương cho mỗi năm làm việc
     */
    public function calculateSeverancePay(Contract $contract, ?string $reasonValue = null): array
    {
        // Nếu không truyền reason, lấy từ contract (có thể null nếu chưa terminate)
        $reasonValue = $reasonValue ?? $contract->termination_reason;

        if (!$reasonValue) {
            return [
                'eligible' => false,
                'amount' => 0,
                'note' => 'Chưa xác định lý do chấm dứt',
            ];
        }

        $reason = ContractTerminationReason::from($reasonValue);

        if (!$reason->requiresSeverancePay()) {
            return [
                'eligible' => false,
                'amount' => 0,
                'note' => 'Không được hưởng trợ cấp thôi việc theo lý do chấm dứt',
            ];
        }

        // Tính số năm làm việc
        $startDate = $contract->start_date;
        $endDate = $contract->terminated_at ?? now();
        $yearsWorked = $startDate->diffInYears($endDate);
        $monthsWorked = $startDate->diffInMonths($endDate) % 12;

        // Công thức: 1/2 tháng lương cho mỗi năm
        $monthlySalary = $contract->base_salary + $contract->position_allowance;
        $severancePay = ($yearsWorked * 0.5 + $monthsWorked / 24) * $monthlySalary;

        return [
            'eligible' => true,
            'amount' => round($severancePay),
            'years_worked' => $yearsWorked,
            'months_worked' => $monthsWorked,
            'monthly_salary' => $monthlySalary,
            'formula' => sprintf('(%d năm × 0.5 + %d tháng / 24) × %s',
                $yearsWorked,
                $monthsWorked,
                number_format($monthlySalary)
            ),
            'note' => 'Trợ cấp thôi việc theo Bộ luật Lao động',
        ];
    }

    /**
     * Lấy danh sách hợp đồng đã chấm dứt
     */
    public function getTerminatedContracts(array $filters = [])
    {
        $query = Contract::where('status', 'TERMINATED')
            ->with(['employee', 'department', 'position']);

        // Filter by reason
        if (!empty($filters['reason'])) {
            $query->where('termination_reason', $filters['reason']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->whereDate('terminated_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('terminated_at', '<=', $filters['to_date']);
        }

        // Filter by department
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->orderBy('terminated_at', 'desc');
    }

    /**
     * Thống kê chấm dứt hợp đồng theo lý do
     */
    public function getTerminationStatistics(string $year = null): array
    {
        $year = $year ?? now()->year;

        $stats = Contract::where('status', 'TERMINATED')
            ->whereYear('terminated_at', $year)
            ->selectRaw('termination_reason, COUNT(*) as count')
            ->groupBy('termination_reason')
            ->get()
            ->mapWithKeys(function ($item) {
                $reason = ContractTerminationReason::from($item->termination_reason);
                return [
                    $item->termination_reason => [
                        'label' => $reason->label(),
                        'count' => $item->count,
                    ]
                ];
            })
            ->toArray();

        return [
            'year' => $year,
            'total' => array_sum(array_column($stats, 'count')),
            'by_reason' => $stats,
        ];
    }
}
