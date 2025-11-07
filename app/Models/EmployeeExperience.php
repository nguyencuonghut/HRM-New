<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeeExperience extends Model
{
    use HasUuids;

    protected $table = 'employee_experiences';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'employee_id','company_name','position_title',
        'start_date','end_date','is_current',
        'responsibilities','achievements',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_current' => 'boolean',
    ];

    public function employee(){ return $this->belongsTo(Employee::class); }
}
