<template>
  <div>
    <Toolbar class="mb-4">
      <template #start>
        <Button label="Thêm học vấn" icon="pi pi-plus" class="mr-2" @click="openNew" />
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
      :value="educations"
      v-model:selection="selected"
      dataKey="id"
      :paginator="true"
      :rows="10"
      :filters="filters"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[5,10,25]"
      currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} học vấn"
    >
      <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
          <h4 class="m-0">Danh sách Học vấn</h4>
          <IconField>
            <InputIcon><i class="pi pi-search" /></InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
          </IconField>
        </div>
      </template>

      <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
      <Column field="education_level.name" header="Trình độ" headerStyle="min-width:12rem;">
        <template #body="slotProps">{{ slotProps.data.education_level?.name || '-' }}</template>
      </Column>
      <Column field="school.name" header="Trường" headerStyle="min-width:12rem;">
        <template #body="slotProps">{{ slotProps.data.school?.name || '-' }}</template>
      </Column>
      <Column field="major" header="Chuyên ngành" headerStyle="min-width:12rem;">
        <template #body="slotProps">{{ slotProps.data.major || '-' }}</template>
      </Column>
      <Column field="start_year" header="Từ năm" sortable headerStyle="width:8rem;">
        <template #body="slotProps">{{ slotProps.data.start_year || '-' }}</template>
      </Column>
      <Column field="end_year" header="Đến năm" sortable headerStyle="width:8rem;">
        <template #body="slotProps">{{ slotProps.data.end_year || '-' }}</template>
      </Column>
      <Column field="grade" header="Xếp loại" headerStyle="min-width:10rem;">
        <template #body="slotProps">{{ slotProps.data.grade || '-' }}</template>
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

    <!-- Dialog Học vấn -->
    <Dialog v-model:visible="dialog" :style="{ width: '600px' }" header="Thông tin Học vấn" :modal="true">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-bold mb-2">Trình độ</label>
          <Select
            v-model="form.education_level_id"
            :options="educationLevels"
            optionLabel="name"
            optionValue="id"
            filter
            showClear
            fluid
          />
        </div>
        <div>
          <label class="block font-bold mb-2">Trường</label>
          <Select
            v-model="form.school_id"
            :options="schools"
            optionLabel="name"
            optionValue="id"
            filter
            showClear
            fluid
          />
        </div>
        <div>
          <label class="block font-bold mb-2">Chuyên ngành</label>
          <InputText v-model="form.major" class="w-full" />
        </div>
        <div>
          <label class="block font-bold mb-2">Hình thức học</label>
          <Select
            v-model="form.study_form"
            :options="studyFormOptions"
            optionLabel="label"
            optionValue="value"
            filter
            showClear
            fluid
          />
        </div>
        <div>
          <label class="block font-bold mb-2">Từ năm</label>
          <InputText v-model.number="form.start_year" class="w-full" placeholder="VD: 2018" />
        </div>
        <div>
          <label class="block font-bold mb-2">Đến năm</label>
          <InputText v-model.number="form.end_year" class="w-full" placeholder="VD: 2022" />
        </div>
        <div>
          <label class="block font-bold mb-2">Số hiệu văn bằng</label>
          <InputText v-model="form.certificate_no" class="w-full" />
        </div>
        <div>
          <label class="block font-bold mb-2">Ngày tốt nghiệp</label>
          <DatePicker v-model="form.graduation_date" dateFormat="yy-mm-dd" showIcon fluid />
        </div>
        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Xếp loại</label>
          <InputText v-model="form.grade" class="w-full" />
        </div>
        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Ghi chú</label>
          <Textarea v-model="form.note" autoResize rows="3" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="closeDialog" />
        <Button label="Lưu" icon="pi pi-check" @click="save" :loading="saving" />
      </template>
    </Dialog>

    <!-- Dialog xóa -->
    <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span v-if="currentItem">Bạn có chắc muốn xóa học vấn này?</span>
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
import { EmployeeEducationService } from '@/services'
import { toYMD } from '@/utils/dateHelper'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const props = defineProps({
  employeeId: { type: String, required: true },
  educations: { type: Array, default: () => [] },
  educationLevels: { type: Array, default: () => [] },
  schools: { type: Array, default: () => [] },
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

const studyFormOptions = [
  { value: 'FULLTIME', label: 'Chính quy' },
  { value: 'PARTTIME', label: 'Vừa học vừa làm' },
  { value: 'ONLINE', label: 'Trực tuyến' },
]

const form = ref({
  id: null,
  education_level_id: null,
  school_id: null,
  major: '',
  start_year: null,
  end_year: null,
  study_form: null,
  certificate_no: '',
  graduation_date: null,
  grade: '',
  note: '',
})

function resetForm() {
  form.value = {
    id: null,
    education_level_id: null,
    school_id: null,
    major: '',
    start_year: null,
    end_year: null,
    study_form: null,
    certificate_no: '',
    graduation_date: null,
    grade: '',
    note: '',
  }
}

function openNew() {
  resetForm()
  dialog.value = true
}

function openEdit(row) {
  form.value = {
    id: row.id,
    education_level_id: row.education_level_id,
    school_id: row.school_id,
    major: row.major,
    start_year: row.start_year,
    end_year: row.end_year,
    study_form: row.study_form,
    certificate_no: row.certificate_no,
    graduation_date: row.graduation_date,
    grade: row.grade,
    note: row.note,
  }
  dialog.value = true
}

function closeDialog() {
  dialog.value = false
}

function exportCSV() {
  dt.value?.exportCSV()
}

function save() {
  saving.value = true
  const payload = {
    ...form.value,
    graduation_date: toYMD(form.value.graduation_date)
  }
  const opts = {
    onFinish: () => saving.value = false,
    onSuccess: () => { dialog.value = false }
  }
  if (!form.value.id) {
    EmployeeEducationService.store(props.employeeId, payload, opts)
  } else {
    EmployeeEducationService.update(props.employeeId, form.value.id, payload, opts)
  }
}

function confirmDelete(row) {
  currentItem.value = row
  deleteDialog.value = true
}

function remove() {
  deleting.value = true
  EmployeeEducationService.destroy(props.employeeId, currentItem.value.id, {
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
  EmployeeEducationService.bulkDelete(props.employeeId, ids, {
    onFinish: () => {
      deleting.value = false
      deleteManyDialog.value = false
      selected.value = []
    }
  })
}
</script>
