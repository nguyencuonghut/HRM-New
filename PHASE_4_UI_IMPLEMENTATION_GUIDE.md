# Phase 4: UI Implementation Guide for Insurance Components

## Overview
Cập nhật UI để support hệ thống 5 insurance components mới thay thế 3 boolean fields cũ (has_social_insurance, has_health_insurance, has_unemployment_insurance).

---

## Phase 4.1: Update Contract/Appendix Form UI

### Objectives
- Thêm Insurance tab với 5 component checkboxes
- Thêm BHTN base type selection (INSURANCE_SALARY hoặc FIXED_AMOUNT với input field)
- Giữ backward compatibility với 3 boolean fields (hidden)
- Auto-sync 5 components ↔ 3 booleans

### Files to Update

#### 1. Contract Form Component (Location TBD)
**File**: `resources/js/Pages/Employees/Components/ContractFormModal.vue` hoặc tương tự

**Thêm data properties**:
```javascript
const insuranceComponents = ref([
  { 
    id: null, 
    code: 'BHXH_HUU_TU', 
    name_vi: 'BHXH Hưu trí - Tử tuất', 
    rate: '22%',
    enabled: false 
  },
  { 
    id: null, 
    code: 'BHXH_BENH', 
    name_vi: 'BHXH Ốm đau - Thai sản', 
    rate: '3%',
    enabled: false 
  },
  { 
    id: null, 
    code: 'BHXH_TNLD', 
    name_vi: 'BHXH TNLĐ - Bệnh nghề nghiệp', 
    rate: '0.5%',
    enabled: false 
  },
  { 
    id: null, 
    code: 'BHTN', 
    name_vi: 'Bảo hiểm Thất nghiệp', 
    rate: '2%',
    enabled: false,
    baseType: 'INSURANCE_SALARY', // hoặc 'FIXED_AMOUNT'
    fixedAmount: null
  },
  { 
    id: null, 
    code: 'BHYT', 
    name_vi: 'Bảo hiểm Y tế', 
    rate: '4.5%',
    enabled: false 
  },
])

const bhtnBaseType = ref('INSURANCE_SALARY')
const bhtnFixedAmount = ref(72000000) // Mức trần mặc định
```

**Thêm template section**:
```vue
<template>
  <div class="insurance-section">
    <h3 class="text-lg font-semibold mb-4">Tham gia Bảo hiểm</h3>
    
    <div class="space-y-3">
      <!-- 5 Component Checkboxes -->
      <div v-for="component in insuranceComponents" :key="component.code" class="flex items-start gap-3">
        <Checkbox 
          :id="component.code"
          v-model="component.enabled" 
          :binary="true"
          @change="syncLegacyFields"
        />
        <label :for="component.code" class="flex-1">
          <div class="font-medium">{{ component.name_vi }}</div>
          <div class="text-sm text-gray-500">Tỷ lệ đóng: {{ component.rate }}</div>
        </label>
      </div>
      
      <!-- BHTN Special Base Type -->
      <div v-if="insuranceComponents[3].enabled" class="ml-8 mt-2 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <label class="font-medium block mb-3">Cơ sở tính BHTN:</label>
        
        <div class="flex flex-col gap-3">
          <div class="flex items-center gap-2">
            <RadioButton 
              v-model="bhtnBaseType" 
              inputId="bhtn_salary" 
              value="INSURANCE_SALARY"
              @change="updateBhtnBaseType"
            />
            <label for="bhtn_salary">Theo lương BH của hợp đồng</label>
          </div>
          
          <div class="flex items-center gap-2">
            <RadioButton 
              v-model="bhtnBaseType" 
              inputId="bhtn_fixed" 
              value="FIXED_AMOUNT"
              @change="updateBhtnBaseType"
            />
            <label for="bhtn_fixed" class="flex-1">Mức cố định (trần 72 triệu)</label>
            
            <InputNumber 
              v-if="bhtnBaseType === 'FIXED_AMOUNT'"
              v-model="bhtnFixedAmount"
              mode="currency" 
              currency="VND" 
              locale="vi-VN"
              :min="0"
              :max="72000000"
              :step="100000"
              class="w-64"
              placeholder="Nhập mức đóng"
            />
          </div>
        </div>
        
        <div class="mt-3 text-sm text-blue-600">
          <i class="pi pi-info-circle mr-1"></i>
          <span v-if="bhtnBaseType === 'INSURANCE_SALARY'">Sẽ tự động tính theo lương BH</span>
          <span v-else>Mức trần BHTN hiện hành: 72.000.000đ</span>
        </div>
      </div>
    </div>
    
    <!-- Hidden Legacy Fields for Backward Compatibility -->
    <input type="hidden" v-model="form.has_social_insurance" />
    <input type="hidden" v-model="form.has_health_insurance" />
    <input type="hidden" v-model="form.has_unemployment_insurance" />
  </div>
</template>
```

