<template>
    <Head>
        <title>Báo cáo</title>
    </Head>

    <div>
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Báo cáo</h1>
                    <p class="mt-2">Tổng hợp các báo cáo và phân tích dữ liệu HRM</p>
                </div>
                <div class="flex gap-2">
                    <Button icon="pi pi-refresh" label="Làm mới" severity="secondary" outlined @click="refreshReports" />
                </div>
            </div>
        </div>

        <!-- Report Categories -->
        <div class="space-y-6">
            <div v-for="category in reportCategories" :key="category.name" class="card">
                <div class="flex items-center gap-3 mb-5 pb-3 border-b surface-border">
                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', getCategoryBgClass(category.icon)]">
                        <i :class="['pi', category.icon, 'text-xl', getCategoryIconClass(category.icon)]"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold">{{ category.name }}</h2>
                        <p class="text-sm opacity-70">{{ category.reports.length }} báo cáo</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="report in category.reports"
                        :key="report.code"
                        @click="openReport(report)"
                        class="relative p-5 border surface-border rounded-xl hover:border-primary hover:shadow-lg transition-all duration-300 cursor-pointer group bg-white dark:bg-gray-900"
                    >
                        <div class="flex items-start gap-3">
                            <div :class="['w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0', getReportIconBg(report.code)]">
                                <i :class="['pi', getReportIcon(report.code), 'text-lg', getReportIconColor(report.code)]"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold mb-1 group-hover:text-primary transition-colors">
                                    {{ report.name }}
                                </h3>
                                <p class="text-sm opacity-70 line-clamp-2">{{ report.description }}</p>
                            </div>
                        </div>
                        <div class="absolute top-5 right-5 opacity-0 group-hover:opacity-100 transition-opacity">
                            <i class="pi pi-arrow-right text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ReportService } from '@/services/ReportService';
import Button from 'primevue/button';

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

const refreshReports = () => {
    router.reload({ only: ['reportCategories'] });
};

// Category styling based on icon
const getCategoryBgClass = (icon) => {
    const colors = {
        'pi-users': 'bg-blue-100 dark:bg-blue-900/20',
        'pi-briefcase': 'bg-green-100 dark:bg-green-900/20',
        'pi-calendar': 'bg-purple-100 dark:bg-purple-900/20',
    };
    return colors[icon] || 'bg-gray-100 dark:bg-gray-800';
};

const getCategoryIconClass = (icon) => {
    const colors = {
        'pi-users': 'text-blue-600 dark:text-blue-400',
        'pi-briefcase': 'text-green-600 dark:text-green-400',
        'pi-calendar': 'text-purple-600 dark:text-purple-400',
    };
    return colors[icon] || 'text-gray-600';
};

// Report icon mapping
const getReportIcon = (code) => {
    const icons = {
        'headcount': 'pi-chart-bar',
        'employee-movement': 'pi-arrows-h',
        'employee-list': 'pi-list',
        'data-completeness': 'pi-check-circle',
        'contracts-status': 'pi-file',
        'contracts-expiring': 'pi-clock',
        'contract-approval-sla': 'pi-stopwatch',
        'leave-monthly': 'pi-calendar',
        'leave-balances': 'pi-wallet',
    };
    return icons[code] || 'pi-chart-line';
};

const getReportIconBg = (code) => {
    const bgs = {
        'headcount': 'bg-blue-100 dark:bg-blue-900/20',
        'employee-movement': 'bg-orange-100 dark:bg-orange-900/20',
        'employee-list': 'bg-cyan-100 dark:bg-cyan-900/20',
        'data-completeness': 'bg-green-100 dark:bg-green-900/20',
        'contracts-status': 'bg-indigo-100 dark:bg-indigo-900/20',
        'contracts-expiring': 'bg-red-100 dark:bg-red-900/20',
        'contract-approval-sla': 'bg-purple-100 dark:bg-purple-900/20',
        'leave-monthly': 'bg-pink-100 dark:bg-pink-900/20',
        'leave-balances': 'bg-teal-100 dark:bg-teal-900/20',
    };
    return bgs[code] || 'bg-gray-100 dark:bg-gray-800';
};

const getReportIconColor = (code) => {
    const colors = {
        'headcount': 'text-blue-600 dark:text-blue-400',
        'employee-movement': 'text-orange-600 dark:text-orange-400',
        'employee-list': 'text-cyan-600 dark:text-cyan-400',
        'data-completeness': 'text-green-600 dark:text-green-400',
        'contracts-status': 'text-indigo-600 dark:text-indigo-400',
        'contracts-expiring': 'text-red-600 dark:text-red-400',
        'contract-approval-sla': 'text-purple-600 dark:text-purple-400',
        'leave-monthly': 'text-pink-600 dark:text-pink-400',
        'leave-balances': 'text-teal-600 dark:text-teal-400',
    };
    return colors[code] || 'text-gray-600';
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
