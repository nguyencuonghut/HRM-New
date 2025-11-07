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
        <Toolbar class="mb-4">
            <template #start>
            <Button label="Thêm người thân" icon="pi pi-plus" class="mr-2" @click="openRelNew" />
            <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined"
                @click="confirmRelDeleteSelected" :disabled="!relSelected || !relSelected.length" />
            </template>
        </Toolbar>

        <DataTable
            ref="relDt"
            :value="relRows"
            v-model:selection="relSelected"
            dataKey="id"
            :paginator="true" :rows="10"
            :filters="relFilters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5,10,25]"
            currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} người thân"
        >
            <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Danh sách Người thân</h4>
                <IconField><InputIcon><i class="pi pi-search"/></InputIcon><InputText v-model="relFilters['global'].value" placeholder="Tìm kiếm..." /></IconField>
            </div>
            </template>

            <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
            <Column field="full_name" header="Họ tên" headerStyle="min-width:12rem;" />
            <Column field="relation" header="Quan hệ" headerStyle="min-width:10rem;">
            <template #body="sp">{{ relLabel(sp.data.relation) }}</template>
            </Column>
            <Column field="dob" header="Ngày sinh" headerStyle="min-width:10rem;">
            <template #body="sp">{{ formatDate(sp.data.dob) }}</template>
            </Column>
            <Column field="phone" header="SĐT" headerStyle="min-width:10rem;" />
            <Column field="is_emergency_contact" header="Liên hệ khẩn cấp" headerStyle="min-width:10rem;">
            <template #body="sp"><i :class="sp.data.is_emergency_contact ? 'pi pi-check text-green-500' : 'pi pi-minus text-gray-400'"/></template>
            </Column>
            <Column headerStyle="min-width:10rem;">
            <template #body="sp">
                <Button icon="pi pi-pencil" class="mr-2" outlined severity="success" rounded @click="openRelEdit(sp.data)" />
                <Button icon="pi pi-trash" class="mt-2" outlined severity="danger" rounded @click="confirmRelDelete(sp.data)" />
            </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="relDialog" :style="{ width: '600px' }" header="Thông tin Người thân" :modal="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block font-bold mb-2">Họ tên</label><InputText v-model.trim="relForm.full_name" class="w-full" /></div>
            <div>
                <label class="block font-bold mb-2">Quan hệ</label>
                <Select v-model="relForm.relation" :options="relationOptions" optionLabel="label" optionValue="value" showClear fluid />
            </div>
            <div><label class="block font-bold mb-2">Ngày sinh</label><DatePicker v-model="relForm.dob" dateFormat="yy-mm-dd" showIcon fluid /></div>
            <div><label class="block font-bold mb-2">SĐT</label><InputText v-model.trim="relForm.phone" class="w-full" /></div>
            <div class="md:col-span-2"><label class="block font-bold mb-2">Nghề nghiệp</label><InputText v-model.trim="relForm.occupation" class="w-full" /></div>
            <div class="md:col-span-2"><label class="block font-bold mb-2">Địa chỉ</label><InputText v-model.trim="relForm.address" class="w-full" /></div>
            <div class="md:col-span-2 flex items-center gap-2">
                <Checkbox v-model="relForm.is_emergency_contact" :binary="true" /> <span>Đặt làm liên hệ khẩn cấp</span>
            </div>
            <div class="md:col-span-2"><label class="block font-bold mb-2">Ghi chú</label><Textarea v-model.trim="relForm.note" autoResize rows="3" class="w-full" /></div>
            </div>
            <template #footer>
            <Button label="Hủy" icon="pi pi-times" text @click="relDialog=false" />
            <Button label="Lưu" icon="pi pi-check" @click="saveRelative" :loading="savingRel" />
            </template>
        </Dialog>

        <Dialog v-model:visible="relDeleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa người thân này?</span></div>
            <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="relDeleteDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeRelative" :loading="deletingRel" />
            </template>
        </Dialog>

        <Dialog v-model:visible="relDeleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa các bản ghi đã chọn?</span></div>
            <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="relDeleteManyDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeManyRelative" :loading="deletingRel" />
            </template>
        </Dialog>
      </TabPanel>

      <TabPanel value="experiences">
        <Toolbar class="mb-4">
            <template #start>
            <Button label="Thêm kinh nghiệm" icon="pi pi-plus" class="mr-2" @click="openExpNew" />
            <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined"
                @click="confirmExpDeleteSelected" :disabled="!expSelected || !expSelected.length" />
            </template>
        </Toolbar>

        <DataTable
            ref="expDt"
            :value="expRows"
            v-model:selection="expSelected"
            dataKey="id"
            :paginator="true" :rows="10"
            :filters="expFilters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5,10,25]"
            currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} kinh nghiệm"
        >
            <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Danh sách Kinh nghiệm</h4>
                <IconField><InputIcon><i class="pi pi-search"/></InputIcon><InputText v-model="expFilters['global'].value" placeholder="Tìm kiếm..." /></IconField>
            </div>
            </template>

            <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
            <Column field="company_name" header="Công ty" headerStyle="min-width:14rem;" />
            <Column field="position_title" header="Chức danh" headerStyle="min-width:12rem;" />
            <Column field="start_date" header="Bắt đầu" headerStyle="min-width:10rem;">
            <template #body="sp">{{ formatDate(sp.data.start_date) }}</template>
            </Column>
            <Column field="end_date" header="Kết thúc" headerStyle="min-width:10rem;">
            <template #body="sp">{{ sp.data.is_current ? 'Hiện tại' : formatDate(sp.data.end_date) }}</template>
            </Column>
            <Column field="is_current" header="Đang làm" headerStyle="min-width:8rem;">
            <template #body="sp"><i :class="sp.data.is_current ? 'pi pi-check text-green-500' : 'pi pi-minus text-gray-400'"/></template>
            </Column>
            <Column headerStyle="min-width:10rem;">
            <template #body="sp">
                <Button icon="pi pi-pencil" class="mr-2" outlined severity="success" rounded @click="openExpEdit(sp.data)" />
                <Button icon="pi pi-trash" class="mt-2" outlined severity="danger" rounded @click="confirmExpDelete(sp.data)" />
            </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="expDialog" :style="{ width: '700px' }" header="Thông tin Kinh nghiệm" :modal="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block font-bold mb-2">Công ty</label><InputText v-model.trim="expForm.company_name" class="w-full" /></div>
            <div><label class="block font-bold mb-2">Chức danh</label><InputText v-model.trim="expForm.position_title" class="w-full" /></div>
            <div><label class="block font-bold mb-2">Bắt đầu</label><DatePicker v-model="expForm.start_date" dateFormat="yy-mm-dd" showIcon fluid /></div>
            <div><label class="block font-bold mb-2">Kết thúc</label><DatePicker v-model="expForm.end_date" dateFormat="yy-mm-dd" showIcon fluid :disabled="expForm.is_current" /></div>
            <div class="md:col-span-2 flex items-center gap-2"><Checkbox v-model="expForm.is_current" :binary="true" /> <span>Hiện tại</span></div>
            <div class="md:col-span-2"><label class="block font-bold mb-2">Mô tả công việc</label><Textarea v-model.trim="expForm.responsibilities" autoResize rows="3" class="w-full" /></div>
            <div class="md:col-span-2"><label class="block font-bold mb-2">Thành tích</label><Textarea v-model.trim="expForm.achievements" autoResize rows="3" class="w-full" /></div>
            </div>
            <template #footer>
            <Button label="Hủy" icon="pi pi-times" text @click="expDialog=false" />
            <Button label="Lưu" icon="pi pi-check" @click="saveExperience" :loading="savingExp" />
            </template>
        </Dialog>

        <Dialog v-model:visible="expDeleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa kinh nghiệm này?</span></div>
            <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="expDeleteDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeExperience" :loading="deletingExp" />
            </template>
        </Dialog>

        <Dialog v-model:visible="expDeleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa các bản ghi đã chọn?</span></div>
            <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="expDeleteManyDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeManyExperience" :loading="deletingExp" />
            </template>
        </Dialog>
      </TabPanel>

      <TabPanel value="skills">
        <Toolbar class="mb-4">
            <template #start>
            <Button label="Thêm kỹ năng" icon="pi pi-plus" class="mr-2" @click="openSkillNew" />
            <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined"
                @click="confirmSkillDeleteSelected" :disabled="!skillSelected || !skillSelected.length" />
            </template>
        </Toolbar>

        <DataTable
            ref="skillDt"
            :value="skillRows"
            v-model:selection="skillSelected"
            dataKey="id"
            :paginator="true" :rows="10"
            :filters="skillFilters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5,10,25]"
            currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} kỹ năng"
        >
            <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Kỹ năng của nhân viên</h4>
                <IconField><InputIcon><i class="pi pi-search"/></InputIcon><InputText v-model="skillFilters['global'].value" placeholder="Tìm kiếm..." /></IconField>
            </div>
            </template>

            <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
            <Column field="skill_name" header="Kỹ năng" headerStyle="min-width:14rem;"></Column>
            <Column field="level" header="Mức (0-5)" headerStyle="min-width:8rem;"></Column>
            <Column field="years" header="Số năm" headerStyle="min-width:8rem;"></Column>
            <Column field="note" header="Ghi chú" headerStyle="min-width:12rem;"></Column>
            <Column headerStyle="min-width:10rem;">
            <template #body="sp">
                <Button icon="pi pi-pencil" class="mr-2" outlined severity="success" rounded @click="openSkillEdit(sp.data)" />
                <Button icon="pi pi-trash" class="mt-2" outlined severity="danger" rounded @click="confirmSkillDelete(sp.data)" />
            </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="skillDialog" :style="{ width: '600px' }" header="Thông tin Kỹ năng" :modal="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-bold mb-2">Kỹ năng</label>
                <Select v-model="skillForm.skill_id" :options="props.skills" optionLabel="name" optionValue="id" showClear filter fluid />
            </div>
            <div><label class="block font-bold mb-2">Mức (0-5)</label><InputText v-model.number="skillForm.level" type="number" min="0" max="5" class="w-full" /></div>
            <div><label class="block font-bold mb-2">Số năm</label><InputText v-model.number="skillForm.years" type="number" min="0" class="w-full" /></div>
            <div class="md:col-span-2"><label class="block font-bold mb-2">Ghi chú</label><Textarea v-model.trim="skillForm.note" autoResize rows="3" class="w-full" /></div>
            </div>
            <template #footer>
            <Button label="Hủy" icon="pi pi-times" text @click="skillDialog=false" />
            <Button label="Lưu" icon="pi pi-check" @click="saveSkill" :loading="savingSkill" />
            </template>
        </Dialog>

        <Dialog v-model:visible="skillDeleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa kỹ năng này?</span></div>
            <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="skillDeleteDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeSkill" :loading="deletingSkill" />
            </template>
        </Dialog>

        <Dialog v-model:visible="skillDeleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa các kỹ năng đã chọn?</span></div>
            <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="skillDeleteManyDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeManySkill" :loading="deletingSkill" />
            </template>
        </Dialog>
      </TabPanel>

    </Tabs>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { EmployeeEducationService } from '@/services'
