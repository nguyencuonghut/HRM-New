<template>
    <Head>
        <title>Quản lý Cấu hình Lương BHXH</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button
                        label="Tạo Config Mới"
                        icon="pi pi-plus"
                        class="mr-2"
                        @click="handleCreate"
                    />
                </template>

                <template #end>
                    <Button
                        label="Config Đang Áp Dụng"
                        icon="pi pi-check-circle"
                        severity="success"
                        variant="outlined"
                        @click="handleShowCurrent"
                    />
                </template>
            </Toolbar>

            <DataTable
                ref="dt"
                :value="configSets?.data || []"
                dataKey="id"
                :paginator="true"
                :rows="configSets?.pagination?.per_page || 10"
                :totalRecords="configSets?.pagination?.total || 0"
                :lazy="true"
                @page="onPage"
                :loading="loading"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Hiển thị từ {first} đến {last} trong tổng số {totalRecords} config sets"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Danh sách Cấu hình Lương BHXH</h4>
                        <div class="flex gap-2">
                            <Select
                                v-model="filters.status"
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Lọc theo trạng thái"
                                class="w-48"
                                showClear
                                @change="onFilter"
                            />
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText
                                    v-model="filters.search"
                                    placeholder="Tìm theo mã hoặc tên..."
                                    @input="onSearch"
                                />
                            </IconField>
                        </div>
                    </div>
                </template>

                <Column field="code" header="Mã Config" sortable style="min-width: 12rem">
                    <template #body="slotProps">
                        <span class="font-semibold">{{ slotProps.data.code }}</span>
                    </template>
                </Column>

                <Column field="name" header="Tên Config" sortable style="min-width: 16rem"></Column>

                <Column field="status" header="Trạng thái" style="min-width: 10rem">
                    <template #body="slotProps">
                        <Tag
                            :value="getStatusLabel(slotProps.data.status)"
                            :severity="getStatusSeverity(slotProps.data.status)"
                        />
                    </template>
                </Column>

                <Column field="effective_from" header="Hiệu lực từ" sortable style="min-width: 10rem">
                    <template #body="slotProps">
                        {{ formatDate(slotProps.data.effective_from) }}
                    </template>
                </Column>

                <Column field="effective_to" header="Hiệu lực đến" sortable style="min-width: 10rem">
                    <template #body="slotProps">
                        {{ slotProps.data.effective_to ? formatDate(slotProps.data.effective_to) : 'Vô thời hạn' }}
                    </template>
                </Column>

                <Column field="minimum_wages" header="Mức lương" style="min-width: 8rem; text-align: center">
                    <template #body="slotProps">
                        <Badge :value="slotProps.data.minimum_wages?.length || 0" severity="info" />
                    </template>
                </Column>

                <Column field="salary_grades" header="Bậc lương" style="min-width: 8rem; text-align: center">
                    <template #body="slotProps">
                        <Badge :value="slotProps.data.salary_grades?.length || 0" severity="info" />
                    </template>
                </Column>

                <Column :exportable="false" style="min-width: 14rem">
                    <template #body="slotProps">
                        <Button
                            icon="pi pi-eye"
                            variant="outlined"
                            severity="info"
                            size="small"
                            class="mr-2"
                            @click="handleShow(slotProps.data.id)"
                        />
                        <Button
                            v-if="slotProps.data.status === 'DRAFT'"
                            icon="pi pi-pencil"
                            variant="outlined"
                            severity="secondary"
                            size="small"
                            class="mr-2"
                            @click="handleEdit(slotProps.data.id)"
                        />
                        <Button
                            v-if="slotProps.data.status === 'DRAFT'"
                            icon="pi pi-check"
                            variant="outlined"
                            severity="success"
                            size="small"
                            class="mr-2"
                            @click="confirmActivate(slotProps.data)"
                        />
                        <Button
                            icon="pi pi-copy"
                            variant="outlined"
                            severity="secondary"
                            size="small"
                            class="mr-2"
                            @click="openCloneDialog(slotProps.data)"
                        />
                        <Button
                            v-if="slotProps.data.status === 'DRAFT'"
                            icon="pi pi-trash"
                            variant="outlined"
                            severity="danger"
                            size="small"
                            @click="confirmDelete(slotProps.data)"
                        />
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center p-4">
                        <i class="pi pi-inbox text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-500">Không có config set nào</p>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog
            v-model:visible="deleteDialog"
            :style="{ width: '450px' }"
            header="Xác nhận xóa"
            :modal="true"
        >
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-4xl text-red-500"></i>
                <span v-if="configToDelete">
                    Bạn có chắc chắn muốn xóa config <b>{{ configToDelete.code }}</b>?
                </span>
            </div>
            <template #footer>
                <Button label="Hủy" icon="pi pi-times" variant="outlined" @click="deleteDialog = false" />
                <Button label="Xóa" icon="pi pi-check" severity="danger" @click="handleDelete" />
            </template>
        </Dialog>

        <!-- Activate Confirmation Dialog -->
        <Dialog
            v-model:visible="activateDialog"
            :style="{ width: '450px' }"
            header="Xác nhận kích hoạt"
            :modal="true"
        >
            <div class="flex items-center gap-4">
                <i class="pi pi-question-circle text-4xl text-yellow-500"></i>
                <span v-if="configToActivate">
                    Bạn có chắc chắn muốn kích hoạt config <b>{{ configToActivate.code }}</b>?<br>
                    <small class="text-gray-500">Config hiện tại đang active sẽ được lưu trữ.</small>
                </span>
            </div>
            <template #footer>
                <Button label="Hủy" icon="pi pi-times" variant="outlined" @click="activateDialog = false" />
                <Button label="Kích hoạt" icon="pi pi-check" severity="success" @click="handleActivate" />
            </template>
        </Dialog>

        <!-- Clone Dialog -->
        <Dialog
            v-model:visible="cloneDialog"
            :style="{ width: '550px' }"
            header="Sao chép Config Set"
            :modal="true"
        >
            <div class="flex flex-col gap-4 py-4">
                <div>
                    <label class="block mb-2 font-semibold">Mã Config <span class="text-red-500">*</span></label>
                    <InputText v-model="cloneForm.code" class="w-full" placeholder="VD: VN_INS_2025_01" />
                </div>
                <div>
                    <label class="block mb-2 font-semibold">Tên Config <span class="text-red-500">*</span></label>
                    <InputText v-model="cloneForm.name" class="w-full" placeholder="VD: Hệ thống lương BHXH 01/2025" />
                </div>
                <div>
                    <label class="block mb-2 font-semibold">Hiệu lực từ <span class="text-red-500">*</span></label>
                    <DatePicker v-model="cloneForm.effective_from" dateFormat="dd/mm/yy" class="w-full" showIcon />
                </div>
                <div>
                    <label class="block mb-2 font-semibold">Hiệu lực đến</label>
                    <DatePicker v-model="cloneForm.effective_to" dateFormat="dd/mm/yy" class="w-full" showIcon showClear />
                </div>
                <div v-if="configToClone" class="p-3 bg-blue-50 rounded">
                    <small class="text-gray-600">
                        Sao chép từ: <b>{{ configToClone.code }}</b><br>
                        Sẽ copy toàn bộ {{ configToClone.minimum_wages?.length || 0 }} mức lương vùng
                        và {{ configToClone.salary_grades?.length || 0 }} bậc lương
                    </small>
                </div>
            </div>
            <template #footer>
                <Button label="Hủy" icon="pi pi-times" variant="outlined" @click="cloneDialog = false" />
                <Button label="Sao chép" icon="pi pi-copy" severity="success" @click="handleClone" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { InsuranceConfigSetService } from '@/services/InsuranceConfigSetService';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';

