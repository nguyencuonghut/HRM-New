<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <Card v-for="(kpi, index) in kpis" :key="index" class="kpi-card">
            <template #content>
                <div class="text-center py-2">
                    <div v-if="kpi.icon" class="mb-3">
                        <i :class="['pi', kpi.icon, 'text-4xl', kpi.iconColor || 'text-primary']"></i>
                    </div>
                    <div :class="['text-3xl font-bold mb-2', kpi.valueColor || 'text-primary']">
                        {{ formatValue(kpi.value, kpi.format) }}
                    </div>
                    <div class="text-sm font-medium">
                        {{ kpi.label }}
                    </div>
                    <div v-if="kpi.subtitle" class="text-xs mt-1">
                        {{ kpi.subtitle }}
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>

<script setup>
import Card from 'primevue/card';
import { ReportService } from '@/services/ReportService';

// Props
const props = defineProps({
    kpis: {
        type: Array,
        required: true,
        // Expected structure:
        // [{
        //   label: 'Total Employees',
        //   value: 150,
        //   icon: 'pi-users',
        //   iconColor: 'text-blue-500',
        //   valueColor: 'text-blue-600',
        //   format: 'number', // 'number', 'percent', 'currency', or null
        //   subtitle: 'Active employees'
        // }]
    },
});

// Methods - Presentation only, use ReportService for formatting
const formatValue = (value, format) => {
    if (value === null || value === undefined) return '-';

    switch (format) {
        case 'number':
            return ReportService.formatNumber(value);
        case 'percent':
            return ReportService.formatPercent(value);
        case 'currency':
            return ReportService.formatNumber(value) + ' đ';
        default:
            return value;
    }
};
</script>

<style scoped>
.kpi-card :deep(.p-card-body) {
    padding: 1rem;
}

.kpi-card :deep(.p-card-content) {
    padding: 0;
}
</style>
