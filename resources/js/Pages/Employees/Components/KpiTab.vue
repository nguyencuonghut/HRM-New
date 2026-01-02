<template>
  <div>
    <Toolbar class="mb-4">
      <template #start>
        <Button label="Thêm KPI tháng" icon="pi pi-plus" class="mr-2" @click="openNew" />
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
      :value="filteredKpis"
      v-model:selection="selected"
      dataKey="id"
      :paginator="true"
      :rows="12"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[12,24,36]"
      currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} KPI"
    >
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <h4 class="m-0">Lịch sử KPI tháng</h4>
          <div class="flex gap-2">
            <Select
              v-model="filterYear"
              :options="availableYears"
              placeholder="Lọc theo năm"
              showClear
            />
            <IconField>
              <InputIcon><i class="pi pi-search" /></InputIcon>
              <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
            </IconField>
          </div>
        </div>
      </template>

      <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
      <Column field="year" header="Năm" sortable headerStyle="width:8rem;"></Column>
      <Column field="month" header="Tháng" sortable headerStyle="width:8rem;">
        <template #body="slotProps">Tháng {{ slotProps.data.month }}</template>
      </Column>
      <Column field="kpi_score" header="Điểm KPI" sortable headerStyle="width:10rem;">
        <template #body="slotProps">
          <Tag :value="slotProps.data.kpi_score" :severity="getScoreSeverity(slotProps.data.kpi_score)" />
        </template>
      </Column>
      <Column field="note" header="Ghi chú" headerStyle="min-width:15rem;">
        <template #body="slotProps">{{ slotProps.data.note || '-' }}</template>
      </Column>
      <Column field="input_at" header="Ngày nhập" sortable headerStyle="min-width:12rem;">
        <template #body="slotProps">{{ formatDateTime(slotProps.data.input_at) }}</template>
      </Column>
      <Column headerStyle="min-width:10rem;">
        <template #body="slotProps">
          <Button
            icon="pi pi-pencil"
            class="mr-2"
            outlined
            severity="success"
            rounded
            @click="openEdit(slotProps.data)"
          />
          <Button
            icon="pi pi-trash"
            class="mt-2"
            outlined
            severity="danger"
            rounded
            @click="confirmDelete(slotProps.data)"
          />
        </template>
      </Column>
    </DataTable>

    <!-- Dialog Form -->
    <Dialog
      v-model:visible="formDialog"
      :header="isEditing ? 'Sửa KPI tháng' : 'Thêm KPI tháng'"
      :modal="true"
      :style="{ width: '500px' }"
    >
      <div class="flex flex-col gap-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="year" class="block font-bold mb-2">Năm <span class="text-red-500">*</span></label>
            <InputNumber
              id="year"
              v-model="formData.year"
              :min="2000"
              :max="2100"
              :useGrouping="false"
              :invalid="submitted && !formData.year"
              fluid
            />
            <small v-if="submitted && !formData.year" class="p-error">Năm là bắt buộc</small>
          </div>
          <div>
            <label for="month" class="block font-bold mb-2">Tháng <span class="text-red-500">*</span></label>
            <Select
              id="month"
              v-model="formData.month"
              :options="monthOptions"
              optionLabel="label"
              optionValue="value"
              :invalid="submitted && !formData.month"
              fluid
            />
            <small v-if="submitted && !formData.month" class="p-error">Tháng là bắt buộc</small>
          </div>
        </div>
        <div>
          <label for="kpi_score" class="block font-bold mb-2">Điểm KPI <span class="text-red-500">*</span></label>
          <InputNumber
            id="kpi_score"
            v-model="formData.kpi_score"
            :min="0"
            :minFractionDigits="2"
            :maxFractionDigits="2"
            :invalid="submitted && formData.kpi_score === null"
            fluid
          />
          <small v-if="submitted && formData.kpi_score === null" class="p-error">Điểm KPI là bắt buộc</small>
        </div>
        <div>
          <label for="note" class="block font-bold mb-2">Ghi chú</label>
          <Textarea id="note" v-model="formData.note" rows="3" fluid />
        </div>
      </div>

      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="formDialog = false" />
        <Button label="Lưu" icon="pi pi-check" @click="save" :loading="saving" />
      </template>
    </Dialog>

    <!-- Delete Dialog -->
    <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận xóa" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span>Bạn có chắc chắn muốn xóa KPI này?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteDialog = false" />
        <Button label="Có" icon="pi pi-check" @click="deleteRecord" severity="danger" :loading="deleting" />
      </template>
    </Dialog>

    <!-- Delete Multiple Dialog -->
    <Dialog v-model:visible="deleteMultiDialog" :style="{ width: '450px' }" header="Xác nhận xóa" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span>Bạn có chắc chắn muốn xóa các KPI đã chọn?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteMultiDialog = false" />
        <Button label="Có" icon="pi pi-check" @click="deleteMultiple" severity="danger" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { FilterMatchMode } from '@primevue/core/api'
