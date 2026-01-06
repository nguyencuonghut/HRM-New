<script setup>
import { ref, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import Breadcrumb from 'primevue/breadcrumb';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Chart from 'primevue/chart';
import Select from 'primevue/select';
import ReportFilterBar from '@/Components/Reports/ReportFilterBar.vue';
import ReportKpiCards from '@/Components/Reports/ReportKpiCards.vue';
import ReportService from '@/services/ReportService';
import { formatDate } from '@/utils/dateHelper';

const props = defineProps({
    newHires: Array,
    terminations: Array,
    transfers: Array,
    summary: Object,
    filters: Object,
    dateRange: Object,
    departments: Array,
});

// Report title
const reportTitle = 'Biến động nhân sự';

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

// Local filters
const localFilters = ref({
    period: props.filters?.period || null,
    year: props.filters?.year || null,
    month: props.filters?.month || null,
    quarter: props.filters?.quarter || null,
    department_id: props.filters?.department_id || null,
});

// Period options
const periodOptions = ReportService.getPeriodOptions();
const yearOptions = ReportService.getYearOptions();
const monthOptions = ReportService.getMonthOptions();
const quarterOptions = [
    { label: 'Quý 1', value: 1 },
    { label: 'Quý 2', value: 2 },
    { label: 'Quý 3', value: 3 },
    { label: 'Quý 4', value: 4 },
];

// Format termination reason
const formatTerminationReason = (reason) => {
    const reasons = {
        'RESIGN': 'Nghỉ việc tự nguyện',
        'TERMINATION': 'Sa thải',
        'CONTRACT_END': 'Hết hạn hợp đồng',
        'LAYOFF': 'Cho thôi việc',
        'RETIREMENT': 'Nghỉ hưu',
        'MATERNITY_LEAVE': 'Nghỉ sinh',
        'REHIRE': 'Tái tuyển dụng',
        'OTHER': 'Khác'
    };
    return reasons[reason] || reason;
};

const departmentOptions = computed(() => {
    return props.departments || [];
});

// Watch for month/quarter mutual exclusivity
watch(() => localFilters.value.month, (newMonth) => {
    if (newMonth !== null) {
        localFilters.value.quarter = null;
    }
});

watch(() => localFilters.value.quarter, (newQuarter) => {
    if (newQuarter !== null) {
        localFilters.value.month = null;
    }
});

// KPI Data
const kpiData = computed(() => [
    {
        label: 'Nhân viên mới',
        value: props.summary?.new_hires || 0,
        format: 'number',
        icon: 'pi-user-plus',
        iconColor: 'text-green-500',
        valueColor: 'text-green-600',
    },
    {
        label: 'Nghỉ việc',
        value: props.summary?.terminations || 0,
        format: 'number',
        icon: 'pi-user-minus',
        iconColor: 'text-red-500',
        valueColor: 'text-red-600',
    },
    {
        label: 'Điều chuyển',
        value: props.summary?.transfers || 0,
        format: 'number',
        icon: 'pi-arrows-h',
        iconColor: 'text-blue-500',
        valueColor: 'text-blue-600',
    },
    {
        label: 'Thay đổi ròng',
        value: props.summary?.net_change || 0,
        format: 'number',
        icon: 'pi-chart-line',
        iconColor: props.summary?.net_change >= 0 ? 'text-green-500' : 'text-red-500',
        valueColor: props.summary?.net_change >= 0 ? 'text-green-600' : 'text-red-600',
    },
]);

// Chart data
const chartData = computed(() => {
    return {
        labels: ['Nhân viên mới', 'Nghỉ việc', 'Điều chuyển'],
        datasets: [{
            label: 'Số lượng',
            data: [
                props.summary?.new_hires || 0,
                props.summary?.terminations || 0,
                props.summary?.transfers || 0,
            ],
            backgroundColor: ['#10b981', '#ef4444', '#3b82f6'],
            borderColor: ['#10b981', '#ef4444', '#3b82f6'],
            borderWidth: 1,
        }]
    };
});

const chartOptions = computed(() => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
            title: {
                display: true,
                text: 'Biến động nhân sự',
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                },
            },
        },
    };
});

// Handlers
const handleApply = () => {
    ReportService.viewReport('employee-movement', localFilters.value);
};

const handleClear = () => {
    localFilters.value = {
        period: null,
        year: null,
        month: null,
        quarter: null,
        department_id: null,
    };
    ReportService.viewReport('employee-movement');
};

const handleExport = () => {
    ReportService.exportReport('employee-movement', localFilters.value);
};

const handleBackToHub = () => {
    ReportService.hub();
};
</script>

