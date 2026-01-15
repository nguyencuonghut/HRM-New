<template>
    <Head>
        <title>{{ pageTitle }}</title>
    </Head>

    <div class="card">
        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-3xl font-bold">{{ pageTitle }}</h1>
            <Breadcrumb :home="breadcrumbHome" :model="breadcrumbItems" class="mt-2" />
            <p class="mt-2">Tổng số nhân viên theo bộ phận và vị trí tại một thời điểm</p>
        </div>

        <!-- Filter Bar -->
        <ReportFilterBar
            @apply="applyFilters"
            @clear="clearFilters"
            @export="exportReport"
        >
            <div class="flex-1">
                <label class="block text-sm font-medium mb-2">Thời điểm</label>
                <DatePicker
                    v-model="filters.as_of_date"
                    dateFormat="yy-mm-dd"
                    placeholder="Chọn ngày"
                    showIcon
                    fluid
                />
            </div>
        </ReportFilterBar>

        <!-- KPI Cards -->
        <ReportKpiCards :kpis="kpiData" />

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- By Department Chart -->
            <Card>
                <template #title>Theo Bộ phận</template>
                <template #content>
                    <Chart
                        type="pie"
                        :data="departmentChartData"
                        :options="chartOptions"
                        class="h-80"
                    />
                </template>
            </Card>

            <!-- By Position Chart -->
            <Card>
                <template #title>Theo Vị trí</template>
                <template #content>
                    <Chart
                        type="bar"
                        :data="positionChartData"
                        :options="barChartOptions"
                        class="h-80"
                    />
                </template>
            </Card>
        </div>

        <!-- Department Breakdown TreeTable -->
        <Card class="mt-6">
            <template #title>Chi tiết theo Bộ phận (Cây)</template>
            <template #content>
                <TreeTable :value="departmentTree" :tableStyle="{ minWidth: '50rem' }" expandableRows rowKey="key">
                    <Column header="Bộ phận" expander>
                        <template #body="{ node }">
                            <span>{{ node.label }}</span>
                        </template>
                    </Column>
                    <Column field="data.headcount" header="Số lượng">
                        <template #body="{ node }">
                            <span class="font-semibold">{{ node.data.headcount }}</span>
                        </template>
                    </Column>
                    <Column header="Tỷ lệ" style="min-width: 280px">
                        <template #body="{ node }">
                            <div class="w-full">
                                <ProgressBar
                                    :value="calculatePercentage(node.data.headcount)"
                                    :showValue="true"
                                    class="h-6 w-full"
                                />
                            </div>
                        </template>
                    </Column>
                </TreeTable>
            </template>
        </Card>

        <!-- Employment Type Breakdown -->
        <Card class="mt-6">
            <template #title>Phân loại theo hợp đồng</template>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        v-for="(count, type) in byEmploymentType"
                        :key="type"
                        class="p-4 border border-gray-200 rounded-lg text-center"
                    >
                        <div class="text-2xl font-bold text-primary mb-2">
                            {{ count }}
                        </div>
                        <div class="text-sm">{{ type }}</div>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ReportService } from '@/services/ReportService';
import ReportFilterBar from '@/Components/Reports/ReportFilterBar.vue';
import ReportKpiCards from '@/Components/Reports/ReportKpiCards.vue';
import TreeTable from 'primevue/treetable';
import Breadcrumb from 'primevue/breadcrumb';
import Card from 'primevue/card';
import DatePicker from 'primevue/datepicker';
import Chart from 'primevue/chart';
import Column from 'primevue/column';
import ProgressBar from 'primevue/progressbar';

// Report metadata
const reportCode = 'headcount';
const pageTitle = ReportService.getReportTitle(reportCode);

// Breadcrumb
const breadcrumbHome = {
    icon: 'pi pi-home',
    command: () => ReportService.hub()
};
const breadcrumbItems = [
    {
        label: 'Báo cáo',
        command: () => ReportService.hub()
    },
    { label: pageTitle },
];

// Props from controller
const props = defineProps({
    asOfDate: String,
    totalHeadcount: Number,
    departmentTree: Array, // hierarchical tree from backend
    byPosition: Array,
    byEmploymentType: Object,
    filters: Object,
});

// Local state - Presentation only
const filters = ref({
    as_of_date: props.asOfDate,
});

// Computed - Use service for calculations
const kpiData = computed(() => [
    {
        label: 'Tổng số nhân viên',
        value: props.totalHeadcount,
        format: 'number',
        icon: 'pi-users',
        iconColor: 'text-blue-500',
        valueColor: 'text-blue-600',
    },
    {
        label: 'Số bộ phận',
        value: props.departmentTree ? props.departmentTree.length : 0,
        format: 'number',
        icon: 'pi-building',
        iconColor: 'text-green-500',
        valueColor: 'text-green-600',
    },
    {
        label: 'Số vị trí',
        value: props.byPosition.length,
        format: 'number',
        icon: 'pi-briefcase',
        iconColor: 'text-purple-500',
        valueColor: 'text-purple-600',
    },
    {
        label: 'Thời điểm',
        value: formatDisplayDate(props.asOfDate),
        icon: 'pi-calendar',
        iconColor: 'text-orange-500',
    },
]);

const departmentChartData = computed(() => {
    // Only use root departments for chart
    const labels = props.departmentTree ? props.departmentTree.map(d => d.label) : [];
    const data = props.departmentTree ? props.departmentTree.map(d => d.data.headcount) : [];
    const colors = ReportService.getChartColors(labels.length);
    return {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: colors,
        }],
    };
});

const positionChartData = computed(() => {
    const labels = props.byPosition.map(p => p.position_name);
    const data = props.byPosition.map(p => p.count);

    return {
        labels: labels,
        datasets: [{
            label: 'Số lượng',
            data: data,
            backgroundColor: ReportService.getChartColors(1)[0],
            borderColor: ReportService.getChartColors(1)[0],
            borderWidth: 1,
        }],
    };
});

const chartOptions = computed(() => ReportService.getDefaultChartOptions('pie'));
const barChartOptions = computed(() => ReportService.getDefaultChartOptions('bar'));

// Methods - Business logic delegated to service
const applyFilters = () => {
    ReportService.headcount(filters.value);
};

const clearFilters = () => {
    filters.value = {
        as_of_date: new Date().toISOString().split('T')[0],
    };
    applyFilters();
};

const exportReport = () => {
    ReportService.exportReport('headcount', filters.value);
};

const calculatePercentage = (count) => {
    if (!props.totalHeadcount || props.totalHeadcount === 0) return 0;
    return Math.round((count / props.totalHeadcount) * 100);
};

const formatDisplayDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('vi-VN');
};
</script>
