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
        <Column field="body_path" header="Blade View" headerStyle="min-width:14rem;"></Column>
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

        <Column v-if="canEdit || canDelete" header="Thao tác" headerStyle="min-width:14rem;">
          <template #body="sp">
            <div class="flex gap-2">
              <Button v-if="canEdit" icon="pi pi-pencil" outlined severity="success" rounded @click="edit(sp.data)" />
              <Button v-if="canDelete" icon="pi pi-trash" outlined severity="danger" rounded @click="confirmDelete(sp.data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog tạo/sửa -->
    <Dialog v-model:visible="dialog" :style="{ width: '700px' }" header="Mẫu Hợp đồng" :modal="true">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-bold mb-2 required-field">Tên mẫu</label>
          <InputText v-model.trim="form.name" class="w-full" :invalid="submitted && !form.name || hasError('name')" />
          <small class="text-red-500" v-if="submitted && !form.name">Tên mẫu là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('name')">{{ errors.name }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Engine</label>
          <InputText v-model.trim="form.engine" class="w-full" :invalid="submitted && !form.engine || hasError('engine')" />
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
        <div>
          <label class="block font-bold mb-2 required-field">Blade View</label>
          <InputText v-model.trim="form.body_path" class="w-full" placeholder="vd: contracts/templates/default"
                     :invalid="submitted && !form.body_path || hasError('body_path')" />
          <small class="text-red-500" v-if="submitted && !form.body_path">Blade View là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('body_path')">{{ errors.body_path }}</small>
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
          <Textarea v-model.trim="form.description" autoResize rows="3" class="w-full" />
        </div>
      </div>

      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
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

const { errors, hasError } = useFormValidation()
const { can } = usePermission()

const props = defineProps({
  templates: { type: Array, default: () => [] },
  contractTypeOptions: { type: Array, default: () => [] },
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

const yesNoOptions = [
  { value: true, label: 'Yes' },
  { value: false, label: 'No' }
]

const contractTypeOptions = computed(() => props.contractTypeOptions || [])
const statusOptions = computed(() => props.statusOptions || [])

const form = ref({
  id: null,
  name: '',
  engine: '',
  type: null,
  body_path: '',
  is_default: false,
  is_active: true,
  description: ''
})

const canCreate = computed(() => can('create contract templates'))
const canEdit   = computed(() => can('edit contract templates'))
const canDelete = computed(() => can('delete contract templates'))

function openNew() {
  form.value = {
    id: null,
    name: '',
    engine: '',
    type: null,
    body_path: '',
    is_default: false,
    is_active: true,
    description: ''
  }
  submitted.value = false
  dialog.value = true
}

function edit(row) {
  form.value = {
    id: row.id,
    name: row.name,
    engine: row.engine,
    type: row.type,
    body_path: row.body_path,
    is_default: !!row.is_default,
    is_active: row.is_active,
    description: row.description || ''
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
  if (!form.value.name || !form.value.engine || !form.value.type || !form.value.body_path) {
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
</script>

<style scoped>
.required-field::after { content: ' *'; color: red; }
</style>
