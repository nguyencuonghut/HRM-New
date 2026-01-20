<template>
    <Head>
        <title>Đánh giá cuối năm</title>
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
                        :disabled="!selectedReviews || !selectedReviews.length"
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
                v-model:selection="selectedReviews"
                :value="reviewsList || []"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Hiển thị từ {first} đến {last} trong tổng số {totalRecords} đánh giá"
                :loading="loading"
                selectionMode="multiple"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Quản lý đánh giá cuối năm</h4>
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
                                    { label: 'Tất cả xếp loại', value: '' },
                                    ...enums.ratings
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                v-model="ratingFilter"
                                placeholder="Xếp loại"
                                @change="applyFilters"
                            />

                            <Select
                                :options="[
                                    { label: 'Tất cả nhân viên', value: '' },
                                    ...employees.map(e => ({ label: `${e.code} - ${e.full_name}`, value: e.id }))
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
                        {{ slotProps.data.employee?.display_name }}
                    </template>
                </Column>

                <Column field="year" header="Năm" sortable style="min-width: 6rem"></Column>

                <Column field="kpi_avg_score" header="Điểm KPI TB" sortable style="min-width: 8rem">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.kpi_avg_score" severity="info" />
                    </template>
                </Column>

                <Column field="final_rating" header="Xếp loại" sortable style="min-width: 8rem">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.rating_label" :severity="getRatingSeverity(slotProps.data.final_rating)" />
                    </template>
                </Column>

                <Column field="final_score" header="Điểm tổng" sortable style="min-width: 8rem">
                    <template #body="slotProps">
                        {{ slotProps.data.final_score || '-' }}
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
                            <Button v-if="can('edit performance reviews')" icon="pi pi-pencil" variant="outlined" rounded @click="editReview(slotProps.data)" />
                            <Button v-if="can('delete performance reviews')" icon="pi pi-trash" variant="outlined" rounded severity="danger" @click="confirmDeleteReview(slotProps.data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Add/Edit Review Dialog -->
        <Dialog
            v-model:visible="reviewDialog"
            :style="{ width: '720px' }"
            :header="isEditing ? 'Sửa đánh giá cuối năm' : 'Thêm đánh giá cuối năm'"
            :modal="true"
        >
            <div class="flex flex-col gap-6">
                <div>
                    <label for="employee" class="block font-bold mb-3">Nhân viên <span class="text-red-500">*</span></label>
                    <Select
                        id="employee"
                        v-model="review.employee_id"
                        :options="employees"
                        optionLabel="display_name"
                        optionValue="id"
                        filter
                        :invalid="(submitted && !review.employee_id) || hasError('employee_id')"
                        fluid
                        placeholder="Chọn nhân viên"
                        @change="onEmployeeChange"
                    />
                    <small v-if="submitted && !review.employee_id" class="text-red-500">Nhân viên là bắt buộc</small>
                    <small v-if="hasError('employee_id')" class="p-error block mt-1">{{ getError('employee_id') }}</small>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="year" class="block font-bold mb-3">Năm <span class="text-red-500">*</span></label>
                        <InputNumber
                            id="year"
                            v-model="review.year"
                            :min="2000"
                            :max="2100"
                            :useGrouping="false"
                            :invalid="(submitted && !review.year) || hasError('year')"
                            fluid
                            placeholder="Năm"
                            @input="onYearChange"
                        />
                        <small v-if="submitted && !review.year" class="text-red-500">Năm là bắt buộc</small>
                        <small v-if="hasError('year')" class="p-error block mt-1">{{ getError('year') }}</small>
                    </div>

                    <div>
                        <label for="kpi_avg_score" class="block font-bold mb-3">
                            Điểm KPI trung bình <span class="text-red-500">*</span>
                            <Button
                                v-if="review.employee_id && review.year"
                                icon="pi pi-calculator"
                                size="small"
                                text
                                rounded
                                @click="calculateKpiAverage"
                                :loading="calculating"
                                v-tooltip.top="'Tính điểm KPI TB từ các tháng'"
                            />
                        </label>
                        <InputNumber
                            id="kpi_avg_score"
                            v-model="review.kpi_avg_score"
                            :min="0"
                            :max="100"
                            :minFractionDigits="2"
                            :maxFractionDigits="2"
                            :invalid="(submitted && review.kpi_avg_score === null) || hasError('kpi_avg_score')"
                            fluid
                            placeholder="Điểm KPI TB"
                        />
                        <small v-if="submitted && review.kpi_avg_score === null" class="text-red-500">Điểm KPI TB là bắt buộc</small>
                        <small v-if="hasError('kpi_avg_score')" class="p-error block mt-1">{{ getError('kpi_avg_score') }}</small>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="final_rating" class="block font-bold mb-3">Xếp loại <span class="text-red-500">*</span></label>
                        <Select
                            id="final_rating"
                            v-model="review.final_rating"
                            :options="enums.ratings"
                            optionLabel="label"
                            optionValue="value"
                            :invalid="(submitted && !review.final_rating) || hasError('final_rating')"
                            fluid
                            placeholder="Chọn xếp loại"
                        />
                        <small v-if="submitted && !review.final_rating" class="text-red-500">Xếp loại là bắt buộc</small>
                        <small v-if="hasError('final_rating')" class="p-error block mt-1">{{ getError('final_rating') }}</small>
                    </div>

                    <div>
                        <label for="final_score" class="block font-bold mb-3">Điểm tổng</label>
                        <InputNumber
                            id="final_score"
                            v-model="review.final_score"
                            :min="0"
                            :minFractionDigits="2"
                            :maxFractionDigits="2"
                            :invalid="hasError('final_score')"
                            fluid
                            placeholder="Điểm tổng (tùy chọn)"
                        />
                        <small v-if="hasError('final_score')" class="p-error block mt-1">{{ getError('final_score') }}</small>
                    </div>
                </div>

                <div>
                    <label for="note" class="block font-bold mb-3">Nhận xét đánh giá</label>
                    <Textarea
                        id="note"
                        v-model="review.note"
                        rows="4"
                        fluid
                        placeholder="Nhập nhận xét đánh giá"
                    />
                </div>
            </div>

            <template #footer>
                <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Lưu" icon="pi pi-check" @click="saveReview" :loading="saving" />
            </template>
        </Dialog>

        <!-- Delete Review Dialog -->
        <Dialog v-model:visible="deleteReviewDialog" :style="{ width: '450px' }" header="Xác nhận xóa đánh giá" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="review">Bạn có chắc chắn muốn xóa đánh giá này?</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteReviewDialog = false" severity="secondary" variant="text" />
                <Button label="Có" icon="pi pi-check" @click="deleteReview" severity="danger" :loading="deleting" />
            </template>
        </Dialog>

        <!-- Delete Multiple Reviews Dialog -->
        <Dialog v-model:visible="deleteReviewsDialog" :style="{ width: '450px' }" header="Xác nhận xóa nhiều đánh giá" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span>Bạn có chắc chắn muốn xóa các đánh giá đã chọn?</span>
            </div>
            <template #footer>
                <Button label="Không" icon="pi pi-times" text @click="deleteReviewsDialog = false" severity="secondary" variant="text" />
                <Button label="Có" icon="pi pi-check" text @click="deleteSelectedReviews" severity="danger" :loading="deleting" />
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

