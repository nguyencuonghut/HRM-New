<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import ConfirmDialog from 'primevue/confirmdialog';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import Message from 'primevue/message';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { CompanyRegionService } from '@/services';

const props = defineProps({
    regions: Array,
    enums: Object,
});

const confirm = useConfirm();
const toast = useToast();
const page = usePage();

// State
const loading = ref(false);
const selectedRegions = ref([]);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const statusFilter = ref('');
const showDialog = ref(false);
const editingRegion = ref(null);
const isEdit = computed(() => !!editingRegion.value);

// Form state
const form = ref({
    region: 3,
    effective_from: new Date(),
    effective_to: null,
    note: '',
});
const formErrors = ref({});
const formProcessing = ref(false);

const regionsList = computed(() => props.regions || []);

const regionNames = {
    1: 'Vùng I',
    2: 'Vùng II',
    3: 'Vùng III',
    4: 'Vùng IV',
};

const isActive = (region) => {
    return region.is_active;
};

const formatDate = (date) => {
    if (!date) return 'Hiện tại';
    return new Date(date).toLocaleDateString('vi-VN');
};

const getSeverity = (region) => {
    return isActive(region) ? 'success' : 'secondary';
};

const getStatusLabel = (region) => {
    return isActive(region) ? 'Đang áp dụng' : 'Đã kết thúc';
};

const getRegionSeverity = (regionValue) => {
    const severities = { 1: 'danger', 2: 'warn', 3: 'info', 4: 'secondary' };
    return severities[regionValue] || 'secondary';
};

const applyStatusFilter = () => {
    CompanyRegionService.index({
        status: statusFilter.value,
    }, {});
};

const openNew = () => {
    editingRegion.value = null;
    form.value = {
        region: 3,
        effective_from: new Date(),
        effective_to: null,
        note: '',
    };
    formErrors.value = {};
    showDialog.value = true;
};

const editRegion = (region) => {
    editingRegion.value = region;
    form.value = {
        region: region.region,
        effective_from: region.effective_from ? new Date(region.effective_from) : new Date(),
        effective_to: region.effective_to ? new Date(region.effective_to) : null,
        note: region.note || '',
    };
    formErrors.value = {};
    showDialog.value = true;
};

const hideDialog = () => {
    showDialog.value = false;
    editingRegion.value = null;
    formErrors.value = {};
};

const saveRegion = () => {
    formProcessing.value = true;
    formErrors.value = {};

    const data = {
        region: form.value.region,
        effective_from: formatDateForServer(form.value.effective_from),
        effective_to: form.value.effective_to ? formatDateForServer(form.value.effective_to) : null,
        note: form.value.note,
    };

    const onSuccess = () => {
        showDialog.value = false;
        editingRegion.value = null;
        CompanyRegionService.index({}, {});
    };

    const onError = (errors) => {
        formErrors.value = errors;
    };

    const onFinish = () => {
        formProcessing.value = false;
    };

    if (isEdit.value) {
        CompanyRegionService.update(editingRegion.value.id, data, {
            onSuccess,
            onError,
            onFinish,
        });
    } else {
        CompanyRegionService.store(data, {
            onSuccess,
            onError,
            onFinish,
        });
    }
};

const deleteRegion = (region) => {
    confirm.require({
        message: `Bạn có chắc chắn muốn xóa cấu hình vùng BHXH "${regionNames[region.region]}" hiệu lực từ ${formatDate(region.effective_from)}?`,
        header: 'Xác nhận xóa',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Hủy',
        acceptLabel: 'Xóa',
        accept: () => {
            CompanyRegionService.destroy(region.id, {
                onSuccess: () => {
                    CompanyRegionService.index({}, {});
                }
            });
        },
    });
};

