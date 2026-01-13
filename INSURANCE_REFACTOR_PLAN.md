# Kế Hoạch Refactor Module BHXH

## 📊 Tổng quan

**Mục tiêu:** Refactor module BHXH từ 3 boolean đơn giản sang component-based architecture với 5 thành phần BHXH riêng biệt, hỗ trợ declaration month logic và snapshot contributions.

**Timeline:** 5 weeks (Jan 10, 2026 - Feb 14, 2026)

**Effort ước tính:** 3-4 weeks dev + 1 week testing

---

## 🎯 Mục tiêu nghiệp vụ

### Base lương BHXH
- Luôn lấy từ Contract/Appendix theo hiệu lực thời gian
- Cho phép "theo thỏa thuận" (nằm trong HĐ/PLHĐ)

### 5 thành phần BHXH
Nhân viên có thể tùy chọn tham gia:
1. **Hưu trí – tử tuất** (22%)
2. **Ốm đau – thai sản** (3%)
3. **TNLĐ – BNN** (0.5%)
4. **BHTN** (2%)
5. **BHYT** (4.5%)

### Ngoại lệ
- Chỉ tham gia 0.5% TNLĐ–BNN
- Tham gia đủ 5 mục nhưng BHTN có base riêng 72 triệu

### Quy tắc duyệt theo tháng
- Hệ thống suggest `declaration_month` theo rule 1–14 / 15–cuối tháng
- Người duyệt được chọn `declaration_month` (override)
- Xử lý "phát sinh tháng trước nhưng kê khai tháng sau" sạch số, audit được

---

## 🏗️ Kiến trúc thiết kế

### Thiết kế dữ liệu: "Component-based + Declaration + Snapshot"

#### 1. insurance_components (Master data)
```
id, code, name_vi, default_rate_total, is_active
```
5 components cố định:
- RETIREMENT_SURVIVOR (22%)
- SICKNESS_MATERNITY (3%)
- OCC_ACCIDENT_DISEASE (0.5%)
- UNEMPLOYMENT (2%)
- HEALTH (4.5%)

#### 2. insurance_participation_components (Detail)
```
insurance_participation_id, component_code, is_enabled, 
rate_total, base_type (INSURANCE_SALARY/FIXED_AMOUNT), 
base_amount, note
```

#### 3. insurance_change_records (Thêm fields)
```
suggested_declaration_month, declaration_month,
declaration_set_by, declaration_set_at,
declaration_override_reason
```

#### 4. insurance_monthly_contributions (Snapshot)
```
report_id, employee_id, base_insurance_salary, total_amount
```

#### 5. insurance_monthly_contribution_items (Snapshot detail)
```
contribution_id, component_code, base_used, rate_total, amount
```

---

## 📋 Phase 1: Foundation (Week 1-2)

### 1.1. Database Schema

**Migrations:**
- `2026_01_10_100000_create_insurance_components_table.php`
- `2026_01_10_100001_create_insurance_participation_components_table.php`
- `2026_01_10_100002_add_declaration_fields_to_insurance_change_records.php`
- `2026_01_10_100003_create_insurance_monthly_contributions_tables.php`

**Schema chi tiết:**