**Thêm methods**:
```javascript
// Load components từ server khi mount
async function loadInsuranceComponents() {
  try {
    const response = await axios.get('/api/insurance-components/active')
    insuranceComponents.value = response.data.map(comp => ({
      id: comp.id,
      code: comp.code,
      name_vi: comp.name_vi,
      rate: `${(comp.default_rate_total * 100).toFixed(1)}%`,
      enabled: false
    }))
    
    // Load participation data if editing existing contract
    if (props.contract && props.contract.participation) {
      loadParticipationComponents()
    }
  } catch (error) {
    console.error('Failed to load insurance components:', error)
  }
}

// Load existing participation components
function loadParticipationComponents() {
  const participation = props.contract.participation
  if (!participation || !participation.components) return
  
  participation.components.forEach(pc => {
    const component = insuranceComponents.value.find(c => c.id === pc.component_id)
    if (component) {
      component.enabled = pc.is_enabled
      
      // Load BHTN special settings
      if (component.code === 'BHTN') {
        bhtnBaseType.value = pc.base_type || 'INSURANCE_SALARY'
        if (pc.base_type === 'FIXED_AMOUNT' && pc.base_amount) {
          bhtnFixedAmount.value = pc.base_amount
        }
      }
    }
  })
  
  syncLegacyFields() // Sync to hidden fields
}

// Auto-sync: 5 components → 3 legacy booleans
function syncLegacyFields() {
  const bhxhEnabled = insuranceComponents.value.slice(0, 3).some(c => c.enabled) // BHXH_HUU_TU, BENH, TNLD
  const bhtnEnabled = insuranceComponents.value[3].enabled
  const bhytEnabled = insuranceComponents.value[4].enabled
  
  form.has_social_insurance = bhxhEnabled
  form.has_unemployment_insurance = bhtnEnabled
  form.has_health_insurance = bhytEnabled
}

// Update BHTN base type
function updateBhtnBaseType() {
  insuranceComponents.value[3].baseType = bhtnBaseType.value
  if (bhtnBaseType.value === 'INSURANCE_SALARY') {
    bhtnFixedAmount.value = null // Clear fixed amount
  } else {
    bhtnFixedAmount.value = bhtnFixedAmount.value || 72000000 // Set default
  }
}

// Submit: Chuyển components data vào form
function prepareSubmitData() {
  // Add insurance components to form
  form.insurance_components = insuranceComponents.value
    .filter(c => c.enabled)
    .map(c => ({
      component_id: c.id,
      is_enabled: true,
      base_type: c.code === 'BHTN' ? bhtnBaseType.value : 'INSURANCE_SALARY',
      base_amount: c.code === 'BHTN' && bhtnBaseType.value === 'FIXED_AMOUNT' ? bhtnFixedAmount.value : null,
      rate_total: null // Backend will use default from component
    }))
  
  // Legacy fields already synced via syncLegacyFields()
}

// Call on mount
onMounted(() => {
  loadInsuranceComponents()
})
```

**CSS Additions**:
```css
.insurance-section {
  @apply space-y-4;
}

.insurance-section .p-checkbox:checked {
  @apply border-blue-600 bg-blue-600;
}

.insurance-section label {
  @apply cursor-pointer select-none;
}
```

---

### Backend API Endpoint

**New Route** (`routes/api.php` hoặc `routes/web.php`):
```php
Route::get('/insurance-components/active', [InsuranceComponentController::class, 'getActiveComponents']);
```

**Controller Method**:
```php
// app/Http/Controllers/InsuranceComponentController.php

public function getActiveComponents(Request $request)
{
    $components = InsuranceComponent::where('is_active', true)
        ->orderBy('code')
        ->get(['id', 'code', 'name_vi', 'name_en', 'default_rate_total']);
    
    return response()->json($components);
}
```

---

### Backend Contract Store/Update Logic

**Update ContractController** (`app/Http/Controllers/ContractController.php`):

