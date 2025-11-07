<template>
  <Head>
    <title>Hồ sơ nhân viên - {{ props.employee.full_name }}</title>
  </Head>

  <div class="card">
    <div class="mb-4">
      <h2 class="text-xl font-semibold">Hồ sơ: {{ props.employee.full_name }} ({{ props.employee.employee_code }})</h2>
    </div>

    <Tabs value="education">
      <TabList>
        <Tab value="education">Học vấn</Tab>
        <Tab value="relatives">Người thân</Tab>
        <Tab value="experiences">Kinh nghiệm</Tab>
        <Tab value="skills">Kỹ năng</Tab>
      </TabList>

      <!-- TAB HỌC VẤN -->
      <TabPanel value="education">
        <Toolbar class="mb-4">
          <template #start>
            <Button label="Thêm học vấn" icon="pi pi-plus" class="mr-2" @click="openEduNew" />
            <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined"
              @click="confirmEduDeleteSelected" :disabled="!selectedEdu || !selectedEdu.length" />
          </template>
          <template #end>
            <Button label="Xuất dữ liệu" icon="pi pi-upload" severity="secondary" @click="exportEduCSV" />
          </template>
        </Toolbar>

        <DataTable
          ref="eduDt"
          :value="eduRows"
          v-model:selection="selectedEdu"
          dataKey="id"
          :paginator="true" :rows="10"
          :filters="eduFilters"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          :rowsPerPageOptions="[5,10,25]"
          currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} học vấn"
        >
          <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
              <h4 class="m-0">Danh sách Học vấn</h4>
              <IconField>
                <InputIcon><i class="pi pi-search" /></InputIcon>
                <InputText v-model="eduFilters['global'].value" placeholder="Tìm kiếm..." />
              </IconField>
            </div>
          </template>

          <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
          <Column field="education_level.name" header="Trình độ" headerStyle="min-width:12rem;">
            <template #body="sp">{{ sp.data.education_level?.name || '-' }}</template>
          </Column>
          <Column field="school.name" header="Trường" headerStyle="min-width:12rem;">
            <template #body="sp">{{ sp.data.school?.name || '-' }}</template>
          </Column>
          <Column field="major" header="Chuyên ngành" headerStyle="min-width:12rem;">
            <template #body="sp">{{ sp.data.major || '-' }}</template>
          </Column>
          <Column field="start_year" header="Từ năm" sortable headerStyle="width:8rem;">
            <template #body="sp">{{ sp.data.start_year || '-' }}</template>
          </Column>
          <Column field="end_year" header="Đến năm" sortable headerStyle="width:8rem;">
            <template #body="sp">{{ sp.data.end_year || '-' }}</template>
          </Column>
          <Column field="grade" header="Xếp loại" headerStyle="min-width:10rem;">
            <template #body="sp">{{ sp.data.grade || '-' }}</template>
          </Column>
          <Column headerStyle="min-width:10rem;">
            <template #body="sp">
              <Button icon="pi pi-pencil" class="mr-2" outlined severity="success" rounded @click="openEduEdit(sp.data)" />
              <Button icon="pi pi-trash" class="mt-2" outlined severity="danger" rounded @click="confirmEduDelete(sp.data)" />
            </template>
          </Column>
        </DataTable>

        <!-- Dialog Học vấn -->
        <Dialog v-model:visible="eduDialog" :style="{ width: '600px' }" header="Thông tin Học vấn" :modal="true">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block font-bold mb-2">Trình độ</label>
              <Select v-model="eduForm.education_level_id" :options="props.education_levels" optionLabel="name" optionValue="id" filter showClear fluid />
            </div>
            <div>
              <label class="block font-bold mb-2">Trường</label>
              <Select v-model="eduForm.school_id" :options="props.schools" optionLabel="name" optionValue="id" filter showClear fluid />
            </div>
            <div>
              <label class="block font-bold mb-2">Chuyên ngành</label>
              <InputText v-model.trim="eduForm.major" class="w-full" />
            </div>
            <div>
              <label class="block font-bold mb-2">Hình thức học</label>
              <Select v-model="eduForm.study_form"
                      :options="studyFormOptions" optionLabel="label" optionValue="value" filter showClear fluid />
            </div>
            <div>
              <label class="block font-bold mb-2">Từ năm</label>
              <InputText v-model.number="eduForm.start_year" class="w-full" placeholder="VD: 2018" />
            </div>
            <div>
              <label class="block font-bold mb-2">Đến năm</label>
              <InputText v-model.number="eduForm.end_year" class="w-full" placeholder="VD: 2022" />
            </div>
            <div>
              <label class="block font-bold mb-2">Số hiệu văn bằng</label>
              <InputText v-model.trim="eduForm.certificate_no" class="w-full" />
            </div>
            <div>
              <label class="block font-bold mb-2">Ngày tốt nghiệp</label>
              <DatePicker v-model="eduForm.graduation_date" dateFormat="yy-mm-dd" showIcon fluid />
            </div>
            <div class="md:col-span-2">
              <label class="block font-bold mb-2">Xếp loại</label>
              <InputText v-model.trim="eduForm.grade" class="w-full" />
            </div>
            <div class="md:col-span-2">
              <label class="block font-bold mb-2">Ghi chú</label>
              <Textarea v-model.trim="eduForm.note" autoResize rows="3" class="w-full" />
            </div>
          </div>
          <template #footer>
            <Button label="Hủy" icon="pi pi-times" text @click="closeEduDialog" />
            <Button label="Lưu" icon="pi pi-check" @click="saveEducation" :loading="savingEdu" />
          </template>
        </Dialog>

        <!-- Dialog xóa -->
        <Dialog v-model:visible="eduDeleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
          <div class="flex items-center gap-4">
            <i class="pi pi-exclamation-triangle !text-3xl" />
            <span v-if="currentEdu">Bạn có chắc muốn xóa học vấn này?</span>
          </div>
          <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="eduDeleteDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeEducation" :loading="deletingEdu" />
          </template>
        </Dialog>

        <!-- Xóa nhiều -->
        <Dialog v-model:visible="eduDeleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
          <div class="flex items-center gap-4">
            <i class="pi pi-exclamation-triangle !text-3xl" />
            <span>Bạn có chắc muốn xóa các bản ghi đã chọn?</span>
          </div>
          <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="eduDeleteManyDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeManyEducation" :loading="deletingEdu" />
          </template>
        </Dialog>
      </TabPanel>

      <!-- 3 Tab còn lại: để bạn fill tương tự (Relatives / Experiences / Skills) -->
      <TabPanel value="relatives">
        <div class="text-gray-500">Tab Người thân — cấu trúc CRUD tương tự, field: họ tên, quan hệ, ngày sinh, SĐT, địa chỉ, liên hệ khẩn cấp…</div>
      </TabPanel>
      <TabPanel value="experiences">
        <div class="text-gray-500">Tab Kinh nghiệm — công ty, chức danh, thời gian, mô tả, thành tích…</div>
      </TabPanel>
      <TabPanel value="skills">
        <div class="text-gray-500">Tab Kỹ năng — chọn kỹ năng từ danh mục, mức độ (0-5), số năm, ghi chú…</div>
      </TabPanel>
    </Tabs>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { EmployeeEducationService } from '@/services'
