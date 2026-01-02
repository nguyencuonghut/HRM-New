<template>
  <div>
    <Toolbar class="mb-4">
      <template #start>
        <Button label="Thêm đánh giá" icon="pi pi-plus" class="mr-2" @click="openNew" />
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
      :value="filteredReviews"
      v-model:selection="selected"
      dataKey="id"
      :paginator="true"
      :rows="10"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[5,10,25]"
      currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} đánh giá"
    >
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <h4 class="m-0">Đánh giá cuối năm</h4>
          <IconField>
            <InputIcon><i class="pi pi-search" /></InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
          </IconField>
        </div>
      </template>

      <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
      <Column field="year" header="Năm" sortable headerStyle="width:8rem;"></Column>
      <Column field="kpi_avg_score" header="Điểm KPI TB" sortable headerStyle="width:10rem;">
        <template #body="slotProps">
          <Tag :value="slotProps.data.kpi_avg_score" severity="info" />
        </template>
      </Column>
      <Column field="final_rating" header="Xếp loại" sortable headerStyle="width:10rem;">
        <template #body="slotProps">
          <Tag :value="slotProps.data.rating_label" :severity="getRatingSeverity(slotProps.data.final_rating)" />
        </template>
      </Column>
      <Column field="note" header="Nhận xét" headerStyle="min-width:15rem;">
        <template #body="slotProps">
          <div class="line-clamp-2">{{ slotProps.data.note || '-' }}</div>
        </template>
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
      :header="isEditing ? 'Sửa đánh giá cuối năm' : 'Thêm đánh giá cuối năm'"
      :modal="true"
      :style="{ width: '600px' }"
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
              @input="onYearChange"
            />
            <small v-if="submitted && !formData.year" class="p-error">Năm là bắt buộc</small>
          </div>
          <div>
            <label for="kpi_avg_score" class="block font-bold mb-2">
              Điểm KPI TB <span class="text-red-500">*</span>
              <Button
                v-if="formData.year"
                icon="pi pi-calculator"
                size="small"
                text
                rounded
                @click="calculateKpiAverage"
                :loading="calculating"
                v-tooltip.top="'Tính tự động từ KPI tháng'"
              />
            </label>
            <InputNumber
              id="kpi_avg_score"
              v-model="formData.kpi_avg_score"
              :min="0"
              :max="100"
              :minFractionDigits="2"
              :maxFractionDigits="2"
              :invalid="submitted && formData.kpi_avg_score === null"
              fluid
            />
            <small v-if="submitted && formData.kpi_avg_score === null" class="p-error">Điểm KPI TB là bắt buộc</small>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="final_rating" class="block font-bold mb-2">Xếp loại <span class="text-red-500">*</span></label>
            <Select
              id="final_rating"
              v-model="formData.final_rating"
              :options="ratingOptions"
              optionLabel="label"
              optionValue="value"
              :invalid="submitted && !formData.final_rating"
              fluid
            />
            <small v-if="submitted && !formData.final_rating" class="p-error">Xếp loại là bắt buộc</small>
          </div>
          <div>
            <label for="final_score" class="block font-bold mb-2">Điểm tổng</label>
            <InputNumber
              id="final_score"
              v-model="formData.final_score"
              :min="0"
              :minFractionDigits="2"
              :maxFractionDigits="2"
              fluid
            />
          </div>
        </div>
        <div>
          <label for="note" class="block font-bold mb-2">Nhận xét đánh giá</label>
          <Textarea id="note" v-model="formData.note" rows="4" fluid />
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
        <span>Bạn có chắc chắn muốn xóa đánh giá này?</span>
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
        <span>Bạn có chắc chắn muốn xóa các đánh giá đã chọn?</span>
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
import axios from 'axios'
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
  reviews: { type: Array, default: () => [] }
})

const dt = ref()
const selected = ref([])
const formDialog = ref(false)
const deleteDialog = ref(false)
const deleteMultiDialog = ref(false)
const submitted = ref(false)
const saving = ref(false)
const deleting = ref(false)
const calculating = ref(false)

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const ratingOptions = [
  { value: 'XUAT_SAC', label: 'Xuất sắc' },
  { value: 'TOT', label: 'Tốt' },
  { value: 'DAT', label: 'Đạt' },
  { value: 'CAN_CAI_THIEN', label: 'Cần cải thiện' }
]

const formData = ref({
  id: null,
  year: new Date().getFullYear(),
  kpi_avg_score: null,
  final_rating: null,
  final_score: null,
  note: ''
})

const isEditing = computed(() => !!formData.value.id)

const filteredReviews = computed(() => {
  let result = [...props.reviews]

  // Filter by global search (year, rating, score, note)
  if (filters.value.global?.value) {
    const search = filters.value.global.value.toLowerCase().trim()
    result = result.filter(r => {
      return (
        r.year?.toString().includes(search) ||
        r.final_rating?.toLowerCase().includes(search) ||
        r.rating_label?.toLowerCase().includes(search) ||
        r.kpi_avg_score?.toString().includes(search) ||
        r.final_score?.toString().includes(search) ||
        r.note?.toLowerCase().includes(search)
      )
    })
  }

  return result
})

function getRatingSeverity(rating) {
  const severityMap = {
    'A': 'success',
    'B': 'info',
    'C': 'warn',
    'D': 'danger'
  }
  return severityMap[rating] || 'secondary'
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
    kpi_avg_score: null,
    final_rating: null,
    final_score: null,
    note: ''
  }
  formDialog.value = true
}

function openEdit(record) {
  submitted.value = false
  formData.value = { ...record }
  formDialog.value = true
}

async function calculateKpiAverage() {
  if (!formData.value.year) return

  calculating.value = true
  try {
    const response = await axios.get(`/employee-annual-reviews/calculate-kpi/${props.employeeId}/${formData.value.year}`)
    formData.value.kpi_avg_score = response.data.kpi_avg_score

    if (response.data.months_count === 0) {
      alert('Không có dữ liệu KPI tháng cho năm ' + formData.value.year)
    }
  } catch (error) {
    console.error('Error calculating KPI:', error)
  } finally {
    calculating.value = false
  }
}

function onYearChange() {
  // Auto calculate when year changes if needed
}

function save() {
  submitted.value = true
  if (!formData.value.year || formData.value.kpi_avg_score === null || !formData.value.final_rating) return

  saving.value = true
  const payload = {
    employee_id: props.employeeId,
    ...formData.value
  }

  const url = isEditing.value
    ? `/employee-annual-reviews/${formData.value.id}`
    : '/employee-annual-reviews'
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
  router.delete(`/employee-annual-reviews/${formData.value.id}`, {
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
  router.delete('/employee-annual-reviews/bulk-delete', {
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

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
