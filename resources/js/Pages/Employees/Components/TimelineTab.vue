<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold">Lịch sử hoạt động</h3>
      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-600">Lọc:</span>
        <Select
          v-model="selectedModule"
          :options="moduleOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Chọn module"
          showClear
          class="w-48"
          @change="onModuleChange"
        />
      </div>
    </div>

    <div v-if="loading" class="text-center py-8">
      <i class="pi pi-spin pi-spinner text-4xl text-gray-400"></i>
      <p class="text-gray-600 mt-3">Đang tải...</p>
    </div>

    <div v-else-if="!activities || activities.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
      <i class="pi pi-info-circle text-4xl text-gray-400 mb-3"></i>
      <p class="text-gray-600">Chưa có hoạt động nào</p>
    </div>

    <Timeline v-else :value="activities" align="left" class="customized-timeline">
      <template #marker="slotProps">
        <span
          class="flex w-8 h-8 items-center justify-center text-white rounded-full z-10"
          :class="getActivityColor(slotProps.item.log_name)"
        >
          <i :class="getActivityIcon(slotProps.item.description)" />
        </span>
      </template>
      <template #content="slotProps">
        <Card class="mt-3">
          <template #title>
            <div class="flex items-center justify-between">
              <span class="text-base">{{ getActivityLabel(slotProps.item) }}</span>
              <Badge
                :value="getModuleLabel(slotProps.item.log_name)"
                :severity="getModuleSeverity(slotProps.item.log_name)"
              />
            </div>
          </template>
          <template #subtitle>
            <div class="text-sm text-gray-600">
              <i class="pi pi-user mr-1" />{{ slotProps.item.causer?.name || 'Hệ thống' }}
              <i class="pi pi-clock ml-3 mr-1" />{{ formatDateTime(slotProps.item.created_at) }}
            </div>
          </template>
          <template #content>
            <div v-if="slotProps.item.properties" class="text-sm">
              <pre class="bg-gray-50 p-3 rounded text-xs overflow-auto">{{ JSON.stringify(slotProps.item.properties, null, 2) }}</pre>
            </div>
          </template>
        </Card>
      </template>
    </Timeline>

    <div v-if="pagination && pagination.last_page > 1" class="mt-4 flex justify-center">
      <Paginator
        :rows="pagination.per_page"
        :totalRecords="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page"
        @page="onPageChange"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Select from 'primevue/select'
import Timeline from 'primevue/timeline'
import Card from 'primevue/card'
import Badge from 'primevue/badge'
import Paginator from 'primevue/paginator'

const props = defineProps({
  employeeId: { type: String, required: true },
})

const activities = ref([])
const loading = ref(false)
const selectedModule = ref(null)
const pagination = ref(null)

const moduleOptions = [
  { label: 'Tất cả', value: null },
  { label: 'Phân công', value: 'employee-assignment' },
  { label: 'Học vấn', value: 'employee-education' },
  { label: 'Người thân', value: 'employee-relative' },
  { label: 'Kinh nghiệm', value: 'employee-experience' },
  { label: 'Kỹ năng', value: 'employee-skill' },
  { label: 'Khen thưởng & Kỷ luật', value: 'reward-discipline' },
]

async function loadActivities(page = 1) {
  loading.value = true
  try {
    const params = new URLSearchParams({ page: page.toString() })
    if (selectedModule.value) {
      params.append('module', selectedModule.value)
    }

    const response = await fetch(`/employees/${props.employeeId}/activities?${params}`)
    const data = await response.json()

    activities.value = data.data || []
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total
    }
  } catch (error) {
    console.error('Failed to load activities:', error)
  } finally {
    loading.value = false
  }
}

function onModuleChange() {
  loadActivities(1)
}

function onPageChange(event) {
  loadActivities(event.page + 1)
}

function getActivityColor(logName) {
  if (logName.includes('assignment')) return 'bg-blue-500'
  if (logName.includes('education')) return 'bg-purple-500'
  if (logName.includes('relative')) return 'bg-green-500'
  if (logName.includes('experience')) return 'bg-orange-500'
  if (logName.includes('skill')) return 'bg-pink-500'
  if (logName.includes('reward-discipline')) return 'bg-yellow-500'
  return 'bg-gray-500'
}

function getActivityIcon(description) {
  if (description.includes('created') || description.includes('Tạo')) return 'pi pi-plus'
  if (description.includes('updated') || description.includes('Cập nhật')) return 'pi pi-pencil'
  if (description.includes('deleted') || description.includes('Xóa')) return 'pi pi-trash'
  return 'pi pi-info-circle'
}

function getActivityLabel(activity) {
  if (activity.properties?.label) {
    return activity.properties.label
  }
  return activity.description || 'Hoạt động'
}

function getModuleLabel(logName) {
  if (logName.includes('assignment')) return 'Phân công'
  if (logName.includes('education')) return 'Học vấn'
  if (logName.includes('relative')) return 'Người thân'
  if (logName.includes('experience')) return 'Kinh nghiệm'
  if (logName.includes('skill')) return 'Kỹ năng'
  if (logName.includes('reward-discipline')) return 'KT & KL'
  return logName
}

function getModuleSeverity(logName) {
  if (logName.includes('assignment')) return 'info'
  if (logName.includes('education')) return 'secondary'
  if (logName.includes('relative')) return 'success'
  if (logName.includes('experience')) return 'warn'
  if (logName.includes('skill')) return 'danger'
  if (logName.includes('reward-discipline')) return 'warning'
  return 'secondary'
}

function formatDateTime(datetime) {
  if (!datetime) return '-'
  const date = new Date(datetime)
  return date.toLocaleString('vi-VN')
}

onMounted(() => {
  loadActivities()
})
</script>
