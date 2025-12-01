<template>
  <Head>
    <title>Hồ sơ Hợp đồng - {{ contract.contract_number }}</title>
  </Head>

  <div class="card">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold">
          Hợp đồng: {{ contract.contract_number }} - {{ contract.employee?.full_name }}
        </h2>
        <div class="flex items-center gap-3 mt-2">
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Loại:</span>
            <Tag :value="contract.contract_type_label" :severity="getContractTypeSeverity(contract.contract_type)" />
          </div>
          |
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Trạng thái:</span>
            <Tag :value="contract.status_label" :severity="getContractStatusSeverity(contract.status)" />
          </div>
        </div>
      </div>
      <Button label="Quay lại" icon="pi pi-arrow-left" outlined @click="goBack" />
    </div>

    <!-- Dùng v-model cho Tabs -->
    <Tabs :value="activeTabIndex">
      <TabList>
        <Tab :value="0">Thông tin chung</Tab>
        <Tab :value="1">Phụ lục</Tab>
        <Tab :value="2">Timeline</Tab>
        <Tab :value="3">Lịch sử phê duyệt</Tab>
      </TabList>

      <TabPanel :value="0">
        <div class="pt-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><b>Nhân viên:</b> {{ contract.employee?.full_name }} ({{ contract.employee?.employee_code }})</div>
            <div><b>Đơn vị:</b> {{ contract.department?.name }}</div>
            <div><b>Chức danh:</b> {{ contract.position?.title }}</div>
            <div><b>Loại HĐ:</b> {{ contract.contract_type_label }}</div>
            <div><b>Bắt đầu:</b> {{ formatDate(contract.start_date) }}</div>
            <div><b>Kết thúc:</b> {{ formatDate(contract.end_date) || '—' }}</div>
          </div>

          <!-- Thông tin chấm dứt hợp đồng -->
          <div v-if="contract.status === 'TERMINATED' && contract.terminated_at" class="mt-6 p-4 bg-red-50 border border-red-200 rounded">
            <h4 class="font-semibold text-red-700 mb-3 flex items-center gap-2">
              <i class="pi pi-ban"></i>
              Thông tin chấm dứt hợp đồng
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <span class="text-gray-600">Ngày chấm dứt:</span>
                <span class="ml-2 font-medium">{{ formatDate(contract.terminated_at) }}</span>
              </div>
              <div>
                <span class="text-gray-600">Lý do:</span>
                <span class="ml-2 font-medium">{{ contract.termination_reason_label || '—' }}</span>
              </div>
              <div v-if="contract.termination_note" class="md:col-span-2">
                <div class="text-gray-600 mb-2">Ghi chú chấm dứt:</div>
                <div class="text-sm text-gray-700 whitespace-pre-line p-3 bg-white rounded border border-gray-200">
                  {{ contract.termination_note }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </TabPanel>

      <TabPanel :value="1">
        <ContractAppendixTab
          :contract-id="contract.id"
          :appendixes="appendixes"
          :appendix-templates="appendixTemplates"
        />
      </TabPanel>

      <TabPanel :value="2">
        <ContractTimelineTab
          :contract-id="contract.id"
          :timeline-events="contractTimeline"
        />
      </TabPanel>

      <TabPanel :value="3">
        <div class="pt-4">
          <div v-if="!timeline?.length" class="text-center py-8 text-gray-500">
            <i class="pi pi-info-circle text-3xl mb-3"></i>
            <p>Chưa có lịch sử</p>
          </div>

          <div v-else class="approval-timeline">
            <div v-for="(event, index) in timeline" :key="event.id" class="timeline-item mb-6">
            <div class="flex gap-4">
              <!-- Icon & line -->
              <div class="flex flex-col items-center">
                <div class="timeline-icon" :class="getIconClass(event.type)">
                  <i :class="getIcon(event.type)"></i>
                </div>
                <div v-if="index < timeline.length - 1" class="timeline-line"></div>
              </div>

              <!-- Content -->
              <div class="flex-1 pb-4">
                <div class="approval-card p-4 rounded border" :class="getCardClass(event.type)">
                  <div class="flex items-start justify-between mb-2">
                    <div>
                      <div class="font-semibold text-gray-800">{{ getEventTitle(event.type) }}</div>
                      <div class="text-sm text-gray-600 mt-1">
                        <i class="pi pi-user text-xs mr-1"></i>
                        {{ event.user.name }}
                        <template v-if="event.user.email !== '-'">
                          ({{ event.user.email }})
                        </template>
                      </div>
                      <div v-if="event.level" class="text-xs text-gray-500 mt-1">
                        <i class="pi pi-shield text-xs mr-1"></i>
                        Cấp: {{ event.level }}
                      </div>
                    </div>
                    <Tag :value="getEventLabel(event.type)" :severity="getEventSeverity(event.type)" />
                  </div>

                  <div v-if="event.comments" class="mt-3 p-3 bg-white rounded border border-gray-200">
                    <div class="text-xs text-gray-500 mb-1">Ý kiến:</div>
                    <div class="text-sm text-gray-700">{{ event.comments }}</div>
                  </div>

                  <div class="mt-3 text-xs text-gray-500">
                    <i class="pi pi-calendar text-xs mr-1"></i>
                    {{ event.timestamp }}
                  </div>
                </div>
              </div>
            </div>
            </div>
          </div>
        </div>
      </TabPanel>
    </Tabs>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanel from 'primevue/tabpanel'
import ContractAppendixTab from './ContractAppendixTab.vue'
import ContractTimelineTab from './ContractTimelineTab.vue'
import { formatDate } from '@/utils/dateHelper'

const props = defineProps({
  contract:          { type: Object, required: true },
  appendixes:        { type: Array,  default: () => [] },
  appendixTemplates: { type: Array,  default: () => [] },
  timeline:          { type: Array,  default: () => [] },
  contractTimeline:  { type: Array,  default: () => [] },
  activeTab:         { type: String, default: 'general' } // nhận từ BE
})

const contract   = props.contract
const appendixes = props.appendixes

// Convert string tab to index
const activeTabIndex = computed(() => {
  const tab = props.activeTab || 'general'
  if (tab === 'appendixes') return 1
  if (tab === 'timeline') return 2
  if (tab === 'approval-history') return 3
  return 0
})

function goBack() {
  router.visit('/contracts')
}

// Contract type severity
function getContractTypeSeverity(type) {
  const severities = {
    INDEFINITE: 'success',
    FIXED_TERM: 'info',
    SEASONAL: 'warn',
    PROBATION: 'secondary',
  }
  return severities[type] || 'info'
}

// Contract status severity
function getContractStatusSeverity(status) {
  const severities = {
    DRAFT: 'secondary',
    PENDING_APPROVAL: 'warn',
    ACTIVE: 'success',
    EXPIRED: 'danger',
    TERMINATED: 'danger',
    REJECTED: 'danger',
  }
  return severities[status] || 'info'
}

// Timeline helper methods
function getEventTitle(type) {
  const titles = {
    CREATED: 'Tạo hợp đồng',
    UPDATED: 'Chỉnh sửa hợp đồng',
    SUBMITTED: 'Gửi phê duyệt',
    APPROVED_STEP: 'Phê duyệt bước',
    APPROVED_FINAL: 'Phê duyệt hoàn tất',
    REJECTED: 'Từ chối',
    RECALLED: 'Thu hồi',
    GENERATED_PDF: 'Sinh file PDF',
    TERMINATED: 'Chấm dứt hợp đồng',
    CONTRACT_RENEWAL_REQUESTED: 'Yêu cầu gia hạn hợp đồng',
    CONTRACT_RENEWAL_APPROVED: 'Phê duyệt gia hạn hợp đồng',
    CONTRACT_RENEWAL_REJECTED: 'Từ chối gia hạn hợp đồng',
  }
  return titles[type] || 'Hành động khác'
}

function getEventLabel(type) {
  const labels = {
    CREATED: 'Tạo mới',
    UPDATED: 'Chỉnh sửa',
    SUBMITTED: 'Chờ duyệt',
    APPROVED_STEP: 'Đã duyệt',
    APPROVED_FINAL: 'Hoàn tất',
    REJECTED: 'Từ chối',
    RECALLED: 'Thu hồi',
    GENERATED_PDF: 'Sinh PDF',
    TERMINATED: 'Đã chấm dứt',
    CONTRACT_RENEWAL_REQUESTED: 'Gia hạn',
    CONTRACT_RENEWAL_APPROVED: 'Đã duyệt',
    CONTRACT_RENEWAL_REJECTED: 'Bị từ chối',
  }
  return labels[type] || type
}

function getEventSeverity(type) {
  const severities = {
    CREATED: 'info',
    UPDATED: 'info',
    SUBMITTED: 'warn',
    APPROVED_STEP: 'success',
    APPROVED_FINAL: 'success',
    REJECTED: 'danger',
    RECALLED: 'secondary',
    GENERATED_PDF: 'contrast',
    TERMINATED: 'danger',
    CONTRACT_RENEWAL_REQUESTED: 'info',
    CONTRACT_RENEWAL_APPROVED: 'success',
    CONTRACT_RENEWAL_REJECTED: 'danger',
  }
  return severities[type] || 'info'
}

function getIcon(type) {
  const icons = {
    CREATED: 'pi pi-file-plus',
    UPDATED: 'pi pi-pencil',
    SUBMITTED: 'pi pi-send',
    APPROVED_STEP: 'pi pi-check',
    APPROVED_FINAL: 'pi pi-check-circle',
    REJECTED: 'pi pi-times-circle',
    RECALLED: 'pi pi-undo',
    GENERATED_PDF: 'pi pi-file-pdf',
    TERMINATED: 'pi pi-ban',
    CONTRACT_RENEWAL_REQUESTED: 'pi pi-refresh',
    CONTRACT_RENEWAL_APPROVED: 'pi pi-check-circle',
    CONTRACT_RENEWAL_REJECTED: 'pi pi-times-circle',
  }
  return icons[type] || 'pi pi-circle'
}

function getIconClass(type) {
  const classes = {
    CREATED: 'bg-blue-100 text-blue-600',
    UPDATED: 'bg-blue-100 text-blue-600',
    SUBMITTED: 'bg-yellow-100 text-yellow-600',
    APPROVED_STEP: 'bg-green-100 text-green-600',
    APPROVED_FINAL: 'bg-green-100 text-green-600',
    REJECTED: 'bg-red-100 text-red-600',
    RECALLED: 'bg-gray-100 text-gray-600',
    GENERATED_PDF: 'bg-purple-100 text-purple-600',
    TERMINATED: 'bg-red-100 text-red-600',
    CONTRACT_RENEWAL_REQUESTED: 'bg-blue-100 text-blue-600',
    CONTRACT_RENEWAL_APPROVED: 'bg-green-100 text-green-600',
    CONTRACT_RENEWAL_REJECTED: 'bg-red-100 text-red-600',
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

function getCardClass(type) {
  const classes = {
    CREATED: 'border-blue-200 bg-blue-50',
    UPDATED: 'border-blue-200 bg-blue-50',
    SUBMITTED: 'border-yellow-200 bg-yellow-50',
    APPROVED_STEP: 'border-green-200 bg-green-50',
    APPROVED_FINAL: 'border-green-200 bg-green-50',
    REJECTED: 'border-red-200 bg-red-50',
    RECALLED: 'border-gray-200 bg-gray-50',
    GENERATED_PDF: 'border-purple-200 bg-purple-50',
    TERMINATED: 'border-red-200 bg-red-50',
    CONTRACT_RENEWAL_REQUESTED: 'border-blue-200 bg-blue-50',
    CONTRACT_RENEWAL_APPROVED: 'border-green-200 bg-green-50',
    CONTRACT_RENEWAL_REJECTED: 'border-red-200 bg-red-50',
  }
  return classes[type] || 'border-gray-200 bg-gray-50'
}

function getTerminationReasonLabel(reason) {
  const labels = {
    EXPIRATION: 'Hết hạn hợp đồng',
    MUTUAL: 'Thỏa thuận hai bên',
    RESIGNATION: 'Người lao động xin nghỉ',
    DISMISSAL: 'Sa thải',
    PROBATION_FAILED: 'Không qua thử việc',
    BREACH: 'Vi phạm hợp đồng',
    FORCE_MAJEURE: 'Bất khả kháng',
    RETIREMENT: 'Nghỉ hưu',
    DECEASED: 'Người lao động qua đời',
    OTHER: 'Lý do khác',
  }
  return labels[reason] || reason || '—'
}
</script>

<style scoped>
.timeline-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: bold;
  flex-shrink: 0;
}

.timeline-line {
  width: 2px;
  flex: 1;
  background: linear-gradient(to bottom, #e5e7eb 0%, #e5e7eb 100%);
  min-height: 40px;
}

.approval-timeline {
  max-width: 800px;
  margin: 0 auto;
}
</style>
