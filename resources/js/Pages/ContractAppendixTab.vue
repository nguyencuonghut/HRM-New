<template>
  <div class="card">
    <Toolbar class="mb-4">
      <template #start>
        <Button label="Thêm phụ lục" icon="pi pi-plus" class="mr-2" @click="openNew" />
        <Button
          label="Xóa"
          icon="pi pi-trash"
          severity="danger"
          variant="outlined"
          :disabled="!selected?.length"
          @click="confirmDeleteSelected"
        />
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
      :rowsPerPageOptions="[5,10,25]"
      currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} phụ lục"
    >
      <template #header>
        <div class="flex items-center justify-between gap-2">
          <h4 class="m-0">Danh sách Phụ lục</h4>
          <IconField>
            <InputIcon><i class="pi pi-search" /></InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
          </IconField>
        </div>
      </template>

      <Column selectionMode="multiple" headerStyle="width:3rem" />
      <Column field="appendix_no" header="Số PL" sortable headerStyle="min-width:10rem;" />
      <Column field="appendix_type_label" header="Loại" sortable headerStyle="min-width:14rem;">
        <template #body="sp">
          {{ sp.data.appendix_type_label }}
        </template>
      </Column>
      <Column field="effective_date" header="Hiệu lực" headerStyle="min-width:10rem;">
        <template #body="sp">
          {{ formatDate(sp.data.effective_date) }}
        </template>
      </Column>
      <Column field="status_label" header="Trạng thái" headerStyle="min-width:10rem;">
        <template #body="sp">
          <Tag :value="sp.data.status_label" :severity="statusSeverity(sp.data.status)" />
        </template>
      </Column>
      <Column header="Tệp sinh ra" headerStyle="min-width:12rem;">
        <template #body="sp">
          <a
            v-if="sp.data.generated_pdf_url"
            :href="sp.data.generated_pdf_url"
            target="_blank"
            class="text-primary underline"
          >
            Xem PDF
          </a>
          <span v-else>—</span>
        </template>
      </Column>
      <Column header="Thao tác" headerStyle="min-width:14rem;">
        <template #body="sp">
          <div class="flex gap-2">
            <Button
              icon="pi pi-pencil"
              outlined
              severity="success"
              rounded
              @click="edit(sp.data)"
              :disabled="!['DRAFT', 'REJECTED'].includes(sp.data.status)"
              v-tooltip="['DRAFT', 'REJECTED'].includes(sp.data.status) ? 'Chỉnh sửa' : 'Chỉ có thể sửa phụ lục Nháp hoặc Bị từ chối'"
            />
            <Button
              icon="pi pi-trash"
              outlined
              severity="danger"
              rounded
              @click="confirmDelete(sp.data)"
              :disabled="!['DRAFT', 'REJECTED'].includes(sp.data.status)"
              v-tooltip="['DRAFT', 'REJECTED'].includes(sp.data.status) ? 'Xóa' : 'Chỉ có thể xóa phụ lục Nháp hoặc Bị từ chối'"
            />
            <Button
              icon="pi pi-file"
              outlined
              rounded
              @click="generateAppendix(sp.data)"
              v-tooltip="'Sinh phụ lục (PDF)'"
            />
            <Button
              v-if="sp.data.status === 'DRAFT'"
              icon="pi pi-send"
              outlined
              severity="info"
              rounded
              @click="submitForApproval(sp.data)"
              v-tooltip="'Gửi phê duyệt'"
            />
            <Button
              v-if="sp.data.status === 'REJECTED'"
              icon="pi pi-refresh"
              outlined
              severity="info"
              rounded
              @click="submitForApproval(sp.data)"
              v-tooltip="'Gửi lại phê duyệt'"
            />
            <Button
              v-if="sp.data.status === 'PENDING_APPROVAL'"
              icon="pi pi-replay"
              outlined
              severity="warning"
              rounded
              @click="recall(sp.data)"
              v-tooltip="'Thu hồi'"
            />
            <Button
              v-if="sp.data.status === 'PENDING_APPROVAL'"
              icon="pi pi-check"
              outlined
              severity="success"
              rounded
              @click="approve(sp.data)"
              v-tooltip="'Phê duyệt'"
            />
            <Button
              v-if="sp.data.status === 'PENDING_APPROVAL'"
              icon="pi pi-times"
              outlined
              severity="danger"
              rounded
              @click="reject(sp.data)"
              v-tooltip="'Từ chối'"
            />
          </div>
        </template>
      </Column>
    </DataTable>
  </div>

  <!-- Dialog tạo/sửa phụ lục -->
  <Dialog v-model:visible="dialog" :style="{ width: '800px' }" header="Phụ lục hợp đồng" :modal="true">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block font-bold mb-2 required-field">Số PL</label>
        <InputText
          v-model.trim="form.appendix_no"
          class="w-full"
          :invalid="(submitted && !form.appendix_no) || hasError('appendix_no')"
        />
        <small class="text-red-500" v-if="submitted && !form.appendix_no">Số phụ lục là bắt buộc.</small>
        <small class="text-red-500" v-if="hasError('appendix_no')">{{ errors.appendix_no }}</small>
      </div>
      <div>
        <label class="block font-bold mb-2 required-field">Loại</label>
        <Select
          v-model="form.appendix_type"
          :options="typeOptions"
          optionLabel="label"
          optionValue="value"
          showClear
          fluid
          :invalid="(submitted && !form.appendix_type) || hasError('appendix_type')"
        />
        <small class="text-red-500" v-if="submitted && !form.appendix_type">Loại phụ lục là bắt buộc.</small>
        <small class="text-red-500" v-if="hasError('appendix_type')">{{ errors.appendix_type }}</small>
      </div>
      <div>
        <label class="block font-bold mb-2 required-field">Hiệu lực từ</label>
        <DatePicker
          v-model="form.effective_date"
          dateFormat="yy-mm-dd"
          showIcon
          fluid
          :invalid="(submitted && !form.effective_date) || hasError('effective_date')"
        />
        <small class="text-red-500" v-if="submitted && !form.effective_date">Ngày hiệu lực là bắt buộc.</small>
        <small class="text-red-500" v-if="hasError('effective_date')">{{ errors.effective_date }}</small>
      </div>
      <div>
        <label class="block font-bold mb-2">Đến</label>
        <DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" showIcon fluid :invalid="hasError('end_date')" />
        <small class="text-red-500" v-if="hasError('end_date')">{{ errors.end_date }}</small>
      </div>

      <div>
        <label class="block font-bold mb-2">Lương cơ bản</label>
        <InputText type="number" v-model.number="form.base_salary" class="w-full" :invalid="hasError('base_salary')" />
        <small class="text-red-500" v-if="hasError('base_salary')">{{ errors.base_salary }}</small>
      </div>
      <div>
        <label class="block font-bold mb-2">Lương BH</label>
        <InputText
          type="number"
          v-model.number="form.insurance_salary"
          class="w-full"
          :invalid="hasError('insurance_salary')"
        />
        <small class="text-red-500" v-if="hasError('insurance_salary')">{{ errors.insurance_salary }}</small>
      </div>
      <div>
        <label class="block font-bold mb-2">PC vị trí</label>
        <InputText
          type="number"
          v-model.number="form.position_allowance"
          class="w-full"
          :invalid="hasError('position_allowance')"
        />
        <small class="text-red-500" v-if="hasError('position_allowance')">{{ errors.position_allowance }}</small>
      </div>
      <div class="md:col-span-2">
        <label class="block font-bold mb-2">Thời gian làm việc</label>
        <InputText v-model.trim="form.working_time" class="w-full" :invalid="hasError('working_time')" />
        <small class="text-red-500" v-if="hasError('working_time')">{{ errors.working_time }}</small>
      </div>
      <div class="md:col-span-2">
        <label class="block font-bold mb-2">Địa điểm</label>
        <InputText v-model.trim="form.work_location" class="w-full" :invalid="hasError('work_location')" />
        <small class="text-red-500" v-if="hasError('work_location')">{{ errors.work_location }}</small>
      </div>

      <!-- Other allowances (repeater) -->
      <div class="md:col-span-2">
        <div class="flex items-center justify-between mb-2">
          <label class="block font-bold">Phụ cấp khác</label>
          <Button size="small" icon="pi pi-plus" label="Thêm phụ cấp" @click="addAllowance" />
        </div>
        <div v-if="!form.other_allowances || form.other_allowances.length === 0" class="text-gray-500 text-sm">
          Chưa có phụ cấp khác.
        </div>
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

      <div class="md:col-span-2">
        <label class="block font-bold mb-2">Tóm tắt</label>
        <Textarea v-model.trim="form.summary" autoResize rows="2" class="w-full" :invalid="hasError('summary')" />
        <small class="text-red-500" v-if="hasError('summary')">{{ errors.summary }}</small>
      </div>
      <div class="md:col-span-2">
        <label class="block font-bold mb-2">Ghi chú</label>
        <Textarea v-model.trim="form.note" autoResize rows="3" class="w-full" :invalid="hasError('note')" />
        <small class="text-red-500" v-if="hasError('note')">{{ errors.note }}</small>
      </div>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="closeDialog" />
      <Button label="Lưu" icon="pi pi-check" @click="save" :loading="saving" />
    </template>
  </Dialog>

  <!-- Dialog xác nhận xóa -->
  <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
    <div class="flex items-center gap-4">
      <i class="pi pi-exclamation-triangle !text-3xl" />
      <span v-if="current">Bạn có chắc muốn xóa phụ lục <b>{{ current.appendix_no }}</b>?</span>
    </div>
    <template #footer>
      <Button label="Không" icon="pi pi-times" text @click="deleteDialog=false" />
      <Button label="Có" icon="pi pi-check" severity="danger" @click="remove" :loading="deleting" />
    </template>
  </Dialog>

  <!-- Dialog xóa nhiều -->
  <Dialog v-model:visible="deleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
    <div class="flex items-center gap-4">
      <i class="pi pi-exclamation-triangle !text-3xl" />
      <span>Bạn có chắc xóa {{ selected.length }} phụ lục đã chọn?</span>
    </div>
    <template #footer>
      <Button label="Không" icon="pi pi-times" text @click="deleteManyDialog=false" />
      <Button label="Có" icon="pi pi-check" severity="danger" @click="removeMany" :loading="deleting" />
    </template>
  </Dialog>

  <!-- Dialog gửi phê duyệt -->
  <Dialog v-model:visible="submitDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
    <div class="flex items-center gap-4">
      <i class="pi pi-send !text-3xl text-blue-600" />
      <span v-if="current">
        Bạn có chắc muốn {{ current.status === 'REJECTED' ? 'gửi lại' : 'gửi' }} phụ lục <b>{{ current.appendix_no }}</b> để phê duyệt?
      </span>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="submitDialog=false" />
      <Button
        :label="current?.status === 'REJECTED' ? 'Gửi lại' : 'Gửi phê duyệt'"
        :icon="current?.status === 'REJECTED' ? 'pi pi-refresh' : 'pi pi-send'"
        severity="info"
        @click="confirmSubmit"
        :loading="submitting"
      />
    </template>
  </Dialog>

  <!-- Dialog thu hồi -->
  <Dialog v-model:visible="recallDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
    <div class="flex items-center gap-4">
      <i class="pi pi-replay !text-3xl text-orange-600" />
      <span v-if="current">Bạn có chắc muốn thu hồi yêu cầu phê duyệt phụ lục <b>{{ current.appendix_no }}</b>?</span>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="recallDialog=false" />
      <Button label="Thu hồi" icon="pi pi-replay" severity="warning" @click="confirmRecall" :loading="recalling" />
    </template>
  </Dialog>

  <!-- Dialog phê duyệt -->
  <Dialog v-model:visible="approveDialog" :style="{ width: '600px' }" header="Phê duyệt phụ lục" :modal="true">
    <div class="mb-4">
      <div class="flex items-center gap-3 mb-4 p-3 bg-green-50 rounded">
        <i class="pi pi-check-circle text-green-600 text-xl"></i>
        <div>
          <div class="font-semibold text-gray-800">{{ current?.appendix_no }}</div>
          <div class="text-sm text-gray-600">{{ current?.appendix_type_label }}</div>
        </div>
      </div>

      <div>
        <label class="block font-bold mb-2">Ý kiến phê duyệt</label>
        <Textarea v-model="approvalNote" autoResize rows="3" class="w-full" placeholder="Nhập ý kiến (không bắt buộc)..." />
      </div>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="approveDialog=false" />
      <Button label="Phê duyệt" icon="pi pi-check" severity="success" @click="confirmApprove" :loading="approving" />
    </template>
  </Dialog>

  <!-- Dialog từ chối -->
  <Dialog v-model:visible="rejectDialog" :style="{ width: '600px' }" header="Từ chối phụ lục" :modal="true">
    <div class="mb-4">
      <div class="flex items-center gap-3 mb-4 p-3 bg-red-50 rounded">
        <i class="pi pi-times-circle text-red-600 text-xl"></i>
        <div>
          <div class="font-semibold text-gray-800">{{ current?.appendix_no }}</div>
          <div class="text-sm text-gray-600">{{ current?.appendix_type_label }}</div>
        </div>
      </div>

      <div>
        <label class="block font-bold mb-2 required-field">Lý do từ chối</label>
        <Textarea v-model="rejectNote" autoResize rows="4" class="w-full" placeholder="Nhập lý do từ chối (bắt buộc)..." :invalid="rejectSubmitted && !rejectNote" />
        <small class="text-red-500" v-if="rejectSubmitted && !rejectNote">Vui lòng nhập lý do từ chối.</small>
      </div>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="rejectDialog=false" />
      <Button label="Từ chối" icon="pi pi-times-circle" severity="danger" @click="confirmReject" :loading="rejecting" />
    </template>
  </Dialog>

  <!-- Dialog chọn template để sinh PDF -->
  <Dialog v-model:visible="generateDialog" :style="{ width: '600px' }" header="Xác nhận sinh PDF" :modal="true">
    <div class="mb-4">
      <div class="flex items-center gap-3 mb-4 p-3 bg-blue-50 rounded">
        <i class="pi pi-info-circle text-blue-600 text-xl"></i>
        <div>
          <div class="font-semibold text-gray-800">Phụ lục: {{ currentAppendix?.appendix_no }}</div>
          <div class="text-sm text-gray-600">Loại: {{ currentAppendix?.appendix_type_label }}</div>
        </div>
      </div>

      <div v-if="defaultTemplate" class="mb-3">
        <p class="text-sm text-gray-700 mb-2">Mẫu được chọn:</p>
        <div class="p-3 border rounded bg-gray-50">
          <div class="font-semibold">{{ defaultTemplate.name }}</div>
          <div class="text-sm text-gray-600">{{ defaultTemplate.code }}</div>
          <div v-if="defaultTemplate.is_default" class="text-xs text-green-600 mt-1">
            <i class="pi pi-check-circle"></i> Mẫu mặc định
          </div>
        </div>
      </div>

      <details class="mt-4">
        <summary class="cursor-pointer text-sm text-primary hover:underline">
          Hoặc chọn mẫu khác...
        </summary>
        <Select
          v-model="selectedTemplateId"
          :options="availableTemplates"
          optionLabel="name"
          optionValue="id"
          placeholder="-- Chọn mẫu khác --"
          showClear
          fluid
          :loading="loadingTemplates"
          class="mt-3"
        >
          <template #option="slotProps">
            <div>
              <div class="font-semibold">{{ slotProps.option.name }}</div>
              <div class="text-sm text-gray-600">{{ slotProps.option.code }}</div>
            </div>
          </template>
        </Select>
      </details>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="generateDialog=false" />
      <Button
        label="Sinh PDF"
        icon="pi pi-file-pdf"
        severity="success"
        @click="confirmGenerate"
        :loading="generating"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import { ContractAppendixService } from '@/services/ContractAppendixService'
