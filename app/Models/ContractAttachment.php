<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ContractAttachment extends Model
{
    use HasUuids;

    protected $fillable = ['contract_id','file_name','file_path','file_size','mime_type'];
    public function contract(){ return $this->belongsTo(Contract::class); }
}
