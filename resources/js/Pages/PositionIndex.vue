<template>
    <Head>
        <title>Quản Lý Chức Vụ</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button v-if="canCreate" label="Thêm chức vụ" icon="pi pi-plus" class="mr-2" @click="openNew" />
                    <Button v-if="canDelete" label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined" @click="confirmDeleteSelected" :disabled="!selectedPositions || !selectedPositions.length" />
                </template>

                <template #end>
                    <Button label="Xuất dữ liệu" icon="pi pi-upload" severity="secondary" @click="exportCSV($event)" />
                </template>
            </Toolbar>

            <DataTable
                ref="dt"
                v-model:selection="selectedPositions"
                :value="positionsList || []"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Hiển thị từ {first} đến {last} trong tổng số {totalRecords} chức vụ"
                :loading="loading"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Quản Lý Chức Vụ</h4>
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
                        </IconField>
                    </div>
                </template>

                <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="canDelete"></Column>
                <Column field="title" header="Tên chức vụ" sortable style="min-width: 16rem">
                    <template #body="slotProps">
                        <span class="font-semibold">{{ slotProps.data.title }}</span>
                    </template>
                </Column>
                <Column field="department_name" header="Phòng ban" sortable style="min-width: 14rem">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.department_name" severity="info" />
                    </template>
                </Column>
                <Column field="level" header="Cấp bậc" sortable style="min-width: 10rem">
                    <template #body="slotProps">
                        <span>{{ slotProps.data.level || '-' }}</span>
                    </template>
                </Column>
                <Column field="position_salary" header="Lương vị trí" sortable style="min-width: 12rem">
                    <template #body="slotProps">
                        <span>{{ formatCurrency(slotProps.data.position_salary) }}</span>
                    </template>
                </Column>
                <Column field="created_at" header="Ngày tạo" sortable style="min-width: 12rem">
                    <template #body="slotProps">
                        {{ formatDate(slotProps.data.created_at) }}
                    </template>
                </Column>
                <Column v-if="canEdit || canDelete" :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Button v-if="canEdit" icon="pi pi-pencil" outlined rounded class="mr-2" @click="editPosition(slotProps.data)" />
                        <Button v-if="canDelete" icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeletePosition(slotProps.data)" />
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Dialog thêm/sửa chức vụ -->
        <Dialog v-model:visible="positionDialog" :style="{ width: '650px' }" :header="isEdit ? 'Sửa chức vụ' : 'Thêm chức vụ'" :modal="true">
            <div class="flex flex-col gap-6">
                <div>
                    <label for="department_id" class="block font-bold mb-3 required-field">Phòng ban</label>
                    <Select
                        id="department_id"
                        v-model="form.department_id"
                        :options="departmentsList"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Chọn phòng ban"
                        :class="{ 'p-invalid': errors.department_id }"
                        class="w-full"
                    />
                    <small class="text-red-500" v-if="errors.department_id">{{ errors.department_id }}</small>
                </div>

                <div>
                    <label for="title" class="block font-bold mb-3 required-field">Tên chức vụ</label>
                    <InputText
                        id="title"
                        v-model="form.title"
                        required="true"
                        :class="{ 'p-invalid': errors.title }"
                        class="w-full"
                        placeholder="Nhập tên chức vụ"
                    />
                    <small class="text-red-500" v-if="errors.title">{{ errors.title }}</small>
                </div>

                <div>
                    <label for="level" class="block font-bold mb-3">Cấp bậc</label>
                    <InputText
                        id="level"
                        v-model="form.level"
                        :class="{ 'p-invalid': errors.level }"
                        class="w-full"
                        placeholder="Nhập cấp bậc (tùy chọn)"
                    />
                    <small class="text-red-500" v-if="errors.level">{{ errors.level }}</small>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="insurance_base_salary" class="block font-bold mb-3">Lương cơ bản BH</label>
                        <InputNumber
                            id="insurance_base_salary"
                            v-model="form.insurance_base_salary"
                            mode="currency"
                            currency="VND"
                            locale="vi-VN"
                            :class="{ 'p-invalid': errors.insurance_base_salary }"
                            class="w-full"
                        />
                        <small class="text-red-500" v-if="errors.insurance_base_salary">{{ errors.insurance_base_salary }}</small>
                    </div>

                    <div>
                        <label for="position_salary" class="block font-bold mb-3">Lương chức vụ</label>
                        <InputNumber
                            id="position_salary"
                            v-model="form.position_salary"
                            mode="currency"
                            currency="VND"
                            locale="vi-VN"
                            :class="{ 'p-invalid': errors.position_salary }"
                            class="w-full"
                        />
                        <small class="text-red-500" v-if="errors.position_salary">{{ errors.position_salary }}</small>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="competency_salary" class="block font-bold mb-3">Lương năng lực</label>
                        <InputNumber
                            id="competency_salary"
                            v-model="form.competency_salary"
                            mode="currency"
                            currency="VND"
                            locale="vi-VN"
                            :class="{ 'p-invalid': errors.competency_salary }"
                            class="w-full"
                        />
                        <small class="text-red-500" v-if="errors.competency_salary">{{ errors.competency_salary }}</small>
                    </div>

                    <div>
                        <label for="allowance" class="block font-bold mb-3">Phụ cấp</label>
                        <InputNumber
                            id="allowance"
                            v-model="form.allowance"
                            mode="currency"
                            currency="VND"
                            locale="vi-VN"
                            :class="{ 'p-invalid': errors.allowance }"
                            class="w-full"
                        />
                        <small class="text-red-500" v-if="errors.allowance">{{ errors.allowance }}</small>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Lưu" icon="pi pi-check" @click="savePosition" :loading="form.processing" />
            </template>
        </Dialog>

        <!-- Dialog xác nhận xóa -->
        <Dialog v-model:visible="deletePositionDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="selectedPosition">
                    Bạn có chắc chắn muốn xóa chức vụ <b>{{ selectedPosition.title }}</b>?
                </span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deletePositionDialog = false" />
                <Button label="Có" icon="pi pi-check" severity="danger" @click="deletePosition" :loading="deleting" />
            </template>
        </Dialog>

        <!-- Dialog xác nhận xóa nhiều -->
        <Dialog v-model:visible="deletePositionsDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="selectedPositions">
                    Bạn có chắc chắn muốn xóa các chức vụ đã chọn?
                </span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deletePositionsDialog = false" />
                <Button label="Có" icon="pi pi-check" severity="danger" @click="deleteSelectedPositions" :loading="deleting" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import { useFormValidation } from '@/composables/useFormValidation';
