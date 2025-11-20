<template>
  <Head>
    <title>Mẫu Phụ lục Hợp đồng</title>
  </Head>

  <div>
    <div class="card">
      <Toolbar class="mb-6">
        <template #start>
          <Button v-if="canCreate" label="Thêm mới" icon="pi pi-plus" class="mr-2" @click="openNew" />
          <Button v-if="canDelete" label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined"
                  @click="confirmDeleteSelected" :disabled="!selected?.length" />
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
        currentPageReportTemplate="Hiển thị {first}–{last}/{totalRecords} mẫu"
      >
        <template #header>
          <div class="flex flex-wrap gap-2 items-center justify-between">
            <h4 class="m-0">Danh sách Mẫu Phụ lục Hợp đồng</h4>
            <IconField>
              <InputIcon><i class="pi pi-search" /></InputIcon>
              <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
            </IconField>
          </div>
        </template>

        <Column v-if="canDelete" selectionMode="multiple" headerStyle="width: 3rem"></Column>
        <Column field="name" header="Tên mẫu" sortable headerStyle="min-width:14rem;"></Column>
        <Column field="code" header="Mã" sortable headerStyle="min-width:10rem;"></Column>
        <Column field="appendix_type_label" header="Loại phụ lục" sortable headerStyle="min-width:12rem;">
          <template #body="sp">
            <Tag :value="sp.data.appendix_type_label" :severity="getAppendixTypeSeverity(sp.data.appendix_type)" />
          </template>
        </Column>
        <Column field="body_path" header="File Template" headerStyle="min-width:14rem;">
          <template #body="sp">
            <span class="text-sm text-gray-600">{{ sp.data.body_path || '-' }}</span>
          </template>
        </Column>
        <Column field="is_default" header="Mặc định" headerStyle="min-width:8rem;">
          <template #body="sp">
            <Tag :value="sp.data.is_default ? 'Yes' : 'No'" :severity="sp.data.is_default ? 'success' : 'secondary'" />
          </template>
        </Column>
        <Column field="status_label" header="Trạng thái" headerStyle="min-width:10rem;">
          <template #body="sp">
            <Tag :value="sp.data.status_label" :severity="sp.data.is_active ? 'success' : 'contrast'" />
          </template>
        </Column>
        <Column field="created_at" header="Tạo lúc" sortable headerStyle="min-width:12rem;"></Column>

        <Column v-if="canEdit || canDelete" header="Thao tác" headerStyle="min-width:20rem;">
          <template #body="sp">
            <div class="flex gap-2">
              <Button v-if="canEdit" icon="pi pi-cog" outlined severity="secondary" rounded
                      @click="managePlaceholders(sp.data)" v-tooltip.top="'Quản lý Placeholders'" />
              <Button v-if="canEdit" icon="pi pi-pencil" outlined severity="success" rounded @click="edit(sp.data)" />
              <Button v-if="canDelete" icon="pi pi-trash" outlined severity="danger" rounded @click="confirmDelete(sp.data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog tạo/sửa -->
    <Dialog v-model:visible="dialog" :style="{ width: '900px' }" header="Mẫu Phụ lục Hợp đồng" :modal="true">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-bold mb-2 required-field">Tên mẫu</label>
          <InputText v-model.trim="form.name" class="w-full" :invalid="submitted && !form.name || hasError('name')" />
          <small class="text-red-500" v-if="submitted && !form.name">Tên mẫu là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('name')">{{ errors.name }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Mã phụ lục</label>
          <InputText v-model.trim="form.code" class="w-full" placeholder="VD: PL-LUONG-01"
                     :invalid="submitted && !form.code || hasError('code')" />
          <small class="text-red-500" v-if="submitted && !form.code">Mã phụ lục là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('code')">{{ errors.code }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Loại phụ lục</label>
          <Select v-model="form.appendix_type" :options="appendixTypeOptions" optionLabel="label" optionValue="value" showClear fluid
                  :invalid="submitted && !form.appendix_type || hasError('appendix_type')" />
          <small class="text-red-500" v-if="submitted && !form.appendix_type">Loại phụ lục là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('appendix_type')">{{ errors.appendix_type }}</small>
        </div>

        <!-- DOCX file upload (REQUIRED) -->
        <div class="md:col-span-2">
          <label class="block font-bold mb-2 required-field">File DOCX Template</label>
          <div class="border-2 border-dashed border-blue-300 rounded-lg p-6 text-center cursor-pointer hover:bg-blue-50 transition"
               @click="docxUploadInput?.click()"
               @dragover.prevent="dragOverDocx = true"
               @dragleave.prevent="dragOverDocx = false"
               @drop.prevent="handleDocxDrop"
               :class="{ 'bg-blue-100 border-blue-500': dragOverDocx }">
            <input
              ref="docxUploadInput"
              type="file"
              accept=".docx"
              class="hidden"
              @change="uploadDocxFile"
            />
            <div v-if="!docxUploading && !uploadedDocxFile">
              <i class="pi pi-upload text-3xl text-blue-500 mb-2"></i>
              <p class="text-gray-700">Kéo thả file .docx hoặc <span class="text-blue-500 font-bold">click để chọn</span></p>
              <p class="text-sm text-gray-500 mt-1">File phải là .docx hợp lệ với placeholders</p>
            </div>
            <div v-else-if="docxUploading" class="flex flex-col items-center gap-2">
              <ProgressSpinner style="width: 40px; height: 40px" strokeWidth="8" />
              <p class="text-gray-600">Đang tải lên...</p>
            </div>
            <div v-else class="flex flex-col items-center gap-2">
              <i class="pi pi-check-circle text-2xl text-green-500"></i>
              <p class="font-bold text-green-600">{{ uploadedDocxFile }}</p>
              <Button label="Thay đổi" icon="pi pi-refresh" severity="warning" size="small"
                      @click.stop="docxUploadInput?.click()" />
            </div>
          </div>
          <small v-if="form.body_path" class="text-green-600 block mt-2">✓ Path: {{ form.body_path }}</small>
          <small class="text-red-500" v-if="submitted && !form.body_path">File DOCX là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('body_path')">{{ errors.body_path }}</small>
          <small v-if="docxUploadError" class="text-red-500 block">{{ docxUploadError }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2">Mặc định theo loại</label>
          <Select v-model="form.is_default" :options="yesNoOptions" optionLabel="label" optionValue="value" fluid />
        </div>
        <div>
          <label class="block font-bold mb-2">Trạng thái</label>
          <Select v-model="form.is_active" :options="statusOptions" optionLabel="label" optionValue="value" fluid />
        </div>

        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Mô tả</label>
          <Textarea v-model.trim="form.description" autoResize rows="2" class="w-full" />
        </div>
      </div>

      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
        <Button v-if="canEdit && form.body_path && form.id"
                label="Xem trước" icon="pi pi-eye" severity="info" @click="previewDocx" />
        <Button label="Lưu" icon="pi pi-check" @click="save" :loading="saving" />
      </template>
    </Dialog>

    <!-- Dialog xóa -->
    <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span v-if="current">Bạn có chắc muốn xóa <b>{{ current.name }}</b>?</span>
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
        <span>Bạn có chắc muốn xóa các mẫu đã chọn?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteManyDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="removeMany" :loading="deleting" />
      </template>
    </Dialog>

    <!-- Placeholder Manager Dialog -->
    <ContractTemplatePlaceholderManager
      v-if="currentTemplate"
      v-model:visible="placeholderDialog"
      :template="currentTemplate"
      :is-appendix="true"
      @saved="onPlaceholdersSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import ProgressSpinner from 'primevue/progressspinner'
import { useFormValidation } from '@/composables/useFormValidation'
import { usePermission } from '@/composables/usePermission'
import { ContractAppendixTemplateService } from '@/services/ContractAppendixTemplateService'
import ContractTemplatePlaceholderManager from '@/Components/ContractTemplatePlaceholderManager.vue'

const { errors, hasError } = useFormValidation()
const { can } = usePermission()

const props = defineProps({
  templates: { type: Array, default: () => [] },
  appendixTypeOptions: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] }
})

