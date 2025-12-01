<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\User;
use App\Events\ContractRenewed;
use App\Enums\AppendixType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ContractRenewalService
{
    /**
     * Gia hạn hợp đồng bằng cách tạo phụ lục EXTENSION
     *
     * @param Contract $contract
     * @param array $renewalData [
     *   'new_end_date' => Carbon,
     *   'title' => string (optional),
     *   'summary' => string (optional),
     *   'note' => string (optional),
     *   'base_salary' => int (optional, nếu điều chỉnh lương),
     *   'insurance_salary' => int (optional),
     *   'position_allowance' => int (optional),
     *   'other_allowances' => array (optional),
     *   'department_id' => uuid (optional),
     *   'position_id' => uuid (optional),
     *   'working_time' => string (optional),
     *   'work_location' => string (optional),
     * ]
     * @param User $creator
     * @return ContractAppendix
     * @throws \Exception
     */
    public function renewContract(Contract $contract, array $renewalData, User $creator): ContractAppendix
    {
        $this->validateRenewal($contract, $renewalData);

        return DB::transaction(function () use ($contract, $renewalData, $creator) {
            // Convert new_end_date to Carbon if it's a string
            if (isset($renewalData['new_end_date']) && is_string($renewalData['new_end_date'])) {
                $renewalData['new_end_date'] = Carbon::parse($renewalData['new_end_date']);
            }

            // Tạo phụ lục gia hạn
            $appendix = $this->createRenewalAppendix($contract, $renewalData);

            // Log hoạt động
            activity()
                ->performedOn($contract)
                ->causedBy($creator)
                ->withProperties([
                    'appendix_id' => $appendix->id,
                    'appendix_no' => $appendix->appendix_no,
                    'old_end_date' => $contract->end_date?->format('Y-m-d'),
                    'new_end_date' => $renewalData['new_end_date']->format('Y-m-d'),
                ])
                ->log('CONTRACT_RENEWAL_REQUESTED');

            // Dispatch event để gửi thông báo
            event(new ContractRenewed($appendix, $creator));

            return $appendix;
        });
    }

    /**
     * Validate dữ liệu gia hạn
     *
     * @param Contract $contract
     * @param array $renewalData
     * @throws \Exception
     */
    public function validateRenewal(Contract $contract, array $renewalData): void
    {
        // Kiểm tra trạng thái hợp đồng
        if (!in_array($contract->status, ['ACTIVE', 'PENDING_APPROVAL'])) {
            throw new \Exception('Chỉ có thể gia hạn hợp đồng đang hoạt động hoặc chờ duyệt');
        }

        // Kiểm tra hợp đồng đã bị chấm dứt chưa
        if ($contract->status === 'TERMINATED') {
            throw new \Exception('Không thể gia hạn hợp đồng đã bị chấm dứt');
        }

        // Kiểm tra new_end_date
        if (empty($renewalData['new_end_date'])) {
            throw new \Exception('Ngày kết thúc mới là bắt buộc');
        }

        $newEndDate = $renewalData['new_end_date'] instanceof Carbon
            ? $renewalData['new_end_date']
            : Carbon::parse($renewalData['new_end_date']);

        // Nếu hợp đồng có end_date, new_end_date phải sau end_date hiện tại
        if ($contract->end_date) {
            if ($newEndDate->lte($contract->end_date)) {
                throw new \Exception('Ngày kết thúc mới phải sau ngày kết thúc hiện tại (' . $contract->end_date->format('d/m/Y') . ')');
            }
        } else {
            // Hợp đồng không xác định thời hạn, new_end_date phải sau ngày bắt đầu
            if ($newEndDate->lte($contract->start_date)) {
                throw new \Exception('Ngày kết thúc mới phải sau ngày bắt đầu hợp đồng (' . $contract->start_date->format('d/m/Y') . ')');
            }
        }

        // Kiểm tra new_end_date không quá xa trong tương lai (ví dụ không quá 10 năm)
        $maxDate = now()->addYears(10);
        if ($newEndDate->gt($maxDate)) {
            throw new \Exception('Ngày kết thúc mới không được vượt quá 10 năm kể từ hôm nay');
        }
    }

    /**
     * Tạo phụ lục gia hạn
     *
     * @param Contract $contract
     * @param array $renewalData
     * @return ContractAppendix
     */
    private function createRenewalAppendix(Contract $contract, array $renewalData): ContractAppendix
    {
        $newEndDate = $renewalData['new_end_date'] instanceof Carbon
            ? $renewalData['new_end_date']
            : Carbon::parse($renewalData['new_end_date']);

        // Tính appendix_no tiếp theo
        $lastAppendixNo = ContractAppendix::where('contract_id', $contract->id)
            ->orderByRaw('CAST(SUBSTRING(appendix_no, 3) AS UNSIGNED) DESC')
            ->value('appendix_no');

        $nextNumber = $lastAppendixNo
            ? intval(substr($lastAppendixNo, 2)) + 1
            : 1;
        $appendixNo = 'PL' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Tạo title mặc định nếu không có
        $title = $renewalData['title'] ?? 'Phụ lục gia hạn hợp đồng';

        // Tạo summary mặc định
        $defaultSummary = 'Gia hạn hợp đồng từ ' .
            ($contract->end_date ? $contract->end_date->format('d/m/Y') : 'không xác định') .
            ' đến ' . $newEndDate->format('d/m/Y');
        $summary = $renewalData['summary'] ?? $defaultSummary;

        // Ngày hiệu lực = ngày sau end_date hiện tại (hoặc hôm nay nếu HĐ không có end_date)
        $effectiveDate = $contract->end_date
            ? $contract->end_date->copy()->addDay()
            : now();

        // Tạo phụ lục
        $appendixData = [
            'contract_id' => $contract->id,
            'appendix_no' => $appendixNo,
            'appendix_type' => AppendixType::EXTENSION->value,
            'source' => 'WORKFLOW',
            'title' => $title,
            'summary' => $summary,
            'effective_date' => $effectiveDate,
            'end_date' => $newEndDate,
            'status' => 'PENDING_APPROVAL', // Cần phê duyệt
            'note' => $renewalData['note'] ?? null,
        ];

        // Thêm các thay đổi khác (nếu có)
        if (isset($renewalData['base_salary'])) {
            $appendixData['base_salary'] = $renewalData['base_salary'];
        }
        if (isset($renewalData['insurance_salary'])) {
            $appendixData['insurance_salary'] = $renewalData['insurance_salary'];
        }
        if (isset($renewalData['position_allowance'])) {
            $appendixData['position_allowance'] = $renewalData['position_allowance'];
        }
        if (isset($renewalData['other_allowances'])) {
            $appendixData['other_allowances'] = $renewalData['other_allowances'];
        }
        if (isset($renewalData['department_id'])) {
            $appendixData['department_id'] = $renewalData['department_id'];
        }
        if (isset($renewalData['position_id'])) {
            $appendixData['position_id'] = $renewalData['position_id'];
        }
        if (isset($renewalData['working_time'])) {
            $appendixData['working_time'] = $renewalData['working_time'];
        }
        if (isset($renewalData['work_location'])) {
            $appendixData['work_location'] = $renewalData['work_location'];
        }

        return ContractAppendix::create($appendixData);
    }

    /**
     * Phê duyệt phụ lục gia hạn
     *
     * @param ContractAppendix $appendix
     * @param User $approver
     * @param string|null $note
     * @return void
     * @throws \Exception
     */
    public function approveRenewal(ContractAppendix $appendix, User $approver, ?string $note = null): void
    {
        if ($appendix->appendix_type !== AppendixType::EXTENSION) {
            throw new \Exception('Phụ lục này không phải là phụ lục gia hạn');
        }

        if ($appendix->status !== 'PENDING_APPROVAL') {
            throw new \Exception('Phụ lục này không ở trạng thái chờ phê duyệt');
        }

        DB::transaction(function () use ($appendix, $approver, $note) {
            // Cập nhật trạng thái phụ lục
            $appendix->update([
                'status' => 'ACTIVE',
                'approver_id' => $approver->id,
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            // Cập nhật end_date của hợp đồng chính
            $contract = $appendix->contract;
            $contract->update([
                'end_date' => $appendix->end_date,
            ]);

            // Nếu có thay đổi lương/phòng ban/chức danh, cập nhật vào hợp đồng
            $updateData = [];
            if ($appendix->base_salary) {
                $updateData['base_salary'] = $appendix->base_salary;
            }
            if ($appendix->insurance_salary) {
                $updateData['insurance_salary'] = $appendix->insurance_salary;
            }
            if ($appendix->position_allowance) {
                $updateData['position_allowance'] = $appendix->position_allowance;
            }
            if ($appendix->other_allowances) {
                $updateData['other_allowances'] = $appendix->other_allowances;
            }
            if ($appendix->department_id) {
                $updateData['department_id'] = $appendix->department_id;
            }
            if ($appendix->position_id) {
                $updateData['position_id'] = $appendix->position_id;
            }
            if ($appendix->working_time) {
                $updateData['working_time'] = $appendix->working_time;
            }
            if ($appendix->work_location) {
                $updateData['work_location'] = $appendix->work_location;
            }

            if (!empty($updateData)) {
                $contract->update($updateData);
            }

            // Log hoạt động
            activity()
                ->performedOn($contract)
                ->causedBy($approver)
                ->withProperties([
                    'appendix_id' => $appendix->id,
                    'appendix_no' => $appendix->appendix_no,
                    'new_end_date' => $appendix->end_date->format('Y-m-d'),
                ])
                ->log('CONTRACT_RENEWAL_APPROVED');
        });
    }

    /**
     * Từ chối phụ lục gia hạn
     *
     * @param ContractAppendix $appendix
     * @param User $approver
     * @param string|null $note
     * @return void
     * @throws \Exception
     */
    public function rejectRenewal(ContractAppendix $appendix, User $approver, ?string $note = null): void
    {
        if ($appendix->appendix_type !== AppendixType::EXTENSION) {
            throw new \Exception('Phụ lục này không phải là phụ lục gia hạn');
        }

        if ($appendix->status !== 'PENDING_APPROVAL') {
            throw new \Exception('Phụ lục này không ở trạng thái chờ phê duyệt');
        }

        DB::transaction(function () use ($appendix, $approver, $note) {
            // Cập nhật trạng thái phụ lục
            $appendix->update([
                'status' => 'REJECTED',
                'approver_id' => $approver->id,
                'rejected_at' => now(),
                'approval_note' => $note,
            ]);

            // Log hoạt động
            activity()
                ->performedOn($appendix->contract)
                ->causedBy($approver)
                ->withProperties([
                    'appendix_id' => $appendix->id,
                    'appendix_no' => $appendix->appendix_no,
                ])
                ->log('CONTRACT_RENEWAL_REJECTED');
        });
    }

    /**
     * Lấy danh sách hợp đồng sắp hết hạn
     *
     * @param int $daysThreshold Số ngày trước khi hết hạn (mặc định 30)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringContracts(int $daysThreshold = 30)
    {
        $thresholdDate = now()->addDays($daysThreshold);

        return Contract::where('status', 'ACTIVE')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', $thresholdDate)
            ->where('end_date', '>=', now())
            ->with(['employee', 'department', 'position'])
            ->orderBy('end_date', 'asc')
            ->get();
    }
}