```php
// insurance_components
Schema::create('insurance_components', function (Blueprint $table) {
    $table->id();
    $table->string('code', 50)->unique()->index(); // RETIREMENT_SURVIVOR, etc.
    $table->string('name_vi');
    $table->decimal('default_rate_total', 8, 5); // 0.22000
    $table->boolean('is_active')->default(true)->index();
    $table->timestamps();
});

// insurance_participation_components
Schema::create('insurance_participation_components', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('insurance_participation_id')->index();
    $table->string('component_code', 50)->index();
    $table->boolean('is_enabled')->default(true)->index();
    $table->decimal('rate_total', 8, 5); // Default = component.default_rate_total
    $table->enum('base_type', ['INSURANCE_SALARY', 'FIXED_AMOUNT'])->default('INSURANCE_SALARY');
    $table->decimal('base_amount', 15, 2)->nullable(); // For FIXED_AMOUNT
    $table->text('note')->nullable();
    $table->timestamps();
    
    $table->foreign('insurance_participation_id')->references('id')->on('insurance_participations')->onDelete('cascade');
    $table->foreign('component_code')->references('code')->on('insurance_components')->onDelete('cascade');
    $table->unique(['insurance_participation_id', 'component_code']);
});

// Add to insurance_change_records
Schema::table('insurance_change_records', function (Blueprint $table) {
    $table->string('suggested_declaration_month', 7)->nullable()->index(); // YYYY-MM
    $table->string('declaration_month', 7)->nullable()->index(); // YYYY-MM
    $table->unsignedBigInteger('declaration_set_by')->nullable();
    $table->timestamp('declaration_set_at')->nullable();
    $table->text('declaration_override_reason')->nullable();
});

// insurance_monthly_contributions
Schema::create('insurance_monthly_contributions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('report_id')->index();
    $table->uuid('employee_id')->index();
    $table->decimal('base_insurance_salary', 15, 2);
    $table->decimal('total_amount', 15, 2);
    $table->timestamps();
    
    $table->foreign('report_id')->references('id')->on('insurance_monthly_reports')->onDelete('cascade');
    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
    $table->unique(['report_id', 'employee_id']);
});

// insurance_monthly_contribution_items
Schema::create('insurance_monthly_contribution_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('contribution_id')->index();
    $table->string('component_code', 50)->index();
    $table->decimal('base_used', 15, 2);
    $table->decimal('rate_total', 8, 5);
    $table->decimal('amount', 15, 2);
    $table->timestamps();
    
    $table->foreign('contribution_id')->references('id')->on('insurance_monthly_contributions')->onDelete('cascade');
    $table->foreign('component_code')->references('code')->on('insurance_components')->onDelete('cascade');
});
```

### 1.2. Models & Relationships

**New Models:**
- `app/Models/InsuranceComponent.php`
- `app/Models/InsuranceParticipationComponent.php`
- `app/Models/InsuranceMonthlyContribution.php`
- `app/Models/InsuranceMonthlyContributionItem.php`

**Update Models:**
- `InsuranceParticipation`: add `components()` hasMany
- `InsuranceChangeRecord`: add declaration fields, casts
- `InsuranceMonthlyReport`: add `contributions()` hasMany

### 1.3. Seeders

**InsuranceComponentSeeder:**
```php
$components = [
    [
        'code' => 'RETIREMENT_SURVIVOR',
        'name_vi' => 'Hưu trí và tử tuất',
        'default_rate_total' => 0.22000,
    ],
    [
        'code' => 'SICKNESS_MATERNITY',
        'name_vi' => 'Ốm đau và thai sản',
        'default_rate_total' => 0.03000,
    ],
    [
        'code' => 'OCC_ACCIDENT_DISEASE',
        'name_vi' => 'Tai nạn lao động - Bệnh nghề nghiệp',
        'default_rate_total' => 0.00500,
    ],
    [
        'code' => 'UNEMPLOYMENT',
        'name_vi' => 'Bảo hiểm thất nghiệp',
        'default_rate_total' => 0.02000,
    ],
    [
        'code' => 'HEALTH',
        'name_vi' => 'Bảo hiểm y tế',
        'default_rate_total' => 0.04500,
    ],
];
```

### 1.4. Backfill Command

**BackfillInsuranceParticipationComponentsCommand:**

```php
php artisan insurance:backfill-components [--dry-run] [--employee=uuid]
```

**Logic:**
- Query all `insurance_participations`
- For each participation:
  - `has_social_insurance=true` → create 3 components (RETIREMENT_SURVIVOR, SICKNESS_MATERNITY, OCC_ACCIDENT_DISEASE)
  - `has_health_insurance=true` → create HEALTH component
  - `has_unemployment_insurance=true` → create UNEMPLOYMENT component
  - All use `base_type=INSURANCE_SALARY`, `rate_total` from component default
- Progress bar, dry-run mode
- Log results: created/skipped/errors

### 1.5. Testing Phase 1

**Unit Tests:**
- `InsuranceComponentTest`: CRUD, relationships
- `InsuranceParticipationComponentTest`: CRUD, relationships, validation