import Button from 'primevue/button'
import Toolbar from 'primevue/toolbar'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'

const props = defineProps({
  employeeId: { type: String, required: true },
  kpis: { type: Array, default: () => [] }
})

const dt = ref()
const selected = ref([])
const formDialog = ref(false)
const deleteDialog = ref(false)
const deleteMultiDialog = ref(false)
const submitted = ref(false)
const saving = ref(false)
const deleting = ref(false)
const filterYear = ref(null)

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const monthOptions = Array.from({ length: 12 }, (_, i) => ({
  value: i + 1,
  label: `Tháng ${i + 1}`
}))

const availableYears = computed(() => {
  const years = [...new Set(props.kpis.map(k => k.year))].sort((a, b) => b - a)
  return years.length ? years : [new Date().getFullYear()]
})

const formData = ref({
  id: null,
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1,
  kpi_score: null,
  note: ''
})

const isEditing = computed(() => !!formData.value.id)

const filteredKpis = computed(() => {
  let result = [...props.kpis]

  // Filter by year
  if (filterYear.value) {
    result = result.filter(k => k.year === filterYear.value)
  }

  // Filter by global search (year, month, score, note)
  if (filters.value.global?.value) {
    const search = filters.value.global.value.toLowerCase().trim()
    result = result.filter(k => {
      const monthText = `tháng ${k.month}`.toLowerCase()
      const searchNoSpace = search.replace(/\s+/g, '')
      const monthTextNoSpace = monthText.replace(/\s+/g, '')

      return (
        k.year?.toString().includes(search) ||
        k.month?.toString().includes(search) ||
        monthText.includes(search) ||
        monthTextNoSpace.includes(searchNoSpace) ||
        k.kpi_score?.toString().includes(search) ||
        k.note?.toLowerCase().includes(search)
      )
    })
  }

  return result
})

function getScoreSeverity(score) {
  if (score >= 90) return 'success'
  if (score >= 70) return 'info'
  if (score >= 50) return 'warn'
  return 'danger'
}

function formatDateTime(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('vi-VN')
}

function openNew() {
  submitted.value = false
  formData.value = {
    id: null,
    year: new Date().getFullYear(),
    month: new Date().getMonth() + 1,
    kpi_score: null,
    note: ''
  }
  formDialog.value = true
}

function openEdit(record) {
  submitted.value = false
  formData.value = { ...record }
  formDialog.value = true
}

function save() {
  submitted.value = true
  if (!formData.value.year || !formData.value.month || formData.value.kpi_score === null) return

  saving.value = true
  const payload = {
    employee_id: props.employeeId,
    ...formData.value
  }

  const url = isEditing.value
    ? `/employee-kpi-months/${formData.value.id}`
    : '/employee-kpi-months'
  const method = isEditing.value ? 'put' : 'post'

  router[method](url, payload, {
    onSuccess: () => {
      formDialog.value = false
      saving.value = false
    },
    onError: () => {
      saving.value = false
    }
  })
}

function confirmDelete(record) {
  formData.value = { ...record }
  deleteDialog.value = true
}

function deleteRecord() {
  deleting.value = true
  router.delete(`/employee-kpi-months/${formData.value.id}`, {
    onSuccess: () => {
      deleteDialog.value = false
      deleting.value = false
    },
    onError: () => {
      deleting.value = false
    }
  })
}

function confirmDeleteSelected() {
  deleteMultiDialog.value = true
}

function deleteMultiple() {
  deleting.value = true
  const ids = selected.value.map(x => x.id)
  router.delete('/employee-kpi-months/bulk-delete', {
    data: { ids },
    onSuccess: () => {
      deleteMultiDialog.value = false
      selected.value = []
      deleting.value = false
    },
    onError: () => {
      deleting.value = false
    }
  })
}

function exportCSV() {
  dt.value?.exportCSV()
}
</script>
