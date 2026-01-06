<template>
    <Head>
        <title>{{ reportTitle }}</title>
    </Head>

    <div class="card">
        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-3xl font-bold">{{ reportTitle }}</h1>
            <Breadcrumb :home="breadcrumbHome" :model="breadcrumbItems" class="mt-2" />
            <p class="mt-2">Tình trạng nghỉ phép của nhân viên</p>
        </div>

        <!-- Filter Bar -->
        <ReportFilterBar
            @apply="applyFilters"
            @clear="clearFilters"
            @export="exportReport"
        >
            <div>
                <label class="block text-sm font-medium mb-2">Thời điểm</label>
                <DatePicker
                    v-model="localFilters.as_of_date"
                    dateFormat="yy-mm-dd"
                    placeholder="Chọn ngày"
                    showIcon
                    fluid
                />
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Bộ phận</label>
                <Select
                    v-model="localFilters.department_id"
                    :options="departments"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Tất cả"
                    fluid
                    showClear
                />
            </div>
        </ReportFilterBar>

        <!-- Leave Balances Table -->
        <ReportTable
            title="Chi tiết số dư phép"
            :data="balances"
            :paginator="true"
        >
            <Column field="employee_code" header="Mã NV" sortable style="min-width: 100px" />
            <Column field="employee_name" header="Họ và tên" sortable style="min-width: 200px">
                <template #body="{ data }">
                    <div class="font-medium">{{ data.employee_name }}</div>
                </template>
            </Column>
            <Column field="department" header="Bộ phận" style="min-width: 150px" />
            <Column field="leave_type" header="Loại phép" style="min-width: 120px" />
            <Column field="year" header="Năm" sortable style="min-width: 80px" />
            <Column field="allowance" header="Định mức" sortable style="min-width: 100px">
                <template #body="{ data }">
                    <span class="font-semibold">{{ data.allowance }}</span> ngày
                </template>
            </Column>
            <Column field="used" header="Đã dùng" sortable style="min-width: 100px">
                <template #body="{ data }">
                    <span class="text-orange-600 font-semibold">{{ data.used }}</span> ngày
                </template>
            </Column>
            <Column field="remaining" header="Còn lại" sortable style="min-width: 100px">
                <template #body="{ data }">
                    <span :class="getRemainingClass(data.remaining)">
                        {{ data.remaining }}
                    </span> ngày
                </template>
            </Column>
            <Column field="expiry_date" header="Ngày hết hạn" sortable style="min-width: 120px">
                <template #body="{ data }">
                    <div :class="{ 'text-red-600 font-semibold': data.is_expiring_soon }">
                        {{ formatDate(data.expiry_date) }}
                    </div>
                </template>
            </Column>
            <Column header="Trạng thái" style="min-width: 120px">
                <template #body="{ data }">
                    <Tag
                        v-if="data.is_expiring_soon && data.remaining > 0"
                        value="Sắp hết hạn"
                        severity="warn"
                        icon="pi pi-exclamation-triangle"
                    />
                    <Tag
                        v-else-if="data.remaining === 0"
                        value="Đã hết"
                        severity="secondary"
                    />
                    <Tag
                        v-else
                        value="Bình thường"
                        severity="success"
                    />
                </template>
            </Column>
        </ReportTable>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ReportService } from '@/services/ReportService';
import ReportFilterBar from '@/Components/Reports/ReportFilterBar.vue';
import ReportTable from '@/Components/Reports/ReportTable.vue';
import Breadcrumb from 'primevue/breadcrumb';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

// Report metadata
const reportTitle = 'Số dư phép';

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
    balances: Array,
    asOfDate: String,
    departments: Array,
    filters: Object,
});

// Local state
const localFilters = ref({
    as_of_date: props.asOfDate,
    department_id: props.filters.department_id || null,
});

// Methods
const applyFilters = () => {
    ReportService.leaveBalances(localFilters.value);
};

const clearFilters = () => {
    localFilters.value = {
        as_of_date: new Date().toISOString().split('T')[0],
        department_id: null,
    };
    applyFilters();
};

const exportReport = () => {
    ReportService.exportReport('leave-balances', localFilters.value);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('vi-VN');
};

const getRemainingClass = (remaining) => {
    if (remaining === 0) return 'text-gray-400 font-semibold';
    if (remaining < 3) return 'text-orange-600 font-semibold';
    return 'text-green-600 font-semibold';
};
</script>