**Feature Tests:**
- `BackfillComponentsCommandTest`: 
  - Test backfill với participation có các combination boolean
  - Test dry-run không tạo records
  - Test idempotent (chạy 2 lần không duplicate)

**Manual Testing:**
- Run backfill command trên staging DB
- Verify: `SELECT participation_id, COUNT(*) FROM insurance_participation_components GROUP BY participation_id`
- Expected: 0-5 components per participation

---

## 📋 Phase 2: Declaration Month Logic (Week 2-3)

### 2.1. Declaration Service

**InsuranceDeclarationService:**
```php
public function suggestDeclarationMonth(Carbon $effectiveDate, string $reportMonth): string
{
    // Rule: 1-14 → tháng hiện tại, 15-31 → tháng sau
    if ($effectiveDate->day <= 14) {
        return $reportMonth;
    }
    return Carbon::parse($reportMonth)->addMonth()->format('Y-m');
}

public function validateDeclarationMonth(InsuranceMonthlyReport $report): array
{
    // Check all records have declaration_month = report month
    $invalidRecords = $report->changeRecords()
        ->where('declaration_month', '!=', $report->getReportMonth())
        ->get();
    
    return [
        'valid' => $invalidRecords->isEmpty(),
        'invalid_count' => $invalidRecords->count(),
        'invalid_records' => $invalidRecords,
    ];
}
```

### 2.2. Update Report Generation

**InsuranceReportService::detectChanges():**
- Khi tạo `InsuranceChangeRecord`:
  - Set `suggested_declaration_month` = suggestDeclarationMonth($effectiveDate, $reportMonth)
  - Set `declaration_month` = `suggested_declaration_month` (default)
  - Set `declaration_override_reason` = null

### 2.3. Reviewer Override Logic

**Controller:**
```php
public function updateDeclarationMonth(Request $request, InsuranceChangeRecord $record)
{
    $validated = $request->validate([
        'declaration_month' => 'required|date_format:Y-m',
        'override_reason' => 'required_if:declaration_month,!=,' . $record->suggested_declaration_month,
    ]);
    
    $oldMonth = $record->declaration_month;
    $newMonth = $validated['declaration_month'];
    
    if ($oldMonth !== $newMonth) {
        // Move record to appropriate report
        $this->declarationService->moveRecordToReport($record, $newMonth);
    }
    
    $record->update([
        'declaration_month' => $newMonth,
        'declaration_set_by' => auth()->id(),
        'declaration_set_at' => now(),
        'declaration_override_reason' => $validated['override_reason'] ?? null,
    ]);
    
    // Activity log
    activity()
        ->performedOn($record)
        ->causedBy(auth()->user())
        ->withProperties(['old' => $oldMonth, 'new' => $newMonth])
        ->log('Thay đổi tháng kê khai BHXH');
}
```

### 2.4. Move Record Logic

**InsuranceDeclarationService::moveRecordToReport():**
```php
public function moveRecordToReport(InsuranceChangeRecord $record, string $newDeclarationMonth): void
{
    [$year, $month] = explode('-', $newDeclarationMonth);
    
    // Find or create report for declaration month
    $targetReport = InsuranceMonthlyReport::firstOrCreate([
        'year' => (int) $year,
        'month' => (int) $month,
    ], [
        'status' => 'DRAFT',
    ]);
    
    $oldReportId = $record->report_id;
    
    // Move record
    $record->update(['report_id' => $targetReport->id]);
    
    // Recalculate summaries
    $this->recalculateReportSummary($oldReportId);
    $this->recalculateReportSummary($targetReport->id);
}
```

### 2.5. Validation khi Approve

**InsuranceReportService::finalizeReport():**
```php
// Before setting status = FINALIZED
$validation = $this->declarationService->validateDeclarationMonth($report);

if (!$validation['valid']) {
    throw new \Exception(
        "Báo cáo có {$validation['invalid_count']} records với declaration_month khác report month. " .
        "Vui lòng di chuyển các records này sang báo cáo tương ứng."
    );
}
```

### 2.6. Testing Phase 2