import { EmployeeAnnualReviewService } from '@/services'
import { useFormValidation } from '@/composables/useFormValidation'
import { usePermissions } from '@/Composables/usePermissions'

// Props từ Controller
const props = defineProps({
    reviews: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    years: { type: Array, default: () => [] },
    enums: { type: Object, default: () => ({ ratings: [] }) },
})

// Composables
const { errors, hasError, getError } = useFormValidation()
const { can, canAny } = usePermissions()

// Reactive
const dt = ref()
const reviewsList = ref([...props.reviews])
const selectedReviews = ref([])
const reviewDialog = ref(false)
const deleteReviewDialog = ref(false)
const deleteReviewsDialog = ref(false)
const submitted = ref(false)
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const calculating = ref(false)

// Filters
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
})

const yearFilter = ref('')
const ratingFilter = ref('')
const employeeFilter = ref('')

// State form
const review = ref({
    id: null,
    employee_id: null,
    year: new Date().getFullYear(),
    kpi_avg_score: null,
    final_rating: null,
    final_score: null,
    note: '',
})

// Computed
const isEditing = computed(() => !!review.value.id)

// Watch props
watch(
    () => props.reviews,
    (val) => {
        if (Array.isArray(val)) {
            reviewsList.value = [...val]
        }
    },
    { immediate: true, deep: true }
)

