<template>
  <div>
    <Toolbar class="mb-4">
      <template #start>
        <Button label="Thêm kinh nghiệm" icon="pi pi-plus" class="mr-2" @click="openNew" />
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
      :value="experiences"
      v-model:selection="selected"
      dataKey="id"
      :paginator="true"
      :rows="10"
      :filters="filters"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[5,10,25]"
      currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} kinh nghiệm"
    >
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <h4 class="m-0">Danh sách Kinh nghiệm</h4>
          <IconField>
            <InputIcon><i class="pi pi-search"/></InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
          </IconField>
        </div>
      </template>

      <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
      <Column field="company_name" header="Công ty" headerStyle="min-width:14rem;" />
      <Column field="position_title" header="Chức danh" headerStyle="min-width:12rem;" />
      <Column field="start_date" header="Bắt đầu" headerStyle="min-width:10rem;">
        <template #body="slotProps">{{ formatDate(slotProps.data.start_date) }}</template>
      </Column>
      <Column field="end_date" header="Kết thúc" headerStyle="min-width:10rem;">
        <template #body="slotProps">{{ slotProps.data.is_current ? 'Hiện tại' : formatDate(slotProps.data.end_date) }}</template>
      </Column>
      <Column field="is_current" header="Đang làm" headerStyle="min-width:8rem;">
        <template #body="slotProps">
          <i :class="slotProps.data.is_current ? 'pi pi-check text-green-500' : 'pi pi-minus text-gray-400'"/>
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

    <!-- Dialog Kinh nghiệm -->
    <Dialog v-model:visible="dialog" :style="{ width: '700px' }" header="Thông tin Kinh nghiệm" :modal="true">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-bold mb-2">Công ty</label>
          <InputText v-model="form.company_name" class="w-full" />
        </div>
        <div>
          <label class="block font-bold mb-2">Chức danh</label>
          <InputText v-model="form.position_title" class="w-full" />
        </div>
        <div>
          <label class="block font-bold mb-2">Bắt đầu</label>
          <DatePicker v-model="form.start_date" dateFormat="yy-mm-dd" showIcon fluid />
        </div>
        <div>
          <label class="block font-bold mb-2">Kết thúc</label>
          <DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" showIcon fluid :disabled="form.is_current" />
        </div>
        <div class="md:col-span-2 flex items-center gap-2">
          <Checkbox v-model="form.is_current" :binary="true" />
          <span>Hiện tại</span>
        </div>
        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Mô tả công việc</label>
          <Textarea v-model="form.responsibilities" autoResize rows="3" class="w-full" />
        </div>
        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Thành tích</label>
          <Textarea v-model="form.achievements" autoResize rows="3" class="w-full" />
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
        <span>Bạn có chắc muốn xóa kinh nghiệm này?</span>
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
import { EmployeeExperienceService } from '@/services'
import { toYMD, formatDate } from '@/utils/dateHelper'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import Checkbox from 'primevue/checkbox'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const props = defineProps({
  employeeId: { type: String, required: true },
  experiences: { type: Array, default: () => [] },
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

const form = ref({
  id: null,
  company_name: '',
  position_title: '',
  start_date: null,
  end_date: null,
  is_current: false,
  responsibilities: '',
  achievements: ''
})

function resetForm() {
  form.value = {
    id: null,
    company_name: '',
    position_title: '',
    start_date: null,
    end_date: null,
    is_current: false,
    responsibilities: '',
    achievements: ''
  }
}

function openNew() {
  resetForm()
  dialog.value = true
}

function openEdit(row) {
  form.value = { ...row }
  dialog.value = true
}

function save() {
  saving.value = true
  const payload = {
    ...form.value,
    start_date: toYMD(form.value.start_date),
    end_date: toYMD(form.value.end_date)
  }
  const opts = {
    onFinish: () => saving.value = false,
    onSuccess: () => { dialog.value = false }
  }
  if (!form.value.id) {
    EmployeeExperienceService.store(props.employeeId, payload, opts)
  } else {
    EmployeeExperienceService.update(props.employeeId, form.value.id, payload, opts)
  }
}

function confirmDelete(row) {
  currentItem.value = row
  deleteDialog.value = true
}

function remove() {
  deleting.value = true
  EmployeeExperienceService.destroy(props.employeeId, currentItem.value.id, {
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
  EmployeeExperienceService.bulkDelete(props.employeeId, ids, {
    onFinish: () => {
      deleting.value = false
      deleteManyDialog.value = false
      selected.value = []
    }
  })
}
</script>
