<template>
    <Head>
        <title>{{ reportTitle }}</title>
    </Head>

    <div class="card">
        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-3xl font-bold">{{ reportTitle }}</h1>
            <Breadcrumb :home="breadcrumbHome" :model="breadcrumbItems" class="mt-2" />
            <p class="mt-2">Danh sách hợp đồng cần gia hạn hoặc kết thúc</p>
        </div>

        <!-- Filter Bar -->
        <ReportFilterBar
            @apply="applyFilters"
            @clear="clearFilters"
            @export="exportReport"
        >
            <div>
                <label class="block text-sm font-medium mb-2">Từ ngày</label>
                <DatePicker
                    v-model="localFilters.from_date"
                    dateFormat="yy-mm-dd"
                    placeholder="Chọn ngày"
                    showIcon
                    fluid
                />
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Đến ngày</label>
                <DatePicker
                    v-model="localFilters.to_date"
                    dateFormat="yy-mm-dd"
                    placeholder="Chọn ngày"
                    showIcon
                    fluid
                />
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Cảnh báo trước (ngày)</label>
                <InputNumber
                    v-model="localFilters.warning_days"
                    :min="1"
                    :max="365"
                    placeholder="30"
                    fluid
                />
            </div>
        </ReportFilterBar>

        <!-- KPI Cards -->
        <ReportKpiCards :kpis="kpiData" />

        <!-- Contracts Table -->
        <ReportTable
            title="Danh sách hợp đồng"
            :data="contracts"
            :paginator="false"
        >
            <Column field="employee_code" header="Mã NV" sortable style="min-width: 100px" />
            <Column field="employee_name" header="Họ và tên" sortable style="min-width: 200px">
                <template #body="{ data }">
                    <div class="font-medium">{{ data.employee_name }}</div>
                </template>
            </Column>
            <Column field="contract_type" header="Loại hợp đồng" style="min-width: 150px" />
            <Column field="start_date" header="Ngày bắt đầu" sortable style="min-width: 120px">
                <template #body="{ data }">
                    {{ formatDate(data.start_date) }}
                </template>
            </Column>
            <Column field="end_date" header="Ngày kết thúc" sortable style="min-width: 120px">
                <template #body="{ data }">
                    <div :class="getUrgencyClass(data.urgency)">
                        {{ formatDate(data.end_date) }}
                    </div>
                </template>
            </Column>
            <Column field="days_until_expiry" header="Còn lại" sortable style="min-width: 120px">
                <template #body="{ data }">
                    <Tag
                        :value="`${data.days_until_expiry} ngày`"
                        :severity="ReportService.getUrgencySeverity(data.urgency)"
                    />
                </template>
            </Column>
            <Column header="Mức độ" style="min-width: 120px">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <i
                            :class="[
                                'pi',
                                data.urgency === 'critical' ? 'pi-exclamation-triangle' : 'pi-info-circle',
                                getUrgencyTextColor(data.urgency)
                            ]"
                        ></i>
                        <span :class="['font-medium', getUrgencyTextColor(data.urgency)]">
                            {{ getUrgencyLabel(data.urgency) }}
                        </span>
                    </div>
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
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import InputNumber from 'primevue/inputnumber';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

// Report metadata
const reportTitle = 'Hợp đồng sắp hết hạn';

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
    contracts: Array,
    summary: Object,
    dateRange: Object,
    warningDays: Number,
    filters: Object,
});

// Local state
const localFilters = ref({
    from_date: props.dateRange.from,
    to_date: props.dateRange.to,
    warning_days: props.warningDays,
});

// Computed
const kpiData = computed(() => [
    {
        label: 'Tổng số hợp đồng',
        value: props.summary.total,
        format: 'number',
        icon: 'pi-file',
        iconColor: 'text-blue-500',
        valueColor: 'text-blue-600',
    },
    {
        label: 'Khẩn cấp (≤15 ngày)',
        value: props.summary.critical,
        format: 'number',
        icon: 'pi-exclamation-triangle',
        iconColor: 'text-red-500',
        valueColor: 'text-red-600',
    },
    {
        label: 'Cảnh báo (≤30 ngày)',
        value: props.summary.warning,
        format: 'number',
        icon: 'pi-info-circle',
        iconColor: 'text-orange-500',
        valueColor: 'text-orange-600',
    },
    {
        label: 'Bình thường',
        value: props.summary.total - props.summary.critical - props.summary.warning,
        format: 'number',
        icon: 'pi-check-circle',
        iconColor: 'text-green-500',
        valueColor: 'text-green-600',
    },
]);

// Methods
const applyFilters = () => {
    ReportService.contractsExpiring(localFilters.value);
};

const clearFilters = () => {
    const today = new Date().toISOString().split('T')[0];
    const nextMonth = new Date();
    nextMonth.setDate(nextMonth.getDate() + 30);

    localFilters.value = {
        from_date: today,
        to_date: nextMonth.toISOString().split('T')[0],
        warning_days: 30,
    };
    applyFilters();
};

const exportReport = () => {
    ReportService.exportReport('contracts-expiring', localFilters.value);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('vi-VN');
};

const getUrgencyLabel = (urgency) => {
    const labels = {
        'critical': 'Khẩn cấp',
        'warning': 'Cảnh báo',
        'normal': 'Bình thường',
    };
    return labels[urgency] || urgency;
};

const getUrgencyClass = (urgency) => {
    const classes = {
        'critical': 'font-bold text-red-600',
        'warning': 'font-semibold text-orange-600',
        'normal': '',
    };
    return classes[urgency] || '';
};

const getUrgencyTextColor = (urgency) => {
    const colors = {
        'critical': 'text-red-600',
        'warning': 'text-orange-600',
        'normal': 'text-green-600',
    };
    return colors[urgency] || '';
};
</script>
