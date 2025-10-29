<template>
    <Head>
        <title>Quản lý phòng/ban</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button
                        v-if="isSuperAdmin()"
                        label="Thêm"
                        icon="pi pi-plus"
                        class="mr-2"
                        @click="openNew"
                    />
                    <Button
                        v-if="isSuperAdmin()"
                        label="Xoá"
                        icon="pi pi-trash"
                        severity="danger"
                        variant="outlined"
                        @click="confirmDeleteSelected"
                        :disabled="!selectedDepartments || !selectedDepartments.length"
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
                v-model:selection="selectedDepartments"
                :value="departmentsList || []"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Hiển thị từ {first} đến {last} trong tổng số {totalRecords} đơn vị"
                :loading="loading"
                selectionMode="multiple"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Quản lý phòng/ban</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <IconField>
                                <InputIcon><i class="pi pi-search" /></InputIcon>
                                <InputText v-model="filters['global'].value" placeholder="Search ..." />
                            </IconField>

                            <Select
                                :options="[
                                    { label: 'Tất cả', value: '' },
                                    ...enums.types
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                v-model="typeFilter"
                                placeholder="Loại"
                                @change="applyTypeFilter"
                            />

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

                <Column v-if="isSuperAdmin()" selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
                <Column field="name" header="Tên" sortable style="min-width: 16rem"></Column>
                <Column field="code" header="Mã" sortable style="min-width: 10rem"></Column>

                <Column header="Loại" style="min-width: 10rem">
                    <template #body="slotProps">
                        <Tag :value="typeLabel(slotProps.data.type)" />
                    </template>
                </Column>

                <Column header="Trực thuộc" style="min-width: 12rem">
                    <template #body="slotProps">
                        {{ slotProps.data.parent?.name ?? '-' }}
                    </template>
                </Column>

                <Column header="Trạng thái" style="min-width: 8rem">
                    <template #body="slotProps">
                        <Badge v-if="slotProps.data.is_active" value="Kích hoạt" severity="success" />
                        <Badge v-else value="Không kích hoạt" severity="danger" />
                    </template>
                </Column>

                <Column field="created_at" header="Ngày tạo" sortable style="min-width: 10rem">
                    <template #body="slotProps">
                        {{ formatDate(slotProps.data.created_at) }}
                    </template>
                </Column>

                <Column v-if="isSuperAdmin()" header="Hành động" :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <div class="flex gap-2">
                            <Button icon="pi pi-pencil" variant="outlined" rounded @click="editDepartment(slotProps.data)" />
                            <Button icon="pi pi-trash" variant="outlined" rounded severity="danger" @click="confirmDeleteDepartment(slotProps.data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Add/Edit Department Dialog -->
        <Dialog
            v-model:visible="departmentDialog"
            :style="{ width: '520px' }"
            :header="isEditing ? 'Sửa' : 'Thêm'"
            :modal="true"
        >
            <div class="flex flex-col gap-6">
                <div>
                    <label for="name" class="block font-bold mb-3">Tên</label>
                    <InputText
                        id="name"
                        v-model.trim="department.name"
                        required="true"
                        autofocus
                        :invalid="(submitted && !department.name) || hasError('name')"
                        fluid
                    />
                    <small v-if="submitted && !department.name" class="text-red-500">Tên là bắt buộc</small>
                    <small v-if="hasError('name')" class="p-error block mt-1">{{ getError('name') }}</small>
                </div>

                <div>
                    <label for="code" class="block font-bold mb-3">Mã</label>
                    <InputText
                        id="code"
                        v-model.trim="department.code"
                        :invalid="hasError('code')"
                        fluid
                        placeholder="Nhập mã"
                    />
                    <small v-if="hasError('code')" class="p-error block mt-1">{{ getError('code') }}</small>
                </div>

                <div>
                    <label for="type" class="block font-bold mb-3">Loại</label>
                    <Select
                        id="type"
                        v-model="department.type"
                        :options="enums.types"
                        optionLabel="label"
                        optionValue="value"
                        :invalid="(submitted && !department.type) || hasError('type')"
                        fluid
                    />
                    <small v-if="submitted && !department.type" class="text-red-500">Loại là bắt buộc</small>
                    <small v-if="hasError('type')" class="p-error block mt-1">{{ getError('type') }}</small>
                </div>

                <div>
                    <label for="parent" class="block font-bold mb-3">Phòng ban cha</label>
                    <Select
                        id="parent"
                        v-model="department.parent_id"
                        :options="[{ id: null, name: 'Không có' }, ...parents]"
                        optionLabel="name"
                        optionValue="id"
                        filter
                        fluid
                    />
                    <small v-if="hasError('parent_id')" class="p-error block mt-1">{{ getError('parent_id') }}</small>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="order_index" class="block font-bold mb-3">Thứ tự</label>
                        <InputText
                            id="order_index"
                            v-model.number="department.order_index"
                            fluid
                            :invalid="hasError('order_index')"
                        />
                        <small v-if="hasError('order_index')" class="p-error block mt-1">{{ getError('order_index') }}</small>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="department.is_active" :binary="true" inputId="active" />
                        <label for="active" class="font-bold">Kích hoạt</label>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Lưu" icon="pi pi-check" @click="saveDepartment" :loading="saving" />
            </template>
        </Dialog>

        <!-- Delete Department Dialog -->
        <Dialog v-model:visible="deleteDepartmentDialog" :style="{ width: '450px' }" header="Xác nhận xóa phòng ban" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="department">{{ 'Bạn có chắc chắn muốn xóa phòng ban ' + department.name + '?' }}</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteDepartmentDialog = false" severity="secondary" variant="text" />
                <Button label="Có" icon="pi pi-check" @click="deleteDepartment" severity="danger" :loading="deleting" />
            </template>
        </Dialog>

        <!-- Delete Multiple Departments Dialog -->
        <Dialog v-model:visible="deleteDepartmentsDialog" :style="{ width: '450px' }" header="Xác nhận xóa nhiều phòng ban" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span>{{ 'Bạn có chắc chắn muốn xóa các phòng ban đã chọn?' }}</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteDepartmentsDialog = false" severity="secondary" variant="text" />
                <Button label="Có" icon="pi pi-check" text @click="deleteSelectedDepartments" severity="danger" :loading="deleting" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { Head, usePage, router } from '@inertiajs/vue3'

