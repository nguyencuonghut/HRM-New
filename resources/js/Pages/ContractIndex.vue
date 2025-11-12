<template>
  <Head>
    <title>Quản lý Hợp đồng</title>
  </Head>

  <div>
    <div class="card">
      <Toolbar class="mb-6">
        <template #start>
          <Button label="Thêm mới" icon="pi pi-plus" class="mr-2" @click="openNew" />
          <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined" @click="confirmDeleteSelected" :disabled="!selected || !selected.length" />
        </template>
        <template #end>
          <Button label="Xuất dữ liệu" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
        </template>
      </Toolbar>

      <DataTable
        ref="dt"
        :value="rows"
        v-model:selection="selected"
        dataKey="id"
        :paginator="true"
        :rows="10"
        :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Hiển thị {first}–{last}/{totalRecords} hợp đồng"
      >
        <template #header>
          <div class="flex flex-wrap gap-2 items-center justify-between">
            <h4 class="m-0">Danh sách Hợp đồng</h4>
            <IconField>
              <InputIcon><i class="pi pi-search" /></InputIcon>
              <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
            </IconField>
          </div>
        </template>

        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
        <Column field="contract_number" header="Số HĐ" sortable headerStyle="min-width:10rem;">
            <template #body="sp">
                <a
                href="#"
                class="text-primary underline"
                @click.prevent="goToGeneral(sp.data)"
                >
                {{ sp.data.contract_number }}
                </a>
            </template>
        </Column>
        <Column field="employee_name" header="Nhân viên" headerStyle="min-width:14rem;">
          <template #body="sp">
            {{ sp.data.employee?.full_name }} ({{ sp.data.employee?.employee_code }})
          </template>
        </Column>
        <Column field="department_name" header="Đơn vị" headerStyle="min-width:12rem;">
          <template #body="sp">{{ sp.data.department?.name || '-' }}</template>
        </Column>
        <Column field="position_name" header="Chức danh" headerStyle="min-width:12rem;">
          <template #body="sp">{{ sp.data.position?.title || '-' }}</template>
        </Column>
        <Column field="contract_type_label" header="Loại HĐ" sortable headerStyle="min-width:10rem;" />
        <Column field="start_date" header="Bắt đầu" sortable headerStyle="min-width:10rem;">
          <template #body="sp">{{ formatDate(sp.data.start_date) }}</template>
        </Column>
        <Column field="end_date" header="Kết thúc" sortable headerStyle="min-width:10rem;">
          <template #body="sp">{{ formatDate(sp.data.end_date) || '—' }}</template>
        </Column>
        <Column field="status_label" header="Trạng thái" headerStyle="min-width:10rem;">
          <template #body="sp">
            <Tag :value="sp.data.status_label" :severity="statusSeverity(sp.data.status)" />
          </template>
        </Column>
        <Column header="Tệp sinh ra" headerStyle="min-width:12rem;">
          <template #body="sp">
            <a v-if="sp.data.generated_pdf_path" :href="sp.data.generated_pdf_path" target="_blank" class="text-primary underline">Xem PDF</a>
            <span v-else>—</span>
          </template>
        </Column>
        <Column header="Thao tác" headerStyle="min-width:18rem;">
          <template #body="sp">
            <div class="flex gap-2">
              <Button icon="pi pi-pencil" outlined severity="success" rounded @click="edit(sp.data)" v-tooltip="'Chỉnh sửa hợp đồng'" />
              <Button icon="pi pi-trash" outlined severity="danger" rounded @click="confirmDelete(sp.data)" v-tooltip="'Xóa hợp đồng'" />
              <Button icon="pi pi-file" outlined rounded @click="openGenerate(sp.data)" v-tooltip="'Sinh hợp đồng (PDF)'" />
              <Button icon="pi pi-list" outlined rounded @click="goToAppendixes(sp.data)" v-tooltip="'Xem chi tiết & phụ lục'" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog tạo/sửa hợp đồng -->
    <Dialog v-model:visible="dialog" :style="{ width: '900px' }" header="Thông tin Hợp đồng" :modal="true">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-bold mb-2 required-field">Nhân viên</label>
          <Select v-model="form.employee_id" :options="employees" optionLabel="full_name" optionValue="id" filter showClear fluid
                  :invalid="submitted && !form.employee_id"
                  optionGroupLabel=""
                  :itemTemplate="empItemTmpl" />
          <small class="text-red-500" v-if="submitted && !form.employee_id">Nhân viên là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('employee_id')">{{ errors.employee_id }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Số HĐ</label>
          <InputText v-model.trim="form.contract_number" class="w-full" placeholder="VD: HĐ-2025-001" :invalid="submitted && !form.contract_number" />
          <small class="text-red-500" v-if="submitted && !form.contract_number">Số hợp đồng là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('contract_number')">{{ errors.contract_number }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Đơn vị</label>
          <Select v-model="form.department_id" :options="departments" optionLabel="name" optionValue="id" filter showClear fluid :invalid="submitted && !form.department_id" />
          <small class="text-red-500" v-if="submitted && !form.department_id">Đơn vị là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('department_id')">{{ errors.department_id }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Chức danh</label>
          <Select v-model="form.position_id" :options="positions" optionLabel="title" optionValue="id" filter showClear fluid :invalid="submitted && !form.position_id" />
          <small class="text-red-500" v-if="submitted && !form.position_id">Chức danh là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('position_id')">{{ errors.position_id }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Loại HĐ</label>
          <Select v-model="form.contract_type" :options="contractTypeOptions" optionLabel="label" optionValue="value" showClear fluid :invalid="submitted && !form.contract_type" />
          <small class="text-red-500" v-if="submitted && !form.contract_type">Loại hợp đồng là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('contract_type')">{{ errors.contract_type }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2">Trạng thái</label>
          <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" showClear fluid />
          <small class="text-red-500" v-if="hasError('status')">{{ errors.status }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Ngày bắt đầu</label>
          <DatePicker v-model="form.start_date" dateFormat="yy-mm-dd" showIcon fluid :invalid="hasError('start_date') || (submitted && !form.start_date)" />
          <small class="text-red-500" v-if="hasError('start_date')">{{ errors.start_date }}</small>
          <small class="text-red-500" v-else-if="submitted && !form.start_date">Ngày bắt đầu là bắt buộc.</small>
        </div>
        <div>
          <label class="block font-bold mb-2">Ngày kết thúc</label>
          <DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" showIcon fluid :disabled="form.contract_type==='INDEFINITE'" />
          <small class="text-red-500" v-if="hasError('end_date')">{{ errors.end_date }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Lương cơ bản</label>
          <InputText v-model.number="form.base_salary" type="number" class="w-full" placeholder="VND/tháng" :invalid="submitted && !form.base_salary" />
          <small class="text-red-500" v-if="submitted && !form.base_salary">Lương cơ bản là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('base_salary')">{{ errors.base_salary }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Lương đóng BH</label>
          <InputText v-model.number="form.insurance_salary" type="number" class="w-full" placeholder="VND/tháng" :invalid="submitted && !form.insurance_salary" />
          <small class="text-red-500" v-if="submitted && !form.insurance_salary">Lương đóng BH là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('insurance_salary')">{{ errors.insurance_salary }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Phụ cấp vị trí</label>
          <InputText v-model.number="form.position_allowance" type="number" class="w-full" placeholder="VND/tháng" :invalid="submitted && !form.position_allowance" />
          <small class="text-red-500" v-if="submitted && !form.position_allowance">Phụ cấp vị trí là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('position_allowance')">{{ errors.position_allowance }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2">Thời gian làm việc</label>
          <InputText v-model.trim="form.working_time" class="w-full" placeholder="VD: T2–T6 08:00–17:00" />
        </div>

        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Địa điểm làm việc</label>
          <InputText v-model.trim="form.work_location" class="w-full" placeholder="VD: Văn phòng Ninh Bình" />
        </div>

        <!-- Other allowances (repeater) -->
        <div class="md:col-span-2">
          <div class="flex items-center justify-between mb-2">
            <label class="block font-bold">Phụ cấp khác</label>
            <Button size="small" icon="pi pi-plus" label="Thêm phụ cấp" @click="addAllowance" />
          </div>
          <div v-if="!form.other_allowances || form.other_allowances.length===0" class="text-gray-500 text-sm">Chưa có phụ cấp khác.</div>
          <div v-for="(al, idx) in form.other_allowances" :key="idx" class="grid grid-cols-12 gap-2 mb-2">
            <div class="col-span-6">
              <InputText v-model.trim="al.name" class="w-full" placeholder="Tên phụ cấp" />
            </div>
            <div class="col-span-5">
              <InputText v-model.number="al.amount" type="number" class="w-full" placeholder="Số tiền VND/tháng" />
            </div>
            <div class="col-span-1 flex items-center justify-end">
              <Button icon="pi pi-trash" severity="danger" text @click="removeAllowance(idx)" />
            </div>
          </div>
        </div>

        <!-- Bổ sung: nguồn sinh hợp đồng + template -->
        <div>
          <label class="block font-bold mb-2 required-field">Nguồn tạo</label>
          <Select v-model="form.source" :options="sourceOptions" optionLabel="label" optionValue="value" showClear fluid :invalid="submitted && !form.source" />
          <small class="text-red-500" v-if="submitted && !form.source">Nguồn tạo là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('source')">{{ errors.source }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2">Source ID (nếu có)</label>
          <InputText v-model.trim="form.source_id" class="w-full" placeholder="offers.id hoặc để trống" />
          <small class="text-red-500" v-if="hasError('source_id')">{{ errors.source_id }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2">Mẫu hợp đồng</label>
          <Select v-model="form.template_id" :options="templates" optionLabel="name" optionValue="id" filter showClear fluid />
          <small class="text-red-500" v-if="hasError('template_id')">{{ errors.template_id }}</small>
        </div>
        <div class="flex items-center gap-2">
          <Checkbox v-model="form.created_from_offer" :binary="true" inputId="cfo" />
          <label for="cfo">Tạo từ Offer (tuyển dụng)</label>
        </div>

        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Ghi chú</label>
          <Textarea v-model.trim="form.note" autoResize rows="3" class="w-full" />
        </div>
      </div>

      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
        <Button label="Lưu" icon="pi pi-check" @click="save" :loading="saving" />
      </template>
    </Dialog>

    <!-- Dialog sinh hợp đồng (chọn template nhanh) -->
    <Dialog v-model:visible="generateDialog" :style="{ width: '520px' }" header="Sinh hợp đồng (PDF)" :modal="true">
      <div class="flex flex-col gap-4">
        <div>
          <label class="block font-bold mb-2">Chọn mẫu</label>
          <Select v-model="generateTemplateId" :options="templates" optionLabel="name" optionValue="id" filter showClear fluid />
        </div>
        <div class="text-sm text-gray-600">
          Sau khi sinh, tệp PDF sẽ lưu vào hệ thống và hiển thị link tải ở danh sách.
        </div>
      </div>
      <template #footer>
        <Button label="Đóng" icon="pi pi-times" text @click="generateDialog=false" />
        <Button label="Sinh hợp đồng" icon="pi pi-file" @click="doGenerate" :loading="generating" />
      </template>
    </Dialog>

    <!-- Dialog xác nhận xóa -->
    <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span v-if="current">Bạn có chắc muốn xóa <b>{{ current.contract_number || current.employee?.full_name }}</b>?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="remove" :loading="deleting" />
      </template>
    </Dialog>

    <!-- Dialog xác nhận xóa nhiều -->
    <Dialog v-model:visible="deleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span>Bạn có chắc muốn xóa các hợp đồng đã chọn?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteManyDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="removeMany" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import { ContractService } from '@/services/ContractService'
import { useFormValidation } from '@/composables/useFormValidation'
import { formatDate, toYMD } from '@/utils/dateHelper'

const { errors, hasError, getError } = useFormValidation()

const definePropsData = defineProps({
  contracts: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  positions: { type: Array, default: () => [] },
  templates: { type: Array, default: () => [] },
  contractTypeOptions: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] },
  sourceOptions: { type: Array, default: () => [] },
})

// Table
const dt = ref()
const selected = ref([])
const filters = ref({ global: { value: null, matchMode: 'contains' } })
const rows = computed(() => definePropsData.contracts || [])

// Dialog states
const dialog = ref(false)
const deleteDialog = ref(false)
const deleteManyDialog = ref(false)
const generateDialog = ref(false)
const saving = ref(false)
const deleting = ref(false)
const generating = ref(false)
const submitted = ref(false)
const current = ref(null)
const generateTemplateId = ref(null)

// Form model
const form = ref({
  id: null,
  employee_id: null,
  department_id: null,
  position_id: null,
  contract_number: '',
  contract_type: null,
  start_date: null,
  end_date: null,
  base_salary: null,
  insurance_salary: null,
  position_allowance: null,
  other_allowances: [],
  working_time: '',
  work_location: '',
  status: 'DRAFT',
  source: 'LEGACY',
  source_id: '',
  template_id: null,
  created_from_offer: false,
  note: ''
})

// Options - Backend sẽ cung cấp qua props
const contractTypeOptions = computed(() => definePropsData.contractTypeOptions || [])
const statusOptions = computed(() => definePropsData.statusOptions || [])
const sourceOptions = computed(() => definePropsData.sourceOptions || [])
const employees = computed(() => definePropsData.employees || [])
const departments = computed(() => definePropsData.departments || [])
const positions = computed(() => definePropsData.positions || [])
const templates = computed(() => definePropsData.templates || [])

// Helpers
const statusSeverity = (s) => ({
  DRAFT: 'secondary',
  PENDING_APPROVAL: 'warning',
  ACTIVE: 'success',
  REJECTED: 'danger',
  SUSPENDED: 'contrast',
  TERMINATED: 'contrast',
  EXPIRED: 'contrast',
  CANCELLED: 'contrast'
}[s] || 'info')

const empItemTmpl = (opt) => `${opt.full_name} (${opt.employee_code || '—'})`

// CRUD
function openNew() {
  form.value = {
    id: null,
    employee_id: null,
    department_id: null,
    position_id: null,
    contract_number: '',
    contract_type: null,
    start_date: null,
    end_date: null,
    base_salary: null,
    insurance_salary: null,
    position_allowance: null,
    other_allowances: [],
    working_time: '',
    work_location: '',
    status: 'DRAFT',
    source: 'LEGACY',
    source_id: '',
    template_id: null,
    created_from_offer: false,
    note: ''
  }
  submitted.value = false
  dialog.value = true
}
function edit(row) {
  form.value = {
    id: row.id,
    employee_id: row.employee_id,
    department_id: row.department_id,
    position_id: row.position_id,
    contract_number: row.contract_number || '',
    contract_type: row.contract_type || null,
    start_date: row.start_date,
    end_date: row.end_date,
    base_salary: row.base_salary || null,
    insurance_salary: row.insurance_salary || null,
    position_allowance: row.position_allowance || null,
    other_allowances: Array.isArray(row.other_allowances) ? JSON.parse(JSON.stringify(row.other_allowances)) : [],
    working_time: row.working_time || '',
    work_location: row.work_location || '',
    status: row.status || 'DRAFT',
    source: row.source || 'LEGACY',
    source_id: row.source_id || '',
    template_id: row.template_id || null,
    created_from_offer: !!row.created_from_offer,
    note: row.note || ''
  }
  submitted.value = false
  dialog.value = true
}
function hideDialog() {
  dialog.value = false
  submitted.value = false
}
function save() {
  submitted.value = true

  // Validation
  if (!form.value.employee_id || !form.value.contract_number || !form.value.department_id ||
      !form.value.position_id || !form.value.contract_type || !form.value.start_date ||
      !form.value.base_salary || !form.value.insurance_salary || !form.value.position_allowance ||
      !form.value.source) {
    return
  }

  saving.value = true
  const payload = {
    ...form.value,
    start_date: toYMD(form.value.start_date),
    end_date: toYMD(form.value.end_date)
  }
  const opts = {
    onFinish: () => (saving.value = false),
    onSuccess: () => {
      dialog.value = false
      form.value = {}
    }
  }
  if (!form.value.id) {
    ContractService.store(payload, opts)
  } else {
    ContractService.update(form.value.id, payload, opts)
  }
}
function confirmDelete(row) {
  current.value = row
  deleteDialog.value = true
}
function remove() {
  deleting.value = true
  ContractService.destroy(current.value.id, {
    onFinish: () => {
      deleting.value = false
      deleteDialog.value = false
      current.value = null
    }
  })
}
function confirmDeleteSelected() {
  deleteManyDialog.value = true
}
function removeMany() {
  const ids = selected.value.map((x) => x.id)
  deleting.value = true
  ContractService.bulkDelete(ids, {
    onFinish: () => {
      deleting.value = false
      deleteManyDialog.value = false
      selected.value = []
    }
  })
}
function exportCSV() {
  dt.value?.exportCSV()
}

// other_allowances
function addAllowance() {
  if (!Array.isArray(form.value.other_allowances)) form.value.other_allowances = []
  form.value.other_allowances.push({ name: '', amount: null })
}
function removeAllowance(idx) {
  form.value.other_allowances.splice(idx, 1)
}

// Generate PDF
function openGenerate(row) {
  current.value = row
  generateTemplateId.value = row.template_id || null
  generateDialog.value = true
}
function doGenerate() {
  generating.value = true
  ContractService.generate(current.value.id, { template_id: generateTemplateId.value }, {
    onFinish: () => {
      generating.value = false
      generateDialog.value = false
      current.value = null
    }
  })
}
function goToAppendixes(row) {
  router.get(`/contracts/${row.id}`, { tab: 'appendixes' })
}
function goToGeneral(row) {
  router.get(`/contracts/${row.id}`, { tab: 'general' })
}
</script>

<style scoped>
.required-field::after { content: ' *'; color: red; }
</style>
