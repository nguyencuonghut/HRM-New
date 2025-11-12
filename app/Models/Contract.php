<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;

class Contract extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id','department_id','position_id','contract_number','contract_type','status',
        'sign_date','start_date','end_date','probation_end_date',
        'base_salary','insurance_salary','position_allowance','other_allowances',
        'social_insurance','health_insurance','unemployment_insurance',
        'work_location','working_time','approver_id','approved_at','rejected_at','approval_note',
        'terminated_at','termination_reason','source','source_id','template_id','generated_pdf_path',
        'signed_file_path','created_from_offer','note',
    ];

    protected $casts = [
        'other_allowances' => 'array',
        'sign_date'        => 'date',
        'start_date'       => 'date',
        'end_date'         => 'date',
        'probation_end_date'=> 'date',
        'approved_at'      => 'datetime',
        'rejected_at'      => 'datetime',
        'terminated_at'    => 'date',
        'social_insurance' => 'boolean',
        'health_insurance' => 'boolean',
        'unemployment_insurance' => 'boolean',
        'created_from_offer' => 'boolean',
    ];

    public function employee(){ return $this->belongsTo(Employee::class); }
    public function department(){ return $this->belongsTo(Department::class); }
    public function position(){ return $this->belongsTo(Position::class); }
    public function approver(){ return $this->belongsTo(User::class,'approver_id'); }
    public function attachments(){ return $this->hasMany(ContractAttachment::class); }
    public function template(){ return $this->belongsTo(ContractTemplate::class, 'template_id'); }
    public function appendixes(){ return $this->hasMany(ContractAppendix::class, 'contract_id'); }


    public function scopeActive($q){
        return $q->where('status','ACTIVE')
                 ->whereDate('start_date','<=',now())
                 ->where(function($q){
                    $q->whereNull('end_date')->orWhereDate('end_date','>=',now());
                 });
    }

    // URL công khai cho file PDF đã sinh
    public function getGeneratedPdfUrlAttribute(): ?string
    {
        if (!$this->generated_pdf_path) return null;
        // lưu path dạng "public/contracts/generated/xxx.pdf"
        $path = str_starts_with($this->generated_pdf_path, 'public/')
            ? substr($this->generated_pdf_path, strlen('public/'))
            : $this->generated_pdf_path;

        return Storage::url($path);
    }
}
