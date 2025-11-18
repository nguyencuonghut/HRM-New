<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ContractTemplate extends Model
{
    use HasUuids;

    protected $table = 'contract_templates';

    protected $fillable = [
        'name',
        'type',
        'engine',
        'body_path',
        'content',
        'placeholders_json',
        'is_default',
        'is_active',
        'version',
        'updated_by',
    ];

    protected $casts = [
        'is_default'        => 'boolean',
        'is_active'         => 'boolean',
        'version'           => 'integer',
        'placeholders_json' => 'array',
    ];

    /* ----------------- Relationships ----------------- */
    // Một template có thể được nhiều contract tham chiếu
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'template_id');
    }

    /* ----------------- Scopes tiện dụng ----------------- */
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeOfType($q, string $type)
    {
        return $q->where('type', $type);
    }

    public function scopeLatestVersion($q)
    {
        return $q->orderByDesc('version');
    }

    public function isLiquid(): bool
    {
        return $this->engine === 'LIQUID';
    }

    public function isBlade(): bool
    {
        return $this->engine === 'BLADE';
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ----------------- Accessors ----------------- */
    /**
     * Accessor để lấy placeholders dạng array
     */
    public function getPlaceholdersAttribute(): array
    {
        return $this->placeholders_json ?? [];
    }

    /* ----------------- Helpers ----------------- */
    /**
     * Trả về view path dùng cho render (engine BLADE).
     * Với engine khác (DOCX_MERGE/HTML_TO_PDF) bạn xử lý ở service generate.
     */
    public function viewPath(): ?string
    {
        return $this->isBlade() ? $this->body_path : null;
    }
}
