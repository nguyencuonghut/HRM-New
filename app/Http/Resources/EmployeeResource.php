<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\ProfileCompletionService;

class EmployeeResource extends JsonResource
{
    public function toArray($request)
    {
        // Calculate completion score if relationships are loaded
        $completion = null;
        if ($this->relationLoaded('assignments') &&
            $this->relationLoaded('educations') &&
            $this->relationLoaded('relatives') &&
            $this->relationLoaded('experiences') &&
            $this->relationLoaded('employeeSkills')) {
            $completion = ProfileCompletionService::calculateScore($this->resource);
        }

        // Compute status: ACTIVE if any contract is active, else fallback to original status
        $computedStatus = $this->contracts()->active()->exists() ? 'ACTIVE' : $this->status;

        // Contract presence flags (from withExists in controller)
        $hasAnyContract = $this->has_any_contract ?? false;
        $hasActiveContract = $this->has_active_contract ?? false;
        $hasPendingContract = $this->has_pending_contract ?? false;

        // Derive contract status for UI
        $contractStatus = $this->getContractStatus($hasAnyContract, $hasActiveContract, $hasPendingContract);

        return [
            'id'                       => $this->id,
            'user_id'                  => $this->user_id,
            'employee_code'            => $this->employee_code,
            'full_name'                => $this->full_name,
            'dob'                      => $this->dob,
            'gender'                   => $this->gender,
            'marital_status'           => $this->marital_status,
            'avatar'                   => $this->avatar,
            'cccd'                     => $this->cccd,
            'cccd_issued_on'           => $this->cccd_issued_on,
            'cccd_issued_by'           => $this->cccd_issued_by,
            'ward_id'                  => $this->ward_id,
            'address_street'           => $this->address_street,
            'temp_ward_id'             => $this->temp_ward_id,
            'temp_address_street'      => $this->temp_address_street,
            'phone'                    => $this->phone,
            'emergency_contact_phone'  => $this->emergency_contact_phone,
            'personal_email'           => $this->personal_email,
            'company_email'            => $this->company_email,
            'hire_date'                => $this->hire_date,
            'status'                   => $computedStatus,
            'status_label'             => \App\Enums\EmployeeStatus::tryFrom($this->status)?->label() ?? $this->status,
            'status_severity'          => \App\Enums\EmployeeStatus::tryFrom($this->status)?->severity() ?? 'secondary',
            'status_icon'              => 'pi ' . (\App\Enums\EmployeeStatus::tryFrom($this->status)?->icon() ?? 'pi-circle'),
            'si_number'                => $this->si_number,
            'created_at'               => optional($this->created_at)->toDateTimeString(),
            'updated_at'               => optional($this->updated_at)->toDateTimeString(),

            // Contract presence
            'has_any_contract'         => $hasAnyContract,
            'has_active_contract'      => $hasActiveContract,
            'contract_status'          => $contractStatus,

            // Tenure / Seniority info
            'current_tenure'           => $this->getCurrentTenure(),
            'current_tenure_text'      => $this->getCurrentTenureHuman(),
            'cumulative_tenure'        => $this->getCumulativeTenure(),
            'cumulative_tenure_text'   => $this->getCumulativeTenureHuman(),
            'employment_history'       => $this->whenLoaded('employments', fn() => $this->getEmploymentHistory()),
            'current_employment_start' => $this->currentEmployment() ? $this->currentEmployment()->start_date->format('d/m/Y') : null,

            // Profile completion
            'completion_score'         => $completion ? $completion['score'] : null,
            'completion_details'       => $completion ? $completion['details'] : null,
            'completion_missing'       => $completion ? $completion['missing'] : null,
            'completion_level'         => $completion ? ProfileCompletionService::getCompletionLevel($completion['score']) : null,
            'completion_severity'      => $completion ? ProfileCompletionService::getCompletionSeverity($completion['score']) : null,
        ];
    }

    /**
     * Derive contract status for UI display
     */
    protected function getContractStatus(bool $hasAnyContract, bool $hasActiveContract, bool $hasPendingContract): array
    {
        if ($hasActiveContract) {
            return [
                'label' => 'Có HĐ hiệu lực',
                'severity' => 'success',
                'icon' => 'pi pi-check-circle'
            ];
        }

        if ($hasPendingContract) {
            return [
                'label' => 'HĐ chưa hiệu lực',
                'severity' => 'warn',
                'icon' => 'pi pi-clock'
            ];
        }

        if ($hasAnyContract) {
            return [
                'label' => 'HĐ đã hết hạn',
                'severity' => 'contrast',
                'icon' => 'pi pi-calendar-times'
            ];
        }

        if ($this->status === 'ACTIVE') {
            return [
                'label' => 'Thiếu hợp đồng',
                'severity' => 'danger',
                'icon' => 'pi pi-times-circle'
            ];
        }

        return [
            'label' => 'Chưa có HĐ',
            'severity' => 'secondary',
            'icon' => 'pi pi-minus-circle'
        ];
    }
}
