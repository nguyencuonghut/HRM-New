<template>
  <Head>
    <title>Mẫu Hợp đồng</title>
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
            <h4 class="m-0">Danh sách Mẫu Hợp đồng</h4>
            <IconField>
              <InputIcon><i class="pi pi-search" /></InputIcon>
              <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
            </IconField>
          </div>
        </template>

        <Column v-if="canDelete" selectionMode="multiple" headerStyle="width: 3rem"></Column>
        <Column field="name" header="Tên mẫu" sortable headerStyle="min-width:14rem;"></Column>
        <Column field="type_label" header="Loại HĐ" sortable headerStyle="min-width:12rem;"></Column>
        <Column field="engine_label" header="Engine" sortable headerStyle="min-width:12rem;">
          <template #body="sp">
            <Tag :value="sp.data.engine_label" :severity="sp.data.engine === 'LIQUID' ? 'info' : 'secondary'" />
          </template>
        </Column>
        <Column field="body_path" header="Path/Content" headerStyle="min-width:14rem;">
          <template #body="sp">
            <span v-if="sp.data.engine === 'BLADE'">{{ sp.data.body_path || '-' }}</span>
            <span v-else class="text-gray-500 text-sm italic">Nội dung Liquid</span>
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
              <Button v-if="canEdit && sp.data.engine === 'LIQUID'" icon="pi pi-file-edit" outlined severity="info" rounded
                      @click="openEditor(sp.data)" v-tooltip.top="'Chỉnh sửa Liquid'" />
              <Button v-if="canEdit && sp.data.engine === 'DOCX_MERGE'" icon="pi pi-cog" outlined severity="secondary" rounded
                      @click="managePlaceholders(sp.data)" v-tooltip.top="'Quản lý Placeholders'" />
              <Button v-if="canEdit" icon="pi pi-pencil" outlined severity="success" rounded @click="edit(sp.data)" />
              <Button v-if="canDelete" icon="pi pi-trash" outlined severity="danger" rounded @click="confirmDelete(sp.data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog tạo/sửa -->
    <Dialog v-model:visible="dialog" :style="{ width: '900px' }" header="Mẫu Hợp đồng" :modal="true">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-bold mb-2 required-field">Tên mẫu</label>
          <InputText v-model.trim="form.name" class="w-full" :invalid="submitted && !form.name || hasError('name')" />
          <small class="text-red-500" v-if="submitted && !form.name">Tên mẫu là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('name')">{{ errors.name }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Engine</label>
          <Select v-model="form.engine" :options="engineOptions" optionLabel="label" optionValue="value" fluid
                  :invalid="submitted && !form.engine || hasError('engine')" />
          <small class="text-red-500" v-if="submitted && !form.engine">Engine là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('engine')">{{ errors.engine }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Loại HĐ</label>
          <Select v-model="form.type" :options="contractTypeOptions" optionLabel="label" optionValue="value" showClear fluid
                  :invalid="submitted && !form.type || hasError('type')" />
          <small class="text-red-500" v-if="submitted && !form.type">Loại HĐ là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('type')">{{ errors.type }}</small>
        </div>

        <!-- BLADE: body_path -->
        <div v-if="form.engine === 'BLADE'">
          <label class="block font-bold mb-2 required-field">Blade View</label>
          <InputText v-model.trim="form.body_path" class="w-full" placeholder="vd: contracts/templates/default"
                     :invalid="submitted && form.engine === 'BLADE' && !form.body_path || hasError('body_path')" />
          <small class="text-red-500" v-if="submitted && form.engine === 'BLADE' && !form.body_path">Blade View là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('body_path')">{{ errors.body_path }}</small>
        </div>

        <!-- DOCX_MERGE: file upload -->
        <div v-if="form.engine === 'DOCX_MERGE'" class="md:col-span-2">
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
          <small class="text-red-500" v-if="submitted && form.engine === 'DOCX_MERGE' && !form.body_path">File DOCX là bắt buộc.</small>
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

        <!-- LIQUID: content editor -->
        <div v-if="form.engine === 'LIQUID'" class="md:col-span-2">
          <label class="block font-bold mb-2 required-field">Nội dung Template (Liquid)</label>
          <Textarea v-model="form.content" rows="15" class="w-full font-mono text-sm"
                    :invalid="submitted && form.engine === 'LIQUID' && !form.content || hasError('content')"
                    placeholder="Nhập nội dung Liquid template..." />
          <small class="text-red-500" v-if="submitted && form.engine === 'LIQUID' && !form.content">Nội dung template là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('content')">{{ errors.content }}</small>
        </div>

        <!-- Placeholders helper -->
        <div v-if="form.engine === 'LIQUID'" class="md:col-span-2">
          <label class="block font-bold mb-2">Biến hỗ trợ (Placeholders)</label>
          <div class="border rounded p-3 bg-gray-50 max-h-60 overflow-y-auto">
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div v-for="ph in placeholdersList" :key="ph.name" class="flex items-center gap-2">
                <code class="bg-blue-100 px-2 py-1 rounded text-xs" v-text="`{{ ${ph.name} }}`"></code>
                <span class="text-gray-600">{{ ph.description }}</span>
              </div>
            </div>
          </div>
          <small class="text-gray-500">Copy các biến trên để sử dụng trong template</small>
        </div>
      </div>

      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
        <Button v-if="canEdit && form.engine === 'DOCX_MERGE' && form.body_path && form.id"
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
      @saved="onPlaceholdersSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import { useFormValidation } from '@/composables/useFormValidation'