import { useFormValidation } from '@/composables/useFormValidation'
import { toYMD, formatDate } from '@/utils/dateHelper'

const { errors, hasError } = useFormValidation()

const props = defineProps({
  contractId: { type: String, required: true },
  appendixes: { type: Array, default: () => [] },
  appendixTemplates: { type: Array, default: () => [] }
})

const rows = computed(() => props.appendixes || [])
const dt = ref()
const selected = ref([])
const dialog = ref(false)
const saving = ref(false)
const submitted = ref(false)
const current = ref(null)
const deleting = ref(false)
const deleteDialog = ref(false)
const deleteManyDialog = ref(false)
const filters = ref({ global: { value: null, matchMode: 'contains' } })

const generating = ref(false)
const generateDialog = ref(false)
const currentAppendix = ref(null)
const selectedTemplateId = ref(null)
const availableTemplates = ref([])
const loadingTemplates = ref(false)
const defaultTemplate = ref(null)

const form = ref({
  id: null,
  appendix_no: '',
  appendix_type: null,
  effective_date: null,
  end_date: null,
  base_salary: null,
  insurance_salary: null,
  position_allowance: null,
  other_allowances: [],
  working_time: '',
  work_location: '',
  summary: '',
  note: ''
})

const typeOptions = [
  { value: 'SALARY', label: 'Điều chỉnh lương' },
  { value: 'ALLOWANCE', label: 'Điều chỉnh phụ cấp' },
  { value: 'POSITION', label: 'Điều chỉnh chức danh' },
  { value: 'DEPARTMENT', label: 'Điều chuyển đơn vị' },
  { value: 'WORKING_TERMS', label: 'Thời gian/địa điểm làm việc' },
  { value: 'EXTENSION', label: 'Gia hạn HĐ' },
  { value: 'OTHER', label: 'Khác' }
]

