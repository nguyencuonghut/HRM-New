<template>
    <Head>
        <title>KPI tháng nhân viên</title>
    </Head>

    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button
                        v-if="can('create performance reviews')"
                        label="Thêm mới"
                        icon="pi pi-plus"
                        class="mr-2"
                        @click="openNew"
                    />
                    <Button
                        v-if="can('delete performance reviews')"
                        label="Xoá"
                        icon="pi pi-trash"
                        severity="danger"
                        variant="outlined"
                        @click="confirmDeleteSelected"
                        :disabled="!selectedKpis || !selectedKpis.length"
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
                v-model:selection="selectedKpis"
                :value="kpisList || []"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Hiển thị từ {first} đến {last} trong tổng số {totalRecords} KPI"
                :loading="loading"
                selectionMode="multiple"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Quản lý KPI tháng</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                            <IconField>
                                <InputIcon><i class="pi pi-search" /></InputIcon>
                                <InputText v-model="filters['global'].value" placeholder="Tìm kiếm ..." />
                            </IconField>

                            <Select
                                :options="[
                                    { label: 'Tất cả năm', value: '' },
                                    ...years.map(y => ({ label: y.toString(), value: y }))
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                v-model="yearFilter"
                                placeholder="Năm"
                                @change="applyFilters"
                            />

                            <Select
                                :options="[
                                    { label: 'Tất cả tháng', value: '' },
                                    ...enums.months
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                v-model="monthFilter"
                                placeholder="Tháng"
                                @change="applyFilters"
                            />

                            <Select
                                :options="[
                                    { label: 'Tất cả nhân viên', value: '' },
                                    ...employees.map(e => ({ label: `${e.employee_code} - ${e.full_name}`, value: e.id }))
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                v-model="employeeFilter"
                                placeholder="Nhân viên"
                                filter
                                @change="applyFilters"
                            />
                        </div>
                    </div>
                </template>

                <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>

                <Column header="Nhân viên" sortable style="min-width: 14rem">
                    <template #body="slotProps">
                        {{ slotProps.data.employee?.employee_code }} - {{ slotProps.data.employee?.full_name }}
                    </template>
                </Column>

                <Column field="year" header="Năm" sortable style="min-width: 6rem"></Column>

                <Column field="month" header="Tháng" sortable style="min-width: 6rem">
                    <template #body="slotProps">
                        Tháng {{ slotProps.data.month }}
                    </template>
                </Column>

                <Column field="kpi_score" header="Điểm KPI" sortable style="min-width: 8rem">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.kpi_score" :severity="getScoreSeverity(slotProps.data.kpi_score)" />
                    </template>
                </Column>

                <Column header="Người nhập" style="min-width: 10rem">
                    <template #body="slotProps">
                        {{ slotProps.data.input_by ? slotProps.data.input_by_user.name : '-' }}
                    </template>
                </Column>

                <Column field="input_at" header="Thời gian nhập" sortable style="min-width: 10rem">
                    <template #body="slotProps">
                        {{ formatDateTime(slotProps.data.input_at) }}
                    </template>
                </Column>

                <Column v-if="canAny('edit performance reviews', 'delete performance reviews')" header="Hành động" :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <div class="flex gap-2">
                            <Button v-if="can('edit performance reviews')" icon="pi pi-pencil" variant="outlined" rounded @click="editKpi(slotProps.data)" />
                            <Button v-if="can('delete performance reviews')" icon="pi pi-trash" variant="outlined" rounded severity="danger" @click="confirmDeleteKpi(slotProps.data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Add/Edit KPI Dialog -->
        <Dialog
            v-model:visible="kpiDialog"
            :style="{ width: '620px' }"
            :header="isEditing ? 'Sửa KPI tháng' : 'Thêm KPI tháng'"
            :modal="true"
        >
            <div class="flex flex-col gap-6">
                <div>
                    <label for="employee" class="block font-bold mb-3">Nhân viên <span class="text-red-500">*</span></label>
                    <Select
                        id="employee"
                        v-model="kpi.employee_id"
                        :options="employees"
                        optionLabel="full_name"
                        optionValue="id"
                        filter
                        :invalid="(submitted && !kpi.employee_id) || hasError('employee_id')"
                        fluid
                        placeholder="Chọn nhân viên"
                    >
                        <template #option="slotProps">
                            {{ slotProps.option.employee_code }} - {{ slotProps.option.full_name }}
                        </template>
                        <template #value="slotProps">
                            <span v-if="slotProps.value">
                                {{ employees.find(e => e.id === slotProps.value)?.employee_code }} -
                                {{ employees.find(e => e.id === slotProps.value)?.full_name }}
                            </span>
                            <span v-else>Chọn nhân viên</span>
                        </template>
                    </Select>
                    <small v-if="submitted && !kpi.employee_id" class="text-red-500">Nhân viên là bắt buộc</small>
                    <small v-if="hasError('employee_id')" class="p-error block mt-1">{{ getError('employee_id') }}</small>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="year" class="block font-bold mb-3">Năm <span class="text-red-500">*</span></label>
                        <InputNumber
                            id="year"
                            v-model="kpi.year"
                            :min="2000"
                            :max="2100"
                            :useGrouping="false"
                            :invalid="(submitted && !kpi.year) || hasError('year')"
                            fluid
                            placeholder="Năm"
                        />
                        <small v-if="submitted && !kpi.year" class="text-red-500">Năm là bắt buộc</small>
                        <small v-if="hasError('year')" class="p-error block mt-1">{{ getError('year') }}</small>
                    </div>

                    <div>
                        <label for="month" class="block font-bold mb-3">Tháng <span class="text-red-500">*</span></label>
                        <Select
                            id="month"
                            v-model="kpi.month"
                            :options="enums.months"
                            optionLabel="label"
                            optionValue="value"
                            :invalid="(submitted && !kpi.month) || hasError('month')"
                            fluid
                            placeholder="Chọn tháng"
                        />
                        <small v-if="submitted && !kpi.month" class="text-red-500">Tháng là bắt buộc</small>
                        <small v-if="hasError('month')" class="p-error block mt-1">{{ getError('month') }}</small>
                    </div>
                </div>

                <div>
                    <label for="kpi_score" class="block font-bold mb-3">Điểm KPI <span class="text-red-500">*</span></label>
                    <InputNumber
                        id="kpi_score"
                        v-model="kpi.kpi_score"
                        :min="0"
                        :minFractionDigits="2"
                        :maxFractionDigits="2"
                        :invalid="(submitted && kpi.kpi_score === null) || hasError('kpi_score')"
                        fluid
                        placeholder="Điểm KPI"
                    />
                    <small v-if="submitted && kpi.kpi_score === null" class="text-red-500">Điểm KPI là bắt buộc</small>
                    <small v-if="hasError('kpi_score')" class="p-error block mt-1">{{ getError('kpi_score') }}</small>
                </div>

                <div>
                    <label for="note" class="block font-bold mb-3">Ghi chú</label>
                    <Textarea
                        id="note"
                        v-model="kpi.note"
                        rows="3"
                        fluid
                        placeholder="Nhập ghi chú"
                    />
                </div>
            </div>

            <template #footer>
                <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Lưu" icon="pi pi-check" @click="saveKpi" :loading="saving" />
            </template>
        </Dialog>

        <!-- Delete KPI Dialog -->
        <Dialog v-model:visible="deleteKpiDialog" :style="{ width: '450px' }" header="Xác nhận xóa KPI" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="kpi">Bạn có chắc chắn muốn xóa KPI này?</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteKpiDialog = false" severity="secondary" variant="text" />
                <Button label="Có" icon="pi pi-check" @click="deleteKpi" severity="danger" :loading="deleting" />
            </template>
        </Dialog>

        <!-- Delete Multiple KPIs Dialog -->
        <Dialog v-model:visible="deleteKpisDialog" :style="{ width: '450px' }" header="Xác nhận xóa nhiều KPI" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span>Bạn có chắc chắn muốn xóa các KPI đã chọn?</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteKpisDialog = false" severity="secondary" variant="text" />
                <Button label="Có" icon="pi pi-check" text @click="deleteSelectedKpis" severity="danger" :loading="deleting" />
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
import InputNumber from 'primevue/inputnumber'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'