import { EmployeeRelativeService } from '@/services'
import { EmployeeExperienceService } from '@/services'
import { EmployeeSkillService } from '@/services'
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
  relatives: { type: Array, default: () => [] },
  experiences: { type: Array, default: () => [] },
  skills: { type: Array, default: () => [] },           // master danh mục
  employee_skills: { type: Array, default: () => [] },  // kỹ năng của NV
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

/* ===== Relatives state & methods ===== */
const relDt = ref()
const relRows = computed(()=> props.relatives || [])
const relSelected = ref([])
const relFilters = ref({ global: { value: null, matchMode: 'contains' } })
const relDialog = ref(false)
const relDeleteDialog = ref(false)
const relDeleteManyDialog = ref(false)
const currentRel = ref(null)
const savingRel = ref(false)
const deletingRel = ref(false)

const relationOptions = [
  { value:'FATHER', label:'Cha' },{ value:'MOTHER', label:'Mẹ' },
  { value:'SPOUSE', label:'Vợ/Chồng' },{ value:'CHILD', label:'Con' },
  { value:'SIBLING', label:'Anh/Chị/Em' },{ value:'OTHER', label:'Khác' },
]
const relLabel = (v)=> (relationOptions.find(x=>x.value===v)?.label || v)

const relForm = ref({
  id:null, full_name:'', relation:null, dob:null, phone:'', occupation:'', address:'',
  is_emergency_contact:false, note:''
})

