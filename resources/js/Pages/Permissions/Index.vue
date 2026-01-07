<template>
    <Head>
        <title>Quản Lý Quyền</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button v-if="canCreate" label="Thêm quyền" icon="pi pi-plus" class="mr-2" @click="openNew" />
                    <Button v-if="canDelete" label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined"
                        @click="confirmDeleteSelected" :disabled="!selectedPermissions || !selectedPermissions.length" />
                </template>

                <template #end>
                    <SelectButton v-model="viewMode" :options="viewModes" optionLabel="label" optionValue="value" />
                </template>
            </Toolbar>

            <!-- Flat view - All permissions in one table -->
            <DataTable
                v-if="viewMode === 'flat'"
                ref="dt"
                v-model:selection="selectedPermissions"
                :value="permissions || []"
                dataKey="id"
                :paginator="true"
                :rows="25"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[25, 50, 100]"
                currentPageReportTemplate="Hiển thị từ {first} đến {last} trong tổng số {totalRecords} quyền"
                :loading="loading"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Danh Sách Quyền ({{ permissions?.length || 0 }})</h4>
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
                        </IconField>
                    </div>
                </template>

                <Column v-if="canDelete" selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
                <Column field="name" header="Tên quyền" sortable style="min-width: 20rem">
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-lock text-primary"></i>
                            <div class="flex flex-col">
                                <span class="font-medium">{{ slotProps.data.label || slotProps.data.name }}</span>
                                <span class="text-xs text-surface-500">{{ slotProps.data.name }}</span>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column field="roles_count" header="Được gán cho" sortable style="min-width: 12rem">
                    <template #body="slotProps">
                        <div v-if="slotProps.data.roles && slotProps.data.roles.length > 0" class="flex flex-wrap gap-1">
                            <Tag v-for="role in slotProps.data.roles" :key="role.id" :value="role.name" severity="info" class="text-xs" />
                        </div>
                        <span v-else class="text-surface-400 text-sm italic">Chưa gán</span>
                    </template>
                </Column>
                <Column field="guard_name" header="Guard" sortable style="min-width: 8rem">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.guard_name || 'web'" severity="secondary" />
                    </template>
                </Column>
                <Column v-if="canEdit || canDelete" :exportable="false" style="min-width: 10rem">
                    <template #body="slotProps">
                        <Button v-if="canEdit" icon="pi pi-pencil" outlined rounded class="mr-2"
                            @click="editPermission(slotProps.data)" />
                        <Button v-if="canDelete" icon="pi pi-trash" outlined rounded severity="danger"
                            @click="confirmDeletePermission(slotProps.data)" />
                    </template>
                </Column>
            </DataTable>

            <!-- Grouped view - Permissions grouped by module -->
            <div v-else class="space-y-4">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="m-0">Quyền Theo Module ({{ Object.keys(groupedPermissions).length }} modules)</h4>
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="searchQuery" placeholder="Tìm kiếm module/quyền..." />
                    </IconField>
                </div>

                <Accordion v-model:value="expandedPanels" multiple>
                    <AccordionPanel v-for="(perms, module) in filteredGroupedPermissions" :key="module" :value="module">
                        <template #header>
                            <div class="flex items-center justify-between w-full pr-4">
                                <div class="flex items-center gap-3">
                                    <i :class="getModuleIcon(module)" class="text-xl"></i>
                                    <span class="font-semibold">{{ module }}</span>
                                </div>
                                <Badge :value="perms.length" severity="contrast" />
                            </div>
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="permission in perms" :key="permission.id"
                                class="flex items-center justify-between p-3 border border-surface-200 dark:border-surface-700 rounded-lg hover:bg-surface-50 dark:hover:bg-surface-800 transition-colors">
                                <div class="flex flex-col gap-1 flex-1">
                                    <span class="text-sm font-medium">{{ permission.label || permission.name }}</span>
                                    <span class="text-xs text-surface-500">{{ permission.name }}</span>
                                </div>
                                <div class="flex gap-1" v-if="canEdit || canDelete">
                                    <Button v-if="canEdit" icon="pi pi-pencil" text rounded size="small"
                                        @click="editPermission(permission)" />
                                    <Button v-if="canDelete" icon="pi pi-trash" text rounded size="small" severity="danger"
                                        @click="confirmDeletePermission(permission)" />
                                </div>
                            </div>
                        </div>
                    </AccordionPanel>
                </Accordion>
            </div>
        </div>

        <!-- Dialog Create/Edit Permission -->
        <Dialog v-model:visible="permissionDialog" :style="{ width: '600px' }"
            :header="isEdit ? 'Sửa quyền' : 'Thêm quyền mới'" :modal="true">
            <div class="flex flex-col gap-6">
                <div>
                    <label for="name" class="block font-bold mb-3">Tên quyền (English) <span class="text-red-500">*</span></label>
                    <InputText
                        id="name"
                        v-model="form.name"
                        required="true"
                        autofocus
                        :class="{ 'p-invalid': errors.name }"
                        class="w-full"
                        placeholder="vd: view employees, create contracts"
                    />
                    <small class="text-surface-500 block mt-1">Sử dụng snake_case hoặc space-separated</small>
                    <small class="text-red-500" v-if="errors.name">{{ errors.name }}</small>
                </div>

                <div>
                    <label for="label" class="block font-bold mb-3">Nhãn (Tiếng Việt) <span class="text-red-500">*</span></label>
                    <InputText
                        id="label"
                        v-model="form.label"
                        required="true"
                        :class="{ 'p-invalid': errors.label }"
                        class="w-full"
                        placeholder="vd: Xem danh sách nhân viên"
                    />
                    <small class="text-red-500" v-if="errors.label">{{ errors.label }}</small>
                </div>

                <div>
                    <label for="description" class="block font-bold mb-3">Mô tả</label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="w-full"
                        placeholder="Mô tả chi tiết về quyền này..."
                    />
                    <small class="text-red-500" v-if="errors.description">{{ errors.description }}</small>
                </div>

                <div>
                    <label for="guard_name" class="block font-bold mb-3">Guard Name</label>
                    <InputText
                        id="guard_name"
                        v-model="form.guard_name"
                        class="w-full"
                        placeholder="web"
                    />
                    <small class="text-surface-500 block mt-1">Mặc định: web</small>
                </div>
            </div>

            <template #footer>
                <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Lưu" icon="pi pi-check" @click="savePermission" :loading="formLoading" />
            </template>
        </Dialog>

        <!-- Dialog Confirm Delete -->
        <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-4xl text-red-500"></i>
                <span v-if="permission">
                    Bạn có chắc muốn xóa quyền <b>{{ permission.name }}</b>?
                    <br />
                    <small class="text-red-500 mt-2 block" v-if="permission.roles?.length > 0">
                        Cảnh báo: Quyền này đang được gán cho {{ permission.roles.length }} role(s)
                    </small>
                </span>
            </div>
            <template #footer>
                <Button label="Hủy" icon="pi pi-times" text @click="deleteDialog = false" />
                <Button label="Xóa" icon="pi pi-check" severity="danger" @click="deletePermission" :loading="formLoading" />
            </template>
        </Dialog>

        <!-- Dialog Confirm Delete Selected -->
        <Dialog v-model:visible="deleteSelectedDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-4xl text-red-500"></i>
                <span v-if="selectedPermissions">Bạn có chắc muốn xóa {{ selectedPermissions.length }} quyền đã chọn?</span>
            </div>
            <template #footer>
                <Button label="Hủy" icon="pi pi-times" text @click="deleteSelectedDialog = false" />
                <Button label="Xóa" icon="pi pi-check" severity="danger" @click="deleteSelectedPermissions" :loading="formLoading" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import SelectButton from 'primevue/selectbutton';

