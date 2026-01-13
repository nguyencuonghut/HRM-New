<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\InsuranceParticipation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InsuranceParticipation>
 */
class InsuranceParticipationFactory extends Factory
{
    protected $model = InsuranceParticipation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Create a real employee with minimal data
        $employee = Employee::create([
            'id' => Str::uuid(),
            'employee_code' => 'EMP' . $this->faker->unique()->numberBetween(1000, 9999),
            'full_name' => $this->faker->name,
            'dob' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['MALE', 'FEMALE']),
            'phone' => $this->faker->phoneNumber,
            'personal_email' => $this->faker->unique()->safeEmail,
        ]);

        return [
            'id' => Str::uuid(),
            'employee_id' => $employee->id,
            'participation_start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'participation_end_date' => null,
            'has_social_insurance' => true,
            'has_health_insurance' => true,
            'has_unemployment_insurance' => true,
            'insurance_salary' => 10000000, // 10M VND
            'status' => 'ACTIVE',
            'contract_id' => null,
            'contract_appendix_id' => null,
        ];
    }

    /**
     * Indicate participation with all insurance types enabled
     */
    public function withAllInsurance(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_social_insurance' => true,
            'has_health_insurance' => true,
            'has_unemployment_insurance' => true,
        ]);
    }

    /**
     * Indicate participation with only social insurance
     */
    public function onlySocial(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_social_insurance' => true,
            'has_health_insurance' => false,
            'has_unemployment_insurance' => false,
        ]);
    }

    /**
     * Indicate ended participation
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }
}