import { usePermission } from '@/composables/usePermission'
import { ContractTemplateService } from '@/services/ContractTemplateService'
import ContractTemplatePlaceholderManager from '@/Components/ContractTemplatePlaceholderManager.vue'

const { errors, hasError } = useFormValidation()
const { can } = usePermission()

const props = defineProps({
  templates: { type: Array, default: () => [] },
  contractTypeOptions: { type: Array, default: () => [] },
  engineOptions: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] },
  placeholdersList: { type: Array, default: () => [] }
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

const contractTypeOptions = computed(() => props.contractTypeOptions || [])
const engineOptions = computed(() => props.engineOptions || [])
const statusOptions = computed(() => props.statusOptions || [])
const placeholdersList = computed(() => props.placeholdersList || [])

const form = ref({
  id: null,
  name: '',
  engine: 'LIQUID',
  type: null,
  body_path: '',
  content: '',
  is_default: false,
  is_active: 1,
  description: ''
})

const canCreate = computed(() => can('create contract templates'))
const canEdit   = computed(() => can('edit contract templates'))
const canDelete = computed(() => can('delete contract templates'))

function openNew() {
  form.value = {
    id: null,
    name: '',
    engine: 'LIQUID',
    type: null,
    body_path: '',
    content: '',
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
    engine: row.engine || 'LIQUID',
    type: row.type,
    body_path: row.body_path || '',
    content: row.content || '',
    is_default: !!row.is_default,
    is_active: row.is_active,
    description: row.description || ''
  }
  submitted.value = false
  // Reset DOCX upload state for edit
  docxUploading.value = false
  docxUploadError.value = ''
  // Show uploaded filename if body_path exists
  if (row.engine === 'DOCX_MERGE' && row.body_path) {
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
  if (!form.value.name || !form.value.engine || !form.value.type) {
    return
  }
  // Validate theo engine
  if (form.value.engine === 'LIQUID' && !form.value.content) {
    return
  }
  if (form.value.engine === 'BLADE' && !form.value.body_path) {
    return
  }
  if (form.value.engine === 'DOCX_MERGE' && !form.value.body_path) {
    return
  }

  saving.value = true
  const payload = { ...form.value }
  const opts = {
    onFinish: () => (saving.value = false),
    onSuccess: () => { dialog.value = false }
  }
  if (!form.value.id) {
    ContractTemplateService.store(payload, opts)
  } else {
    ContractTemplateService.update(form.value.id, payload, opts)
  }
}

function confirmDelete(row) {
  current.value = row
  deleteDialog.value = true
}

function remove() {
  deleting.value = true
  ContractTemplateService.destroy(current.value.id, {
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
  ContractTemplateService.bulkDelete(ids, {
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

function openEditor(row) {
  window.location.href = `/contract-templates/${row.id}/editor`
}

function managePlaceholders(row) {
  currentTemplate.value = row
  placeholderDialog.value = true
}

function onPlaceholdersSaved() {
  // Reload data if needed
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
  docxUploadError.value = ''
  docxUploading.value = true

  const formData = new FormData()
  formData.append('file', file)
  formData.append('contract_type', form.value.type)

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
    const response = await fetch('/contract-templates/upload', {
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

  // Mở URL trực tiếp trong tab mới - Laravel sẽ xử lý session và return PDF
  const url = `/contract-templates/${form.value.id}/docx-preview`
  window.open(url, '_blank')
}
</script>