const props = defineProps({
    permissions: Array,
    groupedPermissions: Object,
    roles: Array,
    errors: Object,
});

// Permissions check
const canCreate = computed(() => props.permissions?.some(p => p.name === 'create permissions'));
const canEdit = computed(() => props.permissions?.some(p => p.name === 'edit permissions'));
const canDelete = computed(() => props.permissions?.some(p => p.name === 'delete permissions'));

// View modes
const viewModes = [
    { label: 'Danh sách', value: 'flat' },
    { label: 'Theo module', value: 'grouped' }
];
const viewMode = ref('grouped');

// State
const dt = ref();
const loading = ref(false);
const formLoading = ref(false);
const selectedPermissions = ref([]);
const searchQuery = ref('');
const expandedPanels = ref(Object.keys(props.groupedPermissions).slice(0, 5)); // Expand first 5 panels by default

// Filters
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// Dialog states
const permissionDialog = ref(false);
const deleteDialog = ref(false);
const deleteSelectedDialog = ref(false);
const isEdit = ref(false);
const permission = ref({});

// Form data
const form = ref({
    name: '',
    label: '',
    description: '',
    guard_name: 'web'
});

// Computed
const filteredGroupedPermissions = computed(() => {
    if (!searchQuery.value) return props.groupedPermissions;

    const query = searchQuery.value.toLowerCase();
    const filtered = {};

    for (const [module, perms] of Object.entries(props.groupedPermissions)) {
        // Check if module name matches
        if (module.toLowerCase().includes(query)) {
            filtered[module] = perms;
            continue;
        }

        // Check if any permission name matches
        const matchedPerms = perms.filter(p => p.name.toLowerCase().includes(query));
        if (matchedPerms.length > 0) {
            filtered[module] = matchedPerms;
        }
    }

    return filtered;
});