```php
public function store(Request $request)
{
    // Existing validation...
    
    DB::transaction(function () use ($request) {
        // Create contract
        $contract = Contract::create([
            // ... existing fields
            'has_social_insurance' => $request->has_social_insurance,
            'has_health_insurance' => $request->has_health_insurance,
            'has_unemployment_insurance' => $request->has_unemployment_insurance,
        ]);
        
        // Create insurance participation if any component is enabled
        if ($request->has('insurance_components') && count($request->insurance_components) > 0) {
            $participation = InsuranceParticipation::create([
                'employee_id' => $contract->employee_id,
                'participation_start_date' => $contract->start_date,
                'status' => 'ACTIVE',
                'insurance_salary' => $contract->insurance_salary,
            ]);
            
            // Create participation components
            foreach ($request->insurance_components as $componentData) {
                InsuranceParticipationComponent::create([
                    'insurance_participation_id' => $participation->id,
                    'component_id' => $componentData['component_id'],
                    'is_enabled' => $componentData['is_enabled'],
                    'base_type' => $componentData['base_type'] ?? 'INSURANCE_SALARY',
                    'base_amount' => $componentData['base_amount'],
                    'rate_total' => $componentData['rate_total'] ?? null, // Will use default if null
                ]);
            }
        }
    });
}
```

---

## Phase 4.2: Update Monthly Report Screen UI

### File: `resources/js/Pages/Insurance/Reports/MonthlyReportShow.vue`

**Thêm columns vào DataTable**:
```vue
<Column field="suggested_declaration_month" header="Tháng KK gợi ý" style="min-width: 10rem">
  <template #body="{ data }">
    <Badge :value="formatMonth(data.suggested_declaration_month)" severity="info" />
  </template>
</Column>

<Column field="declaration_month" header="Tháng KK chính thức" style="min-width: 12rem">
  <template #body="{ data }">
    <div class="flex items-center gap-2">
      <Dropdown
        v-model="data.declaration_month"
        :options="availableMonths"
        option-label="label"
        option-value="value"
        placeholder="Chọn tháng"
        :class="{ 'border-yellow-500': data.declaration_month !== data.suggested_declaration_month }"
        @change="onDeclarationMonthChange(data)"
      />
      <i 
        v-if="data.declaration_month !== data.suggested_declaration_month" 
        class="pi pi-exclamation-triangle text-yellow-500"
        v-tooltip="'Đã thay đổi từ tháng gợi ý'"
      />
    </div>
  </template>
</Column>

<Column header="Lý do thay đổi" style="min-width: 20rem">
  <template #body="{ data }">
    <InputText
      v-if="data.declaration_month !== data.suggested_declaration_month"
      v-model="data.declaration_override_reason"
      placeholder="Nhập lý do thay đổi (bắt buộc)"
      class="w-full"
      :class="{ 'border-red-500': !data.declaration_override_reason }"
    />
    <span v-else class="text-gray-400">-</span>
  </template>
</Column>
```

**Methods**:
```javascript
async function onDeclarationMonthChange(record) {
  if (record.declaration_month === record.suggested_declaration_month) {
    // Cleared override
    record.declaration_override_reason = null
    await saveDeclarationMonth(record)
  } else {
    // Show warning and require reason
    if (!record.declaration_override_reason) {
      toast.add({
        severity: 'warn',
        summary: 'Yêu cầu lý do',
        detail: 'Vui lòng nhập lý do thay đổi tháng kê khai',
        life: 3000
      })
    }
  }
}

async function saveDeclarationMonth(record) {
  if (record.declaration_month !== record.suggested_declaration_month && !record.declaration_override_reason) {
    toast.add({
      severity: 'error',
      summary: 'Lỗi',
      detail: 'Phải nhập lý do khi thay đổi tháng kê khai',
      life: 3000
    })
    return
  }
  
  try {
    await axios.post(`/insurance/reports/${report.id}/change-records/${record.id}/update-declaration-month`, {
      declaration_month: record.declaration_month,
      declaration_override_reason: record.declaration_override_reason
    })
    
    toast.add({
      severity: 'success',
      summary: 'Đã cập nhật',
      detail: 'Tháng kê khai đã được cập nhật',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Lỗi',
      detail: error.response?.data?.message || 'Không thể cập nhật',
      life: 5000
    })
  }
}
```

---

## Phase 4.3: Create Contribution Summary Tab

### File: `resources/js/Pages/Insurance/Reports/MonthlyReportShow.vue`