// Functions
function getRatingSeverity(rating) {
    const severityMap = {
        'A': 'success',
        'B': 'info',
        'C': 'warn',
        'D': 'warning',
        'E': 'danger'
    }
    return severityMap[rating] || 'secondary'
}

function formatDateTime(dateString) {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleString('vi-VN')
}

function openNew() {
    submitted.value = false
    review.value = {
        id: null,
        employee_id: null,
        year: new Date().getFullYear(),
        kpi_avg_score: null,
        final_rating: null,
        final_score: null,
        note: '',
    }
    reviewDialog.value = true
}

function editReview(row) {
    submitted.value = false
    review.value = {
        id: row.id,
        employee_id: row.employee_id,
        year: row.year,
        kpi_avg_score: row.kpi_avg_score,
        final_rating: row.final_rating,
        final_score: row.final_score,
        note: row.note || '',
    }
    reviewDialog.value = true
}

function hideDialog() {
    reviewDialog.value = false
}

async function calculateKpiAverage() {
    if (!review.value.employee_id || !review.value.year) {
        return
    }

    calculating.value = true
    try {
        const data = await EmployeeAnnualReviewService.calculateKpiAverage(
            review.value.employee_id,
            review.value.year
        )
        review.value.kpi_avg_score = data.kpi_avg_score

        if (data.months_count === 0) {
            alert('Không có dữ liệu KPI tháng cho nhân viên này trong năm ' + review.value.year)
        }
    } catch (error) {
        console.error('Error calculating KPI average:', error)
    } finally {
        calculating.value = false
    }
}

function onEmployeeChange() {
    // Có thể tự động tính KPI TB khi chọn nhân viên
    if (review.value.employee_id && review.value.year) {
        // calculateKpiAverage()
    }
}

function onYearChange() {
    // Có thể tự động tính KPI TB khi thay đổi năm
    if (review.value.employee_id && review.value.year) {
        // calculateKpiAverage()
    }
}

function saveReview() {
    submitted.value = true
    if (!review.value.employee_id || !review.value.year ||
        review.value.kpi_avg_score === null || !review.value.final_rating) return

    saving.value = true
    const payload = { ...review.value }

    const onSuccess = () => {
        saving.value = false
        reviewDialog.value = false
        EmployeeAnnualReviewService.index({}, {})
    }
    const onError = () => (saving.value = false)

    if (!isEditing.value) {
        EmployeeAnnualReviewService.store(payload, { onSuccess, onError })
    } else {
        EmployeeAnnualReviewService.update(review.value.id, payload, { onSuccess, onError })
    }
}

function confirmDeleteReview(row) {
    review.value = { ...row }
    deleteReviewDialog.value = true
}

function deleteReview() {
    deleting.value = true
    EmployeeAnnualReviewService.destroy(review.value.id, {
        onSuccess: () => {
            deleting.value = false
            deleteReviewDialog.value = false
            EmployeeAnnualReviewService.index({}, {})
        },
        onError: () => {
            deleting.value = false
        }
    })
}

function confirmDeleteSelected() {
    deleteReviewsDialog.value = true
}

function deleteSelectedReviews() {
    const ids = selectedReviews.value.map((x) => x.id)
    if (!ids.length) return
    deleting.value = true
    EmployeeAnnualReviewService.bulkDelete(ids, {
        onSuccess: () => {
            deleting.value = false
            deleteReviewsDialog.value = false
            selectedReviews.value = []
            EmployeeAnnualReviewService.index({}, {})
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
    if (ratingFilter.value) filterData.rating = ratingFilter.value
    if (employeeFilter.value) filterData.employee_id = employeeFilter.value
    if (filters.value.global?.value) filterData.search = filters.value.global.value

    EmployeeAnnualReviewService.index(filterData, {})
}
</script>