<template>
    <Head :title="reportTitle" />

    <div class="card">
        <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="text-3xl font-bold">{{ reportTitle }}</h1>
                    <Breadcrumb :home="breadcrumbHome" :model="breadcrumbItems" class="mt-2" />
                </div>
            </div>

            <!-- Filter Bar -->
            <ReportFilterBar
                @apply="handleApply"
                @clear="handleClear"
                @export="handleExport"
            >
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Kỳ báo cáo</label>
                            <Select
                                v-model="localFilters.period"
                                :options="periodOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Chọn kỳ"
                                fluid
                                showClear
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Năm</label>
                            <Select
                                v-model="localFilters.year"
                                :options="yearOptions"
                                placeholder="Chọn năm"
                                fluid
                                showClear
                            />
                        </div>

                        <div v-if="localFilters.year">
                            <label class="block text-sm font-medium mb-2">Tháng</label>
                            <Select
                                v-model="localFilters.month"
                                :options="monthOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Chọn tháng"
                                fluid
                                showClear
                            />
                        </div>

                        <div v-if="localFilters.year">
                            <label class="block text-sm font-medium mb-2">Quý</label>
                            <Select
                                v-model="localFilters.quarter"
                                :options="quarterOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Chọn quý"
                                fluid
                                showClear
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Phòng ban</label>
                            <Select
                                v-model="localFilters.department_id"
                                :options="departmentOptions"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Tất cả phòng ban"
                                fluid
                                showClear
                                filter
                            />
                        </div>
                    </div>
            </ReportFilterBar>

            <!-- KPI Cards -->
            <ReportKpiCards :kpis="kpiData" class="mb-6" />

            <!-- Chart -->
            <div class="mb-6">
                <Chart type="bar" :data="chartData" :options="chartOptions" class="h-64" />
            </div>

            <!-- Tabs for different movement types -->
            <Tabs value="0">
                <TabList>
                    <Tab value="0">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-user-plus text-green-500"></i>
                            <span>Nhân viên mới</span>
                            <Tag :value="newHires.length" severity="success" class="ml-2" />
                        </div>
                    </Tab>
                    <Tab value="1">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-user-minus text-red-500"></i>
                            <span>Nghỉ việc</span>
                            <Tag :value="terminations.length" severity="danger" class="ml-2" />
                        </div>
                    </Tab>
                    <Tab value="2">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-arrows-h text-blue-500"></i>
                            <span>Điều chuyển</span>
                            <Tag :value="transfers.length" severity="info" class="ml-2" />
                        </div>
                    </Tab>
                </TabList>

                <TabPanels>
                    <!-- New Hires Tab -->
                    <TabPanel value="0">
                        <DataTable
                        :value="newHires"
                        paginator
                        :rows="20"
                        :rowsPerPageOptions="[10, 20, 50]"
                        responsiveLayout="scroll"
                        stripedRows
                        class="text-sm"
                    >
                        <Column field="code" header="Mã NV" sortable style="min-width: 100px" />
                        <Column field="full_name" header="Họ tên" sortable style="min-width: 180px" />
                        <Column field="department" header="Phòng ban" sortable style="min-width: 150px" />
                        <Column field="position" header="Chức vụ" sortable style="min-width: 150px" />
                        <Column field="hire_date" header="Ngày vào làm" sortable style="min-width: 120px">
                            <template #body="{ data }">
                                {{ formatDate(data.hire_date) }}
                            </template>
                        </Column>
                    </DataTable>
                    </TabPanel>

                    <!-- Terminations Tab -->
                    <TabPanel value="1">
                        <DataTable
                        :value="terminations"
                        paginator
                        :rows="20"
                        :rowsPerPageOptions="[10, 20, 50]"
                        responsiveLayout="scroll"
                        stripedRows
                        class="text-sm"
                    >
                        <Column field="code" header="Mã NV" sortable style="min-width: 100px" />
                        <Column field="full_name" header="Họ tên" sortable style="min-width: 180px" />
                        <Column field="department" header="Phòng ban" sortable style="min-width: 150px" />
                        <Column field="position" header="Chức vụ" sortable style="min-width: 150px" />
                        <Column field="termination_date" header="Ngày nghỉ" sortable style="min-width: 120px">
                            <template #body="{ data }">
                                {{ formatDate(data.termination_date) }}
                            </template>
                        </Column>
                        <Column field="termination_reason" header="Lý do" sortable style="min-width: 200px">
                            <template #body="{ data }">
                                {{ formatTerminationReason(data.termination_reason) }}
                            </template>
                        </Column>
                    </DataTable>
                    </TabPanel>

                    <!-- Transfers Tab -->
                    <TabPanel value="2">
                        <DataTable
                        :value="transfers"
                        paginator
                        :rows="20"
                        :rowsPerPageOptions="[10, 20, 50]"
                        responsiveLayout="scroll"
                        stripedRows
                        class="text-sm"
                    >
                        <Column field="employee_code" header="Mã NV" sortable style="min-width: 100px" />
                        <Column field="employee_name" header="Họ tên" sortable style="min-width: 180px" />
                        <Column field="department" header="Phòng ban" sortable style="min-width: 150px" />
                        <Column field="position" header="Chức vụ" sortable style="min-width: 150px" />
                        <Column field="start_date" header="Ngày bắt đầu" sortable style="min-width: 140px">
                            <template #body="{ data }">
                                {{ formatDate(data.start_date) }}
                            </template>
                        </Column>
                    </DataTable>
                    </TabPanel>
                </TabPanels>
            </Tabs>
    </div>
</template>
