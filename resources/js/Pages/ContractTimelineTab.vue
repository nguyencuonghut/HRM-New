<template>
  <div class="pt-4">
    <div v-if="!timelineEvents?.length" class="text-center py-8 text-gray-500">
      <i class="pi pi-clock text-3xl mb-3"></i>
      <p>Chưa có lịch sử gia hạn hoặc phụ lục</p>
    </div>

    <div v-else class="timeline-container">
      <div v-for="(event, index) in timelineEvents" :key="event.id" class="timeline-item mb-6">
        <div class="flex gap-4">
          <!-- Icon & line -->
          <div class="flex flex-col items-center">
            <div class="timeline-icon" :class="getIconClass(event.event_type)">
              <i :class="getIcon(event.event_type)"></i>
            </div>
            <div v-if="index < timelineEvents.length - 1" class="timeline-line"></div>
          </div>

          <!-- Content -->
          <div class="flex-1 pb-4">
            <div class="timeline-card p-4 rounded border" :class="getCardClass(event.event_type)">
              <!-- Header -->
              <div class="flex items-start justify-between mb-3">
                <div>
                  <div class="font-semibold text-gray-800 flex items-center gap-2">
                    {{ getEventTitle(event.event_type) }}
                    <Tag v-if="event.status" :value="getStatusLabel(event.status)" :severity="getStatusSeverity(event.status)" />
                  </div>
                  <div class="text-sm text-gray-600 mt-1">
                    <i class="pi pi-calendar text-xs mr-1"></i>
                    {{ formatDateTime(event.created_at) }}
                  </div>
                </div>
                <Tag :value="getEventLabel(event.event_type)" :severity="getEventSeverity(event.event_type)" />
              </div>

              <!-- Nội dung chi tiết theo loại event -->
              <div class="mt-3">
                <!-- Contract Created -->
                <div v-if="event.event_type === 'contract_created'" class="space-y-2">
                  <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                      <span class="text-gray-600">Loại hợp đồng:</span>
                      <span class="ml-2 font-medium">{{ event.details?.contract_type_label }}</span>
                    </div>
                    <div>
                      <span class="text-gray-600">Ngày bắt đầu:</span>
                      <span class="ml-2 font-medium">{{ formatDate(event.details?.start_date) }}</span>
                    </div>
                    <div v-if="event.details?.end_date">
                      <span class="text-gray-600">Ngày kết thúc:</span>
                      <span class="ml-2 font-medium">{{ formatDate(event.details?.end_date) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Contract Renewal -->
                <div v-if="event.event_type === 'contract_renewal'" class="space-y-2">
                  <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                      <span class="text-gray-600">Phụ lục số:</span>
                      <span class="ml-2 font-medium">{{ event.details?.appendix_no }}</span>
                    </div>
                    <div>
                      <span class="text-gray-600">Ngày hết hạn cũ:</span>
                      <span class="ml-2 font-medium">{{ formatDate(event.details?.old_end_date) }}</span>
                    </div>
                    <div>
                      <span class="text-gray-600">Ngày hết hạn mới:</span>
                      <span class="ml-2 font-medium text-green-600">{{ formatDate(event.details?.new_end_date) }}</span>
                    </div>
                    <div>
                      <span class="text-gray-600">Gia hạn thêm:</span>
                      <span class="ml-2 font-medium">{{ calculateDaysDiff(event.details?.old_end_date, event.details?.new_end_date) }} ngày</span>
                    </div>
                  </div>

                  <!-- Approval info if approved -->
                  <div v-if="event.status === 'ACTIVE' && event.details?.approved_at" class="mt-3 p-3 bg-green-50 border border-green-200 rounded">
                    <div class="flex items-center gap-2 text-sm text-green-700">
                      <i class="pi pi-check-circle"></i>
                      <span>Đã phê duyệt bởi <b>{{ event.details?.approver_name }}</b> vào {{ formatDateTime(event.details?.approved_at) }}</span>
                    </div>
                  </div>

                  <!-- Pending approval -->
                  <div v-if="event.status === 'PENDING_APPROVAL'" class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
                    <div class="flex items-center gap-2 text-sm text-yellow-700">
                      <i class="pi pi-clock"></i>
                      <span>Đang chờ phê duyệt</span>
                    </div>
                  </div>

                  <!-- Rejected -->
                  <div v-if="event.status === 'REJECTED' && event.details?.rejected_at" class="mt-3 p-3 bg-red-50 border border-red-200 rounded">
                    <div class="flex items-center gap-2 text-sm text-red-700 mb-2">
                      <i class="pi pi-times-circle"></i>
                      <span>Đã từ chối bởi <b>{{ event.details?.approver_name }}</b> vào {{ formatDateTime(event.details?.rejected_at) }}</span>
                    </div>
                    <div v-if="event.details?.approval_note" class="text-sm text-red-600">
                      <b>Lý do:</b> {{ event.details?.approval_note }}
                    </div>
                  </div>
                </div>

                <!-- Appendix Created -->
                <div v-if="event.event_type === 'appendix_created'" class="space-y-2">
                  <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                      <span class="text-gray-600">Phụ lục số:</span>
                      <span class="ml-2 font-medium">{{ event.details?.appendix_no }}</span>
                    </div>
                    <div>
                      <span class="text-gray-600">Loại:</span>
                      <span class="ml-2 font-medium">{{ event.details?.type_label }}</span>
                    </div>
                    <div v-if="event.details?.effective_date">
                      <span class="text-gray-600">Ngày hiệu lực:</span>
                      <span class="ml-2 font-medium">{{ formatDate(event.details?.effective_date) }}</span>
                    </div>
                  </div>

                  <div v-if="event.details?.description" class="mt-3 p-3 bg-gray-50 rounded text-sm">
                    <div class="text-gray-600 mb-1">Nội dung:</div>
                    <div class="text-gray-700">{{ event.details?.description }}</div>
                  </div>
                </div>

                <!-- Contract Terminated -->
                <div v-if="event.event_type === 'contract_terminated'" class="space-y-2">
                  <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                      <span class="text-gray-600">Ngày chấm dứt:</span>
                      <span class="ml-2 font-medium">{{ formatDate(event.details?.terminated_at) }}</span>
                    </div>
                    <div>
                      <span class="text-gray-600">Lý do:</span>
                      <span class="ml-2 font-medium">{{ event.details?.termination_reason_label }}</span>
                    </div>
                  </div>

                  <div v-if="event.details?.termination_note" class="mt-3 p-3 bg-red-50 rounded text-sm">
                    <div class="text-gray-600 mb-1">Ghi chú:</div>
                    <div class="text-gray-700">{{ event.details?.termination_note }}</div>
                  </div>
                </div>
              </div>

              <!-- Actor info -->
              <div v-if="event.actor" class="mt-3 pt-3 border-t border-gray-200 flex items-center gap-2 text-sm text-gray-600">
                <i class="pi pi-user text-xs"></i>
                <span>{{ event.actor?.name }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Tag from 'primevue/tag'
import { formatDate, formatDateTime, calculateDaysDiff } from '@/utils/dateHelper'

const props = defineProps({
  contractId: { type: Number, required: true },
  timelineEvents: { type: Array, default: () => [] }
})

// Event type helpers
function getEventTitle(type) {
  const titles = {
    contract_created: 'Hợp đồng được tạo',
    contract_renewal: 'Gia hạn hợp đồng',
    appendix_created: 'Phụ lục được tạo',
    appendix_approved: 'Phụ lục được phê duyệt',
    appendix_rejected: 'Phụ lục bị từ chối',
    contract_terminated: 'Hợp đồng bị chấm dứt',
  }
  return titles[type] || 'Sự kiện khác'
}

function getEventLabel(type) {
  const labels = {
    contract_created: 'Tạo mới',
    contract_renewal: 'Gia hạn',
    appendix_created: 'Phụ lục',
    appendix_approved: 'Phê duyệt',
    appendix_rejected: 'Từ chối',
    contract_terminated: 'Chấm dứt',
  }
  return labels[type] || type
}

function getEventSeverity(type) {
  const severities = {
    contract_created: 'success',
    contract_renewal: 'info',
    appendix_created: 'secondary',
    appendix_approved: 'success',
    appendix_rejected: 'danger',
    contract_terminated: 'danger',
  }
  return severities[type] || 'secondary'
}

function getStatusLabel(status) {
  const labels = {
    DRAFT: 'Nháp',
    PENDING_APPROVAL: 'Chờ duyệt',
    ACTIVE: 'Đã duyệt',
    REJECTED: 'Từ chối',
  }
  return labels[status] || status
}

function getStatusSeverity(status) {
  const severities = {
    DRAFT: 'secondary',
    PENDING_APPROVAL: 'warn',
    ACTIVE: 'success',
    REJECTED: 'danger',
  }
  return severities[status] || 'secondary'
}

// Icon helpers
function getIcon(type) {
  const icons = {
    contract_created: 'pi pi-file-plus',
    contract_renewal: 'pi pi-refresh',
    appendix_created: 'pi pi-file',
    appendix_approved: 'pi pi-check',
    appendix_rejected: 'pi pi-times',
    contract_terminated: 'pi pi-ban',
  }
  return icons[type] || 'pi pi-circle'
}

function getIconClass(type) {
  const classes = {
    contract_created: 'bg-green-100 text-green-600',
    contract_renewal: 'bg-blue-100 text-blue-600',
    appendix_created: 'bg-purple-100 text-purple-600',
    appendix_approved: 'bg-green-100 text-green-600',
    appendix_rejected: 'bg-red-100 text-red-600',
    contract_terminated: 'bg-gray-100 text-gray-600',
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

function getCardClass(type) {
  const classes = {
    contract_created: 'border-green-200 bg-green-50',
    contract_renewal: 'border-blue-200 bg-blue-50',
    appendix_created: 'border-purple-200 bg-purple-50',
    appendix_approved: 'border-green-200 bg-green-50',
    appendix_rejected: 'border-red-200 bg-red-50',
    contract_terminated: 'border-gray-200 bg-gray-50',
  }
  return classes[type] || 'border-gray-200 bg-white'
}
</script>

<style scoped>
.timeline-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.timeline-line {
  width: 2px;
  flex-grow: 1;
  background: linear-gradient(to bottom, #e5e7eb 0%, #f3f4f6 100%);
  margin-top: 0.5rem;
  min-height: 2rem;
}

.timeline-card {
  transition: all 0.2s ease;
}

.timeline-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}
</style>
