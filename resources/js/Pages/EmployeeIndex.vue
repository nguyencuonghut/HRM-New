<template>
    <Head>
        <title>Nhân viên</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button :label="'Thêm mới'" icon="pi pi-plus" class="mr-2" @click="openNew" />
                </template>

                <template #end>
                    <Button :label="'Xuất CSV'" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
                </template>
            </Toolbar>

            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="list || []"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="employees.meta.per_page"
                :totalRecords="employees.meta.total"
                :first="(employees.meta.current_page - 1) * employees.meta.per_page"
                @page="onPage($event)"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} nhân viên"
                :loading="loading"
                scrollable
                scrollHeight="600px"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Nhân viên</h4>
                        <div class="flex gap-2 items-center">
                            <IconField>
                                <InputIcon><i class="pi pi-search" /></InputIcon>
                                <InputText v-model="searchQuery" placeholder="Tìm kiếm..." @keyup.enter="handleSearchClick" />
                            </IconField>
                            <Button
                                icon="pi pi-search"
                                label="Tìm"
                                @click="handleSearchClick"
                                :loading="searching"
                                size="small"
                            />
                            <Select
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                v-model="statusFilter"
                                placeholder="-- Trạng thái --"
                                showClear
                                @change="applyStatusFilter"
                            />
                            <div class="flex items-center gap-2">
                                <Checkbox v-model="missingContractFilter" :binary="true" inputId="missing_contract" @change="applyFilters" />
                                <label for="missing_contract" class="cursor-pointer text-sm">Thiếu HĐ</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox v-model="hasActiveContractFilter" :binary="true" inputId="has_active_contract" @change="applyFilters" />
                                <label for="has_active_contract" class="cursor-pointer text-sm">Có HĐ hiệu lực</label>
                            </div>
                            <Checkbox v-model="showIncompleteOnly" :binary="true" inputId="incomplete" @change="applyCompletionFilter" />
                            <label for="incomplete" class="ml-1 cursor-pointer">Hồ sơ chưa đầy đủ (&lt; 80%)</label>
                        </div>
                    </div>
                </template>

                <!-- Frozen Left: Checkbox + Mã NV + Họ tên -->
                <Column selectionMode="multiple" frozen style="width: 3rem" :exportable="false"></Column>
                <Column field="employee_code" header="Mã NV" frozen sortable style="min-width: 8rem"></Column>
                <Column field="full_name" header="Họ tên" frozen sortable style="min-width: 14rem"></Column>

                <!-- Scrollable Middle: Các cột gộp để giảm chiều ngang -->
                <Column header="Liên hệ" style="min-width: 14rem">
                    <template #body="slotProps">
                        <div class="flex flex-col gap-1 text-sm">
                            <div v-if="slotProps.data.phone" class="flex items-center gap-1">
                                <i class="pi pi-phone text-xs text-gray-500"></i>
                                <span>{{ slotProps.data.phone }}</span>
                            </div>
                            <div v-if="slotProps.data.company_email" class="flex items-center gap-1">
                                <i class="pi pi-envelope text-xs text-gray-500"></i>
                                <span class="truncate">{{ slotProps.data.company_email }}</span>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="status" header="Trạng thái" style="min-width: 9rem">
                    <template #body="slotProps">
                        <Tag
                            :value="slotProps.data.status_label"
                            :severity="slotProps.data.status_severity"
                            :icon="slotProps.data.status_icon"
                        />
                    </template>
                </Column>

                <Column header="Hợp đồng" style="min-width: 10rem">
                    <template #body="slotProps">
                        <Tag
                            :value="slotProps.data.contract_status.label"
                            :severity="slotProps.data.contract_status.severity"
                            :icon="slotProps.data.contract_status.icon"
                        />
                    </template>
                </Column>

                <Column field="completion_score" header="% Hoàn thiện" sortable style="min-width: 11rem">
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <ProgressBar :value="slotProps.data.completion_score || 0"
                                         :showValue="false"
                                         style="height: 8px; width: 60px"
                                         :pt="{
                                           value: { style: getProgressBarColor(slotProps.data.completion_score) }
                                         }" />
                            <span class="text-sm font-medium">{{ slotProps.data.completion_score || 0 }}%</span>
                        </div>
                    </template>
                </Column>

                <Column header="Thời gian" sortable :sortField="'hire_date'" style="min-width: 11rem">
                    <template #body="slotProps">
                        <div class="flex flex-col gap-1 text-sm">
                            <!-- Ngày vào (từ employment history hoặc hire_date) -->
                            <div v-if="slotProps.data.current_employment_start || slotProps.data.hire_date" class="flex items-center gap-1">
                                <i class="pi pi-calendar text-xs text-gray-500"></i>
                                <span class="text-gray-700">{{ slotProps.data.current_employment_start || formatDate(slotProps.data.hire_date) }}</span>
                            </div>
                            <!-- Thâm niên -->
                            <div v-if="slotProps.data.cumulative_tenure.years || slotProps.data.cumulative_tenure.months" class="flex items-center gap-1">
                                <i class="pi pi-clock text-xs text-gray-500"></i>
                                <span class="text-gray-600">
                                    <span v-if="slotProps.data.cumulative_tenure.years > 0">{{ slotProps.data.cumulative_tenure.years }} năm </span><span v-if="slotProps.data.cumulative_tenure.months > 0">{{ slotProps.data.cumulative_tenure.months }} tháng</span>
                                </span>
                            </div>
                        </div>
                    </template>
                </Column>

                <!-- Frozen Right: Thao tác -->
                <Column header="Thao tác" frozen alignFrozen="right" :exportable="false" style="min-width: 10rem">
                    <template #body="slotProps">
                        <div class="flex gap-2">
                            <Button icon="pi pi-id-card" variant="outlined" rounded size="small" @click="goProfile(slotProps.data)" />
                            <Button icon="pi pi-pencil" variant="outlined" rounded size="small" @click="edit(slotProps.data)" />
                            <Button icon="pi pi-trash" variant="outlined" rounded size="small" severity="danger"
                                    @click="confirmDelete(slotProps.data)" />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8">
                        <i class="pi pi-inbox text-5xl text-gray-400 mb-3"></i>
                        <p class="text-gray-500 text-lg">Không tìm thấy nhân viên</p>
                        <p class="text-gray-400 text-sm mt-1">Thử thay đổi bộ lọc hoặc tìm kiếm</p>
                    </div>
                </template>

                <template #loadingicon>
                    <i class="pi pi-spin pi-spinner text-primary" style="font-size: 2rem"></i>
                </template>
            </DataTable>
        </div>

        <!-- Add/Edit Dialog -->
        <Dialog v-model:visible="dialog" :style="{ width: '720px' }" :header="isEditing ? 'Cập nhật nhân viên' : 'Thêm nhân viên'" :modal="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-2 required-field">Mã nhân viên</label>
                    <InputText v-model="form.employee_code" :invalid="submitted && !form.employee_code || hasError('employee_code')" fluid />
                    <small v-if="submitted && !form.employee_code" class="p-error block mt-1">Mã nhân viên là bắt buộc</small>
                    <small v-if="hasError('employee_code')" class="p-error block mt-1">{{ getError('employee_code') }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2 required-field">Họ và tên</label>
                    <InputText v-model="form.full_name" :invalid="submitted && !form.full_name || hasError('full_name')" fluid />
                    <small v-if="submitted && !form.full_name" class="p-error block mt-1">Họ tên là bắt buộc</small>
                    <small v-if="hasError('full_name')" class="p-error block mt-1">{{ getError('full_name') }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Ngày sinh</label>
                    <DatePicker v-model="form.dob" showIcon fluid inputId="dob" dateFormat="yy-mm-dd" />
                </div>

                <div>
                    <label class="block font-bold mb-2">Giới tính</label>
                    <Select v-model="form.gender" :options="genderOptions" optionLabel="label" optionValue="value" showClear fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">Tình trạng hôn nhân</label>
                    <Select v-model="form.marital_status" :options="maritalOptions" optionLabel="label" optionValue="value" showClear fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">SĐT</label>
                    <InputText v-model="form.phone" fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">Email công ty</label>
                    <InputText v-model="form.company_email" type="email" fluid :invalid="hasError('company_email')" />
                    <small v-if="hasError('company_email')" class="p-error block mt-1">{{ getError('company_email') }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Email cá nhân</label>
                    <InputText v-model="form.personal_email" type="email" fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">Ngày vào làm</label>
                    <DatePicker v-model="form.hire_date" showIcon fluid dateFormat="yy-mm-dd" />
                </div>

                <div>
                    <label class="block font-bold mb-2 required-field">Trạng thái</label>
                    <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid />
                    <small v-if="submitted && !form.status" class="p-error block mt-1">Trạng thái là bắt buộc</small>
                </div>

                <div class="md:col-span-2">
                    <label class="block font-bold mb-2">Địa chỉ thường trú (theo CCCD)</label>
                    <div class="mb-2">
                        <AddressSelector
                            v-model="form.ward_id"
                            v-model:provinceId="form.province_id"
                        />
                    </div>
                    <InputText v-model="form.address_street" placeholder="Số nhà, đường..." fluid />
                </div>

                <div class="md:col-span-2">
                    <label class="block font-bold mb-2">Địa chỉ tạm trú (nếu khác thường trú)</label>
                    <div class="mb-2">
                        <AddressSelector
                            v-model="form.temp_ward_id"
                            v-model:provinceId="form.temp_province_id"
                        />
                    </div>
                    <InputText v-model="form.temp_address_street" placeholder="Số nhà, đường..." fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">CCCD</label>
                    <InputText v-model="form.cccd" fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">Ngày cấp CCCD</label>
                    <DatePicker v-model="form.cccd_issued_on" showIcon fluid dateFormat="yy-mm-dd" />
                </div>

                <div>
                    <label class="block font-bold mb-2">Nơi cấp CCCD</label>
                    <InputText v-model="form.cccd_issued_by" fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">SĐT khẩn cấp</label>
                    <InputText v-model="form.emergency_contact_phone" fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">Mã số BHXH</label>
                    <InputText v-model="form.si_number" fluid />
                </div>
            </div>

            <template #footer>
                <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
                <Button :label="isEditing ? 'Cập nhật' : 'Lưu'" icon="pi pi-check" @click="save" :loading="saving" />
            </template>
        </Dialog>

        <!-- Delete 1 -->
        <Dialog v-model:visible="deleteDialog" :key="deleteDialog ? (current?.id || 'delete') : 'hidden'" :style="{ width: '450px' }" header="Xác nhận xóa" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="current">Bạn chắc chắn muốn xóa <b>{{ current.full_name }}</b>?</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteDialog = false" severity="secondary" variant="text" />
                <Button label="Có" icon="pi pi-check" @click="doDelete" severity="danger" :loading="deleting" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, watch, watchEffect } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { Head, router } from '@inertiajs/vue3'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Checkbox from 'primevue/checkbox'
import ProgressBar from 'primevue/progressbar'
import AddressSelector from '@/Components/AddressSelector.vue'
import { EmployeeService } from '@/services';
import { useFormValidation } from '@/composables/useFormValidation'
import { toYMD, formatDate } from '@/utils/dateHelper'
import { trimStringValues } from '@/utils/stringHelpers'

const props = defineProps({
    employees: {
        type: Object,
        default: () => ({
            data: [],
            meta: { total: 0, per_page: 20, current_page: 1 },
            links: []
        })
    },
    statusOptions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const { errors, hasError, getError } = useFormValidation()

const dt = ref()
const list = ref([...props.employees.data])
const selected = ref([])
const dialog = ref(false)
const deleteDialog = ref(false)
const submitted = ref(false)
const loading = ref(false)
const searching = ref(false) // Separate loading state for search

// Watch for props.employees changes and update list
watch(() => props.employees.data, (newData) => {
    list.value = [...newData]
}, { deep: true })

// Contract filters
const missingContractFilter = ref(props.filters.missing_contract || false)
const hasActiveContractFilter = ref(props.filters.has_active_contract_filter || false)
const saving = ref(false)
const deleting = ref(false)
const current = ref(null)

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
})
const searchQuery = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || null)
const showIncompleteOnly = ref(false)

const genderOptions = [
  { label:'Nam', value:'MALE' },
  { label:'Nữ', value:'FEMALE' },
  { label:'Khác', value:'OTHER' },
]
const maritalOptions = [
  { label:'Độc thân', value:'SINGLE' },
  { label:'Kết hôn', value:'MARRIED' },
  { label:'Đã ly hôn', value:'DIVORCED' },
  { label:'Góa', value:'WIDOWED' },
]

const form = ref({
  id: null,
  user_id: null,
  employee_code: '',
  full_name: '',
  dob: null,
  gender: null,
  marital_status: null,
  avatar: null,
  cccd: '',
  cccd_issued_on: null,
  cccd_issued_by: '',
  province_id: null, // For address selector
  ward_id: null,
  address_street: '',
  temp_province_id: null, // For temp address selector
  temp_ward_id: null,
  temp_address_street: '',
  phone: '',
  emergency_contact_phone: '',
  personal_email: '',
  company_email: '',
  hire_date: null,
  status: 'ACTIVE',
  si_number: '',
})

watch(() => props.employees.data, (val)=> { list.value = [...val] }, { immediate:true, deep:true })

// Debounced search - increased to 800ms for better performance
const SEARCH_DEBOUNCE = 800
let searchTimeout = null
watch(searchQuery, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    clearTimeout(searchTimeout)
    searching.value = true
    searchTimeout = setTimeout(() => {
      applyFilters()
    }, SEARCH_DEBOUNCE)
  }
})

