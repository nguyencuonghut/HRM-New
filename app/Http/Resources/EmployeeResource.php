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
            'status'                   => $this->status,
            'si_number'                => $this->si_number,
            'created_at'               => optional($this->created_at)->toDateTimeString(),
            'updated_at'               => optional($this->updated_at)->toDateTimeString(),

            // Profile completion
            'completion_score'         => $completion ? $completion['score'] : null,
            'completion_details'       => $completion ? $completion['details'] : null,
            'completion_missing'       => $completion ? $completion['missing'] : null,
            'completion_level'         => $completion ? ProfileCompletionService::getCompletionLevel($completion['score']) : null,
            'completion_severity'      => $completion ? ProfileCompletionService::getCompletionSeverity($completion['score']) : null,
        ];
    }
}
