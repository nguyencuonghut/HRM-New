<template>
  <div>
    <Toolbar class="mb-4">
      <template #start>
        <Button label="Thêm phân công" icon="pi pi-plus" class="mr-2" @click="openNew" />
        <Button
          label="Xóa"
          icon="pi pi-trash"
          severity="danger"
          variant="outlined"
          @click="confirmDeleteSelected"
          :disabled="!selected || !selected.length"
        />
      </template>
      <template #end>
        <Button label="Xuất dữ liệu" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
      </template>
    </Toolbar>

    <DataTable
      ref="dt"
      :value="assignments"
      v-model:selection="selected"
      dataKey="id"
      :paginator="true"
      :rows="10"
      :filters="filters"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[5,10,25]"
      currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} phân công"
      :rowClass="rowClass"
    >
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <h4 class="m-0">Danh sách Phân công</h4>
          <IconField>
            <InputIcon><i class="pi pi-search" /></InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
          </IconField>
        </div>
      </template>

      <Column selectionMode="multiple" style="width:3rem" :exportable="false" />
      <Column field="department.name" header="Phòng/Ban" sortable style="min-width:14rem" />
      <Column field="position.title" header="Chức danh" sortable style="min-width:12rem">
        <template #body="slotProps">{{ slotProps.data.position?.title || '-' }}</template>
      </Column>
      <Column header="Vai trò" style="min-width:10rem">
        <template #body="slotProps">
          <Tag :value="getRoleLabel(slotProps.data.role_type)" />
        </template>
      </Column>
      <Column header="Loại" style="min-width:8rem">
        <template #body="slotProps">
          <Badge
            :value="slotProps.data.is_primary ? 'CHÍNH' : 'Phụ'"
            :severity="slotProps.data.is_primary ? 'success' : 'secondary'"
          />
        </template>
      </Column>
      <Column header="Hiệu lực" style="min-width:12rem">
        <template #body="slotProps">
          {{ formatDate(slotProps.data.start_date) }}
          <span v-if="slotProps.data.end_date"> → {{ formatDate(slotProps.data.end_date) }}</span>
        </template>
      </Column>
      <Column header="Trạng thái" style="min-width:10rem">
        <template #body="slotProps">
          <Badge
            :value="slotProps.data.status==='ACTIVE' ? 'Hoạt động' : 'Không hoạt động'"
            :severity="slotProps.data.status==='ACTIVE' ? 'success' : 'danger'"
          />
        </template>
      </Column>
      <Column header="Thao tác" :exportable="false" style="min-width:10rem">
        <template #body="slotProps">
          <div class="flex gap-2">
            <Button
              icon="pi pi-pencil"
              variant="outlined"
              rounded
              @click="openEdit(slotProps.data)"
            />
            <Button
              icon="pi pi-trash"
              variant="outlined"
              rounded
              severity="danger"
              @click="confirmDelete(slotProps.data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <!-- Dialog Add/Edit Assignment -->
    <Dialog
      v-model:visible="dialog"
      :style="{ width: '520px' }"
      :header="form.id ? 'Cập nhật phân công' : 'Thêm phân công'"
      :modal="true"
    >
      <div class="flex flex-col gap-6">
        <div>
          <label class="block font-bold mb-3 required-field">Phòng/Ban</label>
          <Select
            v-model="form.department_id"
            :options="departments"
            optionLabel="name"
            filter
            optionValue="id"
            fluid
            :invalid="submitted && !form.department_id"
          />
          <small v-if="submitted && !form.department_id" class="text-red-500">Bắt buộc</small>
        </div>

        <div>
          <label class="block font-bold mb-3">Chức danh</label>
          <Select
            v-model="form.position_id"
            :options="filteredPositions"
            optionLabel="title"
            filter
            optionValue="id"
            fluid
            showClear
            :placeholder="form.department_id ? 'Chọn chức danh' : 'Vui lòng chọn phòng/ban trước'"
            :disabled="!form.department_id"
          />
          <small v-if="!form.department_id" class="text-gray-500 block mt-1">Chọn phòng/ban để hiển thị chức danh</small>
          <small v-else-if="filteredPositions.length === 0" class="text-orange-500 block mt-1">Phòng/ban này chưa có chức danh nào</small>
          <small v-else class="text-gray-500 block mt-1">{{ filteredPositions.length }} chức danh khả dụng</small>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block font-bold mb-3 required-field">Vai trò</label>
            <Select
              v-model="form.role_type"
              :options="roleTypeOptions"
              optionLabel="label"
              optionValue="value"
              fluid
              :invalid="submitted && !form.role_type"
            />
            <small v-if="submitted && !form.role_type" class="text-red-500">Bắt buộc</small>
          </div>

          <div class="flex items-center gap-2 mt-7">
            <Checkbox v-model="form.is_primary" :binary="true" inputId="is_primary" />
            <label for="is_primary" class="font-bold">Phân công CHÍNH</label>
          </div>
        </div>

        <!-- Warning về primary assignment -->
        <Message v-if="showPrimaryWarning" severity="warn" :closable="false">
          Nhân viên đã có phân công CHÍNH đang HOẠT ĐỘNG. Nếu bạn tạo phân công CHÍNH mới, phân công cũ sẽ tự động chuyển sang KHÔNG HOẠT ĐỘNG hoặc bỏ cờ CHÍNH.
        </Message>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block font-bold mb-3">Ngày bắt đầu</label>
            <DatePicker v-model="form.start_date" dateFormat="yy-mm-dd" showIcon fluid />
          </div>
          <div>
            <label class="block font-bold mb-3">Ngày kết thúc</label>
            <DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" showIcon fluid />
          </div>
        </div>

        <div>
          <label class="block font-bold mb-3 required-field">Trạng thái</label>
          <Select
            v-model="form.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            fluid
            :invalid="submitted && !form.status"
          />
          <small v-if="submitted && !form.status" class="text-red-500">Bắt buộc</small>
        </div>
      </div>

      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="dialog=false" />
        <Button label="Lưu" icon="pi pi-check" @click="save" :loading="saving" />
      </template>
    </Dialog>

    <!-- Dialog Delete Assignment -->
    <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span>Bạn có chắc muốn xóa phân công này?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="remove" :loading="deleting" />
      </template>
    </Dialog>

    <!-- Dialog Delete Many Assignments -->
    <Dialog v-model:visible="deleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span>Bạn có chắc muốn xóa các phân công đã chọn?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteManyDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="removeMany" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { EmployeeAssignmentService } from '@/services'
