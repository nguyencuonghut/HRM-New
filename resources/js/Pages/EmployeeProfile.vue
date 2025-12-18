<template>
  <Head>
    <title>Hồ sơ nhân viên - {{ props.employee.full_name }}</title>
  </Head>

  <div class="employee-profile-layout">
    <!-- Left: Profile Completion Checklist -->
    <div class="profile-checklist-section">
      <ProfileChecklist
        :completion-score="props.employee.completion_score || 0"
        :completion-details="props.employee.completion_details || []"
        :completion-missing="props.employee.completion_missing || []"
        :completion-level="props.employee.completion_level || 'Chưa xác định'"
        :completion-severity="props.employee.completion_severity || 'secondary'"
      />
    </div>

    <!-- Center: Profile Header + Sub-navigation + Content -->
    <div class="profile-main-section">
      <!-- Profile Header -->
      <div class="profile-header card">
        <h2 class="text-xl font-semibold mb-4">
          Hồ sơ: {{ props.employee.full_name }} ({{ props.employee.employee_code }})
        </h2>

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

      <!-- Sub-navigation + Content Area -->
      <div class="profile-content-layout" :class="{ 'sidebar-collapsed': isSidebarCollapsed }">
        <!-- Sub-navigation Sidebar -->
        <ProfileSubSidebar
          ref="subSidebarRef"
          v-model="activeTab"
          @update:collapsed="isSidebarCollapsed = $event"
        />

        <!-- Content Area -->
        <div class="profile-content-area">
          <!-- Mobile Menu Toggle -->
          <button class="mobile-menu-toggle" @click="openMobileSidebar">
            <i class="pi pi-bars"></i>
            <span>Menu hồ sơ</span>
          </button>
          <!-- HỌC VẤN -->
          <div v-show="activeTab === 'education'" class="content-section">
            <EducationTab
              :employee-id="props.employee.id"
              :educations="props.educations"
              :education-levels="props.education_levels"
              :schools="props.schools"
            />
          </div>

          <!-- NGƯỜI THÂN -->
          <div v-show="activeTab === 'relatives'" class="content-section">
            <RelativesTab
              :employee-id="props.employee.id"
              :relatives="props.relatives"
            />
          </div>

          <!-- KINH NGHIỆM -->
          <div v-show="activeTab === 'experiences'" class="content-section">
            <ExperiencesTab
              :employee-id="props.employee.id"
              :experiences="props.experiences"
            />
          </div>

          <!-- KỸ NĂNG -->
          <div v-show="activeTab === 'skills'" class="content-section">
            <SkillsTab
              :employee-id="props.employee.id"
              :employee-skills="props.employee_skills"
              :skills="props.skills"
              :skill-categories="props.skill_categories"
            />
          </div>

          <!-- PHÂN CÔNG -->
          <div v-show="activeTab === 'assignments'" class="content-section">
            <AssignmentsTab
              :employee-id="props.employee.id"
              :assignments="props.assignments"
              :departments="props.departments"
              :positions="props.positions"
            />
          </div>

          <!-- HỢP ĐỒNG -->
          <div v-show="activeTab === 'contracts'" class="content-section">
            <ContractTab :contracts="props.contracts || []" />
          </div>

          <!-- LƯƠNG HIỆN TẠI -->
          <div v-show="activeTab === 'payroll'" class="content-section">
            <PayrollTab :current-payroll="props.current_payroll" />
          </div>

          <!-- SỐ DƯ PHÉP -->
          <div v-show="activeTab === 'leave-balances'" class="content-section">
            <LeaveBalanceTab :employee="employee" />
          </div>

          <!-- LỊCH SỬ LÀM VIỆC -->
          <div v-show="activeTab === 'employment-history'" class="content-section">
            <EmploymentHistoryTab
              :employment-history="props.employee.employment_history || []"
              :current-tenure="props.employee.current_tenure_text || '0 ngày'"
              :cumulative-tenure="props.employee.cumulative_tenure_text || '0 ngày'"
            />
          </div>

          <!-- NHẬT KÝ HOẠT ĐỘNG -->
          <div v-show="activeTab === 'timeline'" class="content-section">
            <TimelineTab :employee-id="props.employee.id" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Head } from '@inertiajs/vue3'
import ProfileChecklist from '@/Components/ProfileChecklist.vue'
import ProfileSubSidebar from '@/Components/ProfileSubSidebar.vue'
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
const isSidebarCollapsed = ref(false);
const subSidebarRef = ref(null);

function handleGotoContractTab() {
  activeTab.value = 'contracts';
}

function openMobileSidebar() {
  subSidebarRef.value?.openMobileMenu()
}
onMounted(() => {
  window.addEventListener('payroll-goto-contract-tab', handleGotoContractTab);
});
onBeforeUnmount(() => {
  window.removeEventListener('payroll-goto-contract-tab', handleGotoContractTab);
});
</script>
<style scoped>
.employee-profile-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 1rem;
  min-height: calc(100vh - 120px);
}

.profile-checklist-section {
  position: sticky;
  top: 80px;
  height: fit-content;
}

.profile-main-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.profile-header {
  padding: 1.5rem;
}

.profile-content-layout {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 0;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  min-height: 600px;
  transition: grid-template-columns 0.3s ease;
  position: relative;
}

.profile-content-layout.sidebar-collapsed {
  grid-template-columns: 60px 1fr;
}

.mobile-menu-toggle {
  display: none;
}

.profile-content-area {
  background: white;
  padding: 1.5rem;
  overflow-y: auto;
  min-height: 600px;
  position: relative;
}

.content-section {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 1280px) {
  .employee-profile-layout {
    grid-template-columns: 1fr;
  }

  .profile-checklist-section {
    position: relative;
    top: 0;
  }

  .profile-content-layout {
    grid-template-columns: 1fr;
  }

  .profile-content-layout.sidebar-collapsed {
    grid-template-columns: 1fr;
  }

  .mobile-menu-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    margin-bottom: 1.25rem;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    transition: all 0.2s;
    width: 100%;
    max-width: 200px;
  }

  .mobile-menu-toggle:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
  }

  .mobile-menu-toggle:active {
    transform: translateY(0);
  }

  .mobile-menu-toggle i {
    font-size: 18px;
  }
}

@media (max-width: 768px) {
  .employee-profile-layout {
    gap: 0.5rem;
  }

  .profile-content-area {
    padding: 1rem;
  }
}
</style>
