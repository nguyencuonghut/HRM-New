<template>
    <Head>
        <title>Nhóm chức danh BHXH</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button
                        v-if="can('create departments')"
                        label="Thêm mới"
                        icon="pi pi-plus"
                        class="mr-2"
                        @click="openNew"
                    />
                    <Button
                        v-if="can('delete departments')"
                        label="Xoá"
                        icon="pi pi-trash"
                        severity="danger"
                        variant="outlined"
                        @click="confirmDeleteSelected"
                        :disabled="!selectedCategories || !selectedCategories.length"
                    />
                </template>

                <template #end>
                    <Button
                        label="Export"
                        icon="pi pi-upload"
                        severity="secondary"
                        @click="exportCSV"
                    />
                </template>
            </Toolbar>

            <DataTable
                ref="dt"
                v-model:selection="selectedCategories"
                :value="insuranceSalaryCategories || []"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Hiển thị từ {first} đến {last} trong tổng số {totalRecords} nhóm"
                :loading="loading"
                selectionMode="multiple"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Nhóm chức danh BHXH</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <IconField>
                                <InputIcon><i class="pi pi-search" /></InputIcon>
                                <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
                            </IconField>

                            <Select
                                :options="[
                                    { label: 'Tất cả', value: '' },
                                    { label: 'Kích hoạt', value: true },
                                    { label: 'Không kích hoạt', value: false },
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                v-model="activeFilter"
                                placeholder="Trạng thái"
                                @change="applyActiveFilter"
                            />
                        </div>
                    </div>
                </template>

                <Column v-if="can('delete departments')" selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
                <Column field="name" header="Tên nhóm" sortable style="min-width: 16rem"></Column>
                <Column field="code" header="Mã" sortable style="min-width: 10rem"></Column>
                <Column field="description" header="Mô tả" style="min-width: 20rem"></Column>
                <Column field="display_order" header="Thứ tự" sortable style="min-width: 8rem"></Column>

                <Column header="Số vị trí" style="min-width: 8rem">
                    <template #body="slotProps">
                        <Badge :value="slotProps.data.positions_count || 0" severity="info" />
                    </template>
                </Column>

                <Column header="Trạng thái" sortable style="min-width: 8rem">
                    <template #body="slotProps">
                        <Tag
                            :value="slotProps.data.is_active ? 'Kích hoạt' : 'Vô hiệu'"
                            :severity="slotProps.data.is_active ? 'success' : 'danger'"
                        />
                    </template>
                </Column>

                <Column :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Button
                            v-if="can('edit departments')"
                            icon="pi pi-pencil"
                            outlined
                            rounded
                            class="mr-2"
                            @click="editCategory(slotProps.data)"
                        />
                        <Button
                            v-if="can('delete departments')"
                            icon="pi pi-trash"
                            outlined
                            rounded
                            severity="danger"
                            @click="confirmDeleteCategory(slotProps.data)"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Dialog Add/Edit -->
        <Dialog
            v-model:visible="categoryDialog"
            :style="{ width: '550px' }"
            :header="editMode ? 'Cập nhật nhóm chức danh' : 'Thêm nhóm chức danh mới'"
            :modal="true"
            class="p-fluid"
        >
            <div class="field">
                <label for="name">Tên nhóm <span class="text-red-500">*</span></label>
                <InputText
                    id="name"
                    v-model.trim="form.name"
                    required="true"
                    autofocus
                    :invalid="!!errors.name"
                />
                <small class="text-red-500" v-if="errors.name">{{ errors.name }}</small>
            </div>

            <div class="field">
                <label for="code">Mã</label>
                <InputText
                    id="code"
                    v-model.trim="form.code"
                    :invalid="!!errors.code"
                />
                <small class="text-muted">Để trống để tự động tạo từ tên</small>
                <small class="text-red-500 block" v-if="errors.code">{{ errors.code }}</small>
            </div>

            <div class="field">
                <label for="description">Mô tả</label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                />
            </div>

            <div class="field">
                <label for="display_order">Thứ tự hiển thị</label>
                <InputNumber
                    id="display_order"
                    v-model="form.display_order"
                    :min="0"
                />
            </div>

            <div class="field-checkbox">
                <Checkbox id="is_active" v-model="form.is_active" :binary="true" />
                <label for="is_active">Kích hoạt</label>
            </div>

            <template #footer>
                <Button label="Huỷ" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Lưu" icon="pi pi-check" :loading="saving" @click="saveCategory" />
            </template>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <ConfirmDialog></ConfirmDialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { FilterMatchMode } from '@primevue/core/api';
import { InsuranceSalaryCategoryService } from '@/services';
import { usePermission } from '@/composables/usePermission';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import ConfirmDialog from 'primevue/confirmdialog';
import Select from 'primevue/select';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';

const { can } = usePermission();
const confirm = useConfirm();
const page = usePage();

// Props
const props = defineProps({
    insuranceSalaryCategories: Array,
});

// State
const dt = ref();
const categoryDialog = ref(false);
const selectedCategories = ref([]);
const loading = ref(false);
const saving = ref(false);
const editMode = ref(false);
const activeFilter = ref('');

// Form
const form = useForm({
    id: null,
    code: '',
    name: '',
    description: '',
    display_order: 0,
    is_active: true,
});

// Filters
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// Computed
const errors = computed(() => page.props.errors || {});

// Methods
const openNew = () => {
    form.reset();
    form.is_active = true;
    form.display_order = 0;
    editMode.value = false;
    categoryDialog.value = true;
};

const hideDialog = () => {
    categoryDialog.value = false;
    form.reset();
    form.clearErrors();
};

const editCategory = (category) => {
    form.id = category.id;
    form.code = category.code;
    form.name = category.name;
    form.description = category.description;
    form.display_order = category.display_order;
    form.is_active = category.is_active;
    editMode.value = true;
    categoryDialog.value = true;
};

const saveCategory = () => {
    saving.value = true;

    const options = {
        onFinish: () => {
            saving.value = false;
        },
        onSuccess: () => {
            hideDialog();
        },
    };

    if (editMode.value) {
        InsuranceSalaryCategoryService.update(form.id, form.data(), options);
    } else {
        InsuranceSalaryCategoryService.store(form.data(), options);
    }
};

const confirmDeleteCategory = (category) => {
    confirm.require({
        message: `Bạn có chắc muốn xoá nhóm "${category.name}"?`,
        header: 'Xác nhận xoá',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Huỷ',
        acceptLabel: 'Xoá',
        accept: () => {
            InsuranceSalaryCategoryService.destroy(category.id);
        },
    });
};

const confirmDeleteSelected = () => {
    confirm.require({
        message: `Bạn có chắc muốn xoá ${selectedCategories.value.length} nhóm đã chọn?`,
        header: 'Xác nhận xoá',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Huỷ',
        acceptLabel: 'Xoá',
        accept: () => {
            const ids = selectedCategories.value.map((c) => c.id);
            InsuranceSalaryCategoryService.bulkDelete({ ids });
            selectedCategories.value = [];
        },
    });
};

const applyActiveFilter = () => {
    loading.value = true;
    InsuranceSalaryCategoryService.index(
        {
            is_active: activeFilter.value,
            search: filters.value.global.value || '',
        },
        {
            onFinish: () => {
                loading.value = false;
            },
        }
    );
};

const exportCSV = () => {
    dt.value.exportCSV();
};

onMounted(() => {
    // Initial load if needed
});
</script>
