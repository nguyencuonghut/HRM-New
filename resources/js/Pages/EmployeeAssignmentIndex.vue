<template>
  <Head>
    <title>Phân công nhân sự</title>
  </Head>

  <div>
    <div class="card">
      <Toolbar class="mb-6">
        <template #start>
          <Button :label="'Thêm phân công'" icon="pi pi-plus" class="mr-2" @click="openNew" />
          <Button :label="'Xoá đã chọn'" icon="pi pi-trash" severity="danger" variant="outlined"
                  @click="confirmDeleteSelected" :disabled="!selectedRows || !selectedRows.length" />
        </template>

        <template #end>
          <Button :label="'Xuất CSV'" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
        </template>
      </Toolbar>

      <DataTable
        ref="dt"
        v-model:selection="selectedRows"
        :value="rows || []"
        dataKey="id"
        :paginator="true"
        :rows="10"
        :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} phân công"
        :loading="loading"
      >
        <template #header>
          <div class="flex flex-wrap gap-2 items-center justify-between">
            <h4 class="m-0">Phân công nhân sự</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
              <IconField>
                <InputIcon><i class="pi pi-search" /></InputIcon>
                <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
              </IconField>

              <Select
                :options="departmentsForSelect"
                optionLabel="name"
                optionValue="id"
                v-model="departmentFilter"
                placeholder="-- Phòng/Ban --"
                showClear
                @change="applyDepartmentFilter"
              />

              <Select
                :options="roleTypesForSelect"
                optionLabel="label"
                optionValue="value"
                v-model="roleFilter"
                placeholder="-- Vai trò --"
                showClear
                @change="applyQuickFilter"
              />

              <Select
                :options="statusesForSelect"
                optionLabel="label"
                optionValue="value"
                v-model="statusFilter"
                placeholder="-- Trạng thái --"
                showClear
                @change="applyQuickFilter"
              />
            </div>
          </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
        <Column field="employee.full_name" header="Nhân viên" sortable style="min-width: 14rem">
          <template #body="sp">
            {{ sp.data.employee?.full_name }}
          </template>
        </Column>
        <Column field="department.name" header="Phòng/Ban" sortable style="min-width: 14rem">
          <template #body="sp">
            {{ sp.data.department?.name }}
          </template>
        </Column>
        <Column field="position.title" header="Chức danh" sortable style="min-width: 12rem">
          <template #body="sp">
            {{ sp.data.position?.title || '-' }}
          </template>
        </Column>
        <Column header="Vai trò" style="min-width: 10rem">
          <template #body="sp">
            <Tag :value="roleLabel(sp.data.role_type)" />
          </template>
        </Column>
        <Column header="Chính">
          <template #body="sp">
            <Badge :value="sp.data.is_primary ? 'Chính' : 'Phụ'" :severity="sp.data.is_primary ? 'success' : 'secondary'" />
          </template>
        </Column>
        <Column header="Hiệu lực" style="min-width: 12rem">
          <template #body="sp">
            {{ formatDate(sp.data.start_date) }} <span v-if="sp.data.end_date">→ {{ formatDate(sp.data.end_date) }}</span>
          </template>
        </Column>
        <Column header="Trạng thái" style="min-width: 10rem">
          <template #body="sp">
            <Badge :value="statusLabel(sp.data.status)" :severity="sp.data.status==='ACTIVE' ? 'success' : 'danger'" />
          </template>
        </Column>
        <Column header="Thao tác" :exportable="false" style="min-width: 12rem">
          <template #body="sp">
            <div class="flex gap-2">
              <Button icon="pi pi-pencil" variant="outlined" rounded @click="editRow(sp.data)" />
              <Button icon="pi pi-trash" variant="outlined" rounded severity="danger" @click="confirmDelete(sp.data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog Add/Edit -->
    <Dialog v-model:visible="dialogVisible" :style="{ width: '520px' }" :header="isEditing ? 'Cập nhật phân công' : 'Thêm phân công'" :modal="true">
      <div class="flex flex-col gap-6">
        <div>
          <label class="block font-bold mb-3 required-field">Nhân viên</label>
          <Select v-model="form.employee_id" :options="employeesForSelect" optionLabel="full_name" filter optionValue="id" fluid
                  :invalid="submitted && !form.employee_id || hasError('employee_id')" />
          <small v-if="submitted && !form.employee_id" class="text-red-500">Bắt buộc</small>
          <small v-if="hasError('employee_id')" class="p-error block mt-1">{{ getError('employee_id') }}</small>
        </div>

        <div>
          <label class="block font-bold mb-3 required-field">Phòng/Ban</label>
          <Select v-model="form.department_id" :options="departmentsForSelect" optionLabel="name" filter optionValue="id" fluid
                  :invalid="submitted && !form.department_id || hasError('department_id')" />
          <small v-if="submitted && !form.department_id" class="text-red-500">Bắt buộc</small>
          <small v-if="hasError('department_id')" class="p-error block mt-1">{{ getError('department_id') }}</small>
        </div>

        <div>
          <label class="block font-bold mb-3">Chức danh</label>
          <Select v-model="form.position_id" :options="positionsForSelect" optionLabel="title" filter optionValue="id" fluid showClear />
          <small v-if="hasError('position_id')" class="p-error block mt-1">{{ getError('position_id') }}</small>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block font-bold mb-3 required-field">Vai trò</label>
            <Select v-model="form.role_type" :options="roleTypesForSelect" optionLabel="label" optionValue="value" fluid
                    :invalid="submitted && !form.role_type || hasError('role_type')" />
            <small v-if="submitted && !form.role_type" class="text-red-500">Bắt buộc</small>
            <small v-if="hasError('role_type')" class="p-error block mt-1">{{ getError('role_type') }}</small>
          </div>

          <div class="flex items-center gap-2 mt-7">
            <Checkbox v-model="form.is_primary" :binary="true" inputId="is_primary" />
            <label for="is_primary" class="font-bold">Phân công CHÍNH</label>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block font-bold mb-3">Ngày bắt đầu</label>
            <DatePicker v-model="form.start_date" dateFormat="yy-mm-dd" showIcon fluid />
            <small v-if="hasError('start_date')" class="p-error block mt-1">{{ getError('start_date') }}</small>
          </div>
          <div>
            <label class="block font-bold mb-3">Ngày kết thúc</label>
            <DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" showIcon fluid />
            <small v-if="hasError('end_date')" class="p-error block mt-1">{{ getError('end_date') }}</small>
          </div>
        </div>

        <div>
          <label class="block font-bold mb-3 required-field">Trạng thái</label>
          <Select v-model="form.status" :options="statusesForSelect" optionLabel="label" optionValue="value" fluid
                  :invalid="submitted && !form.status || hasError('status')" />
          <small v-if="submitted && !form.status" class="text-red-500">Bắt buộc</small>
          <small v-if="hasError('status')" class="p-error block mt-1">{{ getError('status') }}</small>
        </div>
      </div>

      <template #footer>
        <Button :label="'Hủy'" icon="pi pi-times" text @click="hideDialog" />
        <Button :label="'Lưu'" icon="pi pi-check" @click="save" :loading="saving" />
      </template>
    </Dialog>

    <!-- Delete Dialog -->
    <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" :header="'Xác nhận xoá'" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span v-if="currentRow">Bạn có chắc muốn xoá phân công của <b>{{ currentRow.employee?.full_name }}</b>?</span>
      </div>
      <template #footer>
        <Button :label="'Không'" icon="pi pi-times" text @click="deleteDialog = false" severity="secondary" variant="text" />
        <Button :label="'Xoá'" icon="pi pi-check" @click="remove" severity="danger" :loading="deleting" />
      </template>
    </Dialog>

    <!-- Delete Multiple Dialog -->
    <Dialog v-model:visible="deleteManyDialog" :style="{ width: '450px' }" :header="'Xác nhận xoá nhiều'" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span>Bạn có chắc muốn xoá các phân công đã chọn?</span>
      </div>
      <template #footer>
        <Button :label="'Không'" icon="pi pi-times" text @click="deleteManyDialog = false" severity="secondary" variant="text" />
        <Button :label="'Xoá'" icon="pi pi-check" text @click="removeMany" severity="danger" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import { FilterMatchMode } from '@primevue/core/api'
