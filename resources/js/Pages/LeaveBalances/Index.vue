<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import SelectButton from 'primevue/selectbutton';

const props = defineProps({
    balances: Object,
    summary: Object,
    leaveTypes: Array,
    departments: Array,
    filters: Object,
    years: Array,
});

const viewMode = ref('summary'); // 'summary' or 'detailed'
const viewOptions = [
    { label: 'Tổng quan', value: 'summary' },
    { label: 'Chi tiết theo loại phép', value: 'detailed' }
];

const filters = ref({
    year: props.filters.year,
    leave_type_id: props.filters.leave_type_id,
    department_id: props.filters.department_id,
    search: props.filters.search,
});

const selectedEmployee = ref(null);
const showDetailDialog = ref(false);
const employeeDetails = ref([]);

const applyFilters = () => {
    router.get('/leave-balances', filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const initializeBalances = () => {
    if (confirm(`Khởi tạo số dư phép cho năm ${filters.value.year}?`)) {
        router.post('/leave-balances/initialize', { year: filters.value.year });
    }
};

const viewEmployeeDetails = (employeeId) => {
    // Find employee info from summary
    const summaryItem = props.summary.data.find(s => s.employee_id === employeeId);
    selectedEmployee.value = summaryItem?.employee;

    // Use Inertia to fetch details, preserving current state
    router.get('/leave-balances',
        {
            year: filters.value.year,
            employee_id: employeeId
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['balances'],
            onSuccess: (page) => {
                employeeDetails.value = page.props.balances.data || [];
                showDetailDialog.value = true;
            }
        }
    );
};

const getUsageColor = (percentage) => {
    if (percentage >= 80) return 'danger';
    if (percentage >= 50) return 'warn';
    return 'success';
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('vi-VN', { minimumFractionDigits: 1 }).format(value);
};

const calculateUsagePercentage = (used, total) => {
    return total > 0 ? Math.round((used / total) * 100) : 0;
};
</script>

<template>
    <Head>
        <title>Tổng hợp số dư phép</title>
    </Head>

    <div class="card">
        <Toolbar class="mb-6">
            <template #start>
                <h2 class="text-xl font-semibold m-0">Tổng hợp số dư phép</h2>
            </template>
            <template #end>
                <Button
                    label="Khởi tạo số dư"
                    icon="pi pi-refresh"
                    @click="initializeBalances"
                    severity="secondary"
                />
            </template>
        </Toolbar>

        <!-- View Mode Toggle -->
        <div class="mb-4 flex justify-center">
            <SelectButton v-model="viewMode" :options="viewOptions" optionLabel="label" optionValue="value" />
        </div>

        <!-- Filters -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Năm</label>
                    <Select
                        v-model="filters.year"
                        :options="years"
                        placeholder="Chọn năm"
                        class="w-full"
                        @change="applyFilters"
                    />
                </div>
                <div v-if="viewMode === 'detailed'">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Loại phép</label>
                    <Select
                        v-model="filters.leave_type_id"
                        :options="leaveTypes"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Tất cả"
                        showClear
                        class="w-full"
                        @change="applyFilters"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phòng ban</label>
                    <Select
                        v-model="filters.department_id"
                        :options="departments"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Tất cả"
                        showClear
                        class="w-full"
                        @change="applyFilters"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
                    <InputText
                        v-model="filters.search"
                        placeholder="Mã NV, họ tên..."
                        class="w-full"
                        @keyup.enter="applyFilters"
                    />
                </div>
            </div>
        </div>

        <!-- SUMMARY VIEW - Tổng quan -->
        <DataTable
            v-if="viewMode === 'summary'"
            :value="summary.data"
            :rows="50"
            stripedRows
            :paginator="true"
            :rowsPerPageOptions="[25, 50, 100]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} nhân viên"
            class="p-datatable-sm"
        >
            <Column field="employee.employee_code" header="Mã NV" sortable style="min-width: 100px">
                <template #body="{ data }">
                    {{ data.employee?.employee_code }}
                </template>
            </Column>
            <Column field="employee.full_name" header="Họ tên" sortable style="min-width: 200px">
                <template #body="{ data }">
                    {{ data.employee?.full_name }}
                </template>
            </Column>
            <Column header="Phòng ban" style="min-width: 150px">
                <template #body="{ data }">
                    {{ data.employee?.department_name || '-' }}
                </template>
            </Column>
            <Column header="Tổng phép" sortable style="min-width: 100px">
                <template #body="{ data }">
                    <span class="font-semibold text-lg">{{ formatNumber(data.total_all) }}</span>
                </template>
            </Column>
            <Column header="Đã dùng" sortable style="min-width: 100px">
                <template #body="{ data }">
                    <span class="text-red-600 font-semibold">{{ formatNumber(data.used_all) }}</span>
                </template>
            </Column>
            <Column header="Còn lại" sortable style="min-width: 100px">
                <template #body="{ data }">
                    <span class="text-green-600 font-bold text-lg">{{ formatNumber(data.remaining_all) }}</span>
                </template>
            </Column>
            <Column header="Tỷ lệ sử dụng" style="min-width: 180px">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <ProgressBar
                            :value="calculateUsagePercentage(data.used_all, data.total_all)"
                            :showValue="false"
                            class="flex-1 h-3"
                            :severity="getUsageColor(calculateUsagePercentage(data.used_all, data.total_all))"
                        />
                        <span class="text-sm font-semibold w-12 text-right">
                            {{ calculateUsagePercentage(data.used_all, data.total_all) }}%
                        </span>
                    </div>
                </template>
            </Column>
            <Column header="Thao tác" style="min-width: 120px">
                <template #body="{ data }">
                    <Button
                        label="Chi tiết"
                        icon="pi pi-eye"
                        size="small"
                        text
                        @click="viewEmployeeDetails(data.employee_id)"
                    />
                </template>
            </Column>

            <template #empty>
                <div class="text-center py-8 text-gray-500">
                    Không có dữ liệu
                </div>
            </template>
        </DataTable>

        <!-- DETAILED VIEW - Chi tiết theo loại phép -->
        <DataTable
            v-if="viewMode === 'detailed'"
            :value="balances.data"
            :rows="50"
            stripedRows
            :paginator="true"
            :rowsPerPageOptions="[25, 50, 100]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} bản ghi"
            class="p-datatable-sm"
        >
            <Column field="employee.employee_code" header="Mã NV" style="min-width: 100px">
                <template #body="{ data }">
                    {{ data.employee?.employee_code }}
                </template>
            </Column>
            <Column field="employee.full_name" header="Họ tên" style="min-width: 180px">
                <template #body="{ data }">
                    {{ data.employee?.full_name }}
                </template>
            </Column>
            <Column field="leave_type.name" header="Loại phép" style="min-width: 140px">
                <template #body="{ data }">
                    <Tag
                        :value="data.leave_type?.name"
                        :style="{ backgroundColor: data.leave_type?.color, color: '#ffffff' }"
                    />
                </template>
            </Column>
            <Column header="Tổng" style="min-width: 80px">
                <template #body="{ data }">
                    <span class="font-semibold">{{ formatNumber(data.total_days) }}</span>
                </template>
            </Column>
            <Column header="Đã dùng" style="min-width: 80px">
                <template #body="{ data }">
                    <span class="text-red-600">{{ formatNumber(data.used_days) }}</span>
                </template>
            </Column>
            <Column header="Còn lại" style="min-width: 80px">
                <template #body="{ data }">
                    <span class="text-green-600 font-semibold">{{ formatNumber(data.remaining_days) }}</span>
                </template>
            </Column>
            <Column header="Sử dụng" style="min-width: 150px">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <ProgressBar
                            :value="calculateUsagePercentage(data.used_days, data.total_days)"
                            :showValue="false"
                            class="flex-1 h-2"
                            :severity="getUsageColor(calculateUsagePercentage(data.used_days, data.total_days))"
                        />
                        <span class="text-sm font-medium">
                            {{ calculateUsagePercentage(data.used_days, data.total_days) }}%
                        </span>
                    </div>
                </template>
            </Column>
            <Column v-if="filters.year > new Date().getFullYear() - 2" header="Chuyển kỳ" style="min-width: 100px">
                <template #body="{ data }">
                    <span v-if="data.carried_forward > 0" class="text-blue-600">
                        +{{ formatNumber(data.carried_forward) }}
                    </span>
                    <span v-else class="text-gray-400">-</span>
                </template>
            </Column>

            <template #empty>
                <div class="text-center py-8 text-gray-500">
                    Không có dữ liệu
                </div>
            </template>
        </DataTable>

        <!-- Detail Dialog -->
        <Dialog v-model:visible="showDetailDialog" :header="`Chi tiết số dư phép - ${selectedEmployee?.full_name}`" :modal="true" :style="{ width: '800px' }">
            <DataTable :value="employeeDetails" class="p-datatable-sm">
                <Column field="leave_type.name" header="Loại phép" style="min-width: 140px">
                    <template #body="{ data }">
                        <Tag
                            :value="data.leave_type?.name"
                            :style="{ backgroundColor: data.leave_type?.color, color: '#ffffff' }"
                        />
                    </template>
                </Column>
                <Column header="Tổng" style="min-width: 80px">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ formatNumber(data.total_days) }}</span>
                    </template>
                </Column>
                <Column header="Đã dùng" style="min-width: 80px">
                    <template #body="{ data }">
                        <span class="text-red-600">{{ formatNumber(data.used_days) }}</span>
                    </template>
                </Column>
                <Column header="Còn lại" style="min-width: 80px">
                    <template #body="{ data }">
                        <span class="text-green-600 font-semibold">{{ formatNumber(data.remaining_days) }}</span>
                    </template>
                </Column>
                <Column header="Sử dụng" style="min-width: 150px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <ProgressBar
                                :value="calculateUsagePercentage(data.used_days, data.total_days)"
                                :showValue="false"
                                class="flex-1 h-2"
                                :severity="getUsageColor(calculateUsagePercentage(data.used_days, data.total_days))"
                            />
                            <span class="text-sm font-medium">
                                {{ calculateUsagePercentage(data.used_days, data.total_days) }}%
                            </span>
                        </div>
                    </template>
                </Column>
                <Column v-if="filters.year > new Date().getFullYear() - 2" header="Chuyển kỳ" style="min-width: 100px">
                    <template #body="{ data }">
                        <span v-if="data.carried_forward > 0" class="text-blue-600">
                            +{{ formatNumber(data.carried_forward) }}
                        </span>
                        <span v-else class="text-gray-400">-</span>
                    </template>
                </Column>
            </DataTable>
        </Dialog>
    </div>
</template>
