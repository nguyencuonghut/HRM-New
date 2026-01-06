<template>
    <Head>
        <title>{{ reportTitle }}</title>
    </Head>

    <div class="card">
        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-3xl font-bold">{{ reportTitle }}</h1>
            <Breadcrumb :home="breadcrumbHome" :model="breadcrumbItems" class="mt-2" />
            <p class="mt-2">Danh sách chi tiết tất cả nhân viên với bộ lọc</p>
        </div>

        <!-- Filter Bar -->
        <ReportFilterBar
            @apply="applyFilters"
            @clear="clearFilters"
            @export="exportReport"
        >
            <div>
                <label class="block text-sm font-medium mb-2">Tìm kiếm</label>
                <InputText
                    v-model="localFilters.search"
                    placeholder="Mã NV, tên, email, SĐT..."
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

            <div>
                <label class="block text-sm font-medium mb-2">Vị trí</label>
                <MultiSelect
                    v-model="localFilters.position_id"
                    :options="positionsForSelect"
                    optionLabel="displayLabel"
                    optionValue="id"
                    placeholder="Tất cả"
                    fluid
                    showClear
                    display="chip"
                />
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Trạng thái</label>
                <Select
                    v-model="localFilters.status"
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Tất cả"
                    fluid
                    showClear
                />
            </div>
        </ReportFilterBar>

        <!-- Employee Table -->
        <ReportTable
            title="Danh sách nhân viên"
            :data="employees.data"
            :totalRecords="employees.total"
            :rows="employees.per_page"
            :lazy="true"
            @page="onPageChange"
        >
            <Column field="employee_code" header="Mã NV" sortable style="min-width: 100px" />
            <Column field="full_name" header="Họ và tên" sortable style="min-width: 200px">
                <template #body="{ data }">
                    <div class="font-medium">{{ data.full_name }}</div>
                </template>
            </Column>
            <Column header="Bộ phận" style="min-width: 150px">
                <template #body="{ data }">
                    {{ getDepartmentName(data) }}
                </template>
            </Column>
            <Column header="Vị trí" style="min-width: 150px">
                <template #body="{ data }">
                    {{ getPositionName(data) }}
                </template>
            </Column>
            <Column field="company_email" header="Email" style="min-width: 200px" />
            <Column field="phone" header="Điện thoại" style="min-width: 120px" />
            <Column field="status" header="Trạng thái" style="min-width: 120px">
                <template #body="{ data }">
                    <Tag
                        :value="getStatusLabel(data.status)"
                        :severity="getStatusSeverity(data.status)"
                    />
                </template>
            </Column>
            <Column field="hire_date" header="Ngày vào" sortable style="min-width: 120px">
                <template #body="{ data }">
                    {{ formatDate(data.hire_date) }}
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
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

// Report metadata
const reportTitle = 'Danh sách nhân viên';

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
    employees: Object,
    departments: Array,
    positions: Array,
    employeeStatuses: Array,
    filters: Object,
});

// Local state
const localFilters = ref({
    search: props.filters.search || '',
    department_id: props.filters.department_id || null,
    position_id: props.filters.position_id || [],
    status: props.filters.status || null,
});

const statusOptions = props.employeeStatuses || [];

const positionsForSelect = (props.positions || []).map(pos => ({
    ...pos,
    displayLabel: pos.department?.name ? `${pos.title} (${pos.department.name})` : pos.title
}));

// Methods - Presentation logic only
const applyFilters = () => {
    ReportService.employeeList(localFilters.value);
};

const clearFilters = () => {
    localFilters.value = {
        search: '',
        department_id: null,
        position_id: [],
        status: null,
    };
    applyFilters();
};

const exportReport = () => {
    ReportService.exportReport('employee-list', localFilters.value);
};

const onPageChange = (event) => {
    ReportService.employeeList({
        ...localFilters.value,
        page: event.page + 1,
        per_page: event.rows,
    });
};

const getDepartmentName = (employee) => {
    const assignment = employee.assignments?.[0];
    return assignment?.department?.name || '-';
};

const getPositionName = (employee) => {
    const assignment = employee.assignments?.[0];
    return assignment?.position?.title || '-';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('vi-VN');
};

const getStatusLabel = (statusValue) => {
    const status = statusOptions.find(s => s.value === statusValue);
    return status ? status.label : statusValue;
};

const getStatusSeverity = (statusValue) => {
    const status = statusOptions.find(s => s.value === statusValue);
    return status ? status.severity : 'secondary';
};
</script>
