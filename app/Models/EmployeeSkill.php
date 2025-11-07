<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeeSkill extends Pivot
{
    use HasUuids;

    protected $table = 'employee_skills';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['employee_id','skill_id','level','years','note'];

    protected $casts = [
        'level' => 'integer',
        'years' => 'integer',
    ];
}