function openRelNew(){ relForm.value = { id:null, full_name:'', relation:null, dob:null, phone:'', occupation:'', address:'', is_emergency_contact:false, note:'' }; relDialog.value=true }
function openRelEdit(r){
  relForm.value = {
    ...r,
    dob: r.dob ? new Date(r.dob) : null
  }
  relDialog.value=true
}
function saveRelative(){
  savingRel.value = true
  const payload = { ...relForm.value, dob: toYMD(relForm.value.dob) }
  const opts = { onFinish:()=> savingRel.value=false, onSuccess:()=>{ relDialog.value=false } }
  if (!relForm.value.id) EmployeeRelativeService.store(props.employee.id, payload, opts)
  else EmployeeRelativeService.update(props.employee.id, relForm.value.id, payload, opts)
}
function confirmRelDelete(r){ currentRel.value=r; relDeleteDialog.value=true }
function removeRelative(){
  deletingRel.value=true
  EmployeeRelativeService.destroy(props.employee.id, currentRel.value.id, { onFinish:()=>{ deletingRel.value=false; relDeleteDialog.value=false } })
}
function confirmRelDeleteSelected(){ relDeleteManyDialog.value=true }
function removeManyRelative(){
  const ids = relSelected.value.map(x=>x.id)
  deletingRel.value=true
  EmployeeRelativeService.bulkDelete(props.employee.id, ids, { onFinish:()=>{ deletingRel.value=false; relDeleteManyDialog.value=false; relSelected.value=[] } })
}