**Thêm tab mới**:
```vue
<TabView>
  <TabPanel header="Chi tiết thay đổi">
    <!-- Existing change records table -->
  </TabPanel>
  
  <TabPanel header="Tổng hợp đóng BHXH" :disabled="report.status !== 'FINALIZED'">
    <ContributionSummaryTab :report="report" />
  </TabPanel>
</TabView>
```

**Component mới**: `resources/js/Components/ContributionSummaryTab.vue`

```vue
<template>
  <div class="contribution-summary">
    <DataTable 
      :value="contributions" 
      :loading="loading"
      show-gridlines
      footer-class="font-bold"
    >
      <template #header>
        <div class="flex justify-between items-center">
          <h4>Tổng hợp đóng BHXH tháng {{ reportMonth }}</h4>
          <Button label="Xuất Excel" icon="pi pi-download" @click="exportToExcel" />
        </div>
      </template>
      
      <Column field="employee_code" header="Mã NV" style="min-width: 8rem" frozen />
      <Column field="employee_name" header="Họ tên" style="min-width: 14rem" frozen />
      <Column field="base_insurance_salary" header="Lương BH" style="min-width: 12rem">
        <template #body="{ data }">
          {{ formatCurrency(data.base_insurance_salary) }}
        </template>
      </Column>
      
      <!-- 5 Component Columns -->
      <Column header="BHXH Hưu trí" style="min-width: 12rem">
        <template #body="{ data }">
          {{ formatCurrency(data.components.BHXH_HUU_TU?.amount) }}
        </template>
      </Column>
      
      <Column header="BHXH Ốm đau" style="min-width: 12rem">
        <template #body="{ data }">
          {{ formatCurrency(data.components.BHXH_BENH?.amount) }}
        </template>
      </Column>
      
      <Column header="BHXH TNLĐ" style="min-width: 12rem">
        <template #body="{ data }">
          {{ formatCurrency(data.components.BHXH_TNLD?.amount) }}
        </template>
      </Column>
      
      <Column header="BHTN" style="min-width: 12rem">
        <template #body="{ data }">
          <div>
            <div>{{ formatCurrency(data.components.BHTN?.amount) }}</div>
            <div v-if="data.components.BHTN?.base_type === 'FIXED_AMOUNT'" class="text-xs text-gray-500">
              (Cố định: {{ formatCurrency(data.components.BHTN.base_used) }})
            </div>
          </div>
        </template>
      </Column>
      
      <Column header="BHYT" style="min-width: 12rem">
        <template #body="{ data }">
          {{ formatCurrency(data.components.BHYT?.amount) }}
        </template>
      </Column>
      
      <Column field="total_contribution" header="Tổng cộng" style="min-width: 14rem" class="font-bold">
        <template #body="{ data }">
          <span class="font-bold text-blue-600">
            {{ formatCurrency(data.total_contribution) }}
          </span>
        </template>
      </Column>
      
      <template #footer>
        <div class="grid grid-cols-8 gap-4 font-bold text-lg">
          <div class="col-span-3 text-right">TỔNG CỘNG:</div>
          <div>{{ formatCurrency(summary.total_bhxh_huu_tu) }}</div>
          <div>{{ formatCurrency(summary.total_bhxh_benh) }}</div>
          <div>{{ formatCurrency(summary.total_bhxh_tnld) }}</div>
          <div>{{ formatCurrency(summary.total_bhtn) }}</div>
          <div>{{ formatCurrency(summary.total_bhyt) }}</div>
          <div class="text-blue-600">{{ formatCurrency(summary.grand_total) }}</div>
        </div>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
  report: Object
})

const contributions = ref([])
const loading = ref(false)

const reportMonth = computed(() => {
  return `${props.report.month}/${props.report.year}`
})

const summary = computed(() => {
  const totals = {
    total_bhxh_huu_tu: 0,
    total_bhxh_benh: 0,
    total_bhxh_tnld: 0,
    total_bhtn: 0,
    total_bhyt: 0,
    grand_total: 0
  }
  
  contributions.value.forEach(c => {
    totals.total_bhxh_huu_tu += c.components.BHXH_HUU_TU?.amount || 0
    totals.total_bhxh_benh += c.components.BHXH_BENH?.amount || 0
    totals.total_bhxh_tnld += c.components.BHXH_TNLD?.amount || 0
    totals.total_bhtn += c.components.BHTN?.amount || 0
    totals.total_bhyt += c.components.BHYT?.amount || 0
    totals.grand_total += c.total_contribution
  })
  
  return totals
})

async function loadContributions() {
  loading.value = true
  try {
    const response = await axios.get(`/insurance/reports/${props.report.id}/export`)
    contributions.value = response.data.employees.map(emp => ({
      employee_code: emp.employee_code,
      employee_name: emp.full_name,
      base_insurance_salary: emp.base_insurance_salary,
      components: emp.components,
      total_contribution: emp.total_contribution
    }))
  } catch (error) {
    console.error('Failed to load contributions:', error)
  } finally {
    loading.value = false
  }
}

async function exportToExcel() {
  window.location.href = `/insurance/reports/${props.report.id}/export-excel`
}

function formatCurrency(value) {
  if (!value) return '-'
  return new Intl.NumberFormat('vi-VN', { 
    style: 'currency', 
    currency: 'VND' 
  }).format(value)
}

onMounted(() => {
  if (props.report.status === 'FINALIZED') {
    loadContributions()
  }
})
</script>
```