**Unit Tests:**
- `InsuranceDeclarationServiceTest`:
  - Test suggest rule: day 1-14 → same month, day 15-31 → next month
  - Test edge cases: ngày 14, 15, cuối tháng
  - Test validate declaration month

**Feature Tests:**
- `DeclarationMonthOverrideTest`:
  - Test update declaration month
  - Test move record to different report
  - Test validation prevents finalize with mismatched months
  - Test activity log

**Manual Testing:**
- Tạo report tháng 1/2026
- Override một record sang tháng 2/2026
- Verify record bị move sang report tháng 2
- Verify không approve được report tháng 1 nếu còn record declaration_month ≠ 1/2026

---

## 📋 Phase 3: Contribution Calculator + Snapshot (Week 3-4)

### 3.1. Contribution Calculator Service

**InsuranceContributionCalculatorService:**

```php
public function calculateForEmployee(string $employeeId, string $declarationMonth): array
{
    // 1. Get base salary from Contract/Appendix effective in declaration month
    $baseSalary = $this->getBaseSalaryForMonth($employeeId, $declarationMonth);
    
    if (!$baseSalary) {
        return ['total' => 0, 'items' => [], 'base_salary' => 0];
    }
    
    // 2. Get active participation with components
    $participation = InsuranceParticipation::where('employee_id', $employeeId)
        ->where('status', 'ACTIVE')
        ->with(['components' => function ($q) {
            $q->where('is_enabled', true)->with('component');
        }])
        ->first();
    
    if (!$participation || $participation->components->isEmpty()) {
        return ['total' => 0, 'items' => [], 'base_salary' => $baseSalary];
    }
    
    // 3. Calculate for each component
    $items = [];
    $total = 0;
    
    foreach ($participation->components as $participationComponent) {
        $baseUsed = $participationComponent->base_type === 'FIXED_AMOUNT'
            ? $participationComponent->base_amount
            : $baseSalary;
        
        $amount = round($baseUsed * $participationComponent->rate_total, 2);
        
        $items[] = [
            'component_code' => $participationComponent->component_code,
            'component_name' => $participationComponent->component->name_vi,
            'base_used' => $baseUsed,
            'rate_total' => $participationComponent->rate_total,
            'amount' => $amount,
        ];
        
        $total += $amount;
    }
    
    return [
        'total' => $total,
        'items' => $items,
        'base_salary' => $baseSalary,
        'employee_id' => $employeeId,
    ];
}

protected function getBaseSalaryForMonth(string $employeeId, string $declarationMonth): ?float
{
    // Logic to get insurance_salary from Contract/Appendix effective in month
    // Priority: Appendix > Contract
    // ...
}
```

### 3.2. Generate Snapshot on Approve

**InsuranceReportService::finalizeReport():**

```php
public function finalizeReport(InsuranceMonthlyReport $report, User $user): void
{
    // 1. Validate declaration months
    $validation = $this->declarationService->validateDeclarationMonth($report);
    if (!$validation['valid']) {
        throw new \Exception("Report có {$validation['invalid_count']} records với declaration_month không khớp");
    }
    
    // 2. Generate snapshot contributions
    $this->generateSnapshotContributions($report);
    
    // 3. Finalize report
    $report->update([
        'status' => 'FINALIZED',
        'finalized_at' => now(),
        'finalized_by' => $user->id,
    ]);
    
    // Activity log
    activity()
        ->performedOn($report)
        ->causedBy($user)
        ->log('Hoàn tất báo cáo BHXH');
}

protected function generateSnapshotContributions(InsuranceMonthlyReport $report): void
{
    $declarationMonth = $report->year . '-' . str_pad($report->month, 2, '0', STR_PAD_LEFT);
    
    // Get all employees affected by approved change_records in this report
    $employeeIds = $report->changeRecords()
        ->where('approval_status', 'APPROVED')
        ->pluck('employee_id')
        ->unique();
    
    // Delete existing snapshots (if re-generating)
    InsuranceMonthlyContribution::where('report_id', $report->id)->delete();
    
    foreach ($employeeIds as $employeeId) {
        $calculation = $this->calculator->calculateForEmployee($employeeId, $declarationMonth);
        
        if ($calculation['total'] <= 0) {
            continue; // Skip if no contribution
        }
        
        $contribution = InsuranceMonthlyContribution::create([
            'report_id' => $report->id,
            'employee_id' => $employeeId,
            'base_insurance_salary' => $calculation['base_salary'],
            'total_amount' => $calculation['total'],
        ]);
        
        foreach ($calculation['items'] as $item) {
            InsuranceMonthlyContributionItem::create([
                'contribution_id' => $contribution->id,
                'component_code' => $item['component_code'],
                'base_used' => $item['base_used'],
                'rate_total' => $item['rate_total'],
                'amount' => $item['amount'],
            ]);
        }
    }
}
```

