<template>
  <div>
    <Toolbar class="mb-4">
      <template #start>
        <Button label="Thêm người thân" icon="pi pi-plus" class="mr-2" @click="openNew" />
        <Button
          label="Xóa"
          icon="pi pi-trash"
          severity="danger"
          variant="outlined"
          @click="confirmDeleteSelected"
          :disabled="!selected || !selected.length"
        />
      </template>
    </Toolbar>

    <DataTable
      ref="dt"
      :value="relatives"
      v-model:selection="selected"
      dataKey="id"
      :paginator="true"
      :rows="10"
      :filters="filters"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[5,10,25]"
      currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} người thân"
    >
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <h4 class="m-0">Danh sách Người thân</h4>
          <IconField>
            <InputIcon><i class="pi pi-search"/></InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
          </IconField>
        </div>
      </template>

      <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
      <Column field="full_name" header="Họ tên" headerStyle="min-width:12rem;" />
      <Column field="relation" header="Quan hệ" headerStyle="min-width:10rem;">
        <template #body="slotProps">{{ getRelationLabel(slotProps.data.relation) }}</template>
      </Column>
      <Column field="dob" header="Ngày sinh" headerStyle="min-width:10rem;">
        <template #body="slotProps">{{ formatDate(slotProps.data.dob) }}</template>
      </Column>
      <Column field="phone" header="SĐT" headerStyle="min-width:10rem;" />
      <Column field="is_emergency_contact" header="Liên hệ khẩn cấp" headerStyle="min-width:10rem;">
        <template #body="slotProps">
          <i :class="slotProps.data.is_emergency_contact ? 'pi pi-check text-green-500' : 'pi pi-minus text-gray-400'"/>
        </template>
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

    <!-- Dialog Người thân -->
    <Dialog v-model:visible="dialog" :style="{ width: '600px' }" header="Thông tin Người thân" :modal="true">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-bold mb-2">Họ tên</label>
          <InputText v-model="form.full_name" class="w-full" />
        </div>
        <div>
          <label class="block font-bold mb-2">Quan hệ</label>
          <Select
            v-model="form.relation"
            :options="relationOptions"
            optionLabel="label"
            optionValue="value"
            showClear
            fluid
          />
        </div>
        <div>
          <label class="block font-bold mb-2">Ngày sinh</label>
          <DatePicker v-model="form.dob" dateFormat="yy-mm-dd" showIcon fluid />
        </div>
        <div>
          <label class="block font-bold mb-2">SĐT</label>
          <InputText v-model="form.phone" class="w-full" />
        </div>
        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Nghề nghiệp</label>
          <InputText v-model="form.occupation" class="w-full" />
        </div>
        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Địa chỉ</label>
          <InputText v-model="form.address" class="w-full" />
        </div>
        <div class="md:col-span-2 flex items-center gap-2">
          <Checkbox v-model="form.is_emergency_contact" :binary="true" />
          <span>Đặt làm liên hệ khẩn cấp</span>
        </div>
        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Ghi chú</label>
          <Textarea v-model="form.note" autoResize rows="3" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="dialog=false" />
        <Button label="Lưu" icon="pi pi-check" @click="save" :loading="saving" />
      </template>
    </Dialog>

    <!-- Dialog xóa -->
    <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span>Bạn có chắc muốn xóa người thân này?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="remove" :loading="deleting" />
      </template>
    </Dialog>

    <!-- Xóa nhiều -->
    <Dialog v-model:visible="deleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span>Bạn có chắc muốn xóa các bản ghi đã chọn?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteManyDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="removeMany" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { EmployeeRelativeService } from '@/services'
import { toYMD, formatDate } from '@/utils/dateHelper'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Checkbox from 'primevue/checkbox'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const props = defineProps({
  employeeId: { type: String, required: true },
  relatives: { type: Array, default: () => [] },
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

const relationOptions = [
  { value:'FATHER', label:'Cha' },
  { value:'MOTHER', label:'Mẹ' },
  { value:'SPOUSE', label:'Vợ/Chồng' },
  { value:'CHILD', label:'Con' },
  { value:'SIBLING', label:'Anh/Chị/Em' },
  { value:'OTHER', label:'Khác' },
]

const getRelationLabel = (value) => {
  return relationOptions.find(x => x.value === value)?.label || value
}

const form = ref({
  id: null,
  full_name: '',
  relation: null,
  dob: null,
  phone: '',
  occupation: '',
  address: '',
  is_emergency_contact: false,
  note: ''
})

function resetForm() {
  form.value = {
    id: null,
    full_name: '',
    relation: null,
    dob: null,
    phone: '',
    occupation: '',
    address: '',
    is_emergency_contact: false,
    note: ''
  }
}

function openNew() {
  resetForm()
  dialog.value = true
}

function openEdit(row) {
  form.value = {
    ...row,
    dob: row.dob ? new Date(row.dob) : null
  }
  dialog.value = true
}

function save() {
  saving.value = true
  const payload = {
    ...form.value,
    dob: toYMD(form.value.dob)
  }
  const opts = {
    onFinish: () => saving.value = false,
    onSuccess: () => { dialog.value = false }
  }
  if (!form.value.id) {
    EmployeeRelativeService.store(props.employeeId, payload, opts)
  } else {
    EmployeeRelativeService.update(props.employeeId, form.value.id, payload, opts)
  }
}

function confirmDelete(row) {
  currentItem.value = row
  deleteDialog.value = true
}

function remove() {
  deleting.value = true
  EmployeeRelativeService.destroy(props.employeeId, currentItem.value.id, {
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
  EmployeeRelativeService.bulkDelete(props.employeeId, ids, {
    onFinish: () => {
      deleting.value = false
      deleteManyDialog.value = false
      selected.value = []
    }
  })
}
</script>