/* ===== Experiences state & methods ===== */
const expDt = ref()
const expRows = computed(()=> props.experiences || [])
const expSelected = ref([])
const expFilters = ref({ global: { value: null, matchMode: 'contains' } })
const expDialog = ref(false)
const expDeleteDialog = ref(false)
const expDeleteManyDialog = ref(false)
const currentExp = ref(null)
const savingExp = ref(false)
const deletingExp = ref(false)

const expForm = ref({
  id:null, company_name:'', position_title:'', start_date:null, end_date:null,
  is_current:false, responsibilities:'', achievements:''
})

function openExpNew(){ expForm.value={ id:null, company_name:'', position_title:'', start_date:null, end_date:null, is_current:false, responsibilities:'', achievements:'' }; expDialog.value=true }
function openExpEdit(r){ expForm.value={ ...r }; expDialog.value=true }
function saveExperience(){
  savingExp.value=true
  const payload = { ...expForm.value, start_date: toYMD(expForm.value.start_date), end_date: toYMD(expForm.value.end_date) }
  const opts = { onFinish:()=> savingExp.value=false, onSuccess:()=>{ expDialog.value=false } }
  if (!expForm.value.id) EmployeeExperienceService.store(props.employee.id, payload, opts)
  else EmployeeExperienceService.update(props.employee.id, expForm.value.id, payload, opts)
}
function confirmExpDelete(r){ currentExp.value=r; expDeleteDialog.value=true }
function removeExperience(){
  deletingExp.value=true
  EmployeeExperienceService.destroy(props.employee.id, currentExp.value.id, { onFinish:()=>{ deletingExp.value=false; expDeleteDialog.value=false } })
}
function confirmExpDeleteSelected(){ expDeleteManyDialog.value=true }
function removeManyExperience(){
  const ids = expSelected.value.map(x=>x.id)
  deletingExp.value=true
  EmployeeExperienceService.bulkDelete(props.employee.id, ids, { onFinish:()=>{ deletingExp.value=false; expDeleteManyDialog.value=false; expSelected.value=[] } })
}

/* ===== Skills state & methods ===== */
const skillDt = ref()
const skillRows = computed(()=> props.employee_skills || [])
const skillSelected = ref([])
const skillFilters = ref({ global: { value: null, matchMode: 'contains' } })
const skillDialog = ref(false)
const skillDeleteDialog = ref(false)
const skillDeleteManyDialog = ref(false)
const currentSkill = ref(null)
const savingSkill = ref(false)
const deletingSkill = ref(false)

const skillForm = ref({ id:null, skill_id:null, level:0, years:0, note:'' })

function openSkillNew(){ skillForm.value={ id:null, skill_id:null, level:0, years:0, note:'' }; skillDialog.value=true }
function openSkillEdit(r){
  skillForm.value={ id:r.id, skill_id:r.skill_id, level:r.level ?? 0, years:r.years ?? 0, note:r.note ?? '' }
  skillDialog.value=true
}
function saveSkill(){
  savingSkill.value=true
  const payload = { skill_id: skillForm.value.skill_id, level: skillForm.value.level ?? 0, years: skillForm.value.years ?? 0, note: skillForm.value.note }
  const opts = { onFinish:()=> savingSkill.value=false, onSuccess:()=>{ skillDialog.value=false } }
  if (!skillForm.value.id) EmployeeSkillService.store(props.employee.id, payload, opts)
  else EmployeeSkillService.update(props.employee.id, skillForm.value.id, payload, opts)
}
function confirmSkillDelete(r){ currentSkill.value=r; skillDeleteDialog.value=true }
function removeSkill(){
  deletingSkill.value=true
  EmployeeSkillService.destroy(props.employee.id, currentSkill.value.id, { onFinish:()=>{ deletingSkill.value=false; skillDeleteDialog.value=false } })
}
function confirmSkillDeleteSelected(){ skillDeleteManyDialog.value=true }
function removeManySkill(){
  const ids = skillSelected.value.map(x=>x.id)
  deletingSkill.value=true
  EmployeeSkillService.bulkDelete(props.employee.id, ids, { onFinish:()=>{ deletingSkill.value=false; skillDeleteManyDialog.value=false; skillSelected.value=[] } })
}
</script>