### 3.3. Export dùng Snapshot

**InsuranceReportService::exportReport():**

```php
public function exportReport(InsuranceMonthlyReport $report): string
{
    if ($report->status !== 'FINALIZED') {
        throw new \Exception('Chỉ export được report đã FINALIZED');
    }
    
    // Query snapshot instead of recalculating
    $contributions = $report->contributions()
        ->with(['employee', 'items.component'])
        ->get();
    
    // Export to Excel using snapshot data
    // ...
}
```

### 3.4. Testing Phase 3

**Unit Tests:**
- `InsuranceContributionCalculatorServiceTest`:
  - Test calculate với base_type = INSURANCE_SALARY
  - Test calculate với base_type = FIXED_AMOUNT (BHTN 72tr)
  - Test calculate với chỉ 1 component (0.5% TNLĐ)
  - Test calculate với đủ 5 components

**Feature Tests:**
- `SnapshotGenerationTest`:
  - Test generate snapshot on finalize
  - Test snapshot không bị thay đổi khi sửa contract sau
  - Test export dùng snapshot
  - Test re-generate snapshot (delete old + create new)

**Integration Tests:**
- Full flow: Create contract → Generate report → Approve records → Finalize → Export
- Verify export data khớp với snapshot

---

## 📋 Phase 4: UI Updates (Week 4)

### 4.1. Contract/Appendix Form

**File:** `resources/js/Pages/ContractForm.vue` (hoặc tương tự)

**Tab "Bảo hiểm" (Insurance):**

```vue
<TabPanel header="Bảo hiểm">
  <!-- Existing: Base salary -->
  <div class="field">
    <label>Lương đóng BHXH</label>
    <InputNumber v-model="form.insurance_salary" :min="0" />
  </div>
  
  <!-- NEW: Component selection -->
  <div class="field">
    <label>Cấu hình tham gia BHXH</label>
    <div class="flex flex-col gap-3">
      <Checkbox v-model="components.retirement_survivor" label="Hưu trí và tử tuất (22%)" />
      <Checkbox v-model="components.sickness_maternity" label="Ốm đau và thai sản (3%)" />
      <Checkbox v-model="components.occ_accident_disease" label="TNLĐ-BNN (0.5%)" />
      
      <!-- BHTN with special base option -->
      <div>
        <Checkbox v-model="components.unemployment" label="Bảo hiểm thất nghiệp (2%)" />
        <div v-if="components.unemployment" class="ml-6 mt-2">
          <RadioButton v-model="unemploymentBaseType" value="INSURANCE_SALARY" label="Theo lương HĐ" />
          <RadioButton v-model="unemploymentBaseType" value="FIXED_AMOUNT" label="Base cố định" />
          <InputNumber v-if="unemploymentBaseType === 'FIXED_AMOUNT'" 
                       v-model="unemploymentBaseAmount" 
                       placeholder="Ví dụ: 72,000,000" />
        </div>
      </div>
      
      <Checkbox v-model="components.health" label="Bảo hiểm y tế (4.5%)" />
    </div>
  </div>
  
  <!-- Keep old 3 booleans for backward compatibility (hidden) -->
  <input type="hidden" v-model="form.has_social_insurance">
  <input type="hidden" v-model="form.has_health_insurance">
  <input type="hidden" v-model="form.has_unemployment_insurance">
</TabPanel>
```

