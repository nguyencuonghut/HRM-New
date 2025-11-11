<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractAppendix;

class CurrentContractTermsService
{
    /**
     * Tính "điều kiện hiện hành" từ contract + các appendix ACTIVE/đang hiệu lực.
     * Trả về mảng: base_salary, insurance_salary, position_allowance, other_allowances, working_time, work_location
     */
    public static function build(Contract $contract): array
    {
        // Base terms lấy từ contract
        $terms = [
            'base_salary'        => (int) $contract->base_salary,
            'insurance_salary'   => (int) $contract->insurance_salary,
            'position_allowance' => (int) $contract->position_allowance,
            'other_allowances'   => $contract->other_allowances ?: [],
            'working_time'       => $contract->working_time,
            'work_location'      => $contract->work_location,
        ];

        // Lấy các phụ lục ACTIVE có hiệu lực tại thời điểm hiện tại
        // TODO: Uncomment when contract_appendixes table is created
        /*
        $appendixes = ContractAppendix::where('contract_id', $contract->id)
            ->where('status', 'ACTIVE')
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->where(function($q){
                $q->whereNull('end_date')->orWhereDate('end_date','>=', now()->toDateString());
            })
            ->orderBy('effective_date','asc')
            ->get();

        foreach ($appendixes as $a) {
            foreach (['base_salary','insurance_salary','position_allowance','working_time','work_location'] as $k) {
                if (!is_null($a->$k)) $terms[$k] = $a->$k;
            }
            if (is_array($a->other_allowances)) {
                $terms['other_allowances'] = $a->other_allowances; // ghi đè toàn bộ (đơn giản, có thể đổi sang merge)
            }
        }
        */

        return $terms;
    }
}
