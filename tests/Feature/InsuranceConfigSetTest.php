<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\InsuranceConfigSet;
use App\Models\InsuranceMinimumWageConfig;
use App\Models\InsuranceSalaryGradeConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceConfigSetTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Helper: Tạo config set đầy đủ với 4 vùng + 7 bậc
     */
    protected function createValidConfigSet(array $overrides = []): InsuranceConfigSet
    {
        $configSet = InsuranceConfigSet::create(array_merge([
            'code' => 'TEST_' . now()->timestamp,
            'name' => 'Test Config Set',
            'description' => 'Test description',
            'status' => InsuranceConfigSet::STATUS_DRAFT,
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'created_by' => $this->user->id,
        ], $overrides));

        // Tạo 4 vùng
        for ($region = 1; $region <= 4; $region++) {
            InsuranceMinimumWageConfig::create([
                'config_set_id' => $configSet->id,
                'region' => $region,
                'amount' => 4000000 + ($region * 100000),
            ]);
        }

        // Tạo 7 bậc
        for ($grade = 1; $grade <= 7; $grade++) {
            InsuranceSalaryGradeConfig::create([
                'config_set_id' => $configSet->id,
                'grade' => $grade,
                'name' => "Bậc {$grade}",
                'coefficient' => 1.0 + ($grade * 0.05),
                'is_active' => true,
            ]);
        }

        return $configSet->fresh(['minimumWages', 'salaryGrades']);
    }

    /**
     * Helper: Data cho create/update request
     */
    protected function getValidRequestData(): array
    {
        return [
            'code' => 'VN_INS_2025',
            'name' => 'Bảng lương BHXH 2025',
            'description' => 'Test description',
            'effective_from' => '2025-01-01',
            'effective_to' => '2025-12-31',
            'minimum_wages' => [
                ['region' => 1, 'amount' => 4960000, 'note' => 'Vùng I'],
                ['region' => 2, 'amount' => 4410000, 'note' => 'Vùng II'],
                ['region' => 3, 'amount' => 3860000, 'note' => 'Vùng III'],
                ['region' => 4, 'amount' => 3450000, 'note' => 'Vùng IV'],
            ],
            'salary_grades' => [
                ['grade' => 1, 'name' => 'Bậc 1', 'coefficient' => 1.00, 'description' => 'Nhân viên'],
                ['grade' => 2, 'name' => 'Bậc 2', 'coefficient' => 1.05, 'description' => 'Nhân viên'],
                ['grade' => 3, 'name' => 'Bậc 3', 'coefficient' => 1.10, 'description' => 'Nhân viên chính'],
                ['grade' => 4, 'name' => 'Bậc 4', 'coefficient' => 1.16, 'description' => 'Nhân viên cao cấp'],
                ['grade' => 5, 'name' => 'Bậc 5', 'coefficient' => 1.22, 'description' => 'Chuyên viên'],
                ['grade' => 6, 'name' => 'Bậc 6', 'coefficient' => 1.28, 'description' => 'Chuyên viên chính'],
                ['grade' => 7, 'name' => 'Bậc 7', 'coefficient' => 1.35, 'description' => 'Chuyên gia'],
            ],
        ];
    }

    /** @test */
    public function can_create_config_set_with_valid_data()
    {
        $data = $this->getValidRequestData();

        $response = $this->actingAs($this->user)
            ->post(route('insurance-config-sets.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('insurance_config_sets', [
            'code' => 'VN_INS_2025',
            'name' => 'Bảng lương BHXH 2025',
            'status' => 'DRAFT',
        ]);

        // Kiểm tra 4 vùng được tạo
        $configSet = InsuranceConfigSet::where('code', 'VN_INS_2025')->first();
        $this->assertCount(4, $configSet->minimumWages);

        // Kiểm tra 7 bậc được tạo
        $this->assertCount(7, $configSet->salaryGrades);
    }

    /** @test */
    public function cannot_create_config_with_missing_regions()
    {
        $data = $this->getValidRequestData();
        // Chỉ có 3 vùng thay vì 4
        $data['minimum_wages'] = array_slice($data['minimum_wages'], 0, 3);

        $response = $this->actingAs($this->user)
            ->post(route('insurance-config-sets.store'), $data);

        $response->assertSessionHasErrors('minimum_wages');
    }

    /** @test */
    public function cannot_create_config_with_missing_grades()
    {
        $data = $this->getValidRequestData();
        // Chỉ có 5 bậc thay vì 7
        $data['salary_grades'] = array_slice($data['salary_grades'], 0, 5);

        $response = $this->actingAs($this->user)
            ->post(route('insurance-config-sets.store'), $data);

        $response->assertSessionHasErrors('salary_grades');
    }

    /** @test */
    public function cannot_create_config_with_duplicate_regions()
    {
        $data = $this->getValidRequestData();
        // Region 1 bị trùng
        $data['minimum_wages'][3]['region'] = 1;

        $response = $this->actingAs($this->user)
            ->post(route('insurance-config-sets.store'), $data);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function cannot_create_config_with_duplicate_code()
    {
        $this->createValidConfigSet(['code' => 'VN_INS_2025']);

        $data = $this->getValidRequestData();
        $data['code'] = 'VN_INS_2025'; // Trùng code

        $response = $this->actingAs($this->user)
            ->post(route('insurance-config-sets.store'), $data);

        $response->assertSessionHasErrors('code');
    }

    /** @test */
    public function can_update_draft_config_set()
    {
        $configSet = $this->createValidConfigSet();

        $data = $this->getValidRequestData();
        $data['name'] = 'Updated Name';

        $response = $this->actingAs($this->user)
            ->put(route('insurance-config-sets.update', $configSet->id), $data);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('insurance_config_sets', [
            'id' => $configSet->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function cannot_update_active_config_set()
    {
        $configSet = $this->createValidConfigSet([
            'status' => InsuranceConfigSet::STATUS_ACTIVE,
            'activated_at' => now(),
            'activated_by' => $this->user->id,
        ]);

        $data = $this->getValidRequestData();

        $response = $this->actingAs($this->user)
            ->put(route('insurance-config-sets.update', $configSet->id), $data);

        $response->assertSessionHas('type', 'error');
    }

    /** @test */
    public function can_delete_draft_config_set()
    {
        $configSet = $this->createValidConfigSet();

        $response = $this->actingAs($this->user)
            ->delete(route('insurance-config-sets.destroy', $configSet->id));

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $this->assertSoftDeleted('insurance_config_sets', [
            'id' => $configSet->id,
        ]);
    }

    /** @test */
    public function cannot_delete_active_config_set()
    {
        $configSet = $this->createValidConfigSet([
            'status' => InsuranceConfigSet::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('insurance-config-sets.destroy', $configSet->id));

        $response->assertSessionHas('type', 'error');

        $this->assertDatabaseHas('insurance_config_sets', [
            'id' => $configSet->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function can_activate_valid_config_set()
    {
        $configSet = $this->createValidConfigSet();

        $response = $this->actingAs($this->user)
            ->post(route('insurance-config-sets.activate', $configSet->id));

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $configSet->refresh();
        $this->assertEquals(InsuranceConfigSet::STATUS_ACTIVE, $configSet->status);
        $this->assertNotNull($configSet->activated_at);
        $this->assertEquals($this->user->id, $configSet->activated_by);
    }

    /** @test */
    public function activate_archives_other_active_configs()
    {
        // Tạo config ACTIVE hiện tại cho 2024
        $oldActive = $this->createValidConfigSet([
            'code' => 'OLD_ACTIVE',
            'status' => InsuranceConfigSet::STATUS_ACTIVE,
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'activated_at' => now()->subMonth(),
        ]);

        // Tạo config mới cho 2025 để activate
        $newConfig = $this->createValidConfigSet([
            'code' => 'NEW_CONFIG',
            'effective_from' => '2025-01-01',
            'effective_to' => '2025-12-31',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('insurance-config-sets.activate', $newConfig->id));

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        // Kiểm tra config cũ bị archive
        $this->assertDatabaseHas('insurance_config_sets', [
            'id' => $oldActive->id,
            'status' => InsuranceConfigSet::STATUS_ARCHIVED,
        ]);

        // Kiểm tra config mới là ACTIVE
        $this->assertDatabaseHas('insurance_config_sets', [
            'id' => $newConfig->id,
            'status' => InsuranceConfigSet::STATUS_ACTIVE,
        ]);
    }


    /** @test */
    public function cannot_activate_config_without_all_regions()
    {
        $configSet = $this->createValidConfigSet();

        // Xóa 1 vùng (chỉ còn 3 vùng)
        $configSet->minimumWages()->where('region', 4)->delete();

        $response = $this->actingAs($this->user)
            ->post(route('insurance-config-sets.activate', $configSet->id));

        $response->assertSessionHas('type', 'error');

        $configSet->refresh();
        $this->assertEquals(InsuranceConfigSet::STATUS_DRAFT, $configSet->status);
    }

    /** @test */
    public function can_archive_config_set()
    {
        $configSet = $this->createValidConfigSet([
            'status' => InsuranceConfigSet::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('insurance-config-sets.archive', $configSet->id));

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $configSet->refresh();
        $this->assertEquals(InsuranceConfigSet::STATUS_ARCHIVED, $configSet->status);
        $this->assertNotNull($configSet->archived_at);
    }

    /** @test */
    public function can_clone_existing_config_set()
    {
        $sourceSet = $this->createValidConfigSet(['code' => 'SOURCE_SET']);

        $cloneData = [
            'code' => 'CLONED_SET',
            'name' => 'Cloned Config',
            'description' => 'Clone from SOURCE_SET',
            'effective_from' => '2025-01-01',
            'effective_to' => '2025-12-31',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('insurance-config-sets.clone', $sourceSet->id), $cloneData);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $clonedSet = InsuranceConfigSet::where('code', 'CLONED_SET')->first();
        $this->assertNotNull($clonedSet);
        $this->assertEquals($sourceSet->id, $clonedSet->based_on_set_id);

        // Kiểm tra clone có đủ data
        $this->assertCount(4, $clonedSet->minimumWages);
        $this->assertCount(7, $clonedSet->salaryGrades);

        // Kiểm tra data được copy đúng
        $this->assertEquals(
            $sourceSet->minimumWages->pluck('amount')->toArray(),
            $clonedSet->minimumWages->pluck('amount')->toArray()
        );
    }

    /** @test */
    public function index_returns_config_sets_list()
    {
        $this->createValidConfigSet(['code' => 'SET_1', 'name' => 'Config 1']);
        $this->createValidConfigSet(['code' => 'SET_2', 'name' => 'Config 2']);

        $response = $this->actingAs($this->user)
            ->get(route('insurance-config-sets.index'));

        $response->assertOk();
        $response->assertInertia(fn($page) =>
            $page->component('Insurance/ConfigSets/Index')
                ->has('configSets.data', 2)
        );
    }

    /** @test */
    public function can_filter_config_sets_by_status()
    {
        $this->createValidConfigSet(['code' => 'TEST_DRAFT', 'status' => 'DRAFT']);
        $this->createValidConfigSet(['code' => 'TEST_ACTIVE', 'status' => 'ACTIVE']);
        $this->createValidConfigSet(['code' => 'TEST_ARCHIVED', 'status' => 'ARCHIVED']);

        $response = $this->actingAs($this->user)
            ->get(route('insurance-config-sets.index', ['status' => 'ACTIVE']));

        $response->assertOk();
        $response->assertInertia(fn($page) =>
            $page->has('configSets.data', 1)
        );
    }

    /** @test */
    public function can_search_config_sets()
    {
        $this->createValidConfigSet(['code' => 'VN_2024', 'name' => 'Config 2024']);
        $this->createValidConfigSet(['code' => 'VN_2025', 'name' => 'Config 2025']);

        $response = $this->actingAs($this->user)
            ->get(route('insurance-config-sets.index', ['search' => '2025']));

        $response->assertOk();
        $response->assertInertia(fn($page) =>
            $page->has('configSets.data', 1)
        );
    }
}
