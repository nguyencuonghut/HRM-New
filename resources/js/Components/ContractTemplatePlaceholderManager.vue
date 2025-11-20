<template>
  <Dialog :visible="visible" :style="{ width: '1200px' }" header="Quản lý Placeholders" :modal="true" @update:visible="$emit('update:visible', $event)">
    <div class="mb-4">
      <div class="flex justify-between items-center">
        <div>
          <p class="font-bold text-lg">{{ template.name }}</p>
          <p class="text-sm text-gray-600">{{ meta.total }} placeholders ({{ meta.auto_mapped }} auto-mapped, {{ meta.manual_required }} cần config)</p>
        </div>
        <Button label="Đồng bộ lại" icon="pi pi-sync" severity="secondary" @click="resyncPlaceholders" :loading="syncing" v-tooltip.top="'Re-sync placeholders từ file DOCX'" />
      </div>
    </div>

    <DataTable :value="mappings" :loading="loading" class="p-datatable-sm">
      <Column field="placeholder_key" header="Biến" style="min-width: 200px">
        <template #body="{ data }">
          <code class="bg-gray-100 px-2 py-1 rounded">${{ data.placeholder_key }}</code>
        </template>
      </Column>

      <Column field="data_source" header="Nguồn Dữ liệu" style="min-width: 150px">
        <template #body="{ data }">
          <Select v-model="data.data_source" :options="dataSources" optionLabel="label" optionValue="value"
                  class="w-full" @change="markChanged(data.id)" />
        </template>
      </Column>

      <Column field="source_path" header="Đường dẫn" style="min-width: 250px">
        <template #body="{ data }">
          <Select v-model="data.source_path" :options="getSourcePathOptions(data.data_source)"
                  optionLabel="label" optionValue="value" filter editable showClear
                  class="w-full" placeholder="Chọn hoặc nhập đường dẫn"
                  :disabled="data.data_source === 'MANUAL' || data.data_source === 'SYSTEM'"
                  @change="markChanged(data.id)" />
        </template>
      </Column>

      <Column field="transformer" header="Biến đổi" style="min-width: 150px">
        <template #body="{ data }">
          <Select v-model="data.transformer" :options="transformerOptions" optionLabel="label" optionValue="value"
                  class="w-full" showClear @change="markChanged(data.id)" />
        </template>
      </Column>

      <Column field="default_value" header="Giá trị mặc định" style="min-width: 150px">
        <template #body="{ data }">
          <InputText v-model="data.default_value" class="w-full" placeholder="N/A" @input="markChanged(data.id)" />
        </template>
      </Column>

      <Column field="is_required" header="Bắt buộc" style="width: 100px">
        <template #body="{ data }">
          <Checkbox v-model="data.is_required" :binary="true" @change="markChanged(data.id)" />
        </template>
      </Column>

      <Column header="Thao tác" style="width: 120px">
        <template #body="{ data }">
          <div class="flex gap-1">
            <Button icon="pi pi-refresh" severity="secondary" size="small" outlined rounded
                    @click="applyPreset(data)" v-tooltip.top="'Áp dụng Preset'"
                    :disabled="!hasPreset(data.placeholder_key)" />
            <Button icon="pi pi-check" severity="success" size="small" outlined rounded
                    @click="saveOne(data)" v-tooltip.top="'Lưu'"
                    :disabled="!changedIds.has(data.id)" />
          </div>
        </template>
      </Column>
    </DataTable>

    <template #footer>
      <div class="flex justify-between">
        <div class="text-sm text-gray-600">
          <i class="pi pi-info-circle mr-2"></i>
          <span>{{ changedIds.size }} thay đổi chưa lưu</span>
        </div>
        <div class="flex gap-2">
          <Button label="Đóng" severity="secondary" @click="close" />
          <Button label="Lưu tất cả" icon="pi pi-save" @click="saveAll" :loading="saving" :disabled="changedIds.size === 0" />
        </div>
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import { useToast } from 'primevue/usetoast'

const props = defineProps({
  visible: Boolean,
  template: Object
})

const emit = defineEmits(['update:visible', 'saved'])

const toast = useToast()
const loading = ref(false)

// Debug props
console.log('Component mounted, props:', props)
const saving = ref(false)
const syncing = ref(false)
const mappings = ref([])
const meta = ref({ total: 0, auto_mapped: 0, manual_required: 0 })
const changedIds = ref(new Set())
const presets = ref({})

const dataSources = [
  { value: 'CONTRACT', label: 'Dữ liệu Hợp đồng' },
  { value: 'COMPUTED', label: 'Tính toán' },
  { value: 'MANUAL', label: 'Người dùng nhập' },
  { value: 'SYSTEM', label: 'Hệ thống' }
]

const transformerOptions = [
  { value: null, label: 'Không' },
  { value: 'number_format', label: 'Format số' },
  { value: 'currency_to_words', label: 'Số tiền thành chữ' },
  { value: 'date_vn', label: 'Ngày VN' },
  { value: 'datetime_vn', label: 'Ngày giờ VN' },
  { value: 'gender_vn', label: 'Giới tính VN' },
  { value: 'marital_status_vn', label: 'Tình trạng hôn nhân VN' },
  { value: 'contract_type_vn', label: 'Loại hợp đồng VN' },
  { value: 'uppercase', label: 'CHỮ HOA' },
  { value: 'lowercase', label: 'chữ thường' },
  { value: 'ucfirst', label: 'Viết hoa đầu' }
]