import { toYMD, formatDate } from '@/utils/dateHelper'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Checkbox from 'primevue/checkbox'
import Message from 'primevue/message'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'

const props = defineProps({
  employeeId: { type: String, required: true },
  assignments: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  positions: { type: Array, default: () => [] },
})

const dt = ref()
const selected = ref([])
const filters = ref({ global: { value: null, matchMode: 'contains' } })

const dialog = ref(false)
const deleteDialog = ref(false)
const deleteManyDialog = ref(false)
const currentItem = ref(null)
const saving = ref(false)
const deleting = ref(false)
const submitted = ref(false)

const roleTypeOptions = [
  { label: 'Trưởng phòng', value: 'HEAD' },
  { label: 'Phó phòng', value: 'DEPUTY' },
  { label: 'Nhân viên', value: 'MEMBER' }
]

const statusOptions = [
  { label: 'Hoạt động', value: 'ACTIVE' },
  { label: 'Không hoạt động', value: 'INACTIVE' }
]

const form = ref({
  id: null,
  department_id: null,
  position_id: null,
  is_primary: false,
  role_type: 'MEMBER',
  start_date: null,
  end_date: null,
  status: 'ACTIVE'
})

// Filter positions theo department đã chọn
const filteredPositions = computed(() => {
  if (!form.value.department_id) return props.positions || []
  return (props.positions || []).filter(p => p.department_id === form.value.department_id)
})

// Watch department change để reset position nếu không thuộc department mới
watch(() => form.value.department_id, (newDeptId, oldDeptId) => {
  if (newDeptId !== oldDeptId && form.value.position_id) {
    const positionStillValid = filteredPositions.value.some(p => p.id === form.value.position_id)
    if (!positionStillValid) {
      form.value.position_id = null
    }
  }
})

// Kiểm tra xem có primary assignment ACTIVE không
const hasPrimaryActive = computed(() => {
  return props.assignments.some(a => a.is_primary && a.status === 'ACTIVE')
})

// Hiển thị warning khi check primary
const showPrimaryWarning = computed(() => {
  if (form.value.id) {
    const current = props.assignments.find(a => a.id === form.value.id)
    if (current?.is_primary) return false
  }
  return form.value.is_primary && form.value.status === 'ACTIVE' && hasPrimaryActive.value
})

function getRoleLabel(roleType) {
  const found = roleTypeOptions.find(x => x.value === roleType)
  return found ? found.label : roleType
}

// Highlight primary assignment row
function rowClass(data) {
  return data.is_primary && data.status === 'ACTIVE' ? 'bg-green-50' : ''
}

function exportCSV() {
  dt.value?.exportCSV()
}

function resetForm() {
  form.value = {
    id: null,
    department_id: null,
    position_id: null,
    is_primary: false,
    role_type: 'MEMBER',
    start_date: null,
    end_date: null,
    status: 'ACTIVE'
  }
}

function openNew() {
  submitted.value = false
  resetForm()
  dialog.value = true
}

function openEdit(row) {
  submitted.value = false
  form.value = {
    id: row.id,
    department_id: row.department_id,
    position_id: row.position_id,
    is_primary: !!row.is_primary,
    role_type: row.role_type,
    start_date: row.start_date,
    end_date: row.end_date,
    status: row.status
  }
  dialog.value = true
}

function save() {
  submitted.value = true
  if (!form.value.department_id || !form.value.role_type || !form.value.status) return

  saving.value = true
  const payload = {
    ...form.value,
    employee_id: props.employeeId,
    start_date: toYMD(form.value.start_date),
    end_date: toYMD(form.value.end_date)
  }

  const opts = {
    onFinish: () => saving.value = false,
    onSuccess: () => { dialog.value = false }
  }
  if (!form.value.id) {
    EmployeeAssignmentService.storeForEmployee(props.employeeId, payload, opts)
  } else {
    EmployeeAssignmentService.updateForEmployee(props.employeeId, form.value.id, payload, opts)
  }
}

function confirmDelete(row) {
  currentItem.value = row
  deleteDialog.value = true
}

function remove() {
  deleting.value = true
  EmployeeAssignmentService.destroyForEmployee(props.employeeId, currentItem.value.id, {
    onFinish: () => {
      deleting.value = false
      deleteDialog.value = false
    }
  })
}

function confirmDeleteSelected() {
  deleteManyDialog.value = true
}

function removeMany() {
  const ids = selected.value.map(x => x.id)
  deleting.value = true
  EmployeeAssignmentService.bulkDeleteForEmployee(props.employeeId, ids, {
    onFinish: () => {
      deleting.value = false
      deleteManyDialog.value = false
      selected.value = []
    }
  })
}
</script>

<style scoped>
.required-field::after {
  content: ' *';
  color: #ef4444;
}
</style>
