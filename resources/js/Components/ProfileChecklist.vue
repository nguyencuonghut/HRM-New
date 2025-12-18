<template>
  <div class="profile-checklist-card">
    <!-- Header with Score Circle -->
    <div class="checklist-header">
      <div class="score-circle" :class="getScoreCircleClass()">
        <div class="score-number">{{ completionScore }}</div>
        <div class="score-label">điểm</div>
      </div>
      <div class="header-info">
        <h3 class="header-title">Hoàn thiện hồ sơ</h3>
        <Badge :value="completionLevel" :severity="completionSeverity" class="status-badge" />
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-section">
      <ProgressBar :value="completionScore" :show-value="false" class="custom-progress" />
      <div class="progress-text">{{ completionScore }}% hoàn thành</div>
    </div>

    <!-- Details List -->
    <div class="details-list">
      <div v-for="detail in completionDetails" :key="detail.item"
           class="detail-item"
           :class="getDetailClass(detail.status)">
        <div class="detail-left">
          <i :class="getStatusIcon(detail.status)" />
          <span class="detail-name">{{ detail.item }}</span>
        </div>
        <div class="detail-right">
          <span class="detail-score">{{ detail.score }}/{{ detail.max }}</span>
        </div>
      </div>
    </div>

    <!-- Missing Items -->
    <div v-if="completionMissing && completionMissing.length > 0" class="missing-section">
      <div class="missing-header">
        <i class="pi pi-exclamation-triangle" />
        <span>Cần bổ sung</span>
      </div>
      <ul class="missing-list">
        <li v-for="(missing, idx) in completionMissing" :key="idx">
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
      return 'status-complete'
    case 'partial':
      return 'status-partial'
    case 'incomplete':
      return 'status-incomplete'
    default:
      return 'status-default'
  }
}

function getScoreCircleClass() {
  const score = props.completionScore
  if (score >= 80) return 'circle-excellent'
  if (score >= 60) return 'circle-good'
  if (score >= 40) return 'circle-fair'
  return 'circle-poor'
}
</script>

<style scoped>
.profile-checklist-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Header with Score Circle */
.checklist-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.score-circle {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 3px solid;
  background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.6) 100%);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.circle-excellent {
  border-color: #10b981;
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
}

.circle-good {
  border-color: #3b82f6;
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

.circle-fair {
  border-color: #f59e0b;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}

.circle-poor {
  border-color: #ef4444;
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
}

.score-number {
  font-size: 24px;
  font-weight: 700;
  line-height: 1;
  color: #1f2937;
}

.score-label {
  font-size: 10px;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 2px;
}

.header-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.header-title {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.status-badge {
  align-self: flex-start;
}

/* Progress Section */
.progress-section {
  margin-bottom: 1.25rem;
}

.custom-progress {
  height: 8px;
  border-radius: 4px;
  margin-bottom: 0.5rem;
}

.progress-text {
  font-size: 12px;
  color: #6b7280;
  text-align: right;
}

/* Details List */
.details-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.detail-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  border-radius: 8px;
  transition: all 0.2s;
  border: 1px solid;
}

.detail-item.status-complete {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.detail-item.status-partial {
  background: #fffbeb;
  border-color: #fde68a;
}

.detail-item.status-incomplete {
  background: #fef2f2;
  border-color: #fecaca;
}

.detail-item.status-default {
  background: #f9fafb;
  border-color: #e5e7eb;
}

.detail-left {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  flex: 1;
  min-width: 0;
}

.detail-left i {
  font-size: 16px;
  flex-shrink: 0;
}

.detail-name {
  font-size: 13px;
  font-weight: 500;
  color: #374151;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.detail-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.detail-score {
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
}

/* Missing Section */
.missing-section {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.missing-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #f97316;
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 0.75rem;
}

.missing-header i {
  font-size: 16px;
}

.missing-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.missing-list li {
  font-size: 13px;
  color: #6b7280;
  padding-left: 1.25rem;
  position: relative;
}

.missing-list li::before {
  content: '•';
  position: absolute;
  left: 0.5rem;
  color: #f97316;
  font-weight: bold;
}

/* Mobile Responsive */
@media (max-width: 640px) {
  .profile-checklist-card {
    padding: 1rem;
  }

  .checklist-header {
    gap: 0.75rem;
  }

  .score-circle {
    width: 56px;
    height: 56px;
  }

  .score-number {
    font-size: 20px;
  }

  .score-label {
    font-size: 9px;
  }

  .header-title {
    font-size: 14px;
  }

  .detail-item {
    padding: 0.625rem;
  }

  .detail-name {
    font-size: 12px;
  }

  .detail-score {
    font-size: 12px;
  }

  .missing-list li {
    font-size: 12px;
  }
}

@media (max-width: 380px) {
  .score-circle {
    width: 48px;
    height: 48px;
  }

  .score-number {
    font-size: 18px;
  }

  .header-info {
    gap: 0.25rem;
  }

  .detail-left {
    gap: 0.5rem;
  }
}
</style>