// Gợi ý đường dẫn cho từng data source
const sourcePathSuggestions = {
  CONTRACT: [
    // Contract fields
    { value: 'contract_number', label: 'contract_number - Số hợp đồng' },
    { value: 'contract_type', label: 'contract_type - Loại hợp đồng' },
    { value: 'status', label: 'status - Trạng thái HĐ' },
    { value: 'source', label: 'source - Nguồn gốc HĐ' },
    { value: 'sign_date', label: 'sign_date - Ngày ký' },
    { value: 'start_date', label: 'start_date - Ngày bắt đầu' },
    { value: 'end_date', label: 'end_date - Ngày kết thúc' },
    { value: 'probation_end_date', label: 'probation_end_date - Ngày hết thử việc' },
    { value: 'terminated_at', label: 'terminated_at - Ngày chấm dứt' },
    { value: 'approved_at', label: 'approved_at - Ngày phê duyệt' },
    { value: 'termination_reason', label: 'termination_reason - Lý do chấm dứt' },
    { value: 'base_salary', label: 'base_salary - Lương cơ bản' },
    { value: 'insurance_salary', label: 'insurance_salary - Lương BHXH' },
    { value: 'position_allowance', label: 'position_allowance - Phụ cấp vị trí' },
    { value: 'social_insurance', label: 'social_insurance - BHXH' },
    { value: 'health_insurance', label: 'health_insurance - BHYT' },
    { value: 'unemployment_insurance', label: 'unemployment_insurance - BHTN' },
    { value: 'working_time', label: 'working_time - Thời gian làm việc' },
    { value: 'work_location', label: 'work_location - Địa điểm làm việc' },
    { value: 'note', label: 'note - Ghi chú HĐ' },
    { value: 'approval_note', label: 'approval_note - Ghi chú phê duyệt' },

    // Employee fields
    { value: 'employee.full_name', label: 'employee.full_name - Họ tên NV' },
    { value: 'employee.employee_code', label: 'employee.employee_code - Mã NV' },
    { value: 'employee.phone', label: 'employee.phone - SĐT NV' },
    { value: 'employee.emergency_contact_phone', label: 'employee.emergency_contact_phone - SĐT khẩn cấp' },
    { value: 'employee.personal_email', label: 'employee.personal_email - Email cá nhân' },
    { value: 'employee.company_email', label: 'employee.company_email - Email công ty' },
    { value: 'employee.cccd', label: 'employee.cccd - CCCD/CMND' },
    { value: 'employee.cccd_issued_on', label: 'employee.cccd_issued_on - Ngày cấp CCCD' },
    { value: 'employee.cccd_issued_by', label: 'employee.cccd_issued_by - Nơi cấp CCCD' },
    { value: 'employee.si_number', label: 'employee.si_number - Mã số BHXH' },
    { value: 'employee.dob', label: 'employee.dob - Ngày sinh' },
    { value: 'employee.gender', label: 'employee.gender - Giới tính' },
    { value: 'employee.marital_status', label: 'employee.marital_status - Tình trạng hôn nhân' },
    { value: 'employee.address_street', label: 'employee.address_street - Địa chỉ thường trú' },
    { value: 'employee.temp_address_street', label: 'employee.temp_address_street - Địa chỉ tạm trú' },
    { value: 'employee.hire_date', label: 'employee.hire_date - Ngày vào làm' },
    { value: 'employee.status', label: 'employee.status - Trạng thái NV' },

    // Department fields
    { value: 'department.name', label: 'department.name - Tên phòng ban' },
    { value: 'department.code', label: 'department.code - Mã phòng ban' },
    { value: 'department.type', label: 'department.type - Loại đơn vị' },
    { value: 'department.is_active', label: 'department.is_active - Trạng thái hoạt động' },

    // Position fields
    { value: 'position.title', label: 'position.title - Chức danh' },
    { value: 'position.level', label: 'position.level - Cấp bậc' },
    { value: 'position.insurance_base_salary', label: 'position.insurance_base_salary - Lương BHXH chức danh' },
    { value: 'position.position_salary', label: 'position.position_salary - Lương vị trí' },
    { value: 'position.competency_salary', label: 'position.competency_salary - Lương năng lực' },
    { value: 'position.allowance', label: 'position.allowance - Phụ cấp chức danh' },
  ],
  COMPUTED: [
    { value: 'total_salary', label: 'total_salary - Tổng lương' },
    { value: 'contract_duration_months', label: 'contract_duration_months - Thời hạn HĐ (tháng)' },
    { value: 'probation_duration_days', label: 'probation_duration_days - Thời gian thử việc (ngày)' },
    { value: 'employee_full_address', label: 'employee_full_address - Địa chỉ đầy đủ (thường trú)' },
    { value: 'employee_temp_full_address', label: 'employee_temp_full_address - Địa chỉ đầy đủ (tạm trú)' },
  ],
  SYSTEM: [
    { value: 'today', label: 'today - Ngày hôm nay' },
    { value: 'now', label: 'now - Ngày giờ hiện tại' },
    { value: 'current_year', label: 'current_year - Năm hiện tại' },
    { value: 'company_name', label: 'company_name - Tên công ty' },
  ],
  MANUAL: []
}

