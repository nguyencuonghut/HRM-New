<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractTemplatePlaceholderMapping;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DynamicPlaceholderResolverService
{
    /**
     * Resolve tất cả placeholders cho contract dựa theo mappings
     *
     * @param Contract|object $contract Contract model hoặc mock object (stdClass)
     * @param ContractTemplate $template
     * @param array $manualData Data người dùng nhập cho MANUAL placeholders
     * @return array Key-value pairs để merge vào DOCX
     */
    public static function resolve(object $contract, ContractTemplate $template, array $manualData = []): array
    {
        $mappings = $template->placeholderMappings;
        $resolvedData = [];

        foreach ($mappings as $mapping) {
            try {
                $value = self::resolveMapping($contract, $mapping, $manualData);
                $resolvedData[$mapping->placeholder_key] = $value;
            } catch (\Exception $e) {
                Log::warning("Failed to resolve placeholder: {$mapping->placeholder_key}", [
                    'error' => $e->getMessage(),
                    'contract_id' => $contract->id,
                ]);
                $resolvedData[$mapping->placeholder_key] = $mapping->default_value ?? '';
            }
        }

        return $resolvedData;
    }

    /**
     * Resolve 1 mapping cụ thể
     */
    protected static function resolveMapping(object $contract, ContractTemplatePlaceholderMapping $mapping, array $manualData)
    {
        $value = match ($mapping->data_source) {
            'CONTRACT' => self::extractFromContract($contract, $mapping->source_path),
            'COMPUTED' => self::computeValue($contract, $mapping->formula),
            'MANUAL' => $manualData[$mapping->placeholder_key] ?? $mapping->default_value,
            'SYSTEM' => self::getSystemValue($mapping->source_path),
            default => $mapping->default_value,
        };

        // Debug logging
        Log::debug("Resolving placeholder: {$mapping->placeholder_key}", [
            'data_source' => $mapping->data_source,
            'source_path' => $mapping->source_path,
            'raw_value' => $value,
            'transformer' => $mapping->transformer,
        ]);

        // Apply transformer nếu có
        if ($mapping->transformer && $value !== null) {
            $value = self::transform($value, $mapping->transformer);
            Log::debug("After transform: {$mapping->placeholder_key} = {$value}");
        }

        return $value ?? $mapping->default_value ?? '';
    }

    /**
     * Extract data từ Contract model bằng dot notation
     * Supports both Eloquent models and stdClass objects
     */
    protected static function extractFromContract(object $contract, ?string $path): mixed
    {
        if (empty($path)) {
            return null;
        }

        // For Eloquent models, data_get works fine
        // For stdClass, need manual traversal
        $segments = explode('.', $path);
        $current = $contract;

        foreach ($segments as $segment) {
            if (is_object($current)) {
                // Try property access for both Eloquent and stdClass
                if (isset($current->$segment)) {
                    $current = $current->$segment;
                } elseif (method_exists($current, $segment)) {
                    // For Eloquent relationships
                    $current = $current->$segment;
                } else {
                    return null;
                }
            } elseif (is_array($current)) {
                if (isset($current[$segment])) {
                    $current = $current[$segment];
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Compute value từ formula
     */
    protected static function computeValue(object $contract, ?string $formula): mixed
    {
        if (empty($formula)) {
            return null;
        }

        // Simple formula evaluation
        // TODO: Implement safe expression evaluator hoặc dùng symfony/expression-language

        // Temporary: support một số computed fields phổ biến
        return match ($formula) {
            'total_salary' => $contract->base_salary + $contract->position_allowance,
            'contract_duration_months' => self::calculateContractDuration($contract),
            'probation_duration_days' => self::calculateProbationDuration($contract),
            default => null,
        };
    }

    /**
     * Lấy giá trị hệ thống (ngày hiện tại, tên công ty, etc)
     */
    protected static function getSystemValue(?string $path): mixed
    {
        if (empty($path)) {
            return null;
        }

        return match ($path) {
            'today' => Carbon::now()->format('d/m/Y'),
            'now' => Carbon::now()->format('d/m/Y H:i:s'),
            'current_year' => Carbon::now()->year,
            'company_name' => config('app.company_name', 'Công ty'),
            default => null,
        };
    }

    /**
     * Transform giá trị theo transformer được chỉ định
     */
    protected static function transform(mixed $value, string $transformer): mixed
    {
        return match ($transformer) {
            'number_format' => number_format((float)$value, 0, ',', '.'),
            'date_vn' => $value ? Carbon::parse($value)->format('d/m/Y') : '',
            'datetime_vn' => $value ? Carbon::parse($value)->format('d/m/Y H:i') : '',
            'uppercase' => mb_strtoupper((string)$value),
            'lowercase' => mb_strtolower((string)$value),
            'ucfirst' => mb_convert_case((string)$value, MB_CASE_TITLE),
            default => $value,
        };
    }

    /**
     * Tính thời gian hợp đồng (tháng)
     */
    protected static function calculateContractDuration(object $contract): ?int
    {
        if (!$contract->start_date || !$contract->end_date) {
            return null;
        }

        $start = Carbon::parse($contract->start_date);
        $end = Carbon::parse($contract->end_date);

        return $start->diffInMonths($end);
    }

    /**
     * Tính thời gian thử việc (ngày)
     */
    protected static function calculateProbationDuration(object $contract): ?int
    {
        if (!$contract->start_date || !$contract->probation_end_date) {
            return null;
        }

        $start = Carbon::parse($contract->start_date);
        $end = Carbon::parse($contract->probation_end_date);

        return $start->diffInDays($end);
    }

    /**
     * Get danh sách placeholders cần manual input
     */
    public static function getManualPlaceholders(ContractTemplate $template): array
    {
        return $template->placeholderMappings()
            ->where('data_source', 'MANUAL')
            ->get()
            ->map(fn($m) => [
                'key' => $m->placeholder_key,
                'default' => $m->default_value,
                'required' => $m->is_required,
                'rules' => $m->validation_rules,
            ])
            ->toArray();
    }
}
