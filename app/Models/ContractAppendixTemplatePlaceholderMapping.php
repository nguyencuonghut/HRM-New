<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAppendixTemplatePlaceholderMapping extends Model
{
    use HasUuids;

    protected $fillable = [
        'appendix_template_id',
        'placeholder_key',
        'data_source',
        'source_path',
        'default_value',
        'transformer',
        'formula',
        'validation_rules',
        'is_required',
        'display_order',
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Appendix template mà mapping này thuộc về
     */
    public function appendixTemplate(): BelongsTo
    {
        return $this->belongsTo(ContractAppendixTemplate::class, 'appendix_template_id');
    }

    /**
     * Scope: chỉ lấy mappings của data source cụ thể
     */
    public function scopeOfSource($query, string $source)
    {
        return $query->where('data_source', $source);
    }

    /**
     * Scope: chỉ lấy required mappings
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Check xem mapping có cần user input không
     */
    public function requiresManualInput(): bool
    {
        return $this->data_source === 'MANUAL';
    }

    /**
     * Get validation rules cho manual input
     */
    public function getValidationRulesAttribute($value): array
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }
}
