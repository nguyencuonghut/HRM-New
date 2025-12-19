<template>
  <div>
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <Card>
        <template #content>
          <div class="flex justify-between items-start">
            <div>
              <div class="text-500 font-medium mb-1">Tổng thưởng {{ stats.current_year }}</div>
              <div class="text-2xl font-bold text-green-600">
                {{ formatCurrency(stats.total_rewards_amount) }}
              </div>
              <div class="text-sm mt-1">{{ stats.total_rewards_count }} lần</div>
            </div>
            <div class="bg-green-100 rounded-full p-3">
              <i class="pi pi-star-fill text-green-600 text-2xl"></i>
            </div>
          </div>
          <div v-if="stats.rewards_change_percent !== null" class="mt-2 text-sm">
            <span :class="stats.rewards_change_percent >= 0 ? 'text-green-600' : 'text-red-600'">
              <i :class="stats.rewards_change_percent >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'"></i>
              {{ Math.abs(stats.rewards_change_percent) }}% so với {{ stats.current_year - 1 }}
            </span>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex justify-between items-start">
            <div>
              <div class="text-500 font-medium mb-1">Kỷ luật {{ stats.current_year }}</div>
              <div class="text-2xl font-bold text-red-600">
                {{ stats.total_disciplines_count }} lần
              </div>
              <div v-if="stats.total_disciplines_amount > 0" class="text-sm mt-1">
                Phạt: {{ formatCurrency(stats.total_disciplines_amount) }}
              </div>
            </div>
            <div class="bg-red-100 rounded-full p-3">
              <i class="pi pi-exclamation-triangle text-red-600 text-2xl"></i>
            </div>
          </div>
          <div v-if="stats.disciplines_change_percent !== null" class="mt-2 text-sm">
            <span :class="stats.disciplines_change_percent <= 0 ? 'text-green-600' : 'text-red-600'">
              <i :class="stats.disciplines_change_percent <= 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up'"></i>
              {{ Math.abs(stats.disciplines_change_percent) }}% so với {{ stats.current_year - 1 }}
            </span>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex justify-between items-start">
            <div>
              <div class="text-500 font-medium mb-1">Khen thưởng gần nhất</div>
              <div v-if="stats.latest_reward" class="text-lg font-semibold">
                {{ stats.latest_reward.category_label }}
              </div>
              <div v-else class="text-lg text-500">(Chưa có)</div>
              <div v-if="stats.latest_reward" class="text-sm mt-1 text-500">
                {{ formatDate(stats.latest_reward.effective_date) }}
              </div>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
              <i class="pi pi-trophy text-blue-600 text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex justify-between items-start">
            <div>
              <div class="text-500 font-medium mb-1">Kỷ luật gần nhất</div>
              <div v-if="stats.latest_discipline" class="text-lg font-semibold text-red-600">
                {{ stats.latest_discipline.category_label }}
              </div>
              <div v-else class="text-lg text-500">(Không có)</div>
              <div v-if="stats.latest_discipline" class="text-sm mt-1 text-500">
                {{ formatDate(stats.latest_discipline.effective_date) }}
              </div>
            </div>
            <div class="bg-red-100 rounded-full p-3">
              <i class="pi pi-ban text-red-600 text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Toolbar -->
    <Toolbar class="mb-4">
      <template #start>
        <Button label="Thêm khen thưởng" icon="pi pi-plus" class="mr-2" @click="openNewReward" severity="success" />
        <Button label="Thêm kỷ luật" icon="pi pi-plus" @click="openNewDiscipline" severity="danger" />
      </template>
      <template #end>
        <Button label="Xuất dữ liệu" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
      </template>
    </Toolbar>

    <!-- DataTable -->
    <DataTable
      ref="dt"
      :value="props.records"
      dataKey="id"
      :paginator="true"
      :rows="10"
      :filters="filters"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[5,10,25]"
      currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} bản ghi"
    >
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <h4 class="m-0">Danh sách Khen thưởng & Kỷ luật</h4>
          <IconField>
            <InputIcon><i class="pi pi-search" /></InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
          </IconField>
        </div>
      </template>

      <Column field="type" header="Loại" headerStyle="min-width:10rem;">
        <template #body="slotProps">
          <Tag :value="slotProps.data.type_label" :severity="slotProps.data.type_severity" :icon="slotProps.data.type_icon" />
        </template>
      </Column>
      <Column field="category_label" header="Hình thức" sortable headerStyle="min-width:14rem;"></Column>
      <Column field="decision_no" header="Số QĐ" sortable headerStyle="min-width:12rem;"></Column>
      <Column field="decision_date" header="Ngày QĐ" sortable headerStyle="min-width:10rem;"></Column>
      <Column field="effective_date" header="Ngày hiệu lực" sortable headerStyle="min-width:10rem;"></Column>
      <Column field="amount" header="Số tiền" headerStyle="min-width:12rem;">
        <template #body="slotProps">
          <span v-if="slotProps.data.amount" :class="slotProps.data.type === 'REWARD' ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'">
            {{ slotProps.data.formatted_amount }}
          </span>
          <span v-else class="text-500">-</span>
        </template>
      </Column>
      <Column field="issued_by_name" header="Người ký" headerStyle="min-width:14rem;"></Column>
      <Column field="status" header="Trạng thái" headerStyle="min-width:10rem;">
        <template #body="slotProps">
          <Tag :value="slotProps.data.status_label" :severity="slotProps.data.status_severity" />
        </template>
      </Column>
      <Column headerStyle="min-width:10rem;">
        <template #body="slotProps">
          <Button
            icon="pi pi-eye"
            class="mr-2"
            outlined
            severity="info"
            rounded
            @click="openView(slotProps.data)"
          />
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
            outlined
            severity="danger"
            rounded
            @click="confirmDelete(slotProps.data)"
          />
        </template>
      </Column>
    </DataTable>

    <!-- Dialog Form -->
    <Dialog v-model:visible="dialog" :style="{ width: '800px' }" :header="dialogTitle" :modal="true">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-bold mb-2 required-field">Loại</label>
          <Select
            v-model="form.type"
            :options="typeOptions"
            optionLabel="label"
            optionValue="value"
            @change="onTypeChange"
            fluid
            :disabled="viewMode"
          />
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Hình thức</label>
          <Select
            v-model="form.category"
            :options="categoryOptionsLocal"
            optionLabel="label"
            optionValue="value"
            filter
            fluid
            :disabled="viewMode"
          />
          <small v-if="submitted && !form.category" class="p-error block mt-1">Hình thức là bắt buộc</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Số quyết định</label>
          <InputText v-model="form.decision_no" fluid :disabled="viewMode" />
          <small v-if="submitted && !form.decision_no" class="p-error block mt-1">Số quyết định là bắt buộc</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Ngày ra quyết định</label>
          <DatePicker v-model="form.decision_date" dateFormat="yy-mm-dd" showIcon fluid :disabled="viewMode" />
          <small v-if="submitted && !form.decision_date" class="p-error block mt-1">Ngày ra QĐ là bắt buộc</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Ngày có hiệu lực</label>
          <DatePicker v-model="form.effective_date" dateFormat="yy-mm-dd" showIcon fluid :disabled="viewMode" />
          <small v-if="submitted && !form.effective_date" class="p-error block mt-1">Ngày hiệu lực là bắt buộc</small>
        </div>
        <div>
          <label class="block font-bold mb-2" :class="{ 'required-field': requiresAmount }">Số tiền (VND)</label>
          <InputNumber v-model="form.amount" mode="decimal" :minFractionDigits="0" :maxFractionDigits="0" fluid :disabled="viewMode" />
          <small v-if="submitted && requiresAmount && !form.amount" class="p-error block mt-1">Số tiền là bắt buộc</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Người ký quyết định</label>
          <Select
            v-model="form.issued_by"
            :options="props.headDeputyEmployees"
            optionLabel="label"
            optionValue="value"
            filter
            fluid
            :disabled="viewMode"
            placeholder="Chọn người ký (Trưởng/Phó)"
          />
          <small v-if="submitted && !form.issued_by" class="p-error block mt-1">Người ký là bắt buộc</small>
        </div>
        <div>
          <label class="block font-bold mb-2">Trạng thái</label>
          <Select
            v-model="form.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            fluid
            :disabled="viewMode"
          />
        </div>
        <div class="md:col-span-2">
          <label class="block font-bold mb-2 required-field">Mô tả chi tiết</label>
          <Textarea v-model="form.description" rows="4" fluid :disabled="viewMode" />
          <small v-if="submitted && !form.description" class="p-error block mt-1">Mô tả là bắt buộc (tối thiểu 10 ký tự)</small>
        </div>
        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Ghi chú</label>
          <Textarea v-model="form.note" rows="2" fluid :disabled="viewMode" />
        </div>
      </div>

      <template #footer>
        <Button v-if="!viewMode" label="Hủy" icon="pi pi-times" text @click="dialog = false" />
        <Button v-if="viewMode" label="Đóng" icon="pi pi-times" @click="dialog = false" />
        <Button v-if="!viewMode" label="Lưu" icon="pi pi-check" @click="save" :loading="saving" />
      </template>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận xóa" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span v-if="current">Bạn chắc chắn muốn xóa bản ghi <b>{{ current.decision_no }}</b>?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteDialog = false" />
        <Button label="Có" icon="pi pi-check" @click="doDelete" severity="danger" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { RewardDisciplineService } from '@/services'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Card from 'primevue/card'