// PrimeVue Components
import Button from 'primevue/button'
import Toolbar from 'primevue/toolbar'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Checkbox from 'primevue/checkbox'

import { DepartmentService } from '@/services'
import { useFormValidation } from '@/composables/useFormValidation'
import { usePermission } from '@/composables/usePermission'

// Props từ Controller
const props = defineProps({
    departments: { type: Array, default: () => [] }, // nếu bạn trả về mảng (giống Users)
    parents: { type: Array, default: () => [] },
    enums: { type: Object, default: () => ({ types: [] }) },
})

// Composables
const { errors, hasError, getError } = useFormValidation()
const { isSuperAdmin } = usePermission()

// Reactive
const dt = ref()
const departmentsList = ref(Array.isArray(props.departments) ? [...props.departments] : [])
const selectedDepartments = ref([])
const departmentDialog = ref(false)
const deleteDepartmentDialog = ref(false)
const deleteDepartmentsDialog = ref(false)
const submitted = ref(false)
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)

// Bộ lọc DataTable
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
})

// Khởi tạo filter values từ URL params hoặc default
const urlParams = new URLSearchParams(window.location.search)
const typeFilter = ref(urlParams.get('type') || '') // Khởi tạo với "Tất cả" hoặc từ URL
const activeFilter = ref(urlParams.has('is_active') ? (urlParams.get('is_active') === 'true') : '') // Khởi tạo với "Tất cả" hoặc từ URL

// State form
const department = ref({
    id: null,
    parent_id: null,
    type: 'DEPARTMENT',
    name: '',
    code: '',
    order_index: 0,
    is_active: true,
})

// Computed
const isEditing = computed(() => !!department.value.id)

// Watch props.users-like -> cập nhật list local mỗi lần server phản hồi
watch(
    () => props.departments,
    (val) => {
        if (Array.isArray(val)) {
            departmentsList.value = [...val]
        }
    },
    { immediate: true, deep: true }
)

