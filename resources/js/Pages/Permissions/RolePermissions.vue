<template>
    <Head>
        <title>Phân Quyền - {{ role?.name }}</title>
    </Head>

    <div>
        <div class="mb-6">
            <Button label="Quay lại" icon="pi pi-arrow-left" text @click="router.visit('/roles')" />
        </div>

        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold m-0">Phân Quyền cho Role</h2>
                    <div class="flex items-center gap-3 mt-2">
                        <Tag :value="role?.name" severity="danger" class="text-lg" />
                        <Badge :value="`${selectedPermissions.length} / ${totalPermissions} permissions`" severity="info" />
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button label="Chọn tất cả" icon="pi pi-check-square" outlined @click="selectAll" />
                    <Button label="Bỏ chọn tất cả" icon="pi pi-square" outlined @click="deselectAll" />
                    <Button label="Lưu thay đổi" icon="pi pi-save" @click="savePermissions" :loading="saving" />
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="flex gap-4 mb-6">
                <IconField class="flex-1">
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="searchQuery" placeholder="Tìm kiếm permissions..." class="w-full" />
                </IconField>
                <SelectButton v-model="filterMode" :options="filterModes" optionLabel="label" optionValue="value" />
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="card bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/10 dark:to-cyan-900/10 border-t-2 border-blue-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-surface-500 dark:text-surface-400 text-sm font-medium mb-1">Tổng Permissions</div>
                            <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">{{ totalPermissions }}</div>
                        </div>
                        <div class="bg-blue-100 dark:bg-blue-900/30 rounded-full p-3">
                            <i class="pi pi-lock text-2xl text-blue-600 dark:text-blue-400"></i>
                        </div>
                    </div>
                </div>

                <div class="card bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/10 dark:to-emerald-900/10 border-t-2 border-green-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-surface-500 dark:text-surface-400 text-sm font-medium mb-1">Đã chọn</div>
                            <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">{{ selectedPermissions.length }}</div>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900/30 rounded-full p-3">
                            <i class="pi pi-check text-2xl text-green-600 dark:text-green-400"></i>
                        </div>
                    </div>
                </div>

                <div class="card bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/10 dark:to-amber-900/10 border-t-2 border-orange-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-surface-500 dark:text-surface-400 text-sm font-medium mb-1">Chưa chọn</div>
                            <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">{{ totalPermissions - selectedPermissions.length }}</div>
                        </div>
                        <div class="bg-orange-100 dark:bg-orange-900/30 rounded-full p-3">
                            <i class="pi pi-minus text-2xl text-orange-600 dark:text-orange-400"></i>
                        </div>
                    </div>
                </div>

                <div class="card bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-900/10 dark:to-violet-900/10 border-t-2 border-purple-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-surface-500 dark:text-surface-400 text-sm font-medium mb-1">Tỷ lệ</div>
                            <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">{{ permissionPercentage }}%</div>
                        </div>
                        <div class="bg-purple-100 dark:bg-purple-900/30 rounded-full p-3">
                            <i class="pi pi-chart-pie text-2xl text-purple-600 dark:text-purple-400"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions by Module -->
            <Accordion v-model:value="expandedModules" multiple>
                <AccordionPanel v-for="(permissions, module) in filteredGroupedPermissions" :key="module" :value="module">
                    <template #header>
                        <div class="flex items-center justify-between w-full pr-4">
                            <div class="flex items-center gap-3">
                                <i :class="getModuleIcon(module)" class="text-xl"></i>
                                <span class="font-semibold">{{ module }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <Badge :value="`${getModuleSelectedCount(module)} / ${permissions.length}`"
                                    :severity="getModuleSelectedCount(module) === permissions.length ? 'success' : 'secondary'" />
                                <Button
                                    :label="getModuleSelectedCount(module) === permissions.length ? 'Bỏ chọn' : 'Chọn tất cả'"
                                    text
                                    size="small"
                                    @click.stop="toggleModulePermissions(module, permissions)"
                                />
                            </div>
                        </div>
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="permission in permissions" :key="permission.id"
                            class="flex items-center p-3 border rounded-lg transition-colors"
                            :class="{
                                'border-green-400 bg-green-50 dark:bg-green-900/20': isSelected(permission.id),
                                'border-surface-200 dark:border-surface-700': !isSelected(permission.id)
                            }">
                            <Checkbox v-model="selectedPermissions" :inputId="`permission-${permission.id}`"
                                :value="permission.id" :binary="false" class="mr-3" />
                            <label :for="`permission-${permission.id}`" class="flex flex-col cursor-pointer flex-1">
                                <span class="text-sm font-medium">{{ permission.label || permission.name }}</span>
                                <span class="text-xs text-surface-500">{{ permission.name }}</span>
                            </label>
                        </div>
                    </div>
                </AccordionPanel>
            </Accordion>
        </div>

        <!-- Floating Save Button -->
        <div class="fixed bottom-8 right-8 z-50" v-if="hasChanges">
            <Button label="Lưu thay đổi" icon="pi pi-save" size="large"
                @click="savePermissions" :loading="saving"
                class="shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import SelectButton from 'primevue/selectbutton';

