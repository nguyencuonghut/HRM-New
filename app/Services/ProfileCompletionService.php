<?php

namespace App\Services;

use App\Models\Employee;

/**
 * Service tính toán mức độ hoàn thiện hồ sơ nhân viên
 *
 * Scoring Logic:
 * - Basic Info: 30 points
 * - Assignment: 25 points
 * - Education: 15 points
 * - Relatives: 10 points
 * - Experiences: 10 points
 * - Skills: 10 points
 * Total: 100 points
 */
class ProfileCompletionService
{
    /**
     * Tính toán điểm hoàn thiện hồ sơ
     *
     * @param Employee $employee
     * @return array ['score' => int, 'details' => array, 'missing' => array]
     */
    public static function calculateScore(Employee $employee): array
    {
        $details = [];
        $missing = [];
        $totalScore = 0;

        // 1. Basic Info (30 points)
        $basicScore = self::calculateBasicInfo($employee, $details, $missing);
        $totalScore += $basicScore;

        // 2. Assignment (25 points)
        $assignmentScore = self::calculateAssignment($employee, $details, $missing);
        $totalScore += $assignmentScore;

        // 3. Education (15 points)
        $educationScore = self::calculateEducation($employee, $details, $missing);
        $totalScore += $educationScore;

        // 4. Relatives (10 points)
        $relativeScore = self::calculateRelatives($employee, $details, $missing);
        $totalScore += $relativeScore;

        // 5. Experiences (10 points)
        $experienceScore = self::calculateExperiences($employee, $details, $missing);
        $totalScore += $experienceScore;

        // 6. Skills (10 points)
        $skillScore = self::calculateSkills($employee, $details, $missing);
        $totalScore += $skillScore;

        return [
            'score' => min(100, $totalScore),
            'details' => $details,
            'missing' => $missing,
        ];
    }

    /**
     * Tính điểm thông tin cơ bản (30 điểm)
     *
     * Required fields:
     * - full_name, date_of_birth, gender, phone, email (core - 10 điểm)
     * - citizen_id, citizen_id_date, citizen_id_place (CCCD - 8 điểm)
     * - permanent_address (6 điểm)
     * - tax_code, social_insurance_no (6 điểm)
     */
    private static function calculateBasicInfo(Employee $employee, array &$details, array &$missing): int
    {
        $score = 0;

        // Core info (10 points)
        $coreFields = ['full_name', 'date_of_birth', 'gender', 'phone', 'email'];
        $coreComplete = collect($coreFields)->every(fn($field) => !empty($employee->$field));
        if ($coreComplete) {
            $score += 10;
            $details[] = ['item' => 'Thông tin cơ bản', 'score' => 10, 'max' => 10, 'status' => 'complete'];
        } else {
            $missingFields = collect($coreFields)->filter(fn($f) => empty($employee->$f))->values()->toArray();
            $missing[] = ['item' => 'Thông tin cơ bản', 'fields' => $missingFields];
            $details[] = ['item' => 'Thông tin cơ bản', 'score' => 0, 'max' => 10, 'status' => 'incomplete'];
        }

        // CCCD (8 points)
        $cccdFields = ['citizen_id', 'citizen_id_date', 'citizen_id_place'];
        $cccdComplete = collect($cccdFields)->every(fn($field) => !empty($employee->$field));
        if ($cccdComplete) {
            $score += 8;
            $details[] = ['item' => 'CCCD/CMND', 'score' => 8, 'max' => 8, 'status' => 'complete'];
        } else {
            $missingFields = collect($cccdFields)->filter(fn($f) => empty($employee->$f))->values()->toArray();
            $missing[] = ['item' => 'CCCD/CMND', 'fields' => $missingFields];
            $details[] = ['item' => 'CCCD/CMND', 'score' => 0, 'max' => 8, 'status' => 'incomplete'];
        }

        // Địa chỉ (6 points)
        if (!empty($employee->permanent_address)) {
            $score += 6;
            $details[] = ['item' => 'Địa chỉ thường trú', 'score' => 6, 'max' => 6, 'status' => 'complete'];
        } else {
            $missing[] = ['item' => 'Địa chỉ thường trú', 'fields' => ['permanent_address']];
            $details[] = ['item' => 'Địa chỉ thường trú', 'score' => 0, 'max' => 6, 'status' => 'incomplete'];
        }

        // Thuế & BHXH (6 points)
        if (!empty($employee->tax_code) && !empty($employee->social_insurance_no)) {
            $score += 6;
            $details[] = ['item' => 'Mã số thuế & BHXH', 'score' => 6, 'max' => 6, 'status' => 'complete'];
        } else {
            $missingFields = [];
            if (empty($employee->tax_code)) $missingFields[] = 'tax_code';
            if (empty($employee->social_insurance_no)) $missingFields[] = 'social_insurance_no';
            $missing[] = ['item' => 'Mã số thuế & BHXH', 'fields' => $missingFields];
            $details[] = ['item' => 'Mã số thuế & BHXH', 'score' => 0, 'max' => 6, 'status' => 'incomplete'];
        }

        return $score;
    }