const isEditing = computed(()=> !!form.value.id)

function getProgressBarColor(score) {
  if (score >= 80) return 'background: #22c55e' // green
  if (score >= 60) return 'background: #f59e0b' // orange
  return 'background: #ef4444' // red
}

function applyCompletionFilter() {
  if (showIncompleteOnly.value) {
    list.value = props.employees.data.filter(e => (e.completion_score || 0) < 80)
  } else {
    list.value = props.employees.data.filter(e => {
      if (!statusFilter.value) return true
      return e.status === statusFilter.value
    })
  }
}

function onPage(event) {
  const page = event.page + 1
  const perPage = event.rows

  router.get('/employees', {
    page: page,
    per_page: perPage,
    search: searchQuery.value || undefined,
    status: statusFilter.value || undefined,
    missing_contract: missingContractFilter.value || undefined,
    has_active_contract_filter: hasActiveContractFilter.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['employees'],
    onStart: () => loading.value = true,
    onFinish: () => loading.value = false,
  })
}

function handleSearchClick() {
  clearTimeout(searchTimeout)
  searching.value = true
  applyFilters()
}

function openNew() {
  submitted.value = false
  form.value = { ...form.value, id:null, employee_code:'', full_name:'', status:'ACTIVE' }
  dialog.value = true
}
function edit(row) {
  submitted.value = false
  form.value = {
    ...row,
    // Parse ISO date strings to Date objects for DatePicker
    dob: row.dob ? new Date(row.dob) : null,
    cccd_issued_on: row.cccd_issued_on ? new Date(row.cccd_issued_on) : null,
    hire_date: row.hire_date ? new Date(row.hire_date) : null,
  }
  dialog.value = true
}
function hideDialog() { dialog.value = false }