---

## Phase 4.4: Create Component CRUD UI

### File: `resources/js/Pages/Insurance/InsuranceComponentIndex.vue`

```vue
<template>
  <div>
    <div class="card">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Quản lý tỷ lệ đóng BHXH</h2>
        <Button 
          label="Thêm component" 
          icon="pi pi-plus" 
          @click="openDialog()"
          v-if="can('manage insurance')"
        />
      </div>
      
      <Message severity="warn" :closable="false" class="mb-4">
        <p class="font-semibold">Lưu ý quan trọng:</p>
        <ul class="list-disc ml-6 mt-2">
          <li>Thay đổi tỷ lệ chỉ ảnh hưởng đến hợp đồng MỚI sau này</li>
          <li>Hợp đồng và participation đã tạo sẽ GIỮ NGUYÊN tỷ lệ cũ</li>
          <li>Chỉ Admin được phép thay đổi tỷ lệ đóng</li>
        </ul>
      </Message>
      
      <DataTable :value="components" :loading="loading">
        <Column field="code" header="Mã" style="min-width: 12rem" />
        <Column field="name_vi" header="Tên tiếng Việt" style="min-width: 20rem" />
        <Column header="Tỷ lệ đóng mặc định" style="min-width: 18rem">
          <template #body="{ data }">
            <div class="space-y-1">
              <div>Người lao động: <strong>{{ (data.default_rate_employee * 100).toFixed(2) }}%</strong></div>
              <div>Người sử dụng: <strong>{{ (data.default_rate_employer * 100).toFixed(2) }}%</strong></div>
              <div class="text-blue-600 font-bold">Tổng: {{ (data.default_rate_total * 100).toFixed(2) }}%</div>
            </div>
          </template>
        </Column>
        <Column field="is_active" header="Trạng thái" style="min-width: 10rem">
          <template #body="{ data }">
            <Tag :value="data.is_active ? 'Đang dùng' : 'Ngừng'" :severity="data.is_active ? 'success' : 'danger'" />
          </template>
        </Column>
        <Column header="Thao tác" style="min-width: 10rem">
          <template #body="{ data }">
            <Button icon="pi pi-pencil" rounded outlined @click="openDialog(data)" v-if="can('manage insurance')" />
          </template>
        </Column>
      </DataTable>
    </div>
    
    <!-- Edit Dialog -->
    <Dialog v-model:visible="dialogVisible" header="Chỉnh sửa tỷ lệ đóng" :style="{ width: '600px' }">
      <div class="space-y-4">
        <div>
          <label class="block font-medium mb-2">Mã component</label>
          <InputText v-model="editForm.code" disabled class="w-full" />
        </div>
        
        <div>
          <label class="block font-medium mb-2">Tên tiếng Việt</label>
          <InputText v-model="editForm.name_vi" class="w-full" />
        </div>
        
        <div>
          <label class="block font-medium mb-2">Tỷ lệ người lao động (%)</label>
          <InputNumber 
            v-model="editForm.default_rate_employee_percent" 
            :min-fraction-digits="1"
            :max-fraction-digits="2"
            suffix="%"
            class="w-full"
          />
        </div>
        
        <div>
          <label class="block font-medium mb-2">Tỷ lệ người sử dụng (%)</label>
          <InputNumber 
            v-model="editForm.default_rate_employer_percent" 
            :min-fraction-digits="1"
            :max-fraction-digits="2"
            suffix="%"
            class="w-full"
          />
        </div>
        
        <div class="p-4 bg-blue-50 rounded-lg">
          <div class="font-bold text-blue-600">
            Tổng tỷ lệ đóng: {{ totalRate }}%
          </div>
        </div>
        
        <div class="flex items-center gap-2">
          <Checkbox v-model="editForm.is_active" binary inputId="is_active" />
          <label for="is_active">Đang hoạt động</label>
        </div>
      </div>
      
      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="dialogVisible = false" />
        <Button label="Lưu" icon="pi pi-check" @click="saveComponent" :loading="saving" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
// Implementation với CRUD operations
</script>
```