const dt = ref()
const selected = ref([])
const filters = ref({ global: { value: null, matchMode: 'contains' } })
const rows = computed(() => props.templates || [])

const dialog = ref(false)
const deleteDialog = ref(false)
const deleteManyDialog = ref(false)
const saving = ref(false)
const deleting = ref(false)
const submitted = ref(false)
const current = ref(null)

// DOCX upload state
const docxUploadInput = ref()
const docxUploading = ref(false)
const dragOverDocx = ref(false)
const uploadedDocxFile = ref('')
const docxUploadError = ref('')

// Placeholder management
const placeholderDialog = ref(false)
const currentTemplate = ref(null)

const yesNoOptions = [
  { value: true, label: 'Yes' },
  { value: false, label: 'No' }
]

const appendixTypeOptions = computed(() => props.appendixTypeOptions || [])
const statusOptions = computed(() => props.statusOptions || [])

const form = ref({
  id: null,
  name: '',
  code: '',
  appendix_type: null,
  body_path: '',
  is_default: false,
  is_active: 1,
  description: ''
})

const canCreate = computed(() => can('create appendix templates'))
const canEdit   = computed(() => can('edit appendix templates'))
const canDelete = computed(() => can('delete appendix templates'))

function getAppendixTypeSeverity(type) {
  const severityMap = {
    'SALARY': 'success',
    'ALLOWANCE': 'info',
    'POSITION': 'warn',
    'DEPARTMENT': 'secondary',
    'WORKING_TERMS': 'contrast',
    'EXTENSION': 'primary',
    'OTHER': 'secondary'
  }
  return severityMap[type] || 'secondary'
}

