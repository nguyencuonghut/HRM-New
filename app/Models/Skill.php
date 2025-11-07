<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Skill extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code','name'];

    public function employees(){
        return $this->belongsToMany(Employee::class, 'employee_skills')
            ->using(EmployeeSkill::class)
            ->withPivot(['level','years','note'])
            ->withTimestamps();
    }
}
