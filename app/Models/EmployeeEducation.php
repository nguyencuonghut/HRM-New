<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeeEducation extends Model
{
    use HasUuids;

    protected $table = 'employee_educations';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'employee_id', 'education_level_id', 'school_id', 'major',
        'start_year', 'end_year', 'study_form', 'certificate_no',
        'graduation_date', 'grade', 'note',
    ];

    protected $casts = [
        'start_year' => 'integer',
        'end_year'   => 'integer',
        'graduation_date' => 'date',
    ];

    public function employee(){ return $this->belongsTo(Employee::class); }
    public function educationLevel(){ return $this->belongsTo(EducationLevel::class, 'education_level_id'); }
    public function school(){ return $this->belongsTo(School::class); }
}
