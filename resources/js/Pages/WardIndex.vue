<template>
    <Head>
        <title>Quản lý Phường/Xã</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button label="Thêm mới" icon="pi pi-plus" class="mr-2" @click="openNew" />
                    <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined" @click="confirmDeleteSelected" :disabled="!selectedWards || !selectedWards.length" />
                </template>

                <template #end>
                    <Button label="Xuất dữ liệu" icon="pi pi-upload" severity="secondary" @click="exportCSV($event)" />
                </template>
            </Toolbar>

                    <DataTable
                        ref="dt"
                        :value="wardsList"
                        v-model:selection="selectedWards"
                        dataKey="id"
                        :paginator="true"
                        :rows="10"
                        :filters="filters"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Hiển thị {first} đến {last} trong tổng số {totalRecords} phường/xã"
                    >
                        <template #header>
                            <div class="flex flex-wrap gap-2 items-center justify-between">
                                <h4 class="m-0">Danh sách xã/phường</h4>
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
                                </IconField>
                            </div>
                        </template>

                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                        <Column field="province_name" header="Tỉnh/Thành phố" :sortable="true" headerStyle="min-width:12rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.province_name }}
                            </template>
                        </Column>
                        <Column field="code" header="Mã" :sortable="true" headerStyle="width:14%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.code }}
                            </template>
                        </Column>
                        <Column field="name" header="Tên Phường/Xã" :sortable="true" headerStyle="min-width:12rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.name }}
                            </template>
                        </Column>
                        <Column headerStyle="min-width:10rem;">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="mr-2" outlined severity="success" rounded @click="editWard(slotProps.data)" />
                                <Button icon="pi pi-trash" class="mt-2" outlined severity="danger" rounded @click="confirmDeleteWard(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>
        </div>

        <!-- Dialog thêm/sửa phường/xã -->
        <Dialog v-model:visible="wardDialog" :style="{ width: '450px' }" header="Thông tin Phường/Xã" :modal="true">
            <div class="flex flex-col gap-6">
                <div>
                    <label for="province_id" class="block font-bold mb-3 required-field">Tỉnh/Thành phố</label>
                    <Select
                        id="province_id"
                        v-model="ward.province_id"
                        :options="provincesList"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Chọn Tỉnh/Thành phố"
                        :invalid="submitted && !ward.province_id"
                        class="w-full"
                    />
                    <small class="text-red-500" v-if="submitted && !ward.province_id">Tỉnh/Thành phố là bắt buộc.</small>
                </div>
                <div>
                    <label for="code" class="block font-bold mb-3">Mã</label>
                    <InputText id="code" v-model="ward.code" class="w-full" placeholder="Nhập mã (tùy chọn)" />
                </div>
                <div>
                    <label for="name" class="block font-bold mb-3 required-field">Tên Phường/Xã</label>
                    <InputText id="name" v-model="ward.name" autofocus :invalid="submitted && !ward.name" class="w-full" />
                    <small class="text-red-500" v-if="submitted && !ward.name">Tên là bắt buộc.</small>
                </div>
            </div>

            <template #footer>
                <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Lưu" icon="pi pi-check" @click="saveWard" />
            </template>
        </Dialog>

        <!-- Dialog xác nhận xóa -->
        <Dialog v-model:visible="deleteWardDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="ward">
                    Bạn có chắc chắn muốn xóa <b>{{ ward.name }}</b>?
                </span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteWardDialog = false" />
                <Button label="Có" icon="pi pi-check" severity="danger" @click="deleteWard" />
            </template>
        </Dialog>

        <!-- Dialog xác nhận xóa nhiều -->
        <Dialog v-model:visible="deleteWardsDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="ward">Bạn có chắc chắn muốn xóa các phường/xã đã chọn?</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteWardsDialog = false" />
                <Button label="Có" icon="pi pi-check" severity="danger" @click="deleteSelectedWards" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import { WardService } from '@/services';
import Select from 'primevue/select';
import { trimStringValues } from '@/utils/stringHelpers';

const { props } = usePage();

// Props
const definePropsData = defineProps({
    wards: {
        type: Array,
        required: true
    },
    provinces: {
        type: Array,
        required: true
    }
});

const dt = ref();
const wardDialog = ref(false);
const deleteWardDialog = ref(false);
const deleteWardsDialog = ref(false);
const ward = ref({});
const selectedWards = ref();
const filters = ref({
    global: { value: null, matchMode: 'contains' }
});
const submitted = ref(false);

// Computed
const wardsList = computed(() => definePropsData.wards || []);
const provincesList = computed(() => definePropsData.provinces || []);

const openNew = () => {
    ward.value = {};
    submitted.value = false;
    wardDialog.value = true;
};

const hideDialog = () => {
    wardDialog.value = false;
    submitted.value = false;
};

const saveWard = () => {
    submitted.value = true;
    if (ward.value.name && ward.value.name.trim() && ward.value.province_id) {
        // Trim all string values before sending
        const trimmedWard = trimStringValues(ward.value);

        if (ward.value.id) {
            WardService.update(ward.value.id, trimmedWard, {
                onSuccess: () => {
                    wardDialog.value = false;
                    ward.value = {};
                }
            });
        } else {
            WardService.store(trimmedWard, {
                onSuccess: () => {
                    wardDialog.value = false;
                    ward.value = {};
                }
            });
        }
    }
};

const editWard = (editWard) => {
    ward.value = { ...editWard };
    wardDialog.value = true;
};

const confirmDeleteWard = (editWard) => {
    ward.value = editWard;
    deleteWardDialog.value = true;
};

const deleteWard = () => {
    WardService.destroy(ward.value.id, {
        onSuccess: () => {
            deleteWardDialog.value = false;
            ward.value = {};
        }
    });
};

const exportCSV = () => {
    dt.value.exportCSV();
};

const confirmDeleteSelected = () => {
    deleteWardsDialog.value = true;
};

const deleteSelectedWards = () => {
    const ids = selectedWards.value.map(w => w.id);
    WardService.bulkDelete(ids, {
        onSuccess: () => {
            deleteWardsDialog.value = false;
            selectedWards.value = null;
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