// Methods
const openNew = () => {
    form.value = { name: '', guard_name: 'web' };
    isEdit.value = false;
    permissionDialog.value = true;
};

const hideDialog = () => {
    permissionDialog.value = false;
    isEdit.value = false;
};

const editPermission = (data) => {
    permission.value = { ...data };
    form.value = {
        name: data.name,
        label: data.label || '',
        description: data.description || '',
        guard_name: data.guard_name || 'web'
    };
    isEdit.value = true;
    permissionDialog.value = true;
};

const savePermission = () => {
    formLoading.value = true;

    if (isEdit.value) {
        router.put(route('permissions.update', permission.value.id), form.value, {
            onFinish: () => {
                formLoading.value = false;
                hideDialog();
            }
        });
    } else {
        router.post(route('permissions.store'), form.value, {
            onFinish: () => {
                formLoading.value = false;
                hideDialog();
            }
        });
    }
};

const confirmDeletePermission = (data) => {
    permission.value = data;
    deleteDialog.value = true;
};

const deletePermission = () => {
    formLoading.value = true;
    router.delete(route('permissions.destroy', permission.value.id), {
        onFinish: () => {
            formLoading.value = false;
            deleteDialog.value = false;
            permission.value = {};
        }
    });
};

const confirmDeleteSelected = () => {
    deleteSelectedDialog.value = true;
};

const deleteSelectedPermissions = () => {
    formLoading.value = true;
    router.post(route('permissions.bulk-delete'), {
        ids: selectedPermissions.value.map(p => p.id)
    }, {
        onFinish: () => {
            formLoading.value = false;
            deleteSelectedDialog.value = false;
            selectedPermissions.value = [];
        }
    });
};

const getModuleIcon = (module) => {
    const icons = {
        'System Administration': 'pi pi-cog',
        'User Management': 'pi pi-users',
        'Role Management': 'pi pi-shield',
        'Permission Management': 'pi pi-lock',
        'Backup Management': 'pi pi-database',
        'Activity Logs': 'pi pi-history',
        'Organization Structure': 'pi pi-sitemap',
        'Employee Management': 'pi pi-id-card',
        'Employee Assignment': 'pi pi-user-edit',
        'Position Management': 'pi pi-briefcase',
        'Master Data': 'pi pi-table',
        'Skill Management': 'pi pi-star',
        'Contract Management': 'pi pi-file',
        'Leave Management': 'pi pi-calendar',
        'Payroll & Benefits': 'pi pi-money-bill',
        'Insurance Management': 'pi pi-shield',
        'Performance Management': 'pi pi-chart-line',
        'Rewards & Discipline': 'pi pi-trophy',
        'Reports & Analytics': 'pi pi-chart-bar',
        'Settings & Configuration': 'pi pi-sliders-h',
        'Legacy Data Import': 'pi pi-upload',
    };
    return icons[module] || 'pi pi-circle';
};
</script>

<style scoped>
.p-accordion .p-accordion-header-link {
    padding: 1rem;
}
</style>
