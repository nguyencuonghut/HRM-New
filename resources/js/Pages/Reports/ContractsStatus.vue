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
import ReportFilterBar from '@/Components/Reports/ReportFilterBar.vue';
import ReportKpiCards from '@/Components/Reports/ReportKpiCards.vue';
import ReportService from '@/services/ReportService';
import { formatDate } from '@/utils/dateHelper';

// Report metadata
const reportTitle = 'Tình trạng hợp đồng';

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

const props = defineProps({
    contracts: Array,
    summary: Object,
    statusBreakdown: Array,
    statusOptions: Array,
    departments: Array,
    filters: Object,
});

// Local filters
const localFilters = ref({
    as_of_date: props.filters?.as_of_date || new Date(),
    status: props.filters?.status || null,
    department_id: props.filters?.department_id || null,
});

// Status options from backend enum
// const statusOptions is already passed from props

const departmentOptions = computed(() => {
    return props.departments || [];
});

// KPI Data
const kpiData = computed(() => [
    {
        label: 'Tổng hợp đồng',
        value: props.summary?.total_contracts || 0,
        format: 'number',
        icon: 'pi-file-edit',
        iconColor: 'text-blue-500',
        valueColor: 'text-blue-600',
    },
    {
        label: 'Đang hiệu lực',
        value: props.summary?.active_contracts || 0,
        format: 'number',
        icon: 'pi-check-circle',
        iconColor: 'text-green-500',
        valueColor: 'text-green-600',
    },
    {
        label: 'Sắp hết hạn',
        value: props.summary?.expiring_soon || 0,
        format: 'number',
        icon: 'pi-exclamation-triangle',
        iconColor: 'text-orange-500',
        valueColor: 'text-orange-600',
    },
    {
        label: 'Đã hết hạn',
        value: props.summary?.expired_contracts || 0,
        format: 'number',
        icon: 'pi-times-circle',
        iconColor: 'text-red-500',
        valueColor: 'text-red-600',
    },
]);

// Pie chart data for status distribution
const pieChartData = computed(() => {
    if (!props.statusBreakdown || props.statusBreakdown.length === 0) {
        return { labels: [], datasets: [] };
    }

    const labels = props.statusBreakdown.map(item => item.status_label);
    const data = props.statusBreakdown.map(item => item.count);
    const backgroundColor = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#6b7280', '#ec4899'];

    return {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: backgroundColor.slice(0, data.length),
        }]
    };
});

const pieChartOptions = computed(() => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: 'Phân bổ hợp đồng theo trạng thái',
            },
            legend: {
                position: 'right',
            },
        },
    };
});

// Handlers
const handleApply = () => {
    ReportService.viewReport('contracts-status', localFilters.value);
};

const handleClear = () => {
    localFilters.value = {
        as_of_date: new Date(),
        status: null,
        department_id: null,
    };
    ReportService.viewReport('contracts-status');
};

const handleExport = () => {
    ReportService.exportReport('contracts-status', localFilters.value);
};

// Status severity mapping
const getStatusSeverity = (status) => {
    const severityMap = {
        'DRAFT': 'secondary',
        'PENDING_APPROVAL': 'warn',
        'ACTIVE': 'success',
        'SUSPENDED': 'warn',
        'TERMINATED': 'danger',
        'EXPIRED': 'danger',
        'CANCELLED': 'secondary',
    };
    return severityMap[status] || 'info';
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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Ngày xét</label>
                        <DatePicker
                            v-model="localFilters.as_of_date"
                            dateFormat="dd/mm/yy"
                            placeholder="dd/mm/yyyy"
                            fluid
                            showClear
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Trạng thái</label>
                        <Select
                            v-model="localFilters.status"
                            :options="props.statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Tất cả trạng thái"
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

            <!-- Pie Chart -->
            <div class="mb-6">
                <Chart type="pie" :data="pieChartData" :options="pieChartOptions" class="h-80" />
            </div>

            <!-- Status Breakdown Table -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-3">Phân tích theo trạng thái</h3>
                <DataTable
                    :value="statusBreakdown"
                    responsiveLayout="scroll"
                    stripedRows
                    class="text-sm"
                >
                    <Column field="status_label" header="Trạng thái" style="min-width: 180px">
                        <template #body="{ data }">
                            <Tag :value="data.status_label" :severity="getStatusSeverity(data.status)" />
                        </template>
                    </Column>
                    <Column field="count" header="Số lượng" sortable style="min-width: 120px">
                        <template #body="{ data }">
                            <span class="font-semibold">{{ data.count }}</span>
                        </template>
                    </Column>
                    <Column field="percentage" header="Tỷ lệ" sortable style="min-width: 120px">
                        <template #body="{ data }">
                            {{ data.percentage }}%
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Contracts Table -->
            <div class="mt-6">
                <h3 class="text-lg font-semibold mb-3">Chi tiết hợp đồng</h3>
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
                    <Column field="department_name" header="Phòng ban" sortable style="min-width: 150px" />

                    <Column field="contract_type" header="Loại HĐ" sortable style="min-width: 140px">
                        <template #body="{ data }">
                            <Tag :value="data.contract_type" severity="info" />
                        </template>
                    </Column>

                    <Column field="status" header="Trạng thái" sortable style="min-width: 140px">
                        <template #body="{ data }">
                            <Tag :value="data.status_label" :severity="getStatusSeverity(data.status)" />
                        </template>
                    </Column>

                    <Column field="start_date" header="Ngày bắt đầu" sortable style="min-width: 120px">
                        <template #body="{ data }">
                            {{ formatDate(data.start_date) }}
                        </template>
                    </Column>

                    <Column field="end_date" header="Ngày kết thúc" sortable style="min-width: 120px">
                        <template #body="{ data }">
                            <span v-if="data.end_date">{{ formatDate(data.end_date) }}</span>
                            <Tag v-else value="Không thời hạn" severity="info" />
                        </template>
                    </Column>

                    <Column field="days_until_expiry" header="Còn lại (ngày)" sortable style="min-width: 120px">
                        <template #body="{ data }">
                            <span v-if="data.days_until_expiry !== null">
                                <Tag
                                    :value="data.days_until_expiry + ' ngày'"
                                    :severity="data.days_until_expiry <= 30 ? 'danger' : (data.days_until_expiry <= 90 ? 'warn' : 'success')"
                                />
                            </span>
                            <span v-else class="text-gray-400">N/A</span>
                        </template>
                    </Column>
                </DataTable>
            </div>
    </div>
</template>
