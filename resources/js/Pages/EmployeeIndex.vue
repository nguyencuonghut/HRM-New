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
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} nhân viên"
                :loading="loading"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Nhân viên</h4>
                        <div class="flex gap-2 items-center">
                            <IconField>
                                <InputIcon><i class="pi pi-search" /></InputIcon>
                                <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
                            </IconField>
                            <Select
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                v-model="statusFilter"
                                placeholder="-- Trạng thái --"
                                showClear
                                @change="applyStatusFilter"
                            />
                        </div>
                    </div>
                </template>

                <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
                <Column field="employee_code" header="Mã NV" sortable style="min-width: 10rem"></Column>
                <Column field="full_name" header="Họ tên" sortable style="min-width: 16rem"></Column>
                <Column field="phone" header="SĐT" style="min-width: 10rem"></Column>
                <Column field="company_email" header="Email công ty" style="min-width: 16rem"></Column>
                <Column field="status" header="Trạng thái" style="min-width: 10rem">
                    <template #body="slotProps">
                        <Badge :value="statusLabel(slotProps.data.status)"
                               :severity="statusSeverity(slotProps.data.status)" />
                    </template>
                </Column>
                <Column field="hire_date" header="Ngày vào" sortable style="min-width: 10rem">
                    <template #body="slotProps">
                        {{ formatDate(slotProps.data.hire_date) }}
                    </template>
                </Column>
                <Column header="Thao tác" :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <div class="flex gap-2">
                            <Button icon="pi pi-pencil" variant="outlined" rounded @click="edit(slotProps.data)" />
                            <Button icon="pi pi-trash" variant="outlined" rounded severity="danger"
                                    @click="confirmDelete(slotProps.data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Add/Edit Dialog -->
        <Dialog v-model:visible="dialog" :style="{ width: '720px' }" :header="isEditing ? 'Cập nhật nhân viên' : 'Thêm nhân viên'" :modal="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-2 required-field">Mã nhân viên</label>
                    <InputText v-model.trim="form.employee_code" :invalid="submitted && !form.employee_code || hasError('employee_code')" fluid />
                    <small v-if="submitted && !form.employee_code" class="p-error block mt-1">Mã nhân viên là bắt buộc</small>
                    <small v-if="hasError('employee_code')" class="p-error block mt-1">{{ getError('employee_code') }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2 required-field">Họ và tên</label>
                    <InputText v-model.trim="form.full_name" :invalid="submitted && !form.full_name || hasError('full_name')" fluid />
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
                    <InputText v-model.trim="form.phone" fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">Email công ty</label>
                    <InputText v-model.trim="form.company_email" type="email" fluid :invalid="hasError('company_email')" />
                    <small v-if="hasError('company_email')" class="p-error block mt-1">{{ getError('company_email') }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Email cá nhân</label>
                    <InputText v-model.trim="form.personal_email" type="email" fluid />
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
                    <InputText v-model.trim="form.address_street" placeholder="Số nhà, đường..." fluid />
                </div>

                <div class="md:col-span-2">
                    <label class="block font-bold mb-2">Địa chỉ tạm trú (nếu khác thường trú)</label>
                    <div class="mb-2">
                        <AddressSelector
                            v-model="form.temp_ward_id"
                            v-model:provinceId="form.temp_province_id"
                        />
                    </div>
                    <InputText v-model.trim="form.temp_address_street" placeholder="Số nhà, đường..." fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">CCCD</label>
                    <InputText v-model.trim="form.cccd" fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">Ngày cấp CCCD</label>
                    <DatePicker v-model="form.cccd_issued_on" showIcon fluid dateFormat="yy-mm-dd" />
                </div>

                <div>
                    <label class="block font-bold mb-2">Nơi cấp CCCD</label>
                    <InputText v-model.trim="form.cccd_issued_by" fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">SĐT khẩn cấp</label>
                    <InputText v-model.trim="form.emergency_contact_phone" fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">Mã số BHXH</label>
                    <InputText v-model.trim="form.si_number" fluid />
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
import { ref, computed, watch } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { Head } from '@inertiajs/vue3'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import AddressSelector from '@/components/AddressSelector.vue'
import { EmployeeService } from '@/services';
import { useFormValidation } from '@/composables/useFormValidation'
import { toYMD, formatDate } from '@/utils/dateHelper'

const props = defineProps({
    employees: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
})

const { errors, hasError, getError } = useFormValidation()

const dt = ref()
const list = ref([...props.employees])
const selected = ref([])
const dialog = ref(false)
const deleteDialog = ref(false)
const submitted = ref(false)
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const current = ref(null)

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
})
const statusFilter = ref(null)

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

watch(() => props.employees, (val)=> { list.value = [...val] }, { immediate:true, deep:true })

const isEditing = computed(()=> !!form.value.id)

function statusLabel(v) {
  const f = props.statusOptions.find(x=>x.value===v); return f? f.label : v
}
function statusSeverity(v) {
  return v==='ACTIVE' ? 'success' : (v==='ON_LEAVE' ? 'info' : 'danger')
}

function openNew() {
  submitted.value = false
  form.value = { ...form.value, id:null, employee_code:'', full_name:'', status:'ACTIVE' }
  dialog.value = true
}
function edit(row) {
  submitted.value = false
  form.value = { ...row } // row đã ở định dạng Resource
  dialog.value = true
}
function hideDialog() { dialog.value = false }

function save() {
  submitted.value = true
  if (!form.value.employee_code || !form.value.full_name || !form.value.status) return
  saving.value = true

  // Format dates to Laravel format (Y-m-d) using helper to avoid timezone issues
  const payload = { ...form.value }
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
    onStart: () => { deleting.value = true },
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
  // lọc server-side nhẹ nhàng (giữ đúng style dùng Inertia GET)
  EmployeeService.index({
    onStart: ()=> loading.value = true,
    onFinish: ()=> loading.value = false,
  })
}
</script>