const formatDateForServer = (date) => {
    if (!date) return null;
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

// Show flash messages
onMounted(() => {
    if (page.props.flash?.message) {
        const severity = page.props.flash.type || 'info';
        toast.add({
            severity: severity,
            summary: severity === 'success' ? 'Thành công' : 'Thông báo',
            detail: page.props.flash.message,
            life: 3000,
        });
    }
});
</script>

<template>
    <Head>
        <title>Cấu hình Vùng BHXH Công ty</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button
                        label="Thêm mới"
                        icon="pi pi-plus"
                        @click="openNew"
                    />
                </template>
            </Toolbar>

            <DataTable
                :value="regionsList"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Hiển thị từ {first} đến {last} trong tổng số {totalRecords} bản ghi"
                :loading="loading"
                stripedRows
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Quản lý Vùng BHXH Công ty</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <IconField>
                                <InputIcon><i class="pi pi-search" /></InputIcon>
                                <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
                            </IconField>

                            <Select
                                v-model="statusFilter"
                                :options="enums.statuses"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Trạng thái"
                                @change="applyStatusFilter"
                            />
                        </div>
                    </div>
                </template>

                <Column field="region" header="Vùng BHXH" style="width: 120px">
                    <template #body="{ data }">
                        <Tag :severity="getRegionSeverity(data.region)">
                            {{ regionNames[data.region] }}
                        </Tag>
                    </template>
                </Column>

                <Column field="effective_from" header="Hiệu lực từ" sortable style="width: 150px">
                    <template #body="{ data }">
                        {{ formatDate(data.effective_from) }}
                    </template>
                </Column>

                <Column field="effective_to" header="Hiệu lực đến" sortable style="width: 150px">
                    <template #body="{ data }">
                        <span :class="{ 'font-semibold text-green-600': !data.effective_to }">
                            {{ formatDate(data.effective_to) }}
                        </span>
                    </template>
                </Column>

                <Column header="Trạng thái" style="width: 140px">
                    <template #body="{ data }">
                        <Tag :severity="getSeverity(data)">
                            {{ getStatusLabel(data) }}
                        </Tag>
                    </template>
                </Column>

                <Column field="note" header="Ghi chú">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.note || '-' }}</span>
                    </template>
                </Column>

                <Column header="Thao tác" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Button
                                icon="pi pi-pencil"
                                size="small"
                                severity="info"
                                text
                                rounded
                                @click="editRegion(data)"
                                v-tooltip.top="'Sửa'"
                            />
                            <Button
                                icon="pi pi-trash"
                                size="small"
                                severity="danger"
                                text
                                rounded
                                @click="deleteRegion(data)"
                                v-tooltip.top="'Xóa'"
                            />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-4">
                        <i class="pi pi-inbox text-4xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500">Chưa có cấu hình vùng BHXH nào</p>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Dialog Form -->
        <Dialog
            v-model:visible="showDialog"
            :header="isEdit ? 'Sửa Cấu hình Vùng BHXH' : 'Thêm Cấu hình Vùng BHXH'"
            :modal="true"
            :style="{ width: '600px' }"
            :closable="!formProcessing"
        >
            <Message v-if="!isEdit" severity="info" :closable="false" class="mb-4">
                <p class="text-sm">
                    <strong>Lưu ý:</strong> Khi thêm vùng BHXH mới với "Hiệu lực đến" = trống (hiện tại),
                    hệ thống sẽ tự động đóng vùng BHXH cũ tại ngày trước đó.
                </p>
            </Message>

            <div class="flex flex-col gap-4">
                <!-- Region -->
                <div class="flex flex-col gap-2">
                    <label for="region" class="font-semibold">
                        Vùng BHXH <span class="text-red-500">*</span>
                    </label>
                    <Select
                        id="region"
                        v-model="form.region"
                        :options="enums.regions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Chọn vùng BHXH"
                        :invalid="!!formErrors.region"
                        fluid
                    />
                    <small v-if="formErrors.region" class="text-red-500">
                        {{ formErrors.region }}
                    </small>
                    <small class="text-gray-500">
                        Vùng BHXH quyết định mức lương tối thiểu vùng để tính BHXH
                    </small>
                </div>

                <!-- Effective From -->
                <div class="flex flex-col gap-2">
                    <label for="effective_from" class="font-semibold">
                        Hiệu lực từ <span class="text-red-500">*</span>
                    </label>
                    <DatePicker
                        id="effective_from"
                        v-model="form.effective_from"
                        dateFormat="dd/mm/yy"
                        placeholder="Chọn ngày bắt đầu"
                        :invalid="!!formErrors.effective_from"
                        fluid
                        showIcon
                    />
                    <small v-if="formErrors.effective_from" class="text-red-500">
                        {{ formErrors.effective_from }}
                    </small>
                </div>

                <!-- Effective To -->
                <div class="flex flex-col gap-2">
                    <label for="effective_to" class="font-semibold">
                        Hiệu lực đến
                    </label>
                    <DatePicker
                        id="effective_to"
                        v-model="form.effective_to"
                        dateFormat="dd/mm/yy"
                        placeholder="Để trống nếu là vùng hiện tại"
                        :invalid="!!formErrors.effective_to"
                        fluid
                        showIcon
                    />
                    <small v-if="formErrors.effective_to" class="text-red-500">
                        {{ formErrors.effective_to }}
                    </small>
                    <small class="text-gray-500">
                        Để trống nếu đây là vùng BHXH hiện tại đang áp dụng
                    </small>
                </div>

                <!-- Note -->
                <div class="flex flex-col gap-2">
                    <label for="note" class="font-semibold">Ghi chú</label>
                    <Textarea
                        id="note"
                        v-model="form.note"
                        rows="3"
                        placeholder="Lý do thay đổi vùng BHXH (nếu có)..."
                        :invalid="!!formErrors.note"
                        fluid
                    />
                    <small v-if="formErrors.note" class="text-red-500">
                        {{ formErrors.note }}
                    </small>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Hủy"
                    icon="pi pi-times"
                    severity="secondary"
                    outlined
                    @click="hideDialog"
                    :disabled="formProcessing"
                />
                <Button
                    :label="isEdit ? 'Cập nhật' : 'Tạo mới'"
                    icon="pi pi-save"
                    @click="saveRegion"
                    :loading="formProcessing"
                />
            </template>
        </Dialog>

        <ConfirmDialog />
    </div>
</template>
