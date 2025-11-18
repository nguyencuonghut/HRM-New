<?php

namespace App\Services;

use App\Models\Contract;

class ContractMergeDataBuilder
{
    public static function build(Contract $contract): array
    {
        $employee   = $contract->employee;
        $department = $contract->department;
        $position   = $contract->position;

        $terms = CurrentContractTermsService::build($contract); // như bạn đã có

        // Chuẩn hóa other_allowances thành text nhiều dòng
        $otherAllowancesText = '';
        if (!empty($terms['other_allowances']) && is_array($terms['other_allowances'])) {
            $lines = [];
            foreach ($terms['other_allowances'] as $al) {
                $name   = $al['name'] ?? '';
                $amount = $al['amount'] ?? 0;
                $lines[] = $name . ': ' . number_format($amount, 0, ',', '.') . ' VND';
            }
            $otherAllowancesText = implode("\n", $lines);
        }

        return [
            'employee_full_name'   => $employee->full_name ?? '',
            'employee_code'        => $employee->employee_code ?? '',
            'department_name'      => $department->name ?? '',
            'position_title'       => $position->title ?? '',
            'contract_number'      => $contract->contract_number ?? '',
            'contract_start_date'  => optional($contract->start_date)->format('d/m/Y'),
            'contract_end_date'    => $contract->end_date ? $contract->end_date->format('d/m/Y') : '',
            'base_salary'          => number_format($terms['base_salary'] ?? 0, 0, ',', '.'),
            'insurance_salary'     => number_format($terms['insurance_salary'] ?? 0, 0, ',', '.'),
            'position_allowance'   => number_format($terms['position_allowance'] ?? 0, 0, ',', '.'),
            'working_time'         => $terms['working_time'] ?? '',
            'work_location'        => $terms['work_location'] ?? '',
            'other_allowances_text'=> $otherAllowancesText,
        ];
    }
}