import { EmployeeKpiMonthService } from '@/services'
import { useFormValidation } from '@/composables/useFormValidation'
import { usePermissions } from '@/Composables/usePermissions'

// Props từ Controller
const props = defineProps({
    kpis: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    years: { type: Array, default: () => [] },
    enums: { type: Object, default: () => ({ months: [] }) },
})

// Composables
const { errors, hasError, getError } = useFormValidation()
const { can, canAny } = usePermissions()

// Reactive
const dt = ref()
const kpisList = ref([...props.kpis])
const selectedKpis = ref([])
const kpiDialog = ref(false)
const deleteKpiDialog = ref(false)
const deleteKpisDialog = ref(false)
const submitted = ref(false)
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)

// Filters
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
})

const yearFilter = ref('')
const monthFilter = ref('')
const employeeFilter = ref('')

// State form
const kpi = ref({
    id: null,
    employee_id: null,
    year: new Date().getFullYear(),
    month: new Date().getMonth() + 1,
    kpi_score: null,
    note: '',
})

// Computed
const isEditing = computed(() => !!kpi.value.id)

// Watch props
watch(
    () => props.kpis,
    (val) => {
        if (Array.isArray(val)) {
            kpisList.value = [...val]
        }
    },
    { immediate: true, deep: true }
)

