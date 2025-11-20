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
        'engine',
        'body_path',
        'content',
        'placeholders_json',
        'description',
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

    /**
     * Các appendix sử dụng template này
     */
    public function appendixes()
    {
        return $this->hasMany(ContractAppendix::class, 'template_id');
    }

    /**
     * Placeholder mappings cho template này
     */
    public function placeholderMappings()
    {
        return $this->hasMany(ContractAppendixTemplatePlaceholderMapping::class, 'appendix_template_id')->orderBy('display_order');
    }

    /**
     * User đã update template lần cuối
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ----------------- Scopes ----------------- */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $appendixType)
    {
        return $query->where('appendix_type', $appendixType);
    }

    public function scopeLatestVersion($query)
    {
        return $query->orderByDesc('version');
    }

    /* ----------------- Helpers ----------------- */

    public function isDocxMerge(): bool
    {
        return $this->engine === 'DOCX_MERGE';
    }

    /**
     * Accessor để lấy placeholders dạng array
     */
    public function getPlaceholdersAttribute(): array
    {
        return $this->placeholders_json ?? [];
    }

    /**
     * Trả về view path (hiện tại chỉ dùng cho DOCX_MERGE)
     */
    public function viewPath(): ?string
    {
        return $this->isDocxMerge() ? $this->body_path : null;
    }
}
