<template>
    <Head>
        <title>{{ reportTitle }}</title>
    </Head>

    <div class="card">
        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-3xl font-bold">{{ reportTitle }}</h1>
            <Breadcrumb :home="breadcrumbHome" :model="breadcrumbItems" class="mt-2" />
            <p class="mt-2">Thống kê nghỉ phép theo loại và bộ phận</p>
        </div>

        <!-- Filter Bar -->
        <ReportFilterBar
            @apply="applyFilters"
            @clear="clearFilters"
            @export="exportReport"
        >
            <div>
                <label class="block text-sm font-medium mb-2">Năm</label>
                <Select
                    v-model="localFilters.year"
                    :options="yearOptions"
                    placeholder="Chọn năm"
                    fluid
                />
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Tháng</label>
                <Select
                    v-model="localFilters.month"
                    :options="monthOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Chọn tháng"
                    fluid
                />
            </div>
        </ReportFilterBar>

        <!-- KPI Cards -->
        <ReportKpiCards :kpis="kpiData" />

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- By Leave Type Chart -->
            <Card>
                <template #title>Theo loại phép</template>
                <template #content>
                    <Chart
                        type="pie"
                        :data="leaveTypeChartData"
                        :options="chartOptions"
                        class="h-80"
                    />
                </template>
            </Card>

            <!-- By Department Chart -->
            <Card>
                <template #title>Theo bộ phận</template>
                <template #content>
                    <Chart
                        type="bar"
                        :data="departmentChartData"
                        :options="barChartOptions"
                        class="h-80"
                    />
                </template>
            </Card>
        </div>

        <!-- By Leave Type Table -->
        <ReportTable
            title="Chi tiết theo loại phép"
            :data="byLeaveType"
            :paginator="false"
            class="mb-6"
        >
            <Column field="type_name" header="Loại phép" sortable />
            <Column field="request_count" header="Số đơn" sortable>
                <template #body="{ data }">
                    <span class="font-semibold">{{ data.request_count }}</span>
                </template>
            </Column>
            <Column field="total_days" header="Tổng ngày" sortable>
                <template #body="{ data }">
                    <span class="font-semibold text-primary">{{ data.total_days }}</span> ngày
                </template>
            </Column>
            <Column header="Tỷ lệ">
                <template #body="{ data }">
                    <ProgressBar
                        :value="calculateDaysPercentage(data.total_days)"
                        :showValue="true"
                        class="h-6"
                    />
                </template>
            </Column>
        </ReportTable>

        <!-- By Department Table -->
        <ReportTable
            title="Chi tiết theo bộ phận"
            :data="byDepartment"
            :paginator="false"
        >
            <Column field="department_name" header="Bộ phận" sortable />
            <Column field="request_count" header="Số đơn" sortable>
                <template #body="{ data }">
                    <span class="font-semibold">{{ data.request_count }}</span>
                </template>
            </Column>
            <Column field="total_days" header="Tổng ngày" sortable>
                <template #body="{ data }">
                    <span class="font-semibold text-primary">{{ data.total_days }}</span> ngày
                </template>
            </Column>
            <Column header="Tỷ lệ">
                <template #body="{ data }">
                    <ProgressBar
                        :value="calculateDaysPercentage(data.total_days)"
                        :showValue="true"
                        class="h-6"
                    />
                </template>
            </Column>
        </ReportTable>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ReportService } from '@/services/ReportService';
import ReportFilterBar from '@/Components/Reports/ReportFilterBar.vue';
import ReportKpiCards from '@/Components/Reports/ReportKpiCards.vue';
import ReportTable from '@/Components/Reports/ReportTable.vue';
import Breadcrumb from 'primevue/breadcrumb';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Chart from 'primevue/chart';
import Column from 'primevue/column';
import ProgressBar from 'primevue/progressbar';

// Report metadata
const reportTitle = 'Tổng hợp nghỉ phép tháng';

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
    { label: reportTitle },
];

// Props from controller
const props = defineProps({
    year: Number,
    month: Number,
    summary: Object,
    byLeaveType: Array,
    byDepartment: Array,
    filters: Object,
});

// Local state
const localFilters = ref({
    year: props.year,
    month: props.month,
});

const yearOptions = ReportService.getYearOptions(5);
const monthOptions = ReportService.getMonthOptions();

// Computed
const kpiData = computed(() => [
    {
        label: 'Tổng số đơn',
        value: props.summary.total_requests,
        format: 'number',
        icon: 'pi-file',
        iconColor: 'text-blue-500',
        valueColor: 'text-blue-600',
    },
    {
        label: 'Tổng số ngày',
        value: props.summary.total_days,
        format: 'number',
        icon: 'pi-calendar',
        iconColor: 'text-green-500',
        valueColor: 'text-green-600',
        subtitle: 'ngày nghỉ phép',
    },
    {
        label: 'Trung bình',
        value: props.summary.total_requests > 0
            ? (props.summary.total_days / props.summary.total_requests).toFixed(1)
            : 0,
        format: 'number',
        icon: 'pi-chart-line',
        iconColor: 'text-purple-500',
        valueColor: 'text-purple-600',
        subtitle: 'ngày/đơn',
    },
    {
        label: 'Kỳ báo cáo',
        value: `T${props.month}/${props.year}`,
        icon: 'pi-clock',
        iconColor: 'text-orange-500',
    },
]);

const leaveTypeChartData = computed(() => {
    const labels = props.byLeaveType.map(item => item.type_name);
    const data = props.byLeaveType.map(item => item.total_days);
    const colors = ReportService.getChartColors(labels.length);

    return {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: colors,
        }],
    };
});

const departmentChartData = computed(() => {
    const labels = props.byDepartment.map(item => item.department_name);
    const data = props.byDepartment.map(item => item.total_days);

    return {
        labels: labels,
        datasets: [{
            label: 'Số ngày nghỉ',
            data: data,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
        }],
    };
});

const chartOptions = computed(() => ReportService.getDefaultChartOptions('pie'));
const barChartOptions = computed(() => ReportService.getDefaultChartOptions('bar'));

// Methods
const applyFilters = () => {
    ReportService.leaveMonthly(localFilters.value);
};

const clearFilters = () => {
    const now = new Date();
    localFilters.value = {
        year: now.getFullYear(),
        month: now.getMonth() + 1,
    };
    applyFilters();
};

const exportReport = () => {
    ReportService.exportReport('leave-monthly', localFilters.value);
};

const calculateDaysPercentage = (days) => {
    return ReportService.calculatePercentage(days, props.summary.total_days);
};
</script>
