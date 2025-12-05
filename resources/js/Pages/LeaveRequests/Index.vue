<template>
    <Head>
        <title>Đơn nghỉ phép</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button label="Tạo đơn mới" icon="pi pi-plus" @click="createNew" />
                </template>
                <template #end>
                    <Button label="Phê duyệt" icon="pi pi-check-circle" severity="secondary" @click="goToApprovals" />
                </template>
            </Toolbar>

            <DataTable
                ref="dt"
                :value="leaveRequests.data || []"
                :paginator="true"
                :rows="15"
                :loading="loading"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} đơn nghỉ phép"
            >
                <template #header>
                    <div class="flex flex-col gap-4">
                        <h4 class="m-0">Đơn nghỉ phép</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
                            <div class="flex flex-col">
                                <label class="block text-sm mb-1 font-medium">Loại phép</label>
                                <Select
                                    v-model="filters.leave_type_id"
                                    :options="leaveTypes"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="-- Tất cả --"
                                    showClear
                                    @change="applyFilters"
                                    fluid
                                />
                            </div>
                            <div class="flex flex-col">
                                <label class="block text-sm mb-1 font-medium">Trạng thái</label>
                                <Select
                                    v-model="filters.status"
                                    :options="statusOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="-- Tất cả --"
                                    showClear
                                    @change="applyFilters"
                                    fluid
                                />
                            </div>
                            <div class="flex flex-col">
                                <label class="block text-sm mb-1 font-medium">Từ ngày</label>
                                <DatePicker
                                    v-model="filters.start_date"
                                    showIcon
                                    showButtonBar
                                    dateFormat="yy-mm-dd"
                                    placeholder="dd/mm/yyyy"
                                    @date-select="applyFilters"
                                    fluid
                                >
                                    <template #buttonbar="{ todayCallback }">
                                        <div class="flex justify-between w-full">
                                            <Button size="small" label="Hôm nay" @click="todayCallback" />
                                            <Button size="small" label="Xóa" severity="danger" @click="clearStartDate" />
                                        </div>
                                    </template>
                                </DatePicker>
                            </div>
                            <div class="flex flex-col">
                                <label class="block text-sm mb-1 font-medium">Đến ngày</label>
                                <DatePicker
                                    v-model="filters.end_date"
                                    showIcon
                                    showButtonBar
                                    dateFormat="yy-mm-dd"
                                    placeholder="dd/mm/yyyy"
                                    @date-select="applyFilters"
                                    fluid
                                >
                                    <template #buttonbar="{ todayCallback }">
                                        <div class="flex justify-between w-full">
                                            <Button size="small" label="Hôm nay" @click="todayCallback" />
                                            <Button size="small" label="Xóa" severity="danger" @click="clearEndDate" />
                                        </div>
                                    </template>
                                </DatePicker>
                            </div>
                            <div class="flex flex-col">
                                <label class="block text-sm mb-1 font-medium">Tìm kiếm</label>
                                <IconField>
                                    <InputIcon><i class="pi pi-search" /></InputIcon>
                                    <InputText
                                        v-model="filters.search"
                                        placeholder="Tên NV, lý do..."
                                        @input="debounceSearch"
                                        fluid
                                    />
                                </IconField>
                            </div>
                        </div>
                    </div>
                </template>

                <Column field="employee.employee_code" header="Mã NV" style="min-width: 8rem"></Column>
                <Column field="employee.full_name" header="Nhân viên" style="min-width: 14rem"></Column>
                <Column field="leave_type.name" header="Loại phép" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Badge
                            :value="slotProps.data.leave_type.name"
                            :style="{ backgroundColor: slotProps.data.leave_type.color }"
                        />
                    </template>
                </Column>
                <Column field="start_date" header="Từ ngày" style="min-width: 10rem"></Column>
                <Column field="end_date" header="Đến ngày" style="min-width: 10rem"></Column>
                <Column field="days" header="Số ngày" style="min-width: 8rem">
                    <template #body="slotProps">
                        <span class="font-semibold">{{ slotProps.data.days }}</span>
                    </template>
                </Column>
                <Column field="status" header="Trạng thái" style="min-width: 10rem">
                    <template #body="slotProps">
                        <Badge
                            :value="slotProps.data.status_label"
                            :severity="slotProps.data.status_color"
                        />
                    </template>
                </Column>
                <Column field="submitted_at" header="Ngày nộp" style="min-width: 10rem">
                    <template #body="slotProps">
                        {{ formatDateTime(slotProps.data.submitted_at) }}
                    </template>
                </Column>
                <Column header="Thao tác" :exportable="false" style="min-width: 10rem">
                    <template #body="slotProps">
                        <div class="flex gap-2">
                            <Button
                                icon="pi pi-eye"
                                variant="outlined"
                                rounded
                                @click="viewDetail(slotProps.data)"
                            />
                            <Button
                                v-if="slotProps.data.can_edit"
                                icon="pi pi-pencil"
                                variant="outlined"
                                rounded
                                @click="editRequest(slotProps.data)"
                            />
                            <Button
                                v-if="slotProps.data.can_delete"
                                icon="pi pi-trash"
                                variant="outlined"
                                rounded
                                severity="danger"
                                @click="confirmDelete(slotProps.data)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <Paginator
                :rows="leaveRequests.per_page"
                :totalRecords="leaveRequests.total"
                :first="(leaveRequests.current_page - 1) * leaveRequests.per_page"
                @page="onPageChange"
                template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
            />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Toolbar from 'primevue/toolbar';