function typeLabel(v) {
    const found = props.enums?.types?.find((x) => x.value === v)
    return found ? found.label : v
}

function formatDate(dateString) {
    if (!dateString) return ''
    return new Date(dateString).toLocaleDateString('vi-VN')
}

// Toolbar events
function openNew() {
    submitted.value = false
    department.value = {
        id: null,
        parent_id: null,
        type: 'DEPARTMENT',
        name: '',
        code: '',
        order_index: 0,
        is_active: true,
    }
    departmentDialog.value = true
}

function editDepartment(row) {
    submitted.value = false
    department.value = {
        id: row.id,
        parent_id: row.parent_id,
        type: row.type,
        name: row.name,
        code: row.code || '',
        order_index: row.order_index ?? 0,
        is_active: !!row.is_active,
    }
    departmentDialog.value = true
}

function hideDialog() {
    departmentDialog.value = false
}

function saveDepartment() {
    submitted.value = true
    if (!department.value.name || !department.value.type) return

    saving.value = true
    const payload = { ...department.value }

    const onSuccess = (/* page */) => {
        saving.value = false
        departmentDialog.value = false
        // Option: reload trang hiện tại để đồng bộ danh sách
        DepartmentService.index({}, {})
    }
    const onError = () => (saving.value = false)

    if (!isEditing.value) {
        DepartmentService.store(payload, { onSuccess, onError })
    } else {
        DepartmentService.update(department.value.id, payload, { onSuccess, onError })
    }
}

function confirmDeleteDepartment(row) {
    department.value = { ...row }
    deleteDepartmentDialog.value = true
}

function deleteDepartment() {
    deleting.value = true
    DepartmentService.destroy(department.value.id, {
        onSuccess: () => {
            deleting.value = false
            deleteDepartmentDialog.value = false
            DepartmentService.index({}, {})
        },
        onError: () => {
            deleting.value = false
        }
    })
}

function confirmDeleteSelected() {
    deleteDepartmentsDialog.value = true
}

function deleteSelectedDepartments() {
    const ids = selectedDepartments.value.map((x) => x.id)
    if (!ids.length) return
    deleting.value = true
    DepartmentService.bulkDestroy(ids, {
        onSuccess: () => {
            deleting.value = false
            deleteDepartmentsDialog.value = false
            selectedDepartments.value = []
            DepartmentService.index({}, {})
        },
        onError: () => {
            deleting.value = false
        }
    })
}

function exportCSV() {
    dt.value?.exportCSV()
}

// Lọc nâng cao (type, status) -> gọi GET lại (bám pattern Users dùng Inertia GET sau mỗi thay đổi)
function applyTypeFilter() {
    const filterData = {}

    // Chỉ gửi type filter nếu không phải "Tất cả" (empty string)
    if (typeFilter.value && typeFilter.value !== '') {
        filterData.type = typeFilter.value
    }

    // Chỉ gửi active filter nếu không phải "Tất cả" (empty string)
    if (activeFilter.value !== null && activeFilter.value !== undefined && activeFilter.value !== '') {
        filterData.is_active = activeFilter.value
    }

    if (filters.value.global?.value) {
        filterData.search = filters.value.global.value
    }

    console.log('Type filter applied:', filterData)
    DepartmentService.index(filterData, {
        onSuccess: (response) => {
            console.log('Filter response:', response)
        }
    })
}

function applyActiveFilter() {
    const filterData = {}

    // Chỉ gửi type filter nếu không phải "Tất cả" (empty string)
    if (typeFilter.value && typeFilter.value !== '') {
        filterData.type = typeFilter.value
    }

    // Chỉ gửi active filter nếu không phải "Tất cả" (empty string)
    if (activeFilter.value !== null && activeFilter.value !== undefined && activeFilter.value !== '') {
        filterData.is_active = activeFilter.value
    }

    if (filters.value.global?.value) {
        filterData.search = filters.value.global.value
    }

    console.log('Active filter applied:', filterData)
    DepartmentService.index(filterData, {
        onSuccess: (response) => {
            console.log('Filter response:', response)
        }
    })
}
</script>
