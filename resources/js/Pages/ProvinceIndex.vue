<template>
    <Head>
        <title>Quản lý Tỉnh/Thành phố</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button label="Thêm mới" icon="pi pi-plus" class="mr-2" @click="openNew" />
                    <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined" @click="confirmDeleteSelected" :disabled="!selectedProvinces || !selectedProvinces.length" />
                </template>

                <template #end>
                    <Button label="Xuất dữ liệu" icon="pi pi-upload" severity="secondary" @click="exportCSV($event)" />
                </template>
            </Toolbar>

                    <DataTable
                        ref="dt"
                        :value="provincesList"
                        v-model:selection="selectedProvinces"
                        dataKey="id"
                        :paginator="true"
                        :rows="10"
                        :filters="filters"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Hiển thị {first} đến {last} trong tổng số {totalRecords} tỉnh/thành phố"
                    >
                        <template #header>
                            <div class="flex flex-wrap gap-2 items-center justify-between">
                                <h4 class="m-0">Danh sách Tỉnh/Thành phố</h4>
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
                                </IconField>
                            </div>
                        </template>

                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                        <Column field="code" header="Mã" :sortable="true" headerStyle="width:14%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.code }}
                            </template>
                        </Column>
                        <Column field="name" header="Tên Tỉnh/Thành phố" :sortable="true" headerStyle="min-width:12rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.name }}
                            </template>
                        </Column>
                        <Column field="wards_count" header="Số Phường/Xã" :sortable="true" headerStyle="width:14%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.wards_count }}
                            </template>
                        </Column>
                        <Column headerStyle="min-width:10rem;">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" outlined rounded class="mr-2" severity="success" @click="editProvince(slotProps.data)" />
                                <Button icon="pi pi-trash" outlined rounded class="mt-2" severity="danger" @click="confirmDeleteProvince(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>
        </div>

        <!-- Dialog thêm/sửa tỉnh/thành phố -->
        <Dialog v-model:visible="provinceDialog" :style="{ width: '450px' }" header="Thông tin Tỉnh/Thành phố" :modal="true">
            <div class="flex flex-col gap-6">
                <div>
                    <label for="code" class="block font-bold mb-3 required-field">Mã</label>
                    <InputText id="code" v-model="province.code" autofocus :invalid="submitted && !province.code" class="w-full" />
                    <small class="text-red-500" v-if="submitted && !province.code">Mã là bắt buộc.</small>
                </div>
                <div>
                    <label for="name" class="block font-bold mb-3 required-field">Tên Tỉnh/Thành phố</label>
                    <InputText id="name" v-model="province.name" :invalid="submitted && !province.name" class="w-full" />
                    <small class="text-red-500" v-if="submitted && !province.name">Tên là bắt buộc.</small>
                </div>
            </div>

            <template #footer>
                <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Lưu" icon="pi pi-check" @click="saveProvince" />
            </template>
        </Dialog>

        <!-- Dialog xác nhận xóa -->
        <Dialog v-model:visible="deleteProvinceDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="province">
                    Bạn có chắc chắn muốn xóa <b>{{ province.name }}</b>?
                </span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteProvinceDialog = false" />
                <Button label="Có" icon="pi pi-check" severity="danger" @click="deleteProvince" />
            </template>
        </Dialog>

        <!-- Dialog xác nhận xóa nhiều -->
        <Dialog v-model:visible="deleteProvincesDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="province">Bạn có chắc chắn muốn xóa các tỉnh/thành phố đã chọn?</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteProvincesDialog = false" />
                <Button label="Có" icon="pi pi-check" severity="danger" @click="deleteSelectedProvinces" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import { ProvinceService } from '@/services';
import { trimStringValues } from '@/utils/stringHelpers';

const { props } = usePage();

// Props
const definePropsData = defineProps({
    provinces: {
        type: Array,
        required: true
    }
});

const dt = ref();
const provinceDialog = ref(false);
const deleteProvinceDialog = ref(false);
const deleteProvincesDialog = ref(false);
const province = ref({});
const selectedProvinces = ref();
const filters = ref({
    global: { value: null, matchMode: 'contains' }
});
const submitted = ref(false);

// Computed
const provincesList = computed(() => definePropsData.provinces || []);

const openNew = () => {
    province.value = {};
    submitted.value = false;
    provinceDialog.value = true;
};

const hideDialog = () => {
    provinceDialog.value = false;
    submitted.value = false;
};

const saveProvince = () => {
    submitted.value = true;
    if (province.value.name && province.value.name.trim() && province.value.code && province.value.code.trim()) {
        // Trim all string values before sending
        const trimmedProvince = trimStringValues(province.value);

        if (province.value.id) {
            ProvinceService.update(province.value.id, trimmedProvince, {
                onSuccess: () => {
                    provinceDialog.value = false;
                    province.value = {};
                }
            });
        } else {
            ProvinceService.store(trimmedProvince, {
                onSuccess: () => {
                    provinceDialog.value = false;
                    province.value = {};
                }
            });
        }
    }
};

const editProvince = (editProvince) => {
    province.value = { ...editProvince };
    provinceDialog.value = true;
};

const confirmDeleteProvince = (editProvince) => {
    province.value = editProvince;
    deleteProvinceDialog.value = true;
};

const deleteProvince = () => {
    ProvinceService.destroy(province.value.id, {
        onSuccess: () => {
            deleteProvinceDialog.value = false;
            province.value = {};
        }
    });
};

const exportCSV = () => {
    dt.value.exportCSV();
};

const confirmDeleteSelected = () => {
    deleteProvincesDialog.value = true;
};

const deleteSelectedProvinces = () => {
    const ids = selectedProvinces.value.map(p => p.id);
    ProvinceService.bulkDelete(ids, {
        onSuccess: () => {
            deleteProvincesDialog.value = false;
            selectedProvinces.value = null;
        }
    });
};
</script>

<style scoped lang="scss">
.required-field::after {
    content: ' *';
    color: red;
}
</style>
