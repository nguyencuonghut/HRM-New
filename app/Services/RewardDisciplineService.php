<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeRewardDiscipline;
use App\Enums\RewardDisciplineType;
use Illuminate\Support\Facades\DB;

class RewardDisciplineService
{
    /**
     * Get summary statistics for an employee
     */
    public function getSummaryStats(string $employeeId): array
    {
        $currentYear = now()->year;

        // Total rewards this year (amount)
        $totalRewardsAmount = EmployeeRewardDiscipline::where('employee_id', $employeeId)
            ->rewards()
            ->active()
            ->thisYear()
            ->sum('amount') ?? 0;

        // Total rewards count this year
        $totalRewardsCount = EmployeeRewardDiscipline::where('employee_id', $employeeId)
            ->rewards()
            ->active()
            ->thisYear()
            ->count();

        // Total disciplines count this year
        $totalDisciplinesCount = EmployeeRewardDiscipline::where('employee_id', $employeeId)
            ->disciplines()
            ->active()
            ->thisYear()
            ->count();

        // Total disciplines amount (fines) this year
        $totalDisciplinesAmount = EmployeeRewardDiscipline::where('employee_id', $employeeId)
            ->disciplines()
            ->active()
            ->thisYear()
            ->sum('amount') ?? 0;

        // Latest reward
        $latestReward = EmployeeRewardDiscipline::where('employee_id', $employeeId)
            ->rewards()
            ->active()
            ->latest()
            ->first();

        // Latest discipline
        $latestDiscipline = EmployeeRewardDiscipline::where('employee_id', $employeeId)
            ->disciplines()
            ->active()
            ->latest()
            ->first();

        // Compare with last year
        $lastYearRewardsAmount = EmployeeRewardDiscipline::where('employee_id', $employeeId)
            ->rewards()
            ->active()
            ->whereYear('effective_date', $currentYear - 1)
            ->sum('amount') ?? 0;

        $lastYearDisciplinesCount = EmployeeRewardDiscipline::where('employee_id', $employeeId)
            ->disciplines()
            ->active()
            ->whereYear('effective_date', $currentYear - 1)
            ->count();

        // Calculate percentage change
        $rewardsChangePercent = $this->calculatePercentChange($lastYearRewardsAmount, $totalRewardsAmount);
        $disciplinesChangePercent = $this->calculatePercentChange($lastYearDisciplinesCount, $totalDisciplinesCount);

        return [
            'total_rewards_amount' => $totalRewardsAmount,
            'total_rewards_count' => $totalRewardsCount,
            'total_disciplines_count' => $totalDisciplinesCount,
            'total_disciplines_amount' => $totalDisciplinesAmount,
            'latest_reward' => $latestReward,
            'latest_discipline' => $latestDiscipline,
            'rewards_change_percent' => $rewardsChangePercent,
            'disciplines_change_percent' => $disciplinesChangePercent,
            'current_year' => $currentYear,
        ];
    }

    /**
     * Calculate percentage change
     */
    private function calculatePercentChange($oldValue, $newValue): ?float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : null;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 1);
    }

    /**
     * Get monthly statistics for chart
     */
    public function getMonthlyStats(string $employeeId, int $year): array
    {
        $rewards = EmployeeRewardDiscipline::where('employee_id', $employeeId)
            ->rewards()
            ->active()
            ->whereYear('effective_date', $year)
            ->selectRaw('MONTH(effective_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $disciplines = EmployeeRewardDiscipline::where('employee_id', $employeeId)
            ->disciplines()
            ->active()
            ->whereYear('effective_date', $year)
            ->selectRaw('MONTH(effective_date) as month, COUNT(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        return [
            'rewards' => $rewards,
            'disciplines' => $disciplines,
        ];
    }
}