function save() {
  submitted.value = true
  if (!form.value.employee_code || !form.value.full_name || !form.value.status) return
  saving.value = true

  // Trim all string values before sending to backend
  const trimmedForm = trimStringValues(form.value)

  // Format dates to Laravel format (Y-m-d) using helper to avoid timezone issues
  const payload = { ...trimmedForm }
  payload.dob = toYMD(payload.dob)
  payload.cccd_issued_on = toYMD(payload.cccd_issued_on)
  payload.hire_date = toYMD(payload.hire_date)

  const cb = {
    onFinish: ()=> saving.value=false,
    onSuccess: ()=> { dialog.value=false; EmployeeService.index({}) },
    onError: ()=> {}
  }
  if (!isEditing.value) {
    EmployeeService.store(payload, cb)
  } else {
    EmployeeService.update(form.value.id, payload, cb)
  }
}

function confirmDelete(row){ current.value = row; deleteDialog.value = true }
function doDelete() {
  deleting.value = true
  EmployeeService.destroy(current.value.id, {
    onStart: () => {
      deleting.value = true
    },
    onSuccess: () => {
      // Không cần gọi EmployeeService.index(); backend đã redirect kèm flash
    },
    onError: () => {
      deleting.value = false
      // Giữ dialog mở để user thấy lỗi
    },
    onFinish: () => {
      deleting.value = false
      deleteDialog.value = false   // Đóng modal sau khi visit hoàn tất
      current.value = null
    }
  })
}

function exportCSV(){ dt.value?.exportCSV() }

function applyStatusFilter() {
  applyFilters()
}

function applyFilters() {
  // Filter server-side via query params
  router.get('/employees', {
    search: searchQuery.value || undefined,
    status: statusFilter.value || undefined,
    missing_contract: missingContractFilter.value || undefined,
    has_active_contract_filter: hasActiveContractFilter.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['employees'],
    onStart: ()=> loading.value = true,
    onFinish: ()=> loading.value = false,
  })
}

function goProfile(emp) {
  router.get(`/employees/${emp.id}/profile`, {}, {
    preserveState: true,
    preserveScroll: true,
  });
}
</script>
