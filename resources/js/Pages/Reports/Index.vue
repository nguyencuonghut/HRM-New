<template>
    <Head>
        <title>Báo cáo</title>
    </Head>

    <div>
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold">Báo cáo</h1>
            <p class="mt-2">Tổng hợp các báo cáo và phân tích dữ liệu HRM</p>
        </div>

        <!-- Report Categories -->
        <div class="space-y-6">
            <Card v-for="category in reportCategories" :key="category.name">
                <template #title>
                    <div class="flex items-center gap-3">
                        <i :class="['pi', category.icon, 'text-2xl text-primary']"></i>
                        <span>{{ category.name }}</span>
                    </div>
                </template>

                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="report in category.reports"
                            :key="report.code"
                            @click="openReport(report)"
                            class="p-4 border surface-border rounded-lg hover:border-primary hover:shadow-md transition-all cursor-pointer group"
                        >
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="font-semibold group-hover:text-primary transition-colors">
                                    {{ report.name }}
                                </h3>
                                <i class="pi pi-arrow-right group-hover:text-primary transition-colors"></i>
                            </div>
                            <p class="text-sm">{{ report.description }}</p>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ReportService } from '@/services/ReportService';
import Card from 'primevue/card';

// Props from controller
const props = defineProps({
    reportCategories: Array,
});

// Methods - Presentation logic only, business logic in ReportService
const openReport = (report) => {
    // Check if external link (e.g., Activity Log, Backup)
    if (report.external) {
        window.location.href = report.external;
        return;
    }

    // Navigate using service
    ReportService.viewReport(report.code);
};
</script>

<style scoped>
.grid > div {
    min-height: 120px;
}
</style>