const statusSeverity = (s) =>
  ({
    DRAFT: 'secondary',
    PENDING_APPROVAL: 'warn',
    ACTIVE: 'success',
    REJECTED: 'danger',
    CANCELLED: 'contrast'
  }[s] || 'info')

const exportCSV = () => dt.value?.exportCSV()

function openNew() {
  reset()
  submitted.value = false
  dialog.value = true
}

function edit(row) {
  form.value = {
    ...row,
    other_allowances: Array.isArray(row.other_allowances)
      ? JSON.parse(JSON.stringify(row.other_allowances))
      : []
  }
  submitted.value = false
  dialog.value = true
}

function reset() {
  form.value = {
    id: null,
    appendix_no: '',
    appendix_type: null,
    effective_date: null,
    end_date: null,
    base_salary: null,
    insurance_salary: null,
    position_allowance: null,
    other_allowances: [],
    working_time: '',
    work_location: '',
    summary: '',
    note: ''
  }
}

function closeDialog() {
  dialog.value = false
  submitted.value = false
}

function save() {
  submitted.value = true

  if (!form.value.appendix_no || !form.value.appendix_type || !form.value.effective_date) {
    return
  }

  saving.value = true
  const payload = {
    ...form.value,
    effective_date: toYMD(form.value.effective_date),
    end_date: toYMD(form.value.end_date)
  }
  const opts = {
    onFinish: () => (saving.value = false),
    onSuccess: () => {
      dialog.value = false
      submitted.value = false
    }
  }
  if (!form.value.id) {
    ContractAppendixService.store(props.contractId, payload, opts)
  } else {
    ContractAppendixService.update(props.contractId, form.value.id, payload, opts)
  }
}