// Watch global filter with debounce
let searchTimeout = null
watch(
    () => filters.value.global?.value,
    (newVal) => {
        if (searchTimeout) clearTimeout(searchTimeout)
        searchTimeout = setTimeout(() => {
            applyFilters()
        }, 500) // 500ms debounce
    }
)

// Functions
function getScoreSeverity(score) {
    if (score >= 90) return 'success'
    if (score >= 70) return 'info'
    if (score >= 50) return 'warn'
    return 'danger'
}

function formatDateTime(dateString) {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleString('vi-VN')
}

function openNew() {
    submitted.value = false
    kpi.value = {
        id: null,
        employee_id: null,
        year: new Date().getFullYear(),
        month: new Date().getMonth() + 1,
        kpi_score: null,
        note: '',
    }
    kpiDialog.value = true
}

function editKpi(row) {
    submitted.value = false
    kpi.value = {
        id: row.id,
        employee_id: row.employee_id,
        year: row.year,
        month: row.month,
        kpi_score: row.kpi_score,
        note: row.note || '',
    }
    kpiDialog.value = true
}

function hideDialog() {
    kpiDialog.value = false
}

function saveKpi() {
    submitted.value = true
    if (!kpi.value.employee_id || !kpi.value.year || !kpi.value.month || kpi.value.kpi_score === null) return

    saving.value = true
    const payload = { ...kpi.value }

    const onSuccess = () => {
        saving.value = false
        kpiDialog.value = false
        EmployeeKpiMonthService.index({}, {})
    }
    const onError = () => (saving.value = false)

    if (!isEditing.value) {
        EmployeeKpiMonthService.store(payload, { onSuccess, onError })
    } else {
        EmployeeKpiMonthService.update(kpi.value.id, payload, { onSuccess, onError })
    }
}

function confirmDeleteKpi(row) {
    kpi.value = { ...row }
    deleteKpiDialog.value = true
}

function deleteKpi() {
    deleting.value = true
    EmployeeKpiMonthService.destroy(kpi.value.id, {
        onSuccess: () => {
            deleting.value = false
            deleteKpiDialog.value = false
            EmployeeKpiMonthService.index({}, {})
        },
        onError: () => {
            deleting.value = false
        }
    })
}

function confirmDeleteSelected() {
    deleteKpisDialog.value = true
}

function deleteSelectedKpis() {
    const ids = selectedKpis.value.map((x) => x.id)
    if (!ids.length) return
    deleting.value = true
    EmployeeKpiMonthService.bulkDelete(ids, {
        onSuccess: () => {
            deleting.value = false
            deleteKpisDialog.value = false
            selectedKpis.value = []
            EmployeeKpiMonthService.index({}, {})
        },
        onError: () => {
            deleting.value = false
        }
    })
}

function exportCSV() {
    dt.value?.exportCSV()
}

function applyFilters() {
    const filterData = {}

    if (yearFilter.value) filterData.year = yearFilter.value
    if (monthFilter.value) filterData.month = monthFilter.value
    if (employeeFilter.value) filterData.employee_id = employeeFilter.value
    if (filters.value.global?.value) filterData.search = filters.value.global.value

    EmployeeKpiMonthService.index(filterData, {})
}
</script>