    /**
     * Tính điểm phân công (25 điểm)
     *
     * Requirements:
     * - Có ít nhất 1 assignment (15 điểm)
     * - Có assignment PRIMARY + ACTIVE (10 điểm thêm)
     */
    private static function calculateAssignment(Employee $employee, array &$details, array &$missing): int
    {
        $score = 0;
        $assignments = $employee->assignments ?? collect();

        if ($assignments->isEmpty()) {
            $missing[] = ['item' => 'Phân công công việc', 'fields' => ['assignments']];
            $details[] = ['item' => 'Phân công công việc', 'score' => 0, 'max' => 25, 'status' => 'incomplete'];
            return 0;
        }

        // Có ít nhất 1 assignment
        $score += 15;

        // Có PRIMARY ACTIVE assignment
        $hasPrimaryActive = $assignments->first(function ($a) {
            return $a->is_primary && $a->status === 'ACTIVE';
        });

        if ($hasPrimaryActive) {
            $score += 10;
            $details[] = ['item' => 'Phân công công việc', 'score' => 25, 'max' => 25, 'status' => 'complete'];
        } else {
            $missing[] = ['item' => 'Phân công CHÍNH đang HOẠT ĐỘNG', 'fields' => ['primary_active_assignment']];
            $details[] = ['item' => 'Phân công công việc', 'score' => 15, 'max' => 25, 'status' => 'partial'];
        }

        return $score;
    }

    /**
     * Tính điểm học vấn (15 điểm)
     *
     * Requirements:
     * - Có ít nhất 1 bản ghi education (15 điểm)
     */
    private static function calculateEducation(Employee $employee, array &$details, array &$missing): int
    {
        $educations = $employee->educations ?? collect();

        if ($educations->isEmpty()) {
            $missing[] = ['item' => 'Học vấn', 'fields' => ['educations']];
            $details[] = ['item' => 'Học vấn', 'score' => 0, 'max' => 15, 'status' => 'incomplete'];
            return 0;
        }

        $details[] = ['item' => 'Học vấn', 'score' => 15, 'max' => 15, 'status' => 'complete'];
        return 15;
    }

    /**
     * Tính điểm người thân (10 điểm)
     *
     * Requirements:
     * - Có ít nhất 1 bản ghi relative (10 điểm)
     */
    private static function calculateRelatives(Employee $employee, array &$details, array &$missing): int
    {
        $relatives = $employee->relatives ?? collect();

        if ($relatives->isEmpty()) {
            $missing[] = ['item' => 'Thông tin người thân', 'fields' => ['relatives']];
            $details[] = ['item' => 'Thông tin người thân', 'score' => 0, 'max' => 10, 'status' => 'incomplete'];
            return 0;
        }

        $details[] = ['item' => 'Thông tin người thân', 'score' => 10, 'max' => 10, 'status' => 'complete'];
        return 10;
    }

    /**
     * Tính điểm kinh nghiệm (10 điểm)
     *
     * Requirements:
     * - Có ít nhất 1 bản ghi experience (10 điểm)
     */
    private static function calculateExperiences(Employee $employee, array &$details, array &$missing): int
    {
        $experiences = $employee->experiences ?? collect();

        if ($experiences->isEmpty()) {
            $missing[] = ['item' => 'Kinh nghiệm làm việc', 'fields' => ['experiences']];
            $details[] = ['item' => 'Kinh nghiệm làm việc', 'score' => 0, 'max' => 10, 'status' => 'incomplete'];
            return 0;
        }

        $details[] = ['item' => 'Kinh nghiệm làm việc', 'score' => 10, 'max' => 10, 'status' => 'complete'];
        return 10;
    }

    /**
     * Tính điểm kỹ năng (10 điểm)
     *
     * Requirements:
     * - Có ít nhất 3 kỹ năng (10 điểm)
     * - Nếu có 1-2 kỹ năng: 5 điểm
     */
    private static function calculateSkills(Employee $employee, array &$details, array &$missing): int
    {
        $skills = $employee->employeeSkills ?? collect();
        $count = $skills->count();

        if ($count === 0) {
            $missing[] = ['item' => 'Kỹ năng', 'fields' => ['skills']];
            $details[] = ['item' => 'Kỹ năng', 'score' => 0, 'max' => 10, 'status' => 'incomplete'];
            return 0;
        }

        if ($count >= 3) {
            $details[] = ['item' => 'Kỹ năng', 'score' => 10, 'max' => 10, 'status' => 'complete'];
            return 10;
        }

        // 1-2 skills
        $missing[] = ['item' => 'Kỹ năng (cần thêm ít nhất 3)', 'fields' => ['skills']];
        $details[] = ['item' => 'Kỹ năng', 'score' => 5, 'max' => 10, 'status' => 'partial'];
        return 5;
    }

    /**
     * Get completion level label
     *
     * @param int $score
     * @return string
     */
    public static function getCompletionLevel(int $score): string
    {
        if ($score >= 90) return 'Xuất sắc';
        if ($score >= 80) return 'Tốt';
        if ($score >= 60) return 'Trung bình';
        if ($score >= 40) return 'Yếu';
        return 'Rất yếu';
    }

    /**
     * Get completion severity for UI
     *
     * @param int $score
     * @return string (success|warn|danger)
     */
    public static function getCompletionSeverity(int $score): string
    {
        if ($score >= 80) return 'success';
        if ($score >= 60) return 'warn';
        return 'danger';
    }
}
