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
            <p>Chưa có lịch sử phê duyệt</p>
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
                  <!-- Header with subject badge -->
                  <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                      <div class="flex items-center gap-2 mb-1">
                        <span class="font-semibold text-gray-800">{{ getEventTitle(event.type, event.subject_type) }}</span>
                        <Badge
                          v-if="event.subject_type"
                          :value="getSubjectTypeLabel(event.subject_type)"
                          :severity="getSubjectTypeSeverity(event.subject_type)"
                          class="text-xs"
                        />
                      </div>

                      <!-- Subject info -->
                      <div v-if="event.subject_info" class="text-xs text-gray-600 mb-2">
                        <template v-if="event.subject_type === 'contract'">
                          <i class="pi pi-file text-xs mr-1"></i>
                          Hợp đồng: {{ event.subject_info.number }}
                        </template>
                        <template v-else-if="event.subject_type === 'appendix'">
                          <i class="pi pi-file-edit text-xs mr-1"></i>
                          Phụ lục: {{ event.subject_info.appendix_no }}
                          <span v-if="event.subject_info.type_label" class="ml-1">({{ event.subject_info.type_label }})</span>
                        </template>
                      </div>

                      <div class="text-sm text-gray-600 mt-1">
                        <i class="pi pi-user text-xs mr-1"></i>
                        {{ event.user.name }}
                        <template v-if="event.user.email !== '-'">
                          ({{ event.user.email }})
                        </template>
                      </div>
                      <div v-if="event.level" class="text-xs text-gray-500 mt-1">
                        <i class="pi pi-shield text-xs mr-1"></i>
                        Cấp phê duyệt: {{ event.level }}
                      </div>
                    </div>
                    <Tag :value="getEventLabel(event.type, event.subject_type)" :severity="getEventSeverity(event.type)" />
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
import Badge from 'primevue/badge'
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
function getEventTitle(type, subjectType = null, action = null) {
  // For appendix operations based on subject_type
  if (subjectType === 'appendix') {
    const appendixTitles = {
      CREATED: 'Tạo phụ lục',
      UPDATED: 'Chỉnh sửa phụ lục',
      DELETED: 'Xóa phụ lục',
      BULK_DELETED: 'Xóa nhiều phụ lục',
      APPROVED: 'Phê duyệt phụ lục',
      APPROVED_STEP: 'Phê duyệt phụ lục',
      REJECTED: 'Từ chối phụ lục',
      CANCELLED: 'Hủy phụ lục',
    }
    if (appendixTitles[type]) return appendixTitles[type]
  }

  // For contract operations
  const titles = {
    // Contract operations
    CREATED: 'Tạo hợp đồng',
    UPDATED: 'Chỉnh sửa hợp đồng',
    DELETED: 'Xóa hợp đồng',
    BULK_DELETED: 'Xóa nhiều hợp đồng',
    SUBMITTED: 'Gửi phê duyệt',
    APPROVED_STEP: 'Phê duyệt bước',
    APPROVED_FINAL: 'Phê duyệt hoàn tất - Hợp đồng hiệu lực',
    APPROVED: 'Phê duyệt',
    REJECTED: 'Từ chối phê duyệt',
    RECALLED: 'Thu hồi yêu cầu phê duyệt',
    GENERATED_PDF: 'Sinh file PDF',
    TERMINATED: 'Chấm dứt hợp đồng',
    CANCELLED: 'Hủy',

    // Contract renewal
    CONTRACT_RENEWAL_REQUESTED: 'Yêu cầu gia hạn hợp đồng',
    CONTRACT_RENEWAL_APPROVED: 'Phê duyệt gia hạn hợp đồng',
    CONTRACT_RENEWAL_REJECTED: 'Từ chối gia hạn hợp đồng',
  }
  return titles[type] || 'Hành động khác'
}

function getEventLabel(type, subjectType = null) {
  // For appendix operations
  if (subjectType === 'appendix') {
    const appendixLabels = {
      CREATED: 'Tạo phụ lục',
      UPDATED: 'Cập nhật',
      DELETED: 'Đã xóa',
      BULK_DELETED: 'Xóa nhiều',
      APPROVED: 'Đã duyệt',
      REJECTED: 'Từ chối',
      CANCELLED: 'Đã hủy',
    }
    if (appendixLabels[type]) return appendixLabels[type]
  }

  // For contract operations
  const labels = {
    CREATED: 'Tạo mới',
    UPDATED: 'Cập nhật',
    DELETED: 'Đã xóa',
    BULK_DELETED: 'Xóa nhiều',
    SUBMITTED: 'Chờ duyệt',
    APPROVED_STEP: 'Đã duyệt',
    APPROVED_FINAL: 'Hoàn tất',
    APPROVED: 'Đã duyệt',
    REJECTED: 'Từ chối',
    RECALLED: 'Thu hồi',
    GENERATED_PDF: 'Sinh PDF',
    TERMINATED: 'Chấm dứt',
    CANCELLED: 'Đã hủy',

    // Contract renewal
    CONTRACT_RENEWAL_REQUESTED: 'Yêu cầu gia hạn',
    CONTRACT_RENEWAL_APPROVED: 'Gia hạn được duyệt',
    CONTRACT_RENEWAL_REJECTED: 'Gia hạn bị từ chối',

    OTHER: 'Khác',
  }
  return labels[type] || 'Khác'
}

