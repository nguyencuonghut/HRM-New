<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request)
    {
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
        ];
    }
}