import { useFormValidation } from '@/composables/useFormValidation'
import { usePermission } from '@/composables/usePermission'
import { EmployeeAssignmentService } from '@/services'
import { toYMD, formatDate } from '@/utils/dateHelper';

// Props từ controller
const props = defineProps({
  assignments: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  positions: { type: Array, default: () => [] },
  enums: { type: Object, default: () => ({}) },
})

const { errors, hasError, getError } = useFormValidation()
usePermission() // nếu cần chặn nút theo quyền, gọi hàm trong template

const dt = ref()
const rows = computed(() => props.assignments || [])
const selectedRows = ref([])
const dialogVisible = ref(false)
const deleteDialog = ref(false)
const deleteManyDialog = ref(false)
const currentRow = ref(null)

const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const submitted = ref(false)

// DataTable filters
const filters = ref({ 'global': { value: null, matchMode: FilterMatchMode.CONTAINS } })

// Quick filters
const departmentFilter = ref(null)
const roleFilter = ref(null)
const statusFilter = ref(null)

const employeesForSelect = computed(() => props.employees || [])
const departmentsForSelect = computed(() => props.departments || [])
const positionsForSelect = computed(() => props.positions || [])
const roleTypesForSelect = computed(() => props.enums?.role_types || [])
const statusesForSelect = computed(() => props.enums?.statuses || [])