**Script:**
```js
// Auto-sync 5 components → 3 booleans (backward compatible)
watch(() => [components.retirement_survivor, components.sickness_maternity, components.occ_accident_disease], () => {
  form.has_social_insurance = components.retirement_survivor || components.sickness_maternity || components.occ_accident_disease
})

watch(() => components.health, () => {
  form.has_health_insurance = components.health
})

watch(() => components.unemployment, () => {
  form.has_unemployment_insurance = components.unemployment
})
```

### 4.2. Monthly Report Screen

**File:** `resources/js/Pages/InsuranceReportShow.vue`

**Tab "Biến động cần duyệt":**

```vue
<DataTable :value="changeRecords">
  <Column field="employee.full_name" header="Nhân viên" />
  <Column field="change_type" header="Loại">
    <template #body="{ data }">
      <Tag :value="data.change_type" :severity="getChangeSeverity(data.change_type)" />
    </template>
  </Column>
  <Column field="effective_date" header="Ngày hiệu lực">
    <template #body="{ data }">
      {{ formatDate(data.effective_date) }}
    </template>
  </Column>
  
  <!-- NEW: Suggested declaration month (readonly) -->
  <Column field="suggested_declaration_month" header="Tháng KK gợi ý">
    <template #body="{ data }">
      <Tag :value="data.suggested_declaration_month" severity="info" />
    </template>
  </Column>
  
  <!-- NEW: Declaration month (editable) -->
  <Column field="declaration_month" header="Tháng kê khai">
    <template #body="{ data }">
      <Select v-model="data.declaration_month" 
              :options="availableMonths"
              @change="onDeclarationMonthChange(data)"
              :class="{'bg-yellow-50': data.declaration_month !== data.suggested_declaration_month}" />
    </template>
  </Column>
  
  <!-- Override reason (required if changed) -->
  <Column field="declaration_override_reason" header="Lý do thay đổi">
    <template #body="{ data }">
      <InputText v-if="data.declaration_month !== data.suggested_declaration_month"
                 v-model="data.declaration_override_reason" 
                 placeholder="Nhập lý do..." />
    </template>
  </Column>
  
  <Column header="Actions">
    <template #body="{ data }">
      <Button label="Duyệt" @click="approve(data)" :disabled="!canApprove(data)" />
      <Button label="Từ chối" @click="reject(data)" severity="danger" />
    </template>
  </Column>
</DataTable>
```

**Tab "Tổng hợp đóng BHXH":**

```vue
<DataTable :value="contributions" showGridlines>
  <Column field="employee.full_name" header="Nhân viên" frozen />
  <Column field="base_insurance_salary" header="Lương BH">
    <template #body="{ data }">
      {{ formatCurrency(data.base_insurance_salary) }}
    </template>
  </Column>
  
  <!-- 5 component columns -->
  <Column header="Hưu trí-Tử tuất (22%)">
    <template #body="{ data }">
      {{ formatCurrency(getComponentAmount(data, 'RETIREMENT_SURVIVOR')) }}
    </template>
  </Column>
  <Column header="Ốm đau-Thai sản (3%)">
    <template #body="{ data }">
      {{ formatCurrency(getComponentAmount(data, 'SICKNESS_MATERNITY')) }}
    </template>
  </Column>
  <Column header="TNLĐ-BNN (0.5%)">
    <template #body="{ data }">
      {{ formatCurrency(getComponentAmount(data, 'OCC_ACCIDENT_DISEASE')) }}
    </template>
  </Column>
  <Column header="BHTN (2%)">
    <template #body="{ data }">
      {{ formatCurrency(getComponentAmount(data, 'UNEMPLOYMENT')) }}
    </template>
  </Column>
  <Column header="BHYT (4.5%)">
    <template #body="{ data }">
      {{ formatCurrency(getComponentAmount(data, 'HEALTH')) }}
    </template>
  </Column>
  
  <Column field="total_amount" header="Tổng cộng">
    <template #body="{ data }">
      <strong>{{ formatCurrency(data.total_amount) }}</strong>
    </template>
  </Column>
  
  <!-- Footer: Grand total -->
  <template #footer>
    <div class="flex justify-end">
      <strong>Tổng công ty: {{ formatCurrency(grandTotal) }}</strong>
    </div>
  </template>
</DataTable>
```

