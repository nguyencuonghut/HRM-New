<?php

namespace Tests\Unit;

use App\Models\InsuranceComponent;
use App\Models\InsuranceParticipationComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\InsuranceComponentSeeder::class);
    }

    /** @test */
    public function it_can_create_insurance_component()
    {
        $component = InsuranceComponent::create([
            'code' => 'TEST_COMPONENT',
            'name_vi' => 'Component Test',
            'default_rate_total' => 0.05000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('insurance_components', [
            'code' => 'TEST_COMPONENT',
            'name_vi' => 'Component Test',
        ]);

        $this->assertTrue($component->is_active);
        $this->assertEquals(0.05000, $component->default_rate_total);
    }

    /** @test */
    public function it_has_unique_code_constraint()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        InsuranceComponent::create([
            'code' => 'RETIREMENT_SURVIVOR',
            'name_vi' => 'Duplicate',
            'default_rate_total' => 0.10000,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_query_active_components()
    {
        // Create inactive component
        InsuranceComponent::create([
            'code' => 'INACTIVE_TEST',
            'name_vi' => 'Inactive Component',
            'default_rate_total' => 0.01000,
            'is_active' => false,
        ]);

        $activeComponents = InsuranceComponent::active()->get();
        $allComponents = InsuranceComponent::all();

        $this->assertEquals(5, $activeComponents->count()); // 5 seeded components are active
        $this->assertEquals(6, $allComponents->count()); // 5 + 1 inactive
    }

    /** @test */
    public function it_has_rate_percentage_attribute()
    {
        $component = InsuranceComponent::where('code', 'RETIREMENT_SURVIVOR')->first();

        $this->assertEquals('22.00%', $component->rate_percentage);
    }

    /** @test */
    public function it_has_participation_components_relationship()
    {
        $component = InsuranceComponent::where('code', 'HEALTH')->first();

        // Initially no participation components
        $this->assertEquals(0, $component->participationComponents()->count());

        // Create a participation component
        $participation = \App\Models\InsuranceParticipation::factory()->create();
        InsuranceParticipationComponent::create([
            'insurance_participation_id' => $participation->id,
            'component_id' => $component->id,
            'is_enabled' => true,
            'rate_total' => 0.045,
            'base_type' => 'INSURANCE_SALARY',
        ]);

        $component->refresh();
        $this->assertEquals(1, $component->participationComponents()->count());
    }

    /** @test */
    public function it_can_update_component_details()
    {
        $component = InsuranceComponent::where('code', 'UNEMPLOYMENT')->first();

        $component->update([
            'name_vi' => 'BHTN - Updated Name',
            'default_rate_total' => 0.025, // Changed from 2% to 2.5%
        ]);

        $this->assertDatabaseHas('insurance_components', [
            'code' => 'UNEMPLOYMENT',
            'name_vi' => 'BHTN - Updated Name',
            'default_rate_total' => 0.025,
        ]);
    }

    /** @test */
    public function it_can_deactivate_component()
    {
        $component = InsuranceComponent::where('code', 'OCC_ACCIDENT_DISEASE')->first();
        $this->assertTrue($component->is_active);

        $component->update(['is_active' => false]);

        $this->assertFalse($component->fresh()->is_active);
        $this->assertEquals(4, InsuranceComponent::active()->count());
    }

    /** @test */
    public function all_seeded_components_exist()
    {
        $expectedCodes = [
            'RETIREMENT_SURVIVOR',
            'SICKNESS_MATERNITY',
            'OCC_ACCIDENT_DISEASE',
            'UNEMPLOYMENT',
            'HEALTH',
        ];

        foreach ($expectedCodes as $code) {
            $this->assertDatabaseHas('insurance_components', [
                'code' => $code,
                'is_active' => true,
            ]);
        }
    }

    /** @test */
    public function seeded_components_have_correct_default_rates()
    {
        $expectedRates = [
            'RETIREMENT_SURVIVOR' => 0.22000,
            'SICKNESS_MATERNITY' => 0.03000,
            'OCC_ACCIDENT_DISEASE' => 0.00500,
            'UNEMPLOYMENT' => 0.02000,
            'HEALTH' => 0.04500,
        ];

        foreach ($expectedRates as $code => $rate) {
            $component = InsuranceComponent::where('code', $code)->first();
            $this->assertEquals($rate, $component->default_rate_total);
        }
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $component = InsuranceComponent::where('code', 'HEALTH')->first();

        $this->assertIsBool($component->is_active);
        $this->assertIsString($component->default_rate_total); // Decimal cast returns string
        $this->assertMatchesRegularExpression('/^\d+\.\d{5}$/', $component->default_rate_total);
    }
}