const props = defineProps({
    role: Object,
    groupedPermissions: Object,
    allPermissions: Array,
    currentPermissions: Array,
});

// State
const selectedPermissions = ref([...props.currentPermissions.map(p => p.id)]);
const originalPermissions = ref([...props.currentPermissions.map(p => p.id)]);
const saving = ref(false);
const searchQuery = ref('');
const expandedModules = ref(Object.keys(props.groupedPermissions).slice(0, 7)); // Expand first 7 modules

// Filter modes
const filterModes = [
    { label: 'Tất cả', value: 'all' },
    { label: 'Đã chọn', value: 'selected' },
    { label: 'Chưa chọn', value: 'unselected' }
];
const filterMode = ref('all');

// Computed
const totalPermissions = computed(() => props.allPermissions?.length || 0);

const permissionPercentage = computed(() => {
    if (totalPermissions.value === 0) return 0;
    return Math.round((selectedPermissions.value.length / totalPermissions.value) * 100);
});

const hasChanges = computed(() => {
    const current = [...selectedPermissions.value].sort();
    const original = [...originalPermissions.value].sort();
    return JSON.stringify(current) !== JSON.stringify(original);
});

const filteredGroupedPermissions = computed(() => {
    let filtered = { ...props.groupedPermissions };

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = {};

        for (const [module, perms] of Object.entries(props.groupedPermissions)) {
            if (module.toLowerCase().includes(query)) {
                filtered[module] = perms;
                continue;
            }

            const matchedPerms = perms.filter(p => p.name.toLowerCase().includes(query));
            if (matchedPerms.length > 0) {
                filtered[module] = matchedPerms;
            }
        }
    }

    // Apply selection filter
    if (filterMode.value === 'selected') {
        const result = {};
        for (const [module, perms] of Object.entries(filtered)) {
            const selectedPerms = perms.filter(p => selectedPermissions.value.includes(p.id));
            if (selectedPerms.length > 0) {
                result[module] = selectedPerms;
            }
        }
        filtered = result;
    } else if (filterMode.value === 'unselected') {
        const result = {};
        for (const [module, perms] of Object.entries(filtered)) {
            const unselectedPerms = perms.filter(p => !selectedPermissions.value.includes(p.id));
            if (unselectedPerms.length > 0) {
                result[module] = unselectedPerms;
            }
        }
        filtered = result;
    }

    return filtered;
});

// Methods
const isSelected = (permissionId) => {
    return selectedPermissions.value.includes(permissionId);
};

const togglePermission = (permissionId) => {
    const index = selectedPermissions.value.indexOf(permissionId);
    if (index > -1) {
        selectedPermissions.value.splice(index, 1);
    } else {
        selectedPermissions.value.push(permissionId);
    }
};

const getModuleSelectedCount = (module) => {
    const permissions = props.groupedPermissions[module];
    return permissions.filter(p => selectedPermissions.value.includes(p.id)).length;
};

const toggleModulePermissions = (module, permissions) => {
    const allSelected = permissions.every(p => selectedPermissions.value.includes(p.id));

    if (allSelected) {
        // Deselect all
        permissions.forEach(p => {
            const index = selectedPermissions.value.indexOf(p.id);
            if (index > -1) {
                selectedPermissions.value.splice(index, 1);
            }
        });
    } else {
        // Select all
        permissions.forEach(p => {
            if (!selectedPermissions.value.includes(p.id)) {
                selectedPermissions.value.push(p.id);
            }
        });
    }
};

const selectAll = () => {
    selectedPermissions.value = props.allPermissions.map(p => p.id);
};

const deselectAll = () => {
    selectedPermissions.value = [];
};

const savePermissions = () => {
    saving.value = true;
    router.post(`/roles/${props.role.id}/sync-permissions`, {
        permissions: selectedPermissions.value
    }, {
        onSuccess: () => {
            originalPermissions.value = [...selectedPermissions.value];
        },
        onFinish: () => {
            saving.value = false;
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