### 4.3. Component CRUD (Admin only)

**File:** `resources/js/Pages/InsuranceComponentIndex.vue`

```vue
<DataTable :value="components">
  <Column field="code" header="Mã" />
  <Column field="name_vi" header="Tên" />
  <Column field="default_rate_total" header="Tỷ lệ mặc định">
    <template #body="{ data }">
      {{ (data.default_rate_total * 100).toFixed(2) }}%
    </template>
  </Column>
  <Column field="is_active" header="Trạng thái">
    <template #body="{ data }">
      <Tag :value="data.is_active ? 'Active' : 'Inactive'" 
           :severity="data.is_active ? 'success' : 'secondary'" />
    </template>
  </Column>
  <Column header="Actions">
    <template #body="{ data }">
      <Button label="Sửa" icon="pi pi-pencil" @click="edit(data)" />
    </template>
  </Column>
</DataTable>

<!-- Edit dialog -->
<Dialog v-model:visible="dialogVisible" header="Sửa tỷ lệ BHXH">
  <Message severity="warn">
    Lưu ý: Thay đổi tỷ lệ mặc định sẽ không ảnh hưởng đến các participation đã tạo. 
    Chỉ áp dụng cho participation mới.
  </Message>
  
  <div class="field">
    <label>Tỷ lệ đóng (%)</label>
    <InputNumber v-model="form.default_rate_total" :minFractionDigits="2" :maxFractionDigits="5" />
  </div>
</Dialog>
```

### 4.4. Routes & Permissions

**routes/web.php:**
```php
Route::middleware(['auth'])->group(function () {
    // Insurance components (Admin only)
    Route::resource('insurance-components', InsuranceComponentController::class)
        ->except(['create', 'show'])
        ->middleware('can:manage insurance');
    
    // Declaration month override
    Route::patch('insurance-change-records/{record}/declaration-month', 
        [InsuranceReportController::class, 'updateDeclarationMonth'])
        ->name('insurance-change-records.update-declaration-month');
});
```

---

## 📋 Phase 5: Testing & Rollout (Week 5)

### 5.1. Data Integrity Checks

**Scripts:**

```bash
# Check all participations have components mapped with 3 booleans
php artisan insurance:verify-components

# Check all finalized reports have snapshots
php artisan insurance:verify-snapshots

# Check declaration month consistency
php artisan insurance:verify-declaration-months
```

**Expected outputs:**
- 0 participations without components (if has_*_insurance = true)
- 0 finalized reports without snapshots
- 0 records with declaration_month ≠ report month in finalized reports

### 5.2. Performance Testing

**Load tests:**
- Generate report for month with 1000 employees
- Approve 1000 change records
- Finalize report with 1000 contributions
- Export report with 1000 rows

**Optimization:**
- Index `declaration_month`, `report_id` in `insurance_change_records`
- Eager load relationships in calculator
- Batch insert for snapshot generation

### 5.3. User Acceptance Testing

**Test scenarios:**

1. **Create new contract with special BHTN base:**
   - Create contract with BHTN base = 72,000,000
   - Generate report
   - Verify BHTN contribution = 72M × 2% = 1,440,000

2. **Late entry:**
   - Phát sinh sự kiện effective_date = 2025-12-20
   - Report tháng 12 đã finalized
   - Override declaration_month = 2026-01
   - Verify record moved to report tháng 1/2026

3. **Override declaration month:**
   - Record có suggested = 2026-01
   - Override to 2026-02 with reason
   - Verify moved to report 2026-02
   - Verify activity log

4. **Export snapshot:**
   - Finalize report tháng 1
   - Edit contract after finalize
   - Re-export report
   - Verify export data unchanged (from snapshot)

### 5.4. Documentation

**User Guide:**
- `docs/user-guide/insurance-declaration-month.md`: Cách sử dụng declaration month override
- `docs/user-guide/insurance-components.md`: Giải thích 5 thành phần BHXH
- `docs/user-guide/insurance-special-cases.md`: Xử lý các case ngoại lệ