function getEventSeverity(type) {
  const severities = {
    // Contract operations
    CREATED: 'info',
    UPDATED: 'info',
    DELETED: 'secondary',
    SUBMITTED: 'warn',
    APPROVED_STEP: 'success',
    APPROVED_FINAL: 'success',
    REJECTED: 'danger',
    RECALLED: 'secondary',
    GENERATED_PDF: 'contrast',
    TERMINATED: 'danger',

    // Contract renewal
    CONTRACT_RENEWAL_REQUESTED: 'info',
    CONTRACT_RENEWAL_APPROVED: 'success',
    CONTRACT_RENEWAL_REJECTED: 'danger',

    OTHER: 'contrast',
  }
  return severities[type] || 'contrast'
}

function getIcon(type) {
  const icons = {
    // Contract operations
    CREATED: 'pi pi-file-plus',
    UPDATED: 'pi pi-pencil',
    DELETED: 'pi pi-trash',
    SUBMITTED: 'pi pi-send',
    APPROVED_STEP: 'pi pi-check',
    APPROVED_FINAL: 'pi pi-check-circle',
    REJECTED: 'pi pi-times-circle',
    RECALLED: 'pi pi-undo',
    GENERATED_PDF: 'pi pi-file-pdf',
    TERMINATED: 'pi pi-ban',

    // Contract renewal
    CONTRACT_RENEWAL_REQUESTED: 'pi pi-refresh',
    CONTRACT_RENEWAL_APPROVED: 'pi pi-check-circle',
    CONTRACT_RENEWAL_REJECTED: 'pi pi-times-circle',

    OTHER: 'pi pi-info-circle',
  }
  return icons[type] || 'pi pi-info-circle'
}

function getIconClass(type) {
  const classes = {
    // Contract operations
    CREATED: 'bg-blue-100 text-blue-600',
    UPDATED: 'bg-blue-100 text-blue-600',
    DELETED: 'bg-gray-100 text-gray-600',
    SUBMITTED: 'bg-yellow-100 text-yellow-600',
    APPROVED_STEP: 'bg-green-100 text-green-600',
    APPROVED_FINAL: 'bg-green-100 text-green-600',
    REJECTED: 'bg-red-100 text-red-600',
    RECALLED: 'bg-gray-100 text-gray-600',
    GENERATED_PDF: 'bg-purple-100 text-purple-600',
    TERMINATED: 'bg-red-100 text-red-600',

    // Contract renewal
    CONTRACT_RENEWAL_REQUESTED: 'bg-blue-100 text-blue-600',
    CONTRACT_RENEWAL_APPROVED: 'bg-green-100 text-green-600',
    CONTRACT_RENEWAL_REJECTED: 'bg-red-100 text-red-600',
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

function getCardClass(type) {
  const classes = {
    // Contract operations
    CREATED: 'border-blue-200 bg-blue-50',
    UPDATED: 'border-blue-200 bg-blue-50',
    DELETED: 'border-gray-200 bg-gray-50',
    SUBMITTED: 'border-yellow-200 bg-yellow-50',
    APPROVED_STEP: 'border-green-200 bg-green-50',
    APPROVED_FINAL: 'border-green-200 bg-green-50',
    REJECTED: 'border-red-200 bg-red-50',
    RECALLED: 'border-gray-200 bg-gray-50',
    GENERATED_PDF: 'border-purple-200 bg-purple-50',
    TERMINATED: 'border-red-200 bg-red-50',

    // Contract renewal
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

function getSubjectTypeLabel(subjectType) {
  const labels = {
    contract: 'Hợp đồng',
    appendix: 'Phụ lục',
    other: 'Khác',
  }
  return labels[subjectType] || subjectType
}

function getSubjectTypeSeverity(subjectType) {
  const severities = {
    contract: 'info',
    appendix: 'secondary',
    other: 'contrast',
  }
  return severities[subjectType] || 'secondary'
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
