<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import Breadcrumb from 'primevue/breadcrumb';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Chart from 'primevue/chart';
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import Message from 'primevue/message';
import ReportFilterBar from '@/Components/Reports/ReportFilterBar.vue';
import ReportKpiCards from '@/Components/Reports/ReportKpiCards.vue';
import ReportService from '@/services/ReportService';
import { formatDate } from '@/utils/dateHelper';

// Report metadata
const reportTitle = 'Thời gian phê duyệt hợp đồng (SLA)';

const props = defineProps({
    contracts: Array,
    summary: Object,
    dateRange: Object,
    filters: Object,
});

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
    date_from: props.filters?.date_from || null,
    date_to: props.filters?.date_to || null,
    status: props.filters?.status || null,
    sla_target: props.filters?.sla_target || 3, // Default 3 days
});

// Status options
const statusOptions = [
    { label: 'Đang chờ duyệt', value: 'pending' },
    { label: 'Đã duyệt', value: 'approved' },
    { label: 'Từ chối', value: 'rejected' },
];

// KPI Data
const kpiData = computed(() => [
    {
        label: 'Tổng số HĐ',
        value: props.summary?.total || 0,
        format: 'number',
        icon: 'pi-file-edit',
        iconColor: 'text-blue-500',
        valueColor: 'text-blue-600',
    },
    {
        label: 'Thời gian TB (ngày)',
        value: props.summary?.avg_approval_days || 0,
        format: 'number',
        icon: 'pi-clock',
        iconColor: 'text-purple-500',
        valueColor: 'text-purple-600',
    },
    {
        label: 'Đúng SLA (< 48h)',
        value: props.summary?.sla_compliance_percent || 0,
        format: 'percent',
        icon: 'pi-check-circle',
        iconColor: 'text-green-500',
        valueColor: 'text-green-600',
    },
    {
        label: 'Đã duyệt',
        value: props.summary?.approved || 0,
        format: 'number',
        icon: 'pi-check',
        iconColor: 'text-green-500',
        valueColor: 'text-red-600',
    },
]);

// Chart data - Approval time distribution
const chartData = computed(() => {
    if (!props.summary?.time_distribution) {
        return { labels: [], datasets: [] };
    }

    const labels = ['<= 1 ngày', '2-3 ngày', '4-7 ngày', '> 7 ngày'];
    const data = [
        props.summary.time_distribution.within_1_day || 0,
        props.summary.time_distribution.within_3_days || 0,
        props.summary.time_distribution.within_7_days || 0,
        props.summary.time_distribution.over_7_days || 0,
    ];

    return ReportService.prepareChartData(labels, data, 'bar');
});

const chartOptions = computed(() => {
    return ReportService.getDefaultChartOptions('bar', {
        plugins: {
            title: {
                display: true,
                text: 'Phân bổ thời gian phê duyệt',
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
    });
});

// Get SLA status severity
const getSlaStatusSeverity = (withinSla) => {
    return withinSla ? 'success' : 'danger';
};

// Handlers
const handleApply = () => {
    ReportService.viewReport('contract-approval-sla', localFilters.value);
};

const handleClear = () => {
    localFilters.value = {
        date_from: null,
        date_to: null,
        status: null,
        sla_target: 3,
    };
    ReportService.viewReport('contract-approval-sla');
};

const handleExport = () => {
    ReportService.exportReport('contract-approval-sla', localFilters.value);
};

const handleBackToHub = () => {
    ReportService.navigateToHub();
};
</script>

<template>
    <Head :title="reportTitle" />

    <div class="card">
        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-3xl font-bold">{{ reportTitle }}</h1>
            <p class="text-sm mt-1">SLA (Service Level Agreement - Thời gian cam kết xử lý)</p>
            <Breadcrumb :home="breadcrumbHome" :model="breadcrumbItems" class="mt-2" />
        </div>

        <!-- Filter Bar -->
        <ReportFilterBar
            @apply="handleApply"
            @clear="handleClear"
            @export="handleExport"
        >
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Từ ngày</label>
                    <DatePicker
                        v-model="localFilters.date_from"
                        dateFormat="dd/mm/yy"
                        placeholder="dd/mm/yyyy"
                        fluid
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Đến ngày</label>
                    <DatePicker
                        v-model="localFilters.date_to"
                        dateFormat="dd/mm/yy"
                        placeholder="dd/mm/yyyy"
                        fluid
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Trạng thái</label>
                    <Select
                        v-model="localFilters.status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Tất cả trạng thái"
                        fluid
                        showClear
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">SLA mục tiêu (ngày)</label>
                    <InputNumber
                        v-model="localFilters.sla_target"
                        :min="1"
                        :max="30"
                        placeholder="VD: 3"
                        fluid
                    />
                </div>
            </div>
        </ReportFilterBar>

        <!-- KPI Cards -->
        <ReportKpiCards :kpis="kpiData" class="mb-6" />

        <!-- Chart -->
        <div class="mb-6" v-if="chartData.labels && chartData.labels.length > 0">
            <Chart type="bar" :data="chartData" :options="chartOptions" class="h-64" />
        </div>

        <!-- Info Message -->
        <Message v-if="localFilters.sla_target" severity="info" :closable="false" class="mb-4">
            <strong>SLA mục tiêu:</strong> Hợp đồng phải được phê duyệt trong vòng {{ localFilters.sla_target }} ngày kể từ ngày tạo
        </Message>

        <!-- Contracts Table -->
        <div class="mt-6">
            <DataTable
                :value="contracts"
                paginator
                :rows="20"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Hiển thị {first} - {last} / {totalRecords} hợp đồng"
                responsiveLayout="scroll"
                stripedRows
                class="text-sm"
            >
                <Column field="contract_number" header="Số HĐ" sortable style="min-width: 120px" />
                <Column field="employee_code" header="Mã NV" sortable style="min-width: 100px" />
                <Column field="employee_name" header="Nhân viên" sortable style="min-width: 180px" />
                <Column field="contract_type" header="Loại HĐ" sortable style="min-width: 150px" />

                <Column field="created_at" header="Ngày tạo" sortable style="min-width: 120px">
                    <template #body="{ data }">
                        {{ formatDate(data.created_at) }}
                    </template>
                </Column>

                <Column field="approval_time_days" header="Số ngày" sortable style="min-width: 100px">
                    <template #body="{ data }">
                        <span v-if="data.approval_time_days !== null" class="font-semibold">
                            {{ data.approval_time_days }}
                        </span>
                        <span v-else class="text-gray-400">N/A</span>
                    </template>
                </Column>

                <Column field="status" header="Trạng thái" sortable style="min-width: 140px">
                    <template #body="{ data }">
                        <Tag :value="data.status_label" :severity="data.status === 'approved' ? 'success' : 'warn'" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>