---

## Phase 4.5: Add Routes and Permissions

### File: `routes/web.php`

```php
// Insurance Components Management
Route::middleware(['auth', 'permission:manage insurance'])->group(function () {
    Route::resource('insurance-components', InsuranceComponentController::class);
});

// Insurance Report Declaration Month Override
Route::middleware(['auth', 'permission:finalize insurance reports'])->group(function () {
    Route::post('/insurance/reports/{report}/change-records/{record}/update-declaration-month', 
        [InsuranceReportController::class, 'updateDeclarationMonth']
    )->name('insurance.reports.update-declaration-month');
});

// Export endpoints
Route::get('/insurance/reports/{report}/export', [InsuranceReportController::class, 'exportReport'])
    ->name('insurance.reports.export');
Route::get('/insurance/reports/{report}/export-excel', [InsuranceReportController::class, 'exportToExcel'])
    ->name('insurance.reports.export-excel');
```

### File: `config/permission.php`

**Thêm permissions mới**:
```php
'insurance' => [
    'manage insurance' => 'Quản lý cấu hình BHXH (components, tỷ lệ)',
    'view insurance reports' => 'Xem báo cáo BHXH',
    'create insurance reports' => 'Tạo báo cáo BHXH',
    'approve insurance changes' => 'Duyệt thay đổi BHXH',
    'finalize insurance reports' => 'Hoàn tất báo cáo BHXH',
    'override declaration month' => 'Thay đổi tháng kê khai',
],
```

---

## Testing Checklist

### Phase 4.1 Testing
- [ ] Form hiển thị đúng 5 components
- [ ] Checkbox enable/disable hoạt động
- [ ] BHTN base type selection hoạt động (INSURANCE_SALARY/FIXED_AMOUNT)
- [ ] Fixed amount input chỉ hiện khi chọn FIXED_AMOUNT
- [ ] Auto-sync đúng: 3 BHXH components → has_social_insurance
- [ ] Auto-sync đúng: BHTN → has_unemployment_insurance
- [ ] Auto-sync đúng: BHYT → has_health_insurance
- [ ] Load existing participation data đúng khi edit
- [ ] Submit form tạo participation và components đúng

### Phase 4.2 Testing
- [ ] Hiển thị suggested_declaration_month badge
- [ ] Dropdown declaration_month hoạt động
- [ ] Warning icon hiện khi thay đổi từ suggested
- [ ] Required reason field validation hoạt động
- [ ] Save declaration month API call thành công
- [ ] Toast notifications hiển thị đúng

### Phase 4.3 Testing
- [ ] Tab chỉ enable khi report status = FINALIZED
- [ ] Load contribution data đúng
- [ ] Hiển thị đúng 5 component columns
- [ ] BHTN fixed amount note hiển thị đúng
- [ ] Footer totals tính đúng
- [ ] Export Excel hoạt động

### Phase 4.4 Testing
- [ ] Load components list
- [ ] Edit dialog mở đúng với data
- [ ] Tính total rate tự động
- [ ] Save component hoạt động
- [ ] Warning message hiển thị rõ ràng
- [ ] Permission check hoạt động

### Phase 4.5 Testing
- [ ] Routes hoạt động đúng
- [ ] Permission checks hoạt động
- [ ] Middleware authentication hoạt động

---

## Notes for Developers

1. **Backward Compatibility**: Giữ nguyên 3 boolean fields trong database và form (hidden) để không break existing code
2. **Migration Strategy**: Chạy backfill command để migrate existing data trước khi deploy UI
3. **Performance**: Consider caching insurance components list
4. **Validation**: Always validate permission before allowing declaration month override
5. **Audit Trail**: Log all declaration month changes via activity log

---

## Deployment Steps

1. Deploy database migrations (đã có từ Phase 1)
2. Run backfill command: `php artisan insurance:backfill-components`
3. Verify data: `php artisan insurance:verify-components`
4. Deploy backend API endpoints
5. Deploy frontend UI updates
6. Test with HR team
7. Monitor for issues
8. Update documentation
