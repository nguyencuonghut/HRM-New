<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeEducationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'education_level_id' => $this->education_level_id,
            'school_id' => $this->school_id,
            'major' => $this->major,
            'start_year' => $this->start_year,
            'end_year' => $this->end_year,
            'study_form' => $this->study_form,
            'certificate_no' => $this->certificate_no,
            'graduation_date' => optional($this->graduation_date)->toDateString(),
            'grade' => $this->grade,
            'note' => $this->note,
            'education_level' => $this->whenLoaded('educationLevel', fn() => [
                'id' => $this->educationLevel->id,
                'name' => $this->educationLevel->name,
            ]),
            'school' => $this->whenLoaded('school', fn() => [
                'id' => $this->school->id,
                'name' => $this->school->name,
            ]),
        ];
    }
}
