<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /**
     * Relationship to Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Relationship to Skill
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'skill_id');
    }
}
