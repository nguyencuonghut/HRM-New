<template>
  <div class="card mb-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold">Mức độ hoàn thiện hồ sơ</h3>
      <Badge :value="completionLevel" :severity="completionSeverity" />
    </div>

    <!-- Progress Bar -->
    <div class="mb-4">
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium">Điểm hoàn thiện</span>
        <span class="text-sm font-bold">{{ completionScore }}/100</span>
      </div>
      <ProgressBar :value="completionScore" :show-value="false" />
    </div>

    <!-- Details List -->
    <div class="space-y-2">
      <div v-for="detail in completionDetails" :key="detail.item"
           class="flex items-center justify-between p-3 rounded border"
           :class="getDetailClass(detail.status)">
        <div class="flex items-center gap-2">
          <i :class="getStatusIcon(detail.status)" />
          <span class="text-sm">{{ detail.item }}</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm font-medium">{{ detail.score }}/{{ detail.max }}</span>
          <Tag v-if="detail.status === 'partial'" value="Chưa đủ" severity="warn" size="small" />
        </div>
      </div>
    </div>

    <!-- Missing Items -->
    <div v-if="completionMissing && completionMissing.length > 0" class="mt-4">
      <Divider />
      <h4 class="text-md font-semibold mb-3 text-orange-600">
        <i class="pi pi-exclamation-triangle mr-2" />Cần bổ sung
      </h4>
      <ul class="list-disc list-inside space-y-1">
        <li v-for="(missing, idx) in completionMissing" :key="idx" class="text-sm text-gray-700">
          {{ missing.item }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import ProgressBar from 'primevue/progressbar'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'

const props = defineProps({
  completionScore: { type: Number, default: 0 },
  completionDetails: { type: Array, default: () => [] },
  completionMissing: { type: Array, default: () => [] },
  completionLevel: { type: String, default: '' },
  completionSeverity: { type: String, default: 'secondary' }
})

function getStatusIcon(status) {
  switch (status) {
    case 'complete':
      return 'pi pi-check-circle text-green-500'
    case 'partial':
      return 'pi pi-exclamation-circle text-orange-500'
    case 'incomplete':
      return 'pi pi-times-circle text-red-500'
    default:
      return 'pi pi-minus-circle text-gray-400'
  }
}

function getDetailClass(status) {
  switch (status) {
    case 'complete':
      return 'bg-green-50 border-green-200'
    case 'partial':
      return 'bg-orange-50 border-orange-200'
    case 'incomplete':
      return 'bg-red-50 border-red-200'
    default:
      return 'bg-gray-50 border-gray-200'
  }
}
</script>
