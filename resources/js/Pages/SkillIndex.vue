<template>
    <Head>
        <title>Quản lý kỹ năng</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button
                        label="Thêm mới"
                        icon="pi pi-plus"
                        class="mr-2"
                        @click="openNew"
                    />
                    <Button
                        label="Xoá"
                        icon="pi pi-trash"
                        severity="danger"
                        variant="outlined"
                        @click="confirmDeleteSelected"
                        :disabled="!selectedSkills || !selectedSkills.length"
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
                v-model:selection="selectedSkills"
                :value="skillsList || []"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25, 50]"
                currentPageReportTemplate="Hiển thị từ {first} đến {last} trong tổng số {totalRecords} kỹ năng"
                :loading="loading"
                selectionMode="multiple"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Quản lý kỹ năng</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <IconField>
                                <InputIcon><i class="pi pi-search" /></InputIcon>
                                <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
                            </IconField>

                            <Select
                                :options="[
                                    { id: '', name: 'Tất cả danh mục' },
                                    ...categories
                                ]"
                                optionLabel="name"
                                optionValue="id"
                                v-model="categoryFilter"
                                placeholder="Danh mục"
                                @change="applyCategoryFilter"
                            />
                        </div>
                    </div>
                </template>

                <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
                <Column field="name" header="Tên" sortable style="min-width: 16rem"></Column>
                <Column field="code" header="Mã" sortable style="min-width: 10rem"></Column>

                <Column header="Danh mục" style="min-width: 12rem">
                    <template #body="slotProps">
                        {{ slotProps.data.category?.name ?? '-' }}
                    </template>
                </Column>

                <Column field="created_at" header="Ngày tạo" sortable style="min-width: 10rem">
                    <template #body="slotProps">
                        {{ formatDate(slotProps.data.created_at) }}
                    </template>
                </Column>

                <Column header="Hành động" :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <div class="flex gap-2">
                            <Button icon="pi pi-pencil" variant="outlined" rounded @click="editSkill(slotProps.data)" />
                            <Button icon="pi pi-trash" variant="outlined" rounded severity="danger" @click="confirmDeleteSkill(slotProps.data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Add/Edit Skill Dialog -->
        <Dialog
            v-model:visible="skillDialog"
            :style="{ width: '520px' }"
            :header="isEditing ? 'Sửa kỹ năng' : 'Thêm kỹ năng'"
            :modal="true"
        >
            <div class="flex flex-col gap-6">
                <div>
                    <label for="name" class="block font-bold mb-3">Tên kỹ năng</label>
                    <InputText
                        id="name"
                        v-model="skill.name"
                        required="true"
                        autofocus
                        :invalid="(submitted && !skill.name) || hasError('name')"
                        fluid
                    />
                    <small v-if="submitted && !skill.name" class="text-red-500">Tên là bắt buộc</small>
                    <small v-if="hasError('name')" class="p-error block mt-1">{{ getError('name') }}</small>
                </div>

                <div>
                    <label for="code" class="block font-bold mb-3">Mã</label>
                    <InputText
                        id="code"
                        v-model="skill.code"
                        :invalid="hasError('code')"
                        fluid
                        placeholder="Nhập mã (tự động nếu để trống)"
                    />
                    <small v-if="hasError('code')" class="p-error block mt-1">{{ getError('code') }}</small>
                </div>

                <div>
                    <label for="category" class="block font-bold mb-3">Danh mục</label>
                    <Select
                        id="category"
                        v-model="skill.category_id"
                        :options="categories"
                        optionLabel="name"
                        optionValue="id"
                        filter
                        :invalid="(submitted && !skill.category_id) || hasError('category_id')"
                        fluid
                    />
                    <small v-if="submitted && !skill.category_id" class="text-red-500">Danh mục là bắt buộc</small>
                    <small v-if="hasError('category_id')" class="p-error block mt-1">{{ getError('category_id') }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Lưu" icon="pi pi-check" @click="saveSkill" :loading="saving" />
            </template>
        </Dialog>

        <!-- Delete Skill Dialog -->
        <Dialog v-model:visible="deleteSkillDialog" :style="{ width: '450px' }" header="Xác nhận xóa kỹ năng" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="skill">{{ 'Bạn có chắc chắn muốn xóa kỹ năng ' + skill.name + '?' }}</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteSkillDialog = false" severity="secondary" variant="text" />
                <Button label="Có" icon="pi pi-check" @click="deleteSkill" severity="danger" :loading="deleting" />
            </template>
        </Dialog>

        <!-- Delete Multiple Skills Dialog -->
        <Dialog v-model:visible="deleteSkillsDialog" :style="{ width: '450px' }" header="Xác nhận xóa nhiều kỹ năng" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span>{{ 'Bạn có chắc chắn muốn xóa các kỹ năng đã chọn?' }}</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteSkillsDialog = false" severity="secondary" variant="text" />
                <Button label="Có" icon="pi pi-check" text @click="deleteSelectedSkills" severity="danger" :loading="deleting" />
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

import { SkillService } from '@/services'
import { useFormValidation } from '@/composables/useFormValidation'

// Props
const props = defineProps({
    skills: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
})

// Composables
const { errors, hasError, getError } = useFormValidation()

// Reactive
const dt = ref()
const skillsList = ref([...props.skills])
const selectedSkills = ref([])
const skillDialog = ref(false)
const deleteSkillDialog = ref(false)
const deleteSkillsDialog = ref(false)
const submitted = ref(false)
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)

