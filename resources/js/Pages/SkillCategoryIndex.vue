<template>
    <Head>
        <title>Quản lý danh mục kỹ năng</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button
                        v-if="can('create skill categories')"
                        label="Thêm mới"
                        icon="pi pi-plus"
                        class="mr-2"
                        @click="openNew"
                    />
                    <Button
                        v-if="can('delete skill categories')"
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
                :value="categoriesList || []"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Hiển thị từ {first} đến {last} trong tổng số {totalRecords} danh mục"
                :loading="loading"
                selectionMode="multiple"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Quản lý danh mục kỹ năng</h4>
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

                <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
                <Column field="name" header="Tên" sortable style="min-width: 16rem"></Column>
                <Column field="description" header="Mô tả" sortable style="min-width: 20rem"></Column>

                <Column header="Số kỹ năng" style="min-width: 8rem">
                    <template #body="slotProps">
                        {{ slotProps.data.skills_count || 0 }}
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

                <Column v-if="canAny('edit skill categories', 'delete skill categories')" header="Hành động" :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <div class="flex gap-2">
                            <Button v-if="can('edit skill categories')" icon="pi pi-pencil" variant="outlined" rounded @click="editCategory(slotProps.data)" />
                            <Button v-if="can('delete skill categories')" icon="pi pi-trash" variant="outlined" rounded severity="danger" @click="confirmDeleteCategory(slotProps.data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Add/Edit Category Dialog -->
        <Dialog
            v-model:visible="categoryDialog"
            :style="{ width: '520px' }"
            :header="isEditing ? 'Sửa danh mục' : 'Thêm danh mục'"
            :modal="true"
        >
            <div class="flex flex-col gap-6">
                <div>
                    <label for="name" class="block font-bold mb-3">Tên danh mục</label>
                    <InputText
                        id="name"
                        v-model="category.name"
                        required="true"
                        autofocus
                        :invalid="(submitted && !category.name) || hasError('name')"
                        fluid
                    />
                    <small v-if="submitted && !category.name" class="text-red-500">Tên là bắt buộc</small>
                    <small v-if="hasError('name')" class="p-error block mt-1">{{ getError('name') }}</small>
                </div>

                <div>
                    <label for="description" class="block font-bold mb-3">Mô tả</label>
                    <Textarea
                        id="description"
                        v-model="category.description"
                        rows="3"
                        fluid
                    />
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model="category.is_active" :binary="true" inputId="active" />
                    <label for="active" class="font-bold">Kích hoạt</label>
                </div>
            </div>

            <template #footer>
                <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Lưu" icon="pi pi-check" @click="saveCategory" :loading="saving" />
            </template>
        </Dialog>

        <!-- Delete Category Dialog -->
        <Dialog v-model:visible="deleteCategoryDialog" :style="{ width: '450px' }" header="Xác nhận xóa danh mục" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="category">{{ 'Bạn có chắc chắn muốn xóa danh mục ' + category.name + '?' }}</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteCategoryDialog = false" severity="secondary" variant="text" />
                <Button label="Có" icon="pi pi-check" @click="deleteCategory" severity="danger" :loading="deleting" />
            </template>
        </Dialog>

        <!-- Delete Multiple Categories Dialog -->
        <Dialog v-model:visible="deleteCategoriesDialog" :style="{ width: '450px' }" header="Xác nhận xóa nhiều danh mục" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span>{{ 'Bạn có chắc chắn muốn xóa các danh mục đã chọn?' }}</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteCategoriesDialog = false" severity="secondary" variant="text" />
                <Button label="Có" icon="pi pi-check" text @click="deleteSelectedCategories" severity="danger" :loading="deleting" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import { Head } from '@inertiajs/vue3'

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
import Badge from 'primevue/badge'
import Checkbox from 'primevue/checkbox'
import Textarea from 'primevue/textarea'

import { SkillCategoryService } from '@/services'
import { useFormValidation } from '@/composables/useFormValidation'
import { usePermissions } from '@/Composables/usePermissions'

// Props
const props = defineProps({
    categories: { type: Array, default: () => [] },
})

// Composables
const { errors, hasError, getError } = useFormValidation()
const { can, canAny } = usePermissions()

// Reactive
const dt = ref()
const categoriesList = ref([...props.categories])
const selectedCategories = ref([])
const categoryDialog = ref(false)
const deleteCategoryDialog = ref(false)
const deleteCategoriesDialog = ref(false)
const submitted = ref(false)
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)

// Filters
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
})

const urlParams = new URLSearchParams(window.location.search)
const activeFilter = ref(urlParams.has('is_active') ? (urlParams.get('is_active') === 'true') : '')

// Form state
const category = ref({
    id: null,
    name: '',
    description: '',
    is_active: true,
})

// Computed
const isEditing = computed(() => !!category.value.id)

// Watch props
watch(
    () => props.categories,
    (val) => {
        if (Array.isArray(val)) {
            categoriesList.value = [...val]
        }
    },
    { immediate: true, deep: true }
)

function formatDate(dateString) {
    if (!dateString) return ''
    return new Date(dateString).toLocaleDateString('vi-VN')
}

function openNew() {
    submitted.value = false
    category.value = {
        id: null,
        name: '',
        description: '',
        is_active: true,
    }
    categoryDialog.value = true
}

function editCategory(row) {
    submitted.value = false
    category.value = {
        id: row.id,
        name: row.name,
        description: row.description || '',
        is_active: !!row.is_active,
    }
    categoryDialog.value = true
}

function hideDialog() {
    categoryDialog.value = false
}

function saveCategory() {
    submitted.value = true
    if (!category.value.name) return

    saving.value = true
    const payload = { ...category.value }

    const onSuccess = () => {
        saving.value = false
        categoryDialog.value = false
        SkillCategoryService.index({}, {})
    }
    const onError = () => (saving.value = false)

    if (!isEditing.value) {
        SkillCategoryService.store(payload, { onSuccess, onError })
    } else {
        SkillCategoryService.update(category.value.id, payload, { onSuccess, onError })
    }
}

function confirmDeleteCategory(row) {
    category.value = { ...row }
    deleteCategoryDialog.value = true
}

function deleteCategory() {
    deleting.value = true
    SkillCategoryService.destroy(category.value.id, {
        onSuccess: () => {
            deleting.value = false
            deleteCategoryDialog.value = false
            SkillCategoryService.index({}, {})
        },
        onError: () => {
            deleting.value = false
        }
    })
}

function confirmDeleteSelected() {
    deleteCategoriesDialog.value = true
}

function deleteSelectedCategories() {
    const ids = selectedCategories.value.map((x) => x.id)
    if (!ids.length) return
    deleting.value = true
    SkillCategoryService.bulkDelete(ids, {
        onSuccess: () => {
            deleting.value = false
            deleteCategoriesDialog.value = false
            selectedCategories.value = []
            SkillCategoryService.index({}, {})
        },
        onError: () => {
            deleting.value = false
        }
    })
}

function exportCSV() {
    dt.value?.exportCSV()
}

function applyActiveFilter() {
    const filterData = {}

    if (activeFilter.value !== null && activeFilter.value !== undefined && activeFilter.value !== '') {
        filterData.is_active = activeFilter.value
    }

    if (filters.value.global?.value) {
        filterData.search = filters.value.global.value
    }

    SkillCategoryService.index(filterData, {})
}
</script>