function roleLabel(v) {
  const f = props.enums.role_types?.find(x => x.value === v)
  return f ? f.label : v
}
function statusLabel(v) {
  const f = props.enums.statuses?.find(x => x.value === v)
  return f ? f.label : v
}

// Form state
const form = ref({
  id: null,
  employee_id: null,
  department_id: null,
  position_id: null,
  is_primary: false,
  role_type: 'MEMBER',
  start_date: null,
  end_date: null,
  status: 'ACTIVE',
})
const isEditing = computed(() => !!form.value.id)

function openNew() {
  submitted.value = false
  form.value = {
    id: null,
    employee_id: null,
    department_id: null,
    position_id: null,
    is_primary: false,
    role_type: 'MEMBER',
    start_date: null,
    end_date: null,
    status: 'ACTIVE',
  }
  dialogVisible.value = true
}

function editRow(row) {
  submitted.value = false
  form.value = {
    id: row.id,
    employee_id: row.employee_id,
    department_id: row.department_id,
    position_id: row.position_id,
    is_primary: !!row.is_primary,
    role_type: row.role_type,
    start_date: row.start_date,
    end_date: row.end_date,
    status: row.status,
  }
  dialogVisible.value = true
}

function hideDialog() { dialogVisible.value = false }

function save() {
  submitted.value = true
  if (!form.value.employee_id || !form.value.department_id || !form.value.role_type || !form.value.status) return

  saving.value = true
  const payload = {
    ...form.value,
    start_date: toYMD(form.value.start_date),
    end_date: toYMD(form.value.end_date),
  }

  const hooks = {
    onStart: () => {},
    onFinish: () => { saving.value = false },
    onError: () => {},
    onSuccess: () => {
      dialogVisible.value = false
      EmployeeAssignmentService.index()
    }
  }

  if (!isEditing.value) {
    EmployeeAssignmentService.store(payload, hooks)
  } else {
    EmployeeAssignmentService.update(form.value.id, payload, hooks)
  }
}

function confirmDelete(row) {
  currentRow.value = row
  deleteDialog.value = true
}
function remove() {
  deleting.value = true
  EmployeeAssignmentService.destroy(currentRow.value.id, {
    onFinish: () => { deleting.value = false; deleteDialog.value = false },
    onSuccess: () => EmployeeAssignmentService.index(),
  })
}

function confirmDeleteSelected() { deleteManyDialog.value = true }
function removeMany() {
  const ids = selectedRows.value.map(x => x.id)
  if (!ids.length) return
  deleting.value = true
  EmployeeAssignmentService.bulkDelete(ids, {
    onFinish: () => { deleting.value = false; deleteManyDialog.value = false; selectedRows.value = [] },
    onSuccess: () => EmployeeAssignmentService.index(),
  })
}

function exportCSV() { dt.value?.exportCSV() }

// Quick filter (server GET để đồng bộ)
function applyDepartmentFilter() {
  EmployeeAssignmentService.index({ department_id: departmentFilter.value || undefined })
}
function applyQuickFilter() {
  // hiện tại DataTable filter text đã đủ; nếu muốn filter server-side cho role/status thì thêm query param tương tự
}
</script>
