<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Enums\AppendixType;

class ContractAppendix extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'contract_id','appendix_no','appendix_type','source','title','summary',
        'effective_date','end_date','status','approver_id','approved_at','rejected_at','approval_note',
        'base_salary','insurance_salary','position_allowance','other_allowances',
        'department_id','position_id','working_time','work_location','note','generated_pdf_path'
    ];

    protected $casts = [
        'appendix_type'    => AppendixType::class,
        'effective_date' => 'date',
        'end_date'       => 'date',
        'approved_at'    => 'datetime',
        'rejected_at'    => 'datetime',
        'other_allowances' => 'array',
    ];

    public function contract(){ return $this->belongsTo(Contract::class); }
    public function approver(){ return $this->belongsTo(User::class,'approver_id'); }
    public function attachments(){ return $this->hasMany(ContractAppendixAttachment::class,'appendix_id'); }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['appendix_no', 'appendix_type', 'title', 'status', 'effective_date', 'end_date',
                      'base_salary', 'insurance_salary', 'position_allowance', 'department_id', 'position_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
