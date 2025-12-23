<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold">Lịch sử làm việc tại công ty</h3>
      <div class="text-sm text-gray-600">
        <span class="font-medium">Tổng thời gian:</span>
        <span class="text-blue-600 font-semibold">{{ cumulativeTenure }}</span>
      </div>
    </div>

    <div v-if="!employmentHistory || employmentHistory.length === 0"
         class="text-center py-8 bg-gray-50 rounded-lg">
      <i class="pi pi-info-circle text-4xl text-gray-400 mb-3"></i>
      <p class="text-gray-600">Chưa có lịch sử làm việc</p>
    </div>

    <DataTable v-else :value="employmentHistory" class="p-datatable-sm">
      <Column header="STT" headerStyle="width:4rem">
        <template #body="{ index }">{{ index + 1 }}</template>
      </Column>
      <Column field="start_date" header="Từ ngày" headerStyle="width:10rem">
        <template #body="{ data }">
          <span class="font-medium">{{ data.start_date }}</span>
        </template>
      </Column>
      <Column field="end_date" header="Đến ngày" headerStyle="width:10rem">
        <template #body="{ data }">
          <span :class="data.is_current ? 'text-green-600 font-semibold' : 'font-medium'">
            {{ data.end_date }}
          </span>
        </template>
      </Column>
      <Column field="duration" header="Thời gian" headerStyle="width:12rem">
        <template #body="{ data }">
          <Badge :value="data.duration" severity="info" size="large" />
        </template>
      </Column>
      <Column field="is_current" header="Trạng thái" headerStyle="width:10rem">
        <template #body="{ data }">
          <Badge v-if="data.is_current" value="Đang làm việc" severity="success" />
          <Badge v-else-if="data.end_reason" :value="getEndReasonLabel(data.end_reason)" severity="secondary" />
          <Badge v-else value="Đã kết thúc" severity="secondary" />
        </template>
      </Column>
    </DataTable>

    <!-- Summary Card -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
      <Card class="border border-blue-200 bg-blue-50">
        <template #content>
          <div class="flex items-center gap-3">
            <i class="pi pi-calendar text-blue-600 text-3xl"></i>
            <div>
              <p class="text-sm text-gray-600 mb-1">Thâm niên đợt hiện tại</p>
              <p class="text-xl font-bold text-blue-700">{{ currentTenure }}</p>
            </div>
          </div>
        </template>
      </Card>
      <Card class="border border-green-200 bg-green-50">
        <template #content>
          <div class="flex items-center gap-3">
            <i class="pi pi-chart-line text-green-600 text-3xl"></i>
            <div>
              <p class="text-sm text-gray-600 mb-1">Thâm niên tích lũy</p>
              <p class="text-xl font-bold text-green-700">{{ cumulativeTenure }}</p>
            </div>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup>
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import Card from 'primevue/card'

const props = defineProps({
  employmentHistory: { type: Array, default: () => [] },
  currentTenure: { type: String, default: '0 ngày' },
  cumulativeTenure: { type: String, default: '0 ngày' },
})

function getEndReasonLabel(reason) {
  const labels = {
    'CONTRACT_END': 'Hết hạn hợp đồng',
    'RESIGN': 'Nghỉ việc',
    'TERMINATION': 'Sa thải',
    'LAYOFF': 'Cắt giảm nhân sự',
    'RETIREMENT': 'Nghỉ hưu',
    'MATERNITY_LEAVE': 'Nghỉ sinh',
    'REHIRE': 'Tái tuyển dụng',
    'OTHER': 'Lý do khác'
  }
  return labels[reason] || reason
}
</script>