**Developer Docs:**
- `docs/dev/insurance-architecture.md`: Component-based architecture
- `docs/dev/insurance-calculator.md`: Calculator logic và formulas
- `docs/dev/insurance-snapshot.md`: Snapshot mechanism

**Migration Guide:**
- `docs/migration/insurance-refactor.md`: Hướng dẫn migrate từ 3 boolean sang 5 components

---

## 📊 Tiến độ và Phân công

| Phase | Tasks | Assignee | Deadline | Status |
|-------|-------|----------|----------|--------|
| **Phase 1** | Database Schema | Dev 1 | Week 1 | Not Started |
| | Models & Relationships | Dev 1 | Week 1 | Not Started |
| | Seeders | Dev 2 | Week 1 | Not Started |
| | Backfill Command | Dev 2 | Week 2 | Not Started |
| | Testing Phase 1 | QA | Week 2 | Not Started |
| **Phase 2** | Declaration Service | Dev 1 | Week 2 | Not Started |
| | Update Report Generation | Dev 1 | Week 2 | Not Started |
| | Override Logic | Dev 2 | Week 3 | Not Started |
| | Move Record Logic | Dev 2 | Week 3 | Not Started |
| | Testing Phase 2 | QA | Week 3 | Not Started |
| **Phase 3** | Calculator Service | Dev 1 | Week 3 | Not Started |
| | Snapshot Generation | Dev 1 | Week 4 | Not Started |
| | Export Logic | Dev 2 | Week 4 | Not Started |
| | Testing Phase 3 | QA | Week 4 | Not Started |
| **Phase 4** | Contract Form UI | Frontend | Week 4 | Not Started |
| | Report Screen UI | Frontend | Week 4 | Not Started |
| | Component CRUD UI | Frontend | Week 4 | Not Started |
| **Phase 5** | Data Integrity Checks | Dev 1 | Week 5 | Not Started |
| | Performance Testing | Dev 2 | Week 5 | Not Started |
| | UAT | QA + HR | Week 5 | Not Started |
| | Documentation | Tech Writer | Week 5 | Not Started |

---

## ✅ Definition of Done

### Phase 1
- [x] All migrations created and runnable
- [x] All models with relationships tested
- [x] Seeder creates 5 components
- [x] Backfill command runs without errors
- [x] 100% of participations have components mapped

### Phase 2
- [x] Declaration service suggests correct month
- [x] Override UI allows changing declaration month
- [x] Records move to correct report when declaration month changes
- [x] Cannot finalize report with mismatched declaration months
- [x] Activity logs for all overrides

### Phase 3
- [x] Calculator handles all 5 components
- [x] Calculator handles FIXED_AMOUNT base type
- [x] Snapshot generated on finalize
- [x] Export uses snapshot (not recalculate)
- [x] Re-finalize regenerates snapshot

### Phase 4
- [x] Contract form has 5 component checkboxes
- [x] BHTN has base type radio (HĐ / Fixed)
- [x] Report screen shows suggested vs actual declaration month
- [x] Report screen allows override with reason
- [x] Contribution tab shows breakdown by 5 components
- [x] Component CRUD for admin

### Phase 5
- [x] All data integrity scripts pass
- [x] Performance tests < 5s for 1000 employees
- [x] UAT scenarios completed
- [x] Documentation complete

---

## 🚨 Risks & Mitigation

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Breaking changes phá UI cũ | High | Medium | Maintain backward compatibility với 3 boolean |
| Backfill data sai | High | Low | Dry-run mode, extensive testing, rollback plan |
| Performance degradation | Medium | Medium | Index optimization, query optimization, load testing |
| User confusion với declaration month | Medium | High | Clear UI/UX, tooltips, training documentation |
| Missing edge cases | Medium | Medium | Comprehensive test scenarios, UAT with real HR staff |

---

## 📞 Support & Contact

**Technical Lead:** [Name]  
**Product Owner:** [Name]  
**QA Lead:** [Name]  

**Slack Channel:** #insurance-refactor  
**Jira Board:** [Link]  
**Confluence:** [Link]

---

**Last Updated:** January 10, 2026  
**Version:** 1.0  
**Status:** Planning
