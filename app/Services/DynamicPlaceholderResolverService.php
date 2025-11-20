<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractTemplatePlaceholderMapping;
use App\Models\ContractAppendixTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DynamicPlaceholderResolverService
{
    /**
     * Resolve tất cả placeholders cho contract dựa theo mappings
     *
     * @param Contract|ContractAppendix|object $contract Contract/Appendix model hoặc mock object (stdClass)
     * @param ContractTemplate|ContractAppendixTemplate|object $template Template có placeholderMappings relationship
     * @param array $manualData Data người dùng nhập cho MANUAL placeholders
     * @return array Key-value pairs để merge vào DOCX
     */
    public static function resolve(object $contract, object $template, array $manualData = []): array
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
    protected static function resolveMapping(object $contract, object $mapping, array $manualData)
    {
        $value = match ($mapping->data_source) {
            'CONTRACT' => self::extractFromContract($contract, $mapping->source_path),
            'COMPUTED' => self::computeValue($contract, $mapping->formula ?? $mapping->source_path),
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

        // Detect if this is ContractAppendix
        $isAppendix = isset($contract->contract);

        // Auto-fix path for appendix context:
        // 1. If path starts with 'contractAppendix.', strip it (since $contract IS the appendix)
        if ($isAppendix && str_starts_with($path, 'contractAppendix.')) {
            $path = substr($path, strlen('contractAppendix.'));
        }

        // 2. If path starts with employee/department/position but object is appendix, prepend 'contract.'
        if ($isAppendix && preg_match('/^(employee|department|position)\./', $path)) {
            $path = 'contract.' . $path;
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

        // Detect if this is ContractAppendix or Contract
        // ContractAppendix has $contract property, Contract doesn't
        $isAppendix = isset($contract->contract);

        // Get employee reference based on object type
        $employee = $isAppendix
            ? ($contract->contract->employee ?? null)
            : ($contract->employee ?? null);

        // Simple formula evaluation
        return match ($formula) {
            'total_salary' => $contract->base_salary + $contract->position_allowance,
            'contract_duration_months' => self::calculateContractDuration($contract),
            'probation_duration_days' => self::calculateProbationDuration($contract),
            'employee_full_address' => $employee ? self::buildFullAddress($employee, 'permanent') : null,
            'employee_temp_full_address' => $employee ? self::buildFullAddress($employee, 'temporary') : null,
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
            'currency_to_words' => \App\Helpers\NumberToWords::convert($value, 'đồng'),
            'date_vn' => $value ? Carbon::parse($value)->format('d/m/Y') : '',
            'datetime_vn' => $value ? Carbon::parse($value)->format('d/m/Y H:i') : '',
            'gender_vn' => self::transformGender($value),
            'marital_status_vn' => self::transformMaritalStatus($value),
            'contract_type_vn' => self::transformContractType($value),
            'uppercase' => mb_strtoupper((string)$value),
            'lowercase' => mb_strtolower((string)$value),
            'ucfirst' => mb_convert_case((string)$value, MB_CASE_TITLE),
            default => $value,
        };
    }

    /**
     * Transform gender enum to Vietnamese
     */
    protected static function transformGender($value): string
    {
        return match (strtolower((string)$value)) {
            'male', 'nam' => 'Nam',
            'female', 'nữ', 'nu' => 'Nữ',
            'other', 'khác', 'khac' => 'Khác',
            default => (string)$value,
        };
    }

    /**
     * Transform marital status to Vietnamese
     */
    protected static function transformMaritalStatus($value): string
    {
        return match (strtolower((string)$value)) {
            'single', 'độc thân' => 'Độc thân',
            'married', 'đã kết hôn', 'kết hôn' => 'Đã kết hôn',
            'divorced', 'ly hôn' => 'Ly hôn',
            'widowed', 'góa' => 'Góa',
            default => (string)$value,
        };
    }

    /**
     * Transform contract type to Vietnamese
     */
    protected static function transformContractType($value): string
    {
        return match (strtolower((string)$value)) {
            'probation', 'thử việc' => 'Hợp đồng thử việc',
            'definite', 'xác định' => 'Hợp đồng xác định thời hạn',
            'indefinite', 'không xác định' => 'Hợp đồng không xác định thời hạn',
            'seasonal', 'theo mùa' => 'Hợp đồng theo mùa vụ',
            default => (string)$value,
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
     * Build full address from employee ward relationship
     * Format: street + ', ' + ward.name + ', ' + province.name
     */
    protected static function buildFullAddress($employee, string $type = 'permanent'): ?string
    {
        if (!$employee) {
            return null;
        }

        $parts = [];

        if ($type === 'temporary') {
            // Temporary address
            if (!empty($employee->temp_address_street)) {
                $parts[] = $employee->temp_address_street;
            }

            // Load temp_ward relationship if available
            if (!empty($employee->temp_ward_id)) {
                $ward = $employee->tempWard ?? null;
                if ($ward) {
                    if (!empty($ward->full_name)) {
                        $parts[] = $ward->full_name;
                    } elseif (!empty($ward->name)) {
                        $parts[] = $ward->name;
                    }

                    // Get province from ward
                    if (!empty($ward->province_id)) {
                        $province = $ward->province ?? null;
                        if ($province) {
                            if (!empty($province->full_name)) {
                                $parts[] = $province->full_name;
                            } elseif (!empty($province->name)) {
                                $parts[] = $province->name;
                            }
                        }
                    }
                }
            }
        } else {
            // Permanent address
            if (!empty($employee->address_street)) {
                $parts[] = $employee->address_street;
            }

            // Load ward relationship
            if (!empty($employee->ward_id)) {
                $ward = $employee->ward ?? null;
                if ($ward) {
                    if (!empty($ward->full_name)) {
                        $parts[] = $ward->full_name;
                    } elseif (!empty($ward->name)) {
                        $parts[] = $ward->name;
                    }

                    // Get province from ward
                    if (!empty($ward->province_id)) {
                        $province = $ward->province ?? null;
                        if ($province) {
                            if (!empty($province->full_name)) {
                                $parts[] = $province->full_name;
                            } elseif (!empty($province->name)) {
                                $parts[] = $province->name;
                            }
                        }
                    }
                }
            }
        }

        return !empty($parts) ? implode(', ', $parts) : null;
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