// Props
const props = defineProps({
    configSets: {
        type: Object,
        default: () => ({ data: [], pagination: {} })
    }
});

// State
const loading = ref(false);
const deleteDialog = ref(false);
const activateDialog = ref(false);
const cloneDialog = ref(false);
const configToDelete = ref(null);
const configToActivate = ref(null);
const configToClone = ref(null);

const filters = reactive({
    search: '',
    status: null,
    per_page: 10
});

const cloneForm = reactive({
    code: '',
    name: '',
    effective_from: null,
    effective_to: null
});

const statusOptions = [
    { label: 'Tất cả', value: null },
    { label: 'Nháp', value: 'DRAFT' },
    { label: 'Đang áp dụng', value: 'ACTIVE' },
    { label: 'Đã lưu trữ', value: 'ARCHIVED' }
];

// Methods
const loadData = () => {
    InsuranceConfigSetService.index(filters, {
        onStart: () => { loading.value = true; },
        onFinish: () => { loading.value = false; }
    });
};

const onSearch = () => {
    loadData();
};

const onFilter = () => {
    loadData();
};

const onPage = (event) => {
    filters.per_page = event.rows;
    loadData();
};

const handleCreate = () => {
    InsuranceConfigSetService.create();
};

const handleShow = (id) => {
    InsuranceConfigSetService.show(id);
};

const handleEdit = (id) => {
    InsuranceConfigSetService.edit(id);
};

const handleShowCurrent = () => {
    InsuranceConfigSetService.getCurrent();
};

const confirmDelete = (config) => {
    configToDelete.value = config;
    deleteDialog.value = true;
};

const handleDelete = () => {
    if (configToDelete.value) {
        InsuranceConfigSetService.destroy(configToDelete.value.id, {
            onSuccess: () => {
                deleteDialog.value = false;
                configToDelete.value = null;
                loadData();
            }
        });
    }
};

const confirmActivate = (config) => {
    configToActivate.value = config;
    activateDialog.value = true;
};

const handleActivate = () => {
    if (configToActivate.value) {
        InsuranceConfigSetService.activate(configToActivate.value.id, {
            onSuccess: () => {
                activateDialog.value = false;
                configToActivate.value = null;
                loadData();
            }
        });
    }
};

const openCloneDialog = (config) => {
    configToClone.value = config;
    cloneForm.code = '';
    cloneForm.name = `${config.name} (Copy)`;
    cloneForm.effective_from = null;
    cloneForm.effective_to = null;
    cloneDialog.value = true;
};

const handleClone = () => {
    if (configToClone.value) {
        InsuranceConfigSetService.clone(configToClone.value.id, cloneForm, {
            onSuccess: () => {
                cloneDialog.value = false;
                configToClone.value = null;
                loadData();
            }
        });
    }
};

const getStatusLabel = (status) => {
    const labels = {
        'DRAFT': 'Nháp',
        'ACTIVE': 'Đang áp dụng',
        'ARCHIVED': 'Đã lưu trữ'
    };
    return labels[status] || status;
};

const getStatusSeverity = (status) => {
    const severities = {
        'DRAFT': 'secondary',
        'ACTIVE': 'success',
        'ARCHIVED': 'warn'
    };
    return severities[status] || 'secondary';
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
};

onMounted(() => {
    // Initial load is done by Inertia
});
</script>
