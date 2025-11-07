<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeeRelative extends Model
{
    use HasUuids;

    protected $table = 'employee_relatives';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'employee_id','full_name','relation','dob','phone',
        'occupation','address','is_emergency_contact','note',
    ];

    protected $casts = [
        'dob' => 'date',
        'is_emergency_contact' => 'boolean',
    ];

    public function employee(){ return $this->belongsTo(Employee::class); }
}