import { formatDate } from '@/utils/dateHelper'

const props = defineProps({
  employee: { type: Object, required: true },
  records: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
  categoryOptions: { type: Object, default: () => ({ rewards: [], disciplines: [] }) },
  headDeputyEmployees: { type: Array, default: () => [] },
})

const dt = ref()
const categoryOptionsLocal = ref([])

const dialog = ref(false)
const deleteDialog = ref(false)
const submitted = ref(false)
const saving = ref(false)
const deleting = ref(false)
const current = ref(null)
const viewMode = ref(false)

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
})

const typeOptions = [
  { label: 'Khen thưởng', value: 'REWARD' },
  { label: 'Kỷ luật', value: 'DISCIPLINE' },
]

const statusOptions = [
  { label: 'Nháp', value: 'DRAFT' },
  { label: 'Đã lưu', value: 'ACTIVE' },
]

const form = ref({
  id: null,
  type: 'REWARD',
  category: null,
  decision_no: '',
  decision_date: null,
  effective_date: null,
  amount: null,
  description: '',
  note: '',
  issued_by: null,
  status: 'ACTIVE',
})

const dialogTitle = computed(() => {
  if (viewMode.value) return 'Chi tiết'
  return form.value.id ? 'Sửa thông tin' : 'Thêm mới'
})