function openNew() {
  form.value = {
    id: null,
    name: '',
    code: '',
    appendix_type: null,
    body_path: '',
    is_default: false,
    is_active: 1,
    description: ''
  }
  submitted.value = false
  docxUploading.value = false
  uploadedDocxFile.value = ''
  docxUploadError.value = ''
  dialog.value = true
}

function edit(row) {
  form.value = {
    id: row.id,
    name: row.name,
    code: row.code,
    appendix_type: row.appendix_type,
    body_path: row.body_path || '',
    is_default: !!row.is_default,
    is_active: row.is_active,
    description: row.description || ''
  }
  submitted.value = false
  docxUploading.value = false
  docxUploadError.value = ''
  // Show uploaded filename if body_path exists
  if (row.body_path) {
    uploadedDocxFile.value = row.body_path.split('/').pop()
  } else {
    uploadedDocxFile.value = ''
  }
  dialog.value = true
}

function hideDialog() {
  dialog.value = false
  submitted.value = false
  uploadedDocxFile.value = ''
  docxUploadError.value = ''
}

function save() {
  submitted.value = true
  if (!form.value.name || !form.value.code || !form.value.appendix_type || !form.value.body_path) {
    return
  }

  saving.value = true
  const payload = { ...form.value }
  const opts = {
    onFinish: () => (saving.value = false),
    onSuccess: () => { dialog.value = false }
  }
  if (!form.value.id) {
    ContractAppendixTemplateService.store(payload, opts)
  } else {
    ContractAppendixTemplateService.update(form.value.id, payload, opts)
  }
}

function confirmDelete(row) {
  current.value = row
  deleteDialog.value = true
}

function remove() {
  deleting.value = true
  ContractAppendixTemplateService.destroy(current.value.id, {
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
  const ids = selected.value.map(x => x.id)
  deleting.value = true
  ContractAppendixTemplateService.bulkDelete(ids, {
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

function managePlaceholders(row) {
  currentTemplate.value = row
  placeholderDialog.value = true
}

function onPlaceholdersSaved() {
  console.log('Placeholders saved')
}

// DOCX Upload Handlers
async function uploadDocxFile(e) {
  const files = e.target.files
  if (!files || files.length === 0) return

  const file = files[0]
  await performDocxUpload(file)
}

async function handleDocxDrop(e) {
  dragOverDocx.value = false
  const files = e.dataTransfer.files
  if (!files || files.length === 0) return

  const file = files[0]
  if (!file.name.endsWith('.docx')) {
    docxUploadError.value = 'File phải là .docx'
    return
  }

  await performDocxUpload(file)
}

async function performDocxUpload(file) {
  if (!form.value.appendix_type) {
    docxUploadError.value = 'Vui lòng chọn loại phụ lục trước khi upload file'
    return
  }

  docxUploadError.value = ''
  docxUploading.value = true

  const formData = new FormData()
  formData.append('file', file)
  formData.append('appendix_type', form.value.appendix_type)

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
    const response = await fetch('/contract-appendix-templates/upload', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken || '',
      }
    })

    const data = await response.json()

    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Lỗi tải file')
    }

    // Success
    form.value.body_path = data.data.body_path
    uploadedDocxFile.value = file.name
    docxUploadError.value = ''
  } catch (error) {
    docxUploadError.value = error.message || 'Lỗi tải file DOCX'
    uploadedDocxFile.value = ''
    form.value.body_path = ''
  } finally {
    docxUploading.value = false
  }
}

function previewDocx() {
  if (!form.value.id) {
    alert('Vui lòng lưu template trước khi xem trước')
    return
  }

  const url = `/contract-appendix-templates/${form.value.id}/docx-preview`
  window.open(url, '_blank')
}
</script>
