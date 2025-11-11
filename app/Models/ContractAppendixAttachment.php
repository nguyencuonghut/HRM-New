<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ContractAppendixAttachment extends Model
{
    use HasUuids;
    protected $fillable = ['appendix_id','file_name','file_path','file_size','mime_type'];
    public function appendix(){ return $this->belongsTo(ContractAppendix::class,'appendix_id'); }
}