// Filters
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
})

const urlParams = new URLSearchParams(window.location.search)
const categoryFilter = ref(urlParams.get('category_id') || '')

// Form state
const skill = ref({
    id: null,
    category_id: null,
    code: '',
    name: '',
})

// Computed
const isEditing = computed(() => !!skill.value.id)

// Watch props
watch(
    () => props.skills,
    (val) => {
        if (Array.isArray(val)) {
            skillsList.value = [...val]
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
    skill.value = {
        id: null,
        category_id: null,
        code: '',
        name: '',
    }
    skillDialog.value = true
}

function editSkill(row) {
    submitted.value = false
    skill.value = {
        id: row.id,
        category_id: row.category_id,
        code: row.code || '',
        name: row.name,
    }
    skillDialog.value = true
}

function hideDialog() {
    skillDialog.value = false
}

function saveSkill() {
    submitted.value = true
    if (!skill.value.name || !skill.value.category_id) return

    saving.value = true
    const payload = { ...skill.value }

    const onSuccess = () => {
        saving.value = false
        skillDialog.value = false
        SkillService.index({}, {})
    }
    const onError = () => (saving.value = false)

    if (!isEditing.value) {
        SkillService.store(payload, { onSuccess, onError })
    } else {
        SkillService.update(skill.value.id, payload, { onSuccess, onError })
    }
}

function confirmDeleteSkill(row) {
    skill.value = { ...row }
    deleteSkillDialog.value = true
}

function deleteSkill() {
    deleting.value = true
    SkillService.destroy(skill.value.id, {
        onSuccess: () => {
            deleting.value = false
            deleteSkillDialog.value = false
            SkillService.index({}, {})
        },
        onError: () => {
            deleting.value = false
        }
    })
}

function confirmDeleteSelected() {
    deleteSkillsDialog.value = true
}

function deleteSelectedSkills() {
    const ids = selectedSkills.value.map((x) => x.id)
    if (!ids.length) return
    deleting.value = true
    SkillService.bulkDelete(ids, {
        onSuccess: () => {
            deleting.value = false
            deleteSkillsDialog.value = false
            selectedSkills.value = []
            SkillService.index({}, {})
        },
        onError: () => {
            deleting.value = false
        }
    })
}

function exportCSV() {
    dt.value?.exportCSV()
}

function applyCategoryFilter() {
    const filterData = {}

    if (categoryFilter.value && categoryFilter.value !== '') {
        filterData.category_id = categoryFilter.value
    }

    if (filters.value.global?.value) {
        filterData.search = filters.value.global.value
    }

    SkillService.index(filterData, {})
}
</script>