function confirmDelete(row) {
  current.value = row
  deleteDialog.value = true
}

function remove() {
  deleting.value = true
  ContractAppendixService.destroy(props.contractId, current.value.id, {
    onSuccess: () => {
      deleting.value = false
      deleteDialog.value = false
      current.value = null
    },
    onError: () => {
      deleting.value = false
    },
    onFinish: () => {
      deleting.value = false
    }
  })
}

function confirmDeleteSelected() {
  deleteManyDialog.value = true
}

function removeMany() {
  const ids = selected.value.map((x) => x.id)
  deleting.value = true
  ContractAppendixService.bulkDelete(props.contractId, ids, {
    onSuccess: () => {
      deleting.value = false
      deleteManyDialog.value = false
      selected.value = []
    },
    onError: () => {
      deleting.value = false
    },
    onFinish: () => {
      deleting.value = false
    }
  })
}

// Approval dialogs
const approveDialog = ref(false)
const rejectDialog = ref(false)
const submitDialog = ref(false)
const recallDialog = ref(false)
const approving = ref(false)
const rejecting = ref(false)
const submitting = ref(false)
const recalling = ref(false)
const approvalNote = ref('')
const rejectNote = ref('')
const rejectSubmitted = ref(false)

function submitForApproval(row) {
  current.value = row
  submitDialog.value = true
}