import Badge from 'primevue/badge';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Paginator from 'primevue/paginator';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { LeaveRequestService } from '@/services/LeaveRequestService';
import { LeaveApprovalService } from '@/services/LeaveApprovalService';
import { ToastService } from '@/services/ToastService';
import { formatDateTime, getStatusOptions } from '@/utils/leaveHelpers';

const props = defineProps({
    leaveRequests: Object,
    leaveTypes: Array,
    filters: Object,
});

const confirm = useConfirm();
const toast = useToast();
ToastService.init(toast);

const dt = ref();
const loading = ref(false);

const filters = ref({
    leave_type_id: props.filters?.leave_type_id || null,
    status: props.filters?.status || null,
    start_date: props.filters?.start_date || null,
    end_date: props.filters?.end_date || null,
    search: props.filters?.search || '',
});

const statusOptions = getStatusOptions();

let searchTimeout;
const debounceSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
};

const applyFilters = () => {
    loading.value = true;
    LeaveRequestService.index(filters.value, {
        onFinish: () => {
            loading.value = false;
        },
    });
};

const clearStartDate = () => {
    filters.value.start_date = null;
    applyFilters();
};

const clearEndDate = () => {
    filters.value.end_date = null;
    applyFilters();
};

const onPageChange = (event) => {
    loading.value = true;
    LeaveRequestService.index({
        ...filters.value,
        page: event.page + 1,
    }, {
        onFinish: () => {
            loading.value = false;
        },
    });
};

const createNew = () => {
    LeaveRequestService.create();
};

const viewDetail = (request) => {
    LeaveRequestService.show(request.id);
};

const editRequest = (request) => {
    LeaveRequestService.edit(request.id);
};

const confirmDelete = (request) => {
    confirm.require({
        message: `Xóa đơn nghỉ phép của ${request.employee.full_name}?`,
        header: 'Xác nhận xóa',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Hủy',
        acceptLabel: 'Xóa',
        accept: () => {
            LeaveRequestService.destroy(request.id, {
                onSuccess: () => {
                    ToastService.success('Đã xóa đơn nghỉ phép');
                },
            });
        },
    });
};

const goToApprovals = () => {
    LeaveApprovalService.navigate();
};
</script>
