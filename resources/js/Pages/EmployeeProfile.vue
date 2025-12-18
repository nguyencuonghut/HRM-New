<template>
  <Head>
    <title>Hồ sơ nhân viên - {{ props.employee.full_name }}</title>
  </Head>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
    <!-- Sidebar: Profile Completion -->
    <div class="lg:col-span-1">
      <ProfileChecklist
        :completion-score="props.employee.completion_score || 0"
        :completion-details="props.employee.completion_details || []"
        :completion-missing="props.employee.completion_missing || []"
        :completion-level="props.employee.completion_level || 'Chưa xác định'"
        :completion-severity="props.employee.completion_severity || 'secondary'"
      />
    </div>

    <!-- Main Content -->
    <div class="lg:col-span-3">
      <div class="card">
        <div class="mb-6">
          <h2 class="text-xl font-semibold mb-4">Hồ sơ: {{ props.employee.full_name }} ({{ props.employee.employee_code }})</h2>

          <!-- Tenure Information -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Card class="bg-blue-50 border border-blue-200">
              <template #content>
                <div class="flex items-center gap-3">
                  <i class="pi pi-calendar text-blue-600 text-2xl flex-shrink-0"></i>
                  <div class="flex-1">
                    <p class="text-xs text-gray-600 mb-1">Thâm niên đợt hiện tại</p>
                    <p class="font-semibold text-blue-700">{{ props.employee.current_tenure_text || '0 ngày' }}</p>
                  </div>
                </div>
              </template>
            </Card>
            <Card class="bg-green-50 border border-green-200">
              <template #content>
                <div class="flex items-center gap-3">
                  <i class="pi pi-chart-line text-green-600 text-2xl flex-shrink-0"></i>
                  <div class="flex-1">
                    <p class="text-xs text-gray-600 mb-1">Thâm niên tích lũy</p>
                    <p class="font-semibold text-green-700">{{ props.employee.cumulative_tenure_text || '0 ngày' }}</p>
                  </div>
                </div>
              </template>
            </Card>
            <Card class="bg-purple-50 border border-purple-200">
              <template #content>
                <div class="flex items-center gap-3">
                  <i class="pi pi-sign-in text-purple-600 text-2xl flex-shrink-0"></i>
                  <div class="flex-1">
                    <p class="text-xs text-gray-600 mb-1">Ngày vào làm (đợt hiện tại)</p>
                    <p class="font-semibold text-purple-700">{{ props.employee.current_employment_start || '-' }}</p>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <Tabs v-model:value="activeTab">
      <TabList>
        <Tab value="education">Học vấn</Tab>
        <Tab value="relatives">Người thân</Tab>
        <Tab value="experiences">Kinh nghiệm</Tab>
        <Tab value="skills">Kỹ năng</Tab>
        <Tab value="assignments">Phân công</Tab>
        <Tab value="contracts">Hợp đồng</Tab>
        <Tab value="payroll">Lương hiện tại</Tab>
        <Tab value="leave-balances">Số dư phép</Tab>
        <Tab value="employment-history">Lịch sử làm việc</Tab>
        <Tab value="timeline">Lịch sử</Tab>
      </TabList>

      <!-- TAB HỌC VẤN -->
      <TabPanel value="education">
        <EducationTab
          :employee-id="props.employee.id"
          :educations="props.educations"
          :education-levels="props.education_levels"
          :schools="props.schools"
        />
      </TabPanel>

      <!-- TAB NGƯỜI THÂN -->
      <TabPanel value="relatives">
        <RelativesTab
          :employee-id="props.employee.id"
          :relatives="props.relatives"
        />
      </TabPanel>

      <!-- TAB KINH NGHIỆM -->
      <TabPanel value="experiences">
        <ExperiencesTab
          :employee-id="props.employee.id"
          :experiences="props.experiences"
        />
      </TabPanel>

      <!-- TAB KỚ NĂNG -->
      <TabPanel value="skills">
        <SkillsTab
          :employee-id="props.employee.id"
          :employee-skills="props.employee_skills"
          :skills="props.skills"
          :skill-categories="props.skill_categories"
        />
      </TabPanel>

      <!-- TAB PHÂN CÔNG -->
      <TabPanel value="assignments">
        <AssignmentsTab
          :employee-id="props.employee.id"
          :assignments="props.assignments"
          :departments="props.departments"
          :positions="props.positions"
        />
      </TabPanel>

      <!-- TAB HỢP ĐỒNG -->
      <TabPanel value="contracts">
        <ContractTab :contracts="props.contracts || []" />
      </TabPanel>

      <!-- TAB LƯƠNG HIỆN TẠI -->
      <TabPanel value="payroll">
        <PayrollTab :current-payroll="props.current_payroll" />
      </TabPanel>

      <!-- TAB SỐ DƯ PHÉP -->
      <TabPanel value="leave-balances">
        <LeaveBalanceTab :employee="employee" />
      </TabPanel>

      <!-- TAB LỊCH SỬ LÀM VIỆC -->
      <TabPanel value="employment-history">
        <EmploymentHistoryTab
          :employment-history="props.employee.employment_history || []"
          :current-tenure="props.employee.current_tenure_text || '0 ngày'"
          :cumulative-tenure="props.employee.cumulative_tenure_text || '0 ngày'"
        />
      </TabPanel>

      <!-- TAB LỊCH SỬ -->
      <TabPanel value="timeline">
        <TimelineTab :employee-id="props.employee.id" />
      </TabPanel>

        </Tabs>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Head } from '@inertiajs/vue3'
import ProfileChecklist from '@/Components/ProfileChecklist.vue'
import EducationTab from '@/Pages/Employees/Components/EducationTab.vue'
import RelativesTab from '@/Pages/Employees/Components/RelativesTab.vue'
import ExperiencesTab from '@/Pages/Employees/Components/ExperiencesTab.vue'
import SkillsTab from '@/Pages/Employees/Components/SkillsTab.vue'
import AssignmentsTab from '@/Pages/Employees/Components/AssignmentsTab.vue'
import LeaveBalanceTab from '@/Pages/Employees/Components/LeaveBalanceTab.vue'
import ContractTab from '@/Pages/Employees/Components/ContractTab.vue'
import PayrollTab from '@/Pages/Employees/Components/PayrollTab.vue'
import EmploymentHistoryTab from '@/Pages/Employees/Components/EmploymentHistoryTab.vue'
import TimelineTab from '@/Pages/Employees/Components/TimelineTab.vue'

// PrimeVue imports
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanel from 'primevue/tabpanel'
import Card from 'primevue/card'

const props = defineProps({
  employee: { type: Object, required: true },
  education_levels: { type: Array, required: true },
  schools: { type: Array, required: true },
  departments: { type: Array, required: true },
  positions: { type: Array, required: true },
  skill_categories: { type: Array, default: () => [] },
  educations: { type: Array, required: true },
  relatives: { type: Array, default: () => [] },
  experiences: { type: Array, default: () => [] },
  skills: { type: Array, default: () => [] },           // master danh mục
  employee_skills: { type: Array, default: () => [] },  // kỹ năng của NV
  assignments: { type: Array, default: () => [] },      // phân công của NV
  contracts: { type: Array, default: () => [] },        // hợp đồng của NV
})

// --- PayrollTab chuyển tab contracts ---
const activeTab = ref('education');
function handleGotoContractTab() {
  activeTab.value = 'contracts';
}
onMounted(() => {
  window.addEventListener('payroll-goto-contract-tab', handleGotoContractTab);
});
onBeforeUnmount(() => {
  window.removeEventListener('payroll-goto-contract-tab', handleGotoContractTab);
});
</script>
