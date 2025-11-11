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
        'type',              // PROBATION | FIXED_TERM | INDEFINITE | SERVICE | INTERNSHIP | PARTTIME
        'engine',            // BLADE | HTML_TO_PDF | DOCX_MERGE
        'body_path',         // ví dụ: 'contracts/templates/probation'
        'placeholders_json', // json danh sách biến
        'is_active',
        'version',
    ];

    protected $casts = [
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

    /* ----------------- Helpers ----------------- */
    /**
     * Trả về view path dùng cho render (engine BLADE).
     * Với engine khác (DOCX_MERGE/HTML_TO_PDF) bạn xử lý ở service generate.
     */
    public function viewPath(): ?string
    {
        return $this->engine === 'BLADE' ? $this->body_path : null;
    }
}