function confirmSubmit() {
  submitting.value = true
  ContractAppendixService.submitForApproval(props.contractId, current.value.id, {
    onSuccess: () => {
      // Update local state instead of reloading
      const index = props.appendixes.findIndex(a => a.id === current.value.id)
      if (index !== -1) {
        props.appendixes[index].status = 'PENDING_APPROVAL'
        props.appendixes[index].status_label = 'Chờ duyệt'
      }
      submitDialog.value = false
      current.value = null
    },
    onError: () => {},
    onFinish: () => {
      submitting.value = false
    }
  })
}

function recall(row) {
  current.value = row
  recallDialog.value = true
}

function confirmRecall() {
  recalling.value = true
  ContractAppendixService.recall(props.contractId, current.value.id, {
    onSuccess: () => {
      // Update local state instead of reloading
      const index = props.appendixes.findIndex(a => a.id === current.value.id)
      if (index !== -1) {
        props.appendixes[index].status = 'DRAFT'
        props.appendixes[index].status_label = 'Nháp'
      }
      recallDialog.value = false
      current.value = null
    },
    onError: () => {},
    onFinish: () => {
      recalling.value = false
    }
  })
}

function approve(row) {
  current.value = row
  approvalNote.value = ''
  approveDialog.value = true
}

function confirmApprove() {
  approving.value = true
  ContractAppendixService.approve(props.contractId, current.value.id, { note: approvalNote.value }, {
    onSuccess: () => {
      // Update local state instead of reloading
      const index = props.appendixes.findIndex(a => a.id === current.value.id)
      if (index !== -1) {
        props.appendixes[index].status = 'ACTIVE'
        props.appendixes[index].status_label = 'Đã duyệt'
      }
      approveDialog.value = false
      current.value = null
      approvalNote.value = ''
    },
    onError: () => {},
    onFinish: () => {
      approving.value = false
    }
  })
}