import { toYMD, formatDate } from '@/utils/dateHelper'

// PrimeVue imports
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanel from 'primevue/tabpanel'

const props = defineProps({
  employee: { type: Object, required: true },
  education_levels: { type: Array, required: true },
  schools: { type: Array, required: true },
  educations: { type: Array, required: true },
})

// ====== Tab EDUCATION ======
const eduDt = ref()
const eduRows = computed(() => props.educations || [])
const selectedEdu = ref([])
const eduFilters = ref({ global: { value: null, matchMode: 'contains' } })

const eduDialog = ref(false)
const eduDeleteDialog = ref(false)
const eduDeleteManyDialog = ref(false)
const currentEdu = ref(null)
const savingEdu = ref(false)
const deletingEdu = ref(false)
const studyFormOptions = [
  { value: 'FULLTIME', label: 'Chính quy' },
  { value: 'PARTTIME', label: 'Vừa học vừa làm' },
  { value: 'ONLINE', label: 'Trực tuyến' },
]

const eduForm = ref({
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

function openEduNew(){ resetEduForm(); eduDialog.value = true }
function openEduEdit(row){
  eduForm.value = {
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
  eduDialog.value = true
}
function closeEduDialog(){ eduDialog.value = false }
function resetEduForm(){
  eduForm.value = {
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

function exportEduCSV(){ eduDt.value?.exportCSV() }

function saveEducation(){
  savingEdu.value = true
  const payload = {
    ...eduForm.value,
    graduation_date: toYMD(eduForm.value.graduation_date)
  }
  const opts = {
    onFinish: () => savingEdu.value = false,
    onSuccess: () => { eduDialog.value = false }
  }
  if (!eduForm.value.id) {
    EmployeeEducationService.store(props.employee.id, payload, opts)
  } else {
    EmployeeEducationService.update(props.employee.id, eduForm.value.id, payload, opts)
  }
}

function confirmEduDelete(row){ currentEdu.value = row; eduDeleteDialog.value = true }
function removeEducation(){
  deletingEdu.value = true
  EmployeeEducationService.destroy(props.employee.id, currentEdu.value.id, {
    onFinish: () => { deletingEdu.value = false; eduDeleteDialog.value = false }
  })
}
function confirmEduDeleteSelected(){ eduDeleteManyDialog.value = true }
function removeManyEducation(){
  const ids = selectedEdu.value.map(x=>x.id)
  deletingEdu.value = true
  EmployeeEducationService.bulkDelete(props.employee.id, ids, {
    onFinish: () => { deletingEdu.value = false; eduDeleteManyDialog.value = false; selectedEdu.value = [] }
  })
}
</script>
