<script setup>
import { ref, computed, watch } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import { usePermissions } from '@/Composables/usePermissions'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Toolbar from 'primevue/toolbar'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'
import { BenefitTypeService } from '@/services/BenefitTypeService'
import { useConfirm } from 'primevue/useconfirm'
import ConfirmDialog from 'primevue/confirmdialog'

const confirm = useConfirm()
const page = usePage()
const { can } = usePermissions()

const props = defineProps({
  benefitTypes: {
    type: Array,
    required: true
  }
})

// Filters
const filters = ref({
  search: '',
  is_active: null
})

const isActiveOptions = [
  { label: 'Tất cả', value: null },
  { label: 'Đang sử dụng', value: 1 },
  { label: 'Ngừng sử dụng', value: 0 }
]

// Dialog state
const dialog = ref(false)
const isEditing = ref(false)
const formData = ref({
  id: null,
  code: '',
  name: '',
  description: '',
  is_active: true
})

// Selection
const selectedBenefitTypes = ref([])

// Search with debounce
let searchTimeout = null
watch(() => filters.value.search, (newVal) => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
})

watch(() => filters.value.is_active, () => {
  applyFilters()
})

const applyFilters = () => {
  BenefitTypeService.index(filters.value)
}

const openCreateDialog = () => {
  isEditing.value = false
  formData.value = {
    id: null,
    code: '',
    name: '',
    description: '',
    is_active: true
  }
  dialog.value = true
}

const openEditDialog = (benefitType) => {
  isEditing.value = true
  formData.value = {
    id: benefitType.id,
    code: benefitType.code,
    name: benefitType.name,
    description: benefitType.description || '',
    is_active: benefitType.is_active
  }
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  formData.value = {
    id: null,
    code: '',
    name: '',
    description: '',
    is_active: true
  }
}

const saveRecord = () => {
  const data = {
    code: formData.value.code,
    name: formData.value.name,
    description: formData.value.description,
    is_active: formData.value.is_active ? 1 : 0
  }

  if (isEditing.value) {
    BenefitTypeService.update(formData.value.id, data, {
      onSuccess: () => {
        closeDialog()
      }
    })
  } else {
    BenefitTypeService.store(data, {
      onSuccess: () => {
        closeDialog()
      }
    })
  }
}

const deleteRecord = (id) => {
  confirm.require({
    message: 'Bạn có chắc chắn muốn xóa loại phúc lợi này?',
    header: 'Xác nhận xóa',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Xóa',
    rejectLabel: 'Hủy',
    accept: () => {
      BenefitTypeService.destroy(id)
    }
  })
}

const bulkDelete = () => {
  if (selectedBenefitTypes.value.length === 0) return

  confirm.require({
    message: `Bạn có chắc chắn muốn xóa ${selectedBenefitTypes.value.length} loại phúc lợi đã chọn?`,
    header: 'Xác nhận xóa',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Xóa',
    rejectLabel: 'Hủy',
    accept: () => {
      const ids = selectedBenefitTypes.value.map(item => item.id)
      BenefitTypeService.bulkDelete(ids, {
        onSuccess: () => {
          selectedBenefitTypes.value = []
        }
      })
    }
  })
}

const getStatusSeverity = (isActive) => {
  return isActive ? 'success' : 'secondary'
}
</script>

<template>
  <Head title="Quản lý loại phúc lợi" />

  <div class="p-6">
    <ConfirmDialog />

      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Quản lý loại phúc lợi</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Danh mục các loại phúc lợi cho nhân viên</p>
      </div>

      <!-- Filters -->
      <div class="card mb-4">
        <Toolbar>
          <template #start>
            <Button label="Thêm mới" icon="pi pi-plus" severity="success" @click="openCreateDialog" class="mr-2" />
            <Button label="Xóa đã chọn" icon="pi pi-trash" severity="danger" @click="bulkDelete" :disabled="!selectedBenefitTypes || selectedBenefitTypes.length === 0" />
          </template>

          <template #end>
            <div class="flex gap-2">
              <Select v-model="filters.is_active" :options="isActiveOptions" optionLabel="label" optionValue="value" placeholder="Trạng thái" class="w-48" />
              <InputText v-model="filters.search" placeholder="Tìm kiếm..." class="w-64" />
            </div>
          </template>
        </Toolbar>
      </div>

      <!-- DataTable -->
      <div class="card">
        <DataTable :value="benefitTypes" v-model:selection="selectedBenefitTypes" dataKey="id" stripedRows showGridlines>
          <Column selectionMode="multiple" headerStyle="width: 3rem" />

          <Column field="code" header="Mã" sortable style="min-width: 120px" />

          <Column field="name" header="Tên loại phúc lợi" sortable style="min-width: 200px" />

          <Column field="description" header="Mô tả" style="min-width: 250px">
            <template #body="{ data }">
              <span class="text-gray-600">{{ data.description || '-' }}</span>
            </template>
          </Column>

          <Column field="payouts_count" header="Số lần chi" sortable style="min-width: 100px; text-align: center">
            <template #body="{ data }">
              <Tag :value="data.payouts_count || 0" severity="info" />
            </template>
          </Column>

          <Column field="is_active" header="Trạng thái" sortable style="min-width: 120px">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Đang dùng' : 'Ngừng dùng'" :severity="getStatusSeverity(data.is_active)" />
            </template>
          </Column>

          <Column v-if="can('manage benefits')" header="Thao tác" style="min-width: 150px">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-pencil" severity="info" text rounded @click="openEditDialog(data)" />
                <Button icon="pi pi-trash" severity="danger" text rounded @click="deleteRecord(data.id)" />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="text-center py-4 text-gray-500">Không có dữ liệu</div>
          </template>
        </DataTable>
      </div>

      <!-- Create/Edit Dialog -->
      <Dialog v-model:visible="dialog" :header="isEditing ? 'Cập nhật loại phúc lợi' : 'Thêm loại phúc lợi mới'" modal :style="{ width: '600px' }">
        <div class="space-y-4 py-4">
          <div>
            <label class="block text-sm font-medium mb-2">Mã <span class="text-red-500">*</span></label>
            <InputText v-model="formData.code" class="w-full" placeholder="VD: BIRTHDAY" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Tên loại phúc lợi <span class="text-red-500">*</span></label>
            <InputText v-model="formData.name" class="w-full" placeholder="VD: Phúc lợi sinh nhật" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Mô tả</label>
            <Textarea v-model="formData.description" class="w-full" rows="4" placeholder="Mô tả chi tiết về loại phúc lợi..." />
          </div>

          <div class="flex items-center gap-2">
            <Checkbox v-model="formData.is_active" inputId="is_active" binary />
            <label for="is_active" class="text-sm font-medium">Đang sử dụng</label>
          </div>
        </div>

        <template #footer>
          <Button label="Hủy" icon="pi pi-times" text @click="closeDialog" />
          <Button label="Lưu" icon="pi pi-check" @click="saveRecord" />
        </template>
      </Dialog>
  </div>
</template>