function reject(row) {
  current.value = row
  rejectNote.value = ''
  rejectSubmitted.value = false
  rejectDialog.value = true
}

function confirmReject() {
  rejectSubmitted.value = true

  if (!rejectNote.value) {
    return
  }

  rejecting.value = true
  ContractAppendixService.reject(props.contractId, current.value.id, { note: rejectNote.value }, {
    onSuccess: () => {
      // Update local state instead of reloading
      const index = props.appendixes.findIndex(a => a.id === current.value.id)
      if (index !== -1) {
        props.appendixes[index].status = 'REJECTED'
        props.appendixes[index].status_label = 'Bị từ chối'
      }
      rejectDialog.value = false
      current.value = null
      rejectNote.value = ''
      rejectSubmitted.value = false
    },
    onError: () => {},
    onFinish: () => {
      rejecting.value = false
    }
  })
}

// other_allowances
function addAllowance() {
  if (!Array.isArray(form.value.other_allowances)) form.value.other_allowances = []
  form.value.other_allowances.push({ name: '', amount: null })
}
function removeAllowance(idx) {
  form.value.other_allowances.splice(idx, 1)
}

// Generate appendix PDF - auto-select default template based on appendix_type
async function generateAppendix(row) {
  currentAppendix.value = row
  selectedTemplateId.value = null
  defaultTemplate.value = null
  generateDialog.value = true

  // Load available templates for this appendix type
  loadingTemplates.value = true
  try {
    const response = await fetch(`/contract-appendix-templates?appendix_type=${row.appendix_type}`)
    const data = await response.json()
    availableTemplates.value = data.data || []

    // Auto-select default template
    defaultTemplate.value = availableTemplates.value.find(t => t.is_default) || availableTemplates.value[0]
    if (defaultTemplate.value) {
      selectedTemplateId.value = defaultTemplate.value.id
    }
  } catch (error) {
    console.error('Failed to load templates:', error)
    availableTemplates.value = []
  } finally {
    loadingTemplates.value = false
  }
}

// Confirm and generate PDF with selected template
function confirmGenerate() {
  if (!currentAppendix.value) return

  generating.value = true
  const payload = selectedTemplateId.value ? { template_id: selectedTemplateId.value } : {}

  ContractAppendixService.generate(props.contractId, currentAppendix.value.id, payload, {
    onFinish: () => {
      generating.value = false
      generateDialog.value = false
    }
  })
}
</script>

<style scoped>
.required-field::after {
  content: ' *';
  color: red;
}
</style>
