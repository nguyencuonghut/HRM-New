<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Breadcrumb from 'primevue/breadcrumb';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import ReportFilterBar from '@/Components/Reports/ReportFilterBar.vue';
import ReportKpiCards from '@/Components/Reports/ReportKpiCards.vue';
import { ReportService } from '@/services/ReportService';

// Report metadata
const reportTitle = 'Độ hoàn thiện hồ sơ';

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
    employees: Array,
    summary: Object,
    departments: Array,
    filters: Object,
});

// Local filters
const localFilters = ref({
    department_id: props.filters?.department_id || null,
});

// KPI Data
const kpiData = computed(() => [
    {
        label: 'Tổng số nhân viên',
        value: props.summary?.total_employees || 0,
        format: 'number',
        icon: 'pi-users',
        iconColor: 'text-blue-500',
        valueColor: 'text-blue-600',
    },
    {
        label: 'Hoàn thành 100%',
        value: props.summary?.complete_100 || 0,
        format: 'number',
        icon: 'pi-check-circle',
        iconColor: 'text-green-500',
        valueColor: 'text-green-600',
    },
    {
        label: 'Tỷ lệ hoàn thiện TB',
        value: props.summary?.average_completion || 0,
        format: 'percent',
        icon: 'pi-percentage',
        iconColor: 'text-purple-500',
        valueColor: 'text-purple-600',
    },
    {
        label: 'Cần bổ sung',
        value: props.summary?.incomplete || 0,
        format: 'number',
        icon: 'pi-exclamation-triangle',
        iconColor: 'text-orange-500',
        valueColor: 'text-orange-600',
    },
]);

// Filter options
const departmentOptions = computed(() => props.departments || []);

// Completion severity
const getCompletionSeverity = (percentage) => {
    if (percentage >= 90) return 'success';
    if (percentage >= 70) return 'warn';
    return 'danger';
};

// Get completion status label
const getCompletionStatus = (percentage) => {
    if (percentage === 100) return 'Hoàn chỉnh';
    if (percentage >= 90) return 'Tốt';
    if (percentage >= 70) return 'Trung bình';
    return 'Cần bổ sung';
};

// Handlers
const handleApply = () => {
    ReportService.dataCompleteness(localFilters.value);
};

const handleClear = () => {
    localFilters.value = {
        department_id: null,
    };
    ReportService.dataCompleteness();
};

const handleExport = () => {
    ReportService.exportReport('data-completeness', localFilters.value);
};
</script>

<template>
    <Head>
        <title>{{ reportTitle }}</title>
    </Head>

    <div class="card">
        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-3xl font-bold">{{ reportTitle }}</h1>
            <Breadcrumb :home="breadcrumbHome" :model="breadcrumbItems" class="mt-2" />
        </div>

        <!-- Filter Bar -->
        <ReportFilterBar
            @apply="handleApply"
            @clear="handleClear"
            @export="handleExport"
        >
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
                />
            </div>
        </ReportFilterBar>

            <!-- KPI Cards -->
            <ReportKpiCards :kpis="kpiData" class="mb-6" />

            <!-- Data Table -->
            <div class="mt-6">
                <DataTable
                    :value="employees"
                    paginator
                    :rows="20"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    currentPageReportTemplate="Hiển thị {first} - {last} / {totalRecords} nhân viên"
                    responsiveLayout="scroll"
                    stripedRows
                    class="text-sm"
                >
                    <Column field="employee_code" header="Mã NV" sortable style="min-width: 100px" />
                    <Column field="full_name" header="Họ tên" sortable style="min-width: 180px" />
                    <Column field="department_name" header="Phòng ban" sortable style="min-width: 150px" />
                    <Column field="position_name" header="Chức vụ" sortable style="min-width: 150px" />

                    <Column field="completion_percentage" header="Tỷ lệ hoàn thiện" sortable style="min-width: 200px">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold">{{ data.completion_percentage }}%</span>
                                    <Tag
                                        :value="getCompletionStatus(data.completion_percentage)"
                                        :severity="getCompletionSeverity(data.completion_percentage)"
                                        class="text-xs"
                                    />
                                </div>
                                <ProgressBar
                                    :value="data.completion_percentage"
                                    :showValue="false"
                                    :style="{ height: '8px' }"
                                />
                            </div>
                        </template>
                    </Column>

                    <Column header="Thông tin thiếu" style="min-width: 250px">
                        <template #body="{ data }">
                            <div v-if="data.missing_items && data.missing_items.length > 0" class="flex flex-wrap gap-1">
                                <Tag
                                    v-for="(item, index) in data.missing_items"
                                    :key="index"
                                    :value="item"
                                    severity="danger"
                                    class="text-xs"
                                />
                            </div>
                            <Tag v-else value="Hoàn chỉnh" severity="success" class="text-xs" />
                        </template>
                    </Column>
                </DataTable>
            </div>
    </div>
</template>
