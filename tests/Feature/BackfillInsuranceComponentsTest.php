<?php

namespace Tests\Feature;

use App\Models\InsuranceComponent;
use App\Models\InsuranceParticipation;
use App\Models\InsuranceParticipationComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillInsuranceComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\InsuranceComponentSeeder::class);
    }

    /** @test */
    public function it_backfills_all_three_social_insurance_components_when_has_social_insurance_is_true()
    {
        // Create participation with only social insurance
        $participation = InsuranceParticipation::factory()->create([
            'has_social_insurance' => true,
            'has_health_insurance' => false,
            'has_unemployment_insurance' => false,
        ]);

        $this->artisan('insurance:backfill-components')
            ->assertExitCode(0);

        // Should create 3 components
        $this->assertEquals(3, $participation->components()->count());

        // Verify correct components
        $componentCodes = $participation->components->map(fn($pc) => $pc->component->code)->toArray();
        $this->assertContains('RETIREMENT_SURVIVOR', $componentCodes);
        $this->assertContains('SICKNESS_MATERNITY', $componentCodes);
        $this->assertContains('OCC_ACCIDENT_DISEASE', $componentCodes);

        // Verify properties
        $participation->components->each(function ($pc) {
            $this->assertTrue($pc->is_enabled);
            $this->assertEquals('INSURANCE_SALARY', $pc->base_type);
            $this->assertNull($pc->base_amount);
            $this->assertGreaterThan(0, $pc->rate_total);
        });
    }

    /** @test */
    public function it_backfills_health_component_when_has_health_insurance_is_true()
    {
        $participation = InsuranceParticipation::factory()->create([
            'has_social_insurance' => false,
            'has_health_insurance' => true,
            'has_unemployment_insurance' => false,
        ]);

        $this->artisan('insurance:backfill-components')
            ->assertExitCode(0);

        $this->assertEquals(1, $participation->components()->count());
        $this->assertEquals('HEALTH', $participation->components->first()->component->code);
    }

    /** @test */
    public function it_backfills_unemployment_component_when_has_unemployment_insurance_is_true()
    {
        $participation = InsuranceParticipation::factory()->create([
            'has_social_insurance' => false,
            'has_health_insurance' => false,
            'has_unemployment_insurance' => true,
        ]);

        $this->artisan('insurance:backfill-components')
            ->assertExitCode(0);

        $this->assertEquals(1, $participation->components()->count());
        $this->assertEquals('UNEMPLOYMENT', $participation->components->first()->component->code);
    }

    /** @test */
    public function it_backfills_all_five_components_when_all_booleans_are_true()
    {
        $participation = InsuranceParticipation::factory()->create([
            'has_social_insurance' => true,
            'has_health_insurance' => true,
            'has_unemployment_insurance' => true,
        ]);

        $this->artisan('insurance:backfill-components')
            ->assertExitCode(0);

        $this->assertEquals(5, $participation->components()->count());

        $componentCodes = $participation->components->map(fn($pc) => $pc->component->code)->toArray();
        $this->assertContains('RETIREMENT_SURVIVOR', $componentCodes);
        $this->assertContains('SICKNESS_MATERNITY', $componentCodes);
        $this->assertContains('OCC_ACCIDENT_DISEASE', $componentCodes);
        $this->assertContains('HEALTH', $componentCodes);
        $this->assertContains('UNEMPLOYMENT', $componentCodes);
    }

    /** @test */
    public function it_creates_no_components_when_all_booleans_are_false()
    {
        $participation = InsuranceParticipation::factory()->create([
            'has_social_insurance' => false,
            'has_health_insurance' => false,
            'has_unemployment_insurance' => false,
        ]);

        $this->artisan('insurance:backfill-components')
            ->assertExitCode(0);

        $this->assertEquals(0, $participation->components()->count());
    }

    /** @test */
    public function it_is_idempotent_and_does_not_create_duplicates()
    {
        $participation = InsuranceParticipation::factory()->create([
            'has_social_insurance' => true,
            'has_health_insurance' => true,
            'has_unemployment_insurance' => true,
        ]);

        // Run backfill first time
        $this->artisan('insurance:backfill-components')
            ->assertExitCode(0);

        $this->assertEquals(5, $participation->components()->count());
        $firstRunIds = $participation->components->pluck('id')->sort()->values()->toArray();

        // Run backfill second time
        $this->artisan('insurance:backfill-components')
            ->assertExitCode(0);

        // Should still have 5 components with same IDs
        $participation->refresh();
        $this->assertEquals(5, $participation->components()->count());
        $secondRunIds = $participation->components->pluck('id')->sort()->values()->toArray();

        $this->assertEquals($firstRunIds, $secondRunIds);
    }

    /** @test */
    public function it_supports_dry_run_mode_without_making_changes()
    {
        $participation = InsuranceParticipation::factory()->create([
            'has_social_insurance' => true,
            'has_health_insurance' => true,
            'has_unemployment_insurance' => true,
        ]);

        $this->artisan('insurance:backfill-components', ['--dry-run' => true])
            ->assertExitCode(0);

        // No components should be created
        $this->assertEquals(0, $participation->components()->count());
        $this->assertEquals(0, InsuranceParticipationComponent::count());
    }

    /** @test */
    public function it_can_filter_by_specific_employee()
    {
        $participation1 = InsuranceParticipation::factory()->create([
            'has_social_insurance' => true,
            'has_health_insurance' => false,
            'has_unemployment_insurance' => false,
        ]);

        $participation2 = InsuranceParticipation::factory()->create([
            'has_social_insurance' => true,
            'has_health_insurance' => false,
            'has_unemployment_insurance' => false,
        ]);

        $this->artisan('insurance:backfill-components', ['--employee' => $participation1->employee_id])
            ->assertExitCode(0);

        // Only first participation should have components
        $this->assertEquals(3, $participation1->components()->count());
        $this->assertEquals(0, $participation2->components()->count());
    }

    /** @test */
    public function it_can_limit_number_of_participations_to_process()
    {
        // Create 5 participations
        $participations = InsuranceParticipation::factory()->count(5)->create([
            'has_social_insurance' => true,
            'has_health_insurance' => false,
            'has_unemployment_insurance' => false,
        ]);

        $this->artisan('insurance:backfill-components', ['--limit' => 3])
            ->assertExitCode(0);

        // Only first 3 should have components (based on created_at order)
        $withComponents = InsuranceParticipation::has('components')->count();
        $this->assertEquals(3, $withComponents);

        $totalComponents = InsuranceParticipationComponent::count();
        $this->assertEquals(9, $totalComponents); // 3 participations * 3 components each
    }

    /** @test */
    public function it_uses_default_rate_from_component()
    {
        $participation = InsuranceParticipation::factory()->create([
            'has_social_insurance' => false,
            'has_health_insurance' => true,
            'has_unemployment_insurance' => false,
        ]);

        $this->artisan('insurance:backfill-components')
            ->assertExitCode(0);

        $healthComponent = InsuranceComponent::where('code', 'HEALTH')->first();
        $participationComponent = $participation->components->first();

        $this->assertEquals($healthComponent->default_rate_total, $participationComponent->rate_total);
        $this->assertEquals(0.04500, $participationComponent->rate_total); // 4.5%
    }

    /** @test */
    public function it_sets_note_field_to_backfilled_message()
    {
        $participation = InsuranceParticipation::factory()->create([
            'has_social_insurance' => false,
            'has_health_insurance' => true,
            'has_unemployment_insurance' => false,
        ]);

        $this->artisan('insurance:backfill-components')
            ->assertExitCode(0);

        $participationComponent = $participation->components->first();
        $this->assertEquals('Backfilled from legacy boolean fields', $participationComponent->note);
    }

    /** @test */
    public function it_fails_when_components_are_not_seeded()
    {
        // Delete all components
        InsuranceComponent::query()->delete();

        $participation = InsuranceParticipation::factory()->create([
            'has_social_insurance' => true,
            'has_health_insurance' => false,
            'has_unemployment_insurance' => false,
        ]);

        $this->artisan('insurance:backfill-components')
            ->assertExitCode(1)
            ->expectsOutput('❌ Error: Not all 5 insurance components are seeded. Run InsuranceComponentSeeder first.');
    }

    /** @test */
    public function it_handles_empty_database_gracefully()
    {
        // No participations exist
        $this->assertEquals(0, InsuranceParticipation::count());

        $this->artisan('insurance:backfill-components')
            ->assertExitCode(0)
            ->expectsOutput('⚠️  No insurance participations found to backfill.');
    }

    /** @test */
    public function it_processes_multiple_participations_with_different_combinations()
    {
        // Create various combinations
        $p1 = InsuranceParticipation::factory()->create([
            'has_social_insurance' => true,
            'has_health_insurance' => false,
            'has_unemployment_insurance' => false,
        ]);

        $p2 = InsuranceParticipation::factory()->create([
            'has_social_insurance' => false,
            'has_health_insurance' => true,
            'has_unemployment_insurance' => true,
        ]);

        $p3 = InsuranceParticipation::factory()->create([
            'has_social_insurance' => true,
            'has_health_insurance' => true,
            'has_unemployment_insurance' => true,
        ]);

        $this->artisan('insurance:backfill-components')
            ->assertExitCode(0);

        // Verify counts
        $this->assertEquals(3, $p1->fresh()->components()->count()); // 3 from social
        $this->assertEquals(2, $p2->fresh()->components()->count()); // health + unemployment
        $this->assertEquals(5, $p3->fresh()->components()->count()); // all 5

        // Total should be 10
        $this->assertEquals(10, InsuranceParticipationComponent::count());
    }

    /** @test */
    public function it_skips_participations_that_already_have_components()
    {
        $participation = InsuranceParticipation::factory()->create([
            'has_social_insurance' => true,
            'has_health_insurance' => false,
            'has_unemployment_insurance' => false,
        ]);

        // Manually create one component
        $component = InsuranceComponent::where('code', 'RETIREMENT_SURVIVOR')->first();
        InsuranceParticipationComponent::create([
            'insurance_participation_id' => $participation->id,
            'component_id' => $component->id,
            'is_enabled' => true,
            'rate_total' => $component->default_rate_total,
            'base_type' => 'INSURANCE_SALARY',
        ]);

        $this->artisan('insurance:backfill-components')
            ->assertExitCode(0);

        // Should have all 3 components now (not duplicates)
        $this->assertEquals(3, $participation->components()->count());

        // Verify no duplicates of RETIREMENT_SURVIVOR
        $retirementCount = $participation->components()
            ->whereHas('component', fn($q) => $q->where('code', 'RETIREMENT_SURVIVOR'))
            ->count();
        $this->assertEquals(1, $retirementCount);
    }
}
