<?php

namespace Tests\Unit;

use App\Models\InsuranceComponent;
use App\Models\InsuranceParticipation;
use App\Models\InsuranceParticipationComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceParticipationComponentTest extends TestCase
{
    use RefreshDatabase;

    protected InsuranceParticipation $participation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\InsuranceComponentSeeder::class);
        $this->participation = InsuranceParticipation::factory()->create();
    }

    /** @test */
    public function it_can_create_participation_component()
    {
        $component = InsuranceComponent::where('code', 'RETIREMENT_SURVIVOR')->first();

        $participationComponent = InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => $component->id,
            'is_enabled' => true,
            'rate_total' => 0.22000,
            'base_type' => 'INSURANCE_SALARY',
            'base_amount' => null,
        ]);

        $this->assertDatabaseHas('insurance_participation_components', [
            'insurance_participation_id' => $this->participation->id,
            'component_id' => $component->id,
            'is_enabled' => true,
        ]);

        $this->assertEquals('INSURANCE_SALARY', $participationComponent->base_type);
    }

    /** @test */
    public function it_enforces_unique_constraint_on_participation_and_component()
    {
        // Create first component
        InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'HEALTH')->first()->id,
            'is_enabled' => true,
            'rate_total' => 0.045,
            'base_type' => 'INSURANCE_SALARY',
        ]);

        // Attempt to create duplicate
        $this->expectException(\Illuminate\Database\QueryException::class);

        InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'HEALTH')->first()->id,
            'is_enabled' => false,
            'rate_total' => 0.050,
            'base_type' => 'FIXED_AMOUNT',
        ]);
    }

    /** @test */
    public function it_belongs_to_participation()
    {
        $participationComponent = InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'UNEMPLOYMENT')->first()->id,
            'is_enabled' => true,
            'rate_total' => 0.02,
            'base_type' => 'INSURANCE_SALARY',
        ]);

        $this->assertInstanceOf(InsuranceParticipation::class, $participationComponent->participation);
        $this->assertEquals($this->participation->id, $participationComponent->participation->id);
    }

    /** @test */
    public function it_belongs_to_component()
    {
        $participationComponent = InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'OCC_ACCIDENT_DISEASE')->first()->id,
            'is_enabled' => true,
            'rate_total' => 0.005,
            'base_type' => 'INSURANCE_SALARY',
        ]);

        $this->assertInstanceOf(InsuranceComponent::class, $participationComponent->component);
        $this->assertEquals('OCC_ACCIDENT_DISEASE', $participationComponent->component->code);
    }

    /** @test */
    public function it_can_query_enabled_components()
    {
        // Create enabled component
        InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'RETIREMENT_SURVIVOR')->first()->id,
            'is_enabled' => true,
            'rate_total' => 0.22,
            'base_type' => 'INSURANCE_SALARY',
        ]);

        // Create disabled component
        InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'HEALTH')->first()->id,
            'is_enabled' => false,
            'rate_total' => 0.045,
            'base_type' => 'INSURANCE_SALARY',
        ]);

        $enabledComponents = InsuranceParticipationComponent::enabled()->get();
        $allComponents = InsuranceParticipationComponent::all();

        $this->assertEquals(1, $enabledComponents->count());
        $this->assertEquals(2, $allComponents->count());
    }

    /** @test */
    public function it_supports_insurance_salary_base_type()
    {
        $participationComponent = InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'SICKNESS_MATERNITY')->first()->id,
            'is_enabled' => true,
            'rate_total' => 0.03,
            'base_type' => 'INSURANCE_SALARY',
            'base_amount' => null,
        ]);

        $this->assertEquals('INSURANCE_SALARY', $participationComponent->base_type);
        $this->assertFalse($participationComponent->isFixedAmount());
        $this->assertNull($participationComponent->base_amount);
    }

    /** @test */
    public function it_supports_fixed_amount_base_type()
    {
        $participationComponent = InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'UNEMPLOYMENT')->first()->id,
            'is_enabled' => true,
            'rate_total' => 0.02,
            'base_type' => 'FIXED_AMOUNT',
            'base_amount' => 72000000.00, // 72M VND for BHTN
        ]);

        $this->assertEquals('FIXED_AMOUNT', $participationComponent->base_type);
        $this->assertTrue($participationComponent->isFixedAmount());
        $this->assertEquals(72000000.00, $participationComponent->base_amount);
    }

    /** @test */
    public function it_can_update_participation_component()
    {
        $participationComponent = InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'HEALTH')->first()->id,
            'is_enabled' => true,
            'rate_total' => 0.045,
            'base_type' => 'INSURANCE_SALARY',
        ]);

        $participationComponent->update([
            'is_enabled' => false,
            'rate_total' => 0.050,
            'note' => 'Disabled temporarily',
        ]);

        $this->assertDatabaseHas('insurance_participation_components', [
            'id' => $participationComponent->id,
            'is_enabled' => false,
            'rate_total' => 0.050,
            'note' => 'Disabled temporarily',
        ]);
    }

    /** @test */
    public function it_can_delete_participation_component()
    {
        $participationComponent = InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'RETIREMENT_SURVIVOR')->first()->id,
            'is_enabled' => true,
            'rate_total' => 0.22,
            'base_type' => 'INSURANCE_SALARY',
        ]);

        $id = $participationComponent->id;
        $participationComponent->delete();

        $this->assertDatabaseMissing('insurance_participation_components', [
            'id' => $id,
        ]);
    }

    /** @test */
    public function participation_can_have_multiple_components()
    {
        $componentCodes = ['RETIREMENT_SURVIVOR', 'SICKNESS_MATERNITY', 'OCC_ACCIDENT_DISEASE', 'HEALTH', 'UNEMPLOYMENT'];

        foreach ($componentCodes as $code) {
            $component = InsuranceComponent::where('code', $code)->first();
            InsuranceParticipationComponent::create([
                'insurance_participation_id' => $this->participation->id,
                'component_id' => $component->id,
                'is_enabled' => true,
                'rate_total' => $component->default_rate_total,
                'base_type' => 'INSURANCE_SALARY',
            ]);
        }

        $this->assertEquals(5, $this->participation->components()->count());
    }

    /** @test */
    public function it_cascades_delete_when_participation_is_deleted()
    {
        $participationComponent = InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'HEALTH')->first()->id,
            'is_enabled' => true,
            'rate_total' => 0.045,
            'base_type' => 'INSURANCE_SALARY',
        ]);

        $componentId = $participationComponent->id;

        // Delete participation
        $this->participation->delete();

        // Component should be deleted too
        $this->assertDatabaseMissing('insurance_participation_components', [
            'id' => $componentId,
        ]);
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $participationComponent = InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'UNEMPLOYMENT')->first()->id,
            'is_enabled' => true,
            'rate_total' => 0.02000,
            'base_type' => 'FIXED_AMOUNT',
            'base_amount' => 72000000.00,
        ]);

        $this->assertIsBool($participationComponent->is_enabled);
        $this->assertIsString($participationComponent->rate_total); // Decimal cast
        $this->assertIsString($participationComponent->base_amount); // Decimal cast
        $this->assertMatchesRegularExpression('/^\d+\.\d{5}$/', $participationComponent->rate_total);
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $participationComponent->base_amount);
    }

    /** @test */
    public function it_can_have_note_field()
    {
        $participationComponent = InsuranceParticipationComponent::create([
            'insurance_participation_id' => $this->participation->id,
            'component_id' => InsuranceComponent::where('code', 'UNEMPLOYMENT')->first()->id,
            'is_enabled' => true,
            'rate_total' => 0.02,
            'base_type' => 'FIXED_AMOUNT',
            'base_amount' => 72000000.00,
            'note' => 'BHTN with fixed base 72M',
        ]);

        $this->assertEquals('BHTN with fixed base 72M', $participationComponent->note);
    }
}