import { usePermission } from '@/composables/usePermission';
import { PositionService } from '@/services';
import Select from 'primevue/select'

const { errors, hasError, getError } = useFormValidation();
const { hasPermission } = usePermission();

// Props
const props = defineProps({
    positions: {
        type: Array,
        required: true
    },
    departments: {
        type: Array,
        required: true
    }
});

// Refs
const dt = ref();
const positionDialog = ref(false);
const deletePositionDialog = ref(false);
const deletePositionsDialog = ref(false);
const selectedPosition = ref(null);
const selectedPositions = ref([]);
const loading = ref(false);
const deleting = ref(false);
const isEdit = ref(false);
const filters = ref({
    global: { value: null, matchMode: 'contains' }
});

// Computed
const positionsList = computed(() => props.positions || []);
const departmentsList = computed(() => props.departments || []);
const canCreate = computed(() => hasPermission('create positions'));
const canEdit = computed(() => hasPermission('edit positions'));
const canDelete = computed(() => hasPermission('delete positions'));

// Forms
const form = useForm({
    department_id: null,
    title: '',
    level: '',
    insurance_base_salary: null,
    position_salary: null,
    competency_salary: null,
    allowance: null
});

// Methods
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatCurrency = (value) => {
    if (!value && value !== 0) return '-';
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(value);
};

const openNew = () => {
    form.reset();
    form.clearErrors();
    isEdit.value = false;
    positionDialog.value = true;
};

const hideDialog = () => {
    positionDialog.value = false;
    form.reset();
    form.clearErrors();
};

const editPosition = (position) => {
    form.reset();
    form.clearErrors();

    form.department_id = position.department_id;
    form.title = position.title;
    form.level = position.level;
    form.insurance_base_salary = position.insurance_base_salary;
    form.position_salary = position.position_salary;
    form.competency_salary = position.competency_salary;
    form.allowance = position.allowance;

    selectedPosition.value = position;
    isEdit.value = true;
    positionDialog.value = true;
};

const savePosition = () => {
    const positionData = {
        department_id: form.department_id,
        title: form.title,
        level: form.level,
        insurance_base_salary: form.insurance_base_salary,
        position_salary: form.position_salary,
        competency_salary: form.competency_salary,
        allowance: form.allowance
    };

    const onSuccess = () => {
        hideDialog();
    };

    const onError = (errors) => {
        // Validation errors will be handled automatically by useFormValidation
    };

    if (isEdit.value) {
        PositionService.update(selectedPosition.value.id, positionData, {
            onSuccess,
            onError
        });
    } else {
        PositionService.store(positionData, {
            onSuccess,
            onError
        });
    }
};

const confirmDeletePosition = (position) => {
    selectedPosition.value = position;
    deletePositionDialog.value = true;
};

const deletePosition = () => {
    deleting.value = true;

    PositionService.destroy(selectedPosition.value.id, {
        onSuccess: () => {
            deleting.value = false;
            deletePositionDialog.value = false;
            selectedPosition.value = null;
        },
        onError: () => {
            deleting.value = false;
        },
        onFinish: () => {
            deleting.value = false;
        }
    });
};

const confirmDeleteSelected = () => {
    deletePositionsDialog.value = true;
};

const deleteSelectedPositions = () => {
    deleting.value = true;

    const ids = selectedPositions.value.map(position => position.id);

    PositionService.bulkDelete(ids, {
        onSuccess: () => {
            deleting.value = false;
            deletePositionsDialog.value = false;
            selectedPositions.value = [];
        },
        onError: () => {
            deleting.value = false;
        },
        onFinish: () => {
            deleting.value = false;
        }
    });
};

const exportCSV = () => {
    dt.value.exportCSV();
};
</script>

<style scoped>
.p-invalid {
    border-color: #ef4444;
}
</style>