const requiresAmount = computed(() => {
  return ['BONUS', 'SALARY_DEDUCTION'].includes(form.value.category)
})

function onTypeChange() {
  form.value.category = null
  updateCategoryOptions()
}

function updateCategoryOptions() {
  categoryOptionsLocal.value = form.value.type === 'REWARD'
    ? props.categoryOptions.rewards
    : props.categoryOptions.disciplines
}

function openNewReward() {
  resetForm()
  form.value.type = 'REWARD'
  updateCategoryOptions()
  viewMode.value = false
  dialog.value = true
}

function openNewDiscipline() {
  resetForm()
  form.value.type = 'DISCIPLINE'
  updateCategoryOptions()
  viewMode.value = false
  dialog.value = true
}

function openEdit(data) {
  form.value = {
    id: data.id,
    type: data.type,
    category: data.category,
    decision_no: data.decision_no,
    decision_date: new Date(data.decision_date_raw),
    effective_date: new Date(data.effective_date_raw),
    amount: data.amount,
    description: data.description,
    note: data.note,
    issued_by: data.issued_by,
    status: data.status,
  }
  updateCategoryOptions()
  viewMode.value = false
  submitted.value = false
  dialog.value = true
}

function openView(data) {
  openEdit(data)
  viewMode.value = true
}

function resetForm() {
  form.value = {
    id: null,
    type: 'REWARD',
    category: null,
    decision_no: '',
    decision_date: null,
    effective_date: null,
    amount: null,
    description: '',
    note: '',
    issued_by: null,
    status: 'ACTIVE',
  }
  submitted.value = false
}

function save() {
  submitted.value = true

  if (!form.value.category || !form.value.decision_no || !form.value.decision_date ||
      !form.value.effective_date || !form.value.description || !form.value.issued_by) {
    return
  }

  if (requiresAmount.value && !form.value.amount) {
    return
  }

  saving.value = true

  const payload = {
    ...form.value,
    decision_date: form.value.decision_date.toISOString().split('T')[0],
    effective_date: form.value.effective_date.toISOString().split('T')[0],
  }

  const opts = {
    onFinish: () => saving.value = false,
    onSuccess: () => {
      dialog.value = false
      submitted.value = false
    }
  }

  if (form.value.id) {
    RewardDisciplineService.update(props.employee.id, form.value.id, payload, opts)
  } else {
    RewardDisciplineService.store(props.employee.id, payload, opts)
  }
}

function confirmDelete(data) {
  current.value = data
  deleteDialog.value = true
}

function doDelete() {
  deleting.value = true
  RewardDisciplineService.destroy(props.employee.id, current.value.id, {
    onFinish: () => {
      deleting.value = false
      deleteDialog.value = false
    }
  })
}

function exportCSV() {
  dt.value.exportCSV()
}

function formatCurrency(value) {
  if (!value) return '0 VND'
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}
</script>

<style scoped>
.required-field::after {
  content: ' *';
  color: red;
}
</style>
