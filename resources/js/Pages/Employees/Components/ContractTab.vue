<template>
  <div>
    <div v-if="!contracts || contracts.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
      <i class="pi pi-file-excel text-4xl text-gray-400 mb-3"></i>
      <p class="text-gray-600">Chưa có hợp đồng nào</p>
    </div>

    <DataTable
      v-else
      :value="contracts"
      v-model:expandedRows="expandedRows"
      dataKey="id"
      :paginator="true"
      :rows="10"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[5, 10, 25]"
      currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} hợp đồng"
    >
      <template #header>
        <div class="flex justify-between items-center">
          <h4 class="m-0">Lịch sử hợp đồng</h4>
          <Badge :value="`${contracts?.length || 0} hợp đồng`" severity="info" />
        </div>
      </template>

      <Column expander style="width: 3rem" />

      <Column header="Số HĐ" style="min-width: 12rem">
        <template #body="{ data }">
          <a :href="`/contracts/${data.id}`" class="text-primary underline hover:text-primary-600">
            {{ data.contract_number }}
          </a>
        </template>
      </Column>

      <Column field="contract_type_label" header="Loại HĐ" style="min-width: 12rem" />

      <Column header="Thời hạn" style="min-width: 20rem">
        <template #body="{ data }">
          <div class="flex items-center gap-2">
            <span>{{ formatDate(data.start_date) }}</span>
            <i class="pi pi-arrow-right text-xs text-gray-400"></i>
            <span>{{ formatDate(data.end_date) || 'Không xác định' }}</span>
          </div>
        </template>
      </Column>

      <Column header="Đơn vị / Chức danh" style="min-width: 16rem">
        <template #body="{ data }">
          <div class="text-sm">
            <div class="font-medium">{{ data.department?.name || '-' }}</div>
            <div class="text-gray-500">{{ data.position?.title || '-' }}</div>
          </div>
        </template>
      </Column>

      <Column header="Trạng thái" style="min-width: 12rem">
        <template #body="{ data }">
          <Tag :value="data.status_label" :severity="statusSeverity(data.status)" />
        </template>
      </Column>

      <Column header="Phụ lục" style="min-width: 8rem">
        <template #body="{ data }">
          <Badge :value="data.appendixes?.data?.length || 0" :severity="data.appendixes?.data?.length > 0 ? 'info' : 'secondary'" />
        </template>
      </Column>

      <Column header="Thao tác" style="min-width: 8rem">
        <template #body="{ data }">
          <Button
            icon="pi pi-external-link"
            outlined
            rounded
            @click="goToContract(data)"
            v-tooltip="'Xem chi tiết'"
          />
        </template>
      </Column>

      <!-- Expanded Row: Danh sách phụ lục -->
      <template #expansion="{ data }">
        <div class="p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center justify-between mb-3">
            <h5 class="font-semibold text-gray-700">Phụ lục của hợp đồng {{ data.contract_number }}</h5>
            <Badge :value="`${data.appendixes?.data?.length || 0} phụ lục`" severity="info" />
          </div>

          <DataTable
            v-if="data.appendixes?.data?.length > 0"
            :value="data.appendixes.data"
            class="p-datatable-sm"
          >
            <Column header="STT" style="width: 4rem">
              <template #body="slotProps">{{ slotProps.index + 1 }}</template>
            </Column>

            <Column field="appendix_no" header="Số phụ lục" style="min-width: 10rem" />

            <Column header="Loại" style="min-width: 14rem">
              <template #body="{ data: appendix }">
                <Tag :value="getAppendixTypeLabel(appendix.appendix_type_label)" severity="secondary" />
              </template>
            </Column>

            <Column field="title" header="Tiêu đề" style="min-width: 16rem" />

            <Column header="Ngày hiệu lực" style="min-width: 10rem">
              <template #body="{ data: appendix }">
                {{ formatDate(appendix.effective_date) }}
              </template>
            </Column>

            <Column header="Trạng thái" style="min-width: 10rem">
              <template #body="{ data: appendix }">
                <Tag :value="appendix.status_label" :severity="appendixStatusSeverity(appendix.status)" />
              </template>
            </Column>
          </DataTable>

          <div v-else class="text-center py-6 text-gray-500">
            <i class="pi pi-info-circle text-2xl mb-2"></i>
            <p>Hợp đồng này chưa có phụ lục</p>
          </div>
        </div>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import { formatDate } from '@/utils/dateHelper'

const props = defineProps({
  contracts: { type: Array, default: () => [] }
})

const expandedRows = ref({})

function statusSeverity(status) {
  const map = {
    DRAFT: 'secondary',
    PENDING_APPROVAL: 'warn',
    ACTIVE: 'success',
    REJECTED: 'danger',
    TERMINATED: 'contrast',
    EXPIRED: 'contrast',
    CANCELLED: 'contrast',
    SUSPENDED: 'contrast'
  }
  return map[status] || 'info'
}

function appendixStatusSeverity(status) {
  const map = {
    DRAFT: 'secondary',
    PENDING_APPROVAL: 'warn',
    ACTIVE: 'success',
    REJECTED: 'danger'
  }
  return map[status] || 'info'
}

function getAppendixTypeLabel(type) {
  const map = {
    SALARY_ADJUSTMENT: 'Điều chỉnh lương',
    ALLOWANCE_ADJUSTMENT: 'Điều chỉnh phụ cấp',
    POSITION_CHANGE: 'Thay đổi vị trí',
    DEPARTMENT_TRANSFER: 'Điều chuyển phòng ban',
    WORKING_TIME_CHANGE: 'Thay đổi giờ làm',
    CONTRACT_EXTENSION: 'Gia hạn HĐ',
    OTHER: 'Khác'
  }
  return map[type] || type
}

function goToContract(contract) {
  window.location.href = `/contracts/${contract.id}`
}
</script>
