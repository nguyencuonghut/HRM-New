<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ContractAppendix extends Model
{
    use HasUuids;

    protected $fillable = [
        'contract_id','appendix_no','appendix_type','source','title','summary',
        'effective_date','end_date','status','approver_id','approved_at','rejected_at','approval_note',
        'base_salary','insurance_salary','position_allowance','other_allowances',
        'department_id','position_id','working_time','work_location','note',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date'       => 'date',
        'approved_at'    => 'datetime',
        'rejected_at'    => 'datetime',
        'other_allowances' => 'array',
    ];

    public function contract(){ return $this->belongsTo(Contract::class); }
    public function approver(){ return $this->belongsTo(User::class,'approver_id'); }
    public function attachments(){ return $this->hasMany(ContractAppendixAttachment::class,'appendix_id'); }
}