// Helper function to clean source_path (remove label part if user typed it)
function cleanSourcePath(path) {
  if (!path) return path
  // If user typed "employee.personal_email - Email cá nhân", extract just "employee.personal_email"
  const dashIndex = path.indexOf(' - ')
  return dashIndex > 0 ? path.substring(0, dashIndex).trim() : path
}

watch(() => props.visible, (val) => {
  console.log('Watch triggered - visible:', val, 'template:', props.template)
  if (val && props.template) {
    console.log('Loading mappings for template ID:', props.template.id)
    loadMappings()
    loadPresets()
  }
}, { immediate: true })

async function loadMappings() {
  loading.value = true
  try {
    const response = await fetch(`/contract-templates/${props.template.id}/placeholders`)
    console.log('Response status:', response.status)

    if (!response.ok) {
      const errorText = await response.text()
      console.error('API Error:', errorText)
      throw new Error(`HTTP ${response.status}: ${errorText}`)
    }

    const result = await response.json()
    console.log('Mappings loaded:', result)

    mappings.value = result.data
    meta.value = result.meta
    changedIds.value.clear()
  } catch (error) {
    console.error('Load mappings error:', error)
    toast.add({ severity: 'error', summary: 'Lỗi', detail: error.message || 'Không thể tải placeholders', life: 3000 })
  } finally {
    loading.value = false
  }
}

async function loadPresets() {
  try {
    const response = await fetch('/contract-templates/placeholders/presets')
    const result = await response.json()
    presets.value = result.data.presets
  } catch (error) {
    console.error('Failed to load presets', error)
  }
}

function markChanged(id) {
  changedIds.value.add(id)
}

function hasPreset(key) {
  return presets.value[key] !== undefined
}

function getSourcePathOptions(dataSource) {
  return sourcePathSuggestions[dataSource] || []
}

async function resyncPlaceholders() {
  if (!confirm('Đồng bộ lại sẽ thêm/xóa placeholders dựa trên file DOCX hiện tại. Tiếp tục?')) {
    return
  }

  syncing.value = true
  try {
    const response = await fetch(`/contract-templates/${props.template.id}/placeholders/resync`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    const result = await response.json()
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Đồng bộ thành công',
        detail: `Thêm: ${result.stats.added}, Xóa: ${result.stats.removed}, Không đổi: ${result.stats.unchanged}`,
        life: 5000
      })
      // Reload mappings
      await loadMappings()
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Lỗi', detail: 'Không thể đồng bộ placeholders', life: 3000 })
  } finally {
    syncing.value = false
  }
}

async function applyPreset(mapping) {
  try {
    const response = await fetch(`/contract-templates/${props.template.id}/placeholders/${mapping.id}/apply-preset`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    const result = await response.json()
    if (result.success) {
      Object.assign(mapping, result.data)
      changedIds.value.delete(mapping.id)
      toast.add({ severity: 'success', summary: 'Thành công', detail: 'Đã áp dụng preset', life: 3000 })
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Lỗi', detail: 'Không thể áp dụng preset', life: 3000 })
  }
}

async function saveOne(mapping) {
  try {
    const response = await fetch(`/contract-templates/${props.template.id}/placeholders/${mapping.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        data_source: mapping.data_source,
        source_path: mapping.source_path,
        default_value: mapping.default_value,
        transformer: mapping.transformer,
        is_required: mapping.is_required
      })
    })

    const result = await response.json()
    if (result.success) {
      changedIds.value.delete(mapping.id)
      toast.add({ severity: 'success', summary: 'Đã lưu', detail: result.message, life: 3000 })
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Lỗi', detail: 'Không thể lưu mapping', life: 3000 })
  }
}

async function saveAll() {
  if (changedIds.value.size === 0) return

  saving.value = true
  try {
    const changedMappings = mappings.value.filter(m => changedIds.value.has(m.id))

    const response = await fetch(`/contract-templates/${props.template.id}/placeholders/bulk-update`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        mappings: changedMappings.map(m => ({
          id: m.id,
          data_source: m.data_source,
          source_path: m.source_path,
          default_value: m.default_value,
          transformer: m.transformer,
          is_required: m.is_required
        }))
      })
    })

    const result = await response.json()
    if (result.success) {
      changedIds.value.clear()
      toast.add({ severity: 'success', summary: 'Thành công', detail: result.message, life: 3000 })
      emit('saved')
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Lỗi', detail: 'Không thể lưu', life: 3000 })
  } finally {
    saving.value = false
  }
}

function close() {
  if (changedIds.value.size > 0) {
    if (!confirm('Có thay đổi chưa lưu. Bạn có chắc muốn đóng?')) {
      return
    }
  }
  emit('update:visible', false)
}
</script>
