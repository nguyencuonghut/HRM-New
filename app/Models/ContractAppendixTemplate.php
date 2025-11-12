<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ContractAppendixTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'contract_appendix_templates';

    protected $fillable = [
        'name',
        'code',
        'appendix_type',
        'blade_view',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];
}
