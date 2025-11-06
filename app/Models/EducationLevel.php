<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EducationLevel extends Model
{
    use HasFactory, HasUuids;

    // Khóa chính là UUID
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];
}
