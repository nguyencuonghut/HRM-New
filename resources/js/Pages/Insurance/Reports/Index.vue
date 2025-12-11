<template>
    <Head>
        <title>Báo cáo Bảo hiểm</title>
    </Head>

    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Báo cáo Bảo hiểm XH</h2>
            <Button
                label="Tạo báo cáo mới"
                icon="pi pi-plus"
                @click="InsuranceReportService.create()"
            />
        </div>

        <!-- Filters -->
        <Card class="mb-6">
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-bold mb-2">Năm</label>
                        <Select
                            v-model="filters.year"
                            :options="yearOptions"
                            placeholder="Chọn năm"
                            @change="applyFilters"
                            fluid
                            showClear
                        />
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Trạng thái</label>
                        <Select
                            v-model="filters.status"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Tất cả"
                            @change="applyFilters"
                            fluid
                            showClear
                        />
                    </div>
                    <div class="flex items-end">
                        <Button
                            label="Xóa bộ lọc"
                            icon="pi pi-filter-slash"
                            severity="secondary"
                            @click="clearFilters"
                            outlined
                        />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Reports Table -->
        <Card>
            <template #content>
                <DataTable
                    :value="reports.data"
                    stripedRows
                    :loading="loading"
                    class="p-datatable-sm"
                >
                    <Column field="title" header="Báo cáo" style="min-width: 200px">
                        <template #body="{ data }">
                            <div class="font-semibold">{{ data.title }}</div>
                        </template>
                    </Column>

                    <Column header="Tăng" style="width: 120px">
                        <template #body="{ data }">
                            <div class="text-center">
                                <div class="text-green-600 font-bold">{{ data.approved_increase }}</div>
                                <div class="text-xs text-gray-500">/ {{ data.total_increase }}</div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Giảm" style="width: 120px">
                        <template #body="{ data }">
                            <div class="text-center">
                                <div class="text-red-600 font-bold">{{ data.approved_decrease }}</div>
                                <div class="text-xs text-gray-500">/ {{ data.total_decrease }}</div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Điều chỉnh" style="width: 120px">
                        <template #body="{ data }">
                            <div class="text-center">
                                <div class="text-blue-600 font-bold">{{ data.approved_adjust }}</div>
                                <div class="text-xs text-gray-500">/ {{ data.total_adjust }}</div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Trạng thái" style="width: 150px">
                        <template #body="{ data }">
                            <Tag
                                :value="data.status === 'DRAFT' ? 'Nháp' : 'Đã hoàn tất'"
                                :severity="data.status === 'DRAFT' ? 'warn' : 'success'"
                            />
                        </template>
                    </Column>

                    <Column header="Tiến độ" style="width: 150px">
                        <template #body="{ data }">
                            <div v-if="!data.is_finalized">
                                <ProgressBar
                                    :value="calculateProgress(data)"
                                    :showValue="false"
                                    style="height: 6px"
                                />
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ getApprovedCount(data) }}/{{ getTotalCount(data) }} đã duyệt
                                </div>
                            </div>
                            <div v-else class="text-green-600">
                                <i class="pi pi-check-circle"></i> Hoàn tất
                            </div>
                        </template>
                    </Column>

                    <Column header="Thao tác" style="width: 200px">
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Button
                                    icon="pi pi-eye"
                                    severity="info"
                                    text
                                    rounded
                                    @click="viewReport(data.id)"
                                    v-tooltip.top="'Xem chi tiết'"
                                />
                                <Button
                                    v-if="data.is_finalized"
                                    icon="pi pi-download"
                                    severity="success"
                                    text
                                    rounded
                                    @click="exportReport(data.id)"
                                    v-tooltip.top="'Xuất Excel'"
                                />
                                <Button
                                    v-if="!data.is_finalized"
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    @click="confirmDelete(data)"
                                    v-tooltip.top="'Xóa'"
                                />
                            </div>
                        </template>
                    </Column>

                    <template #empty>
                        <div class="text-center py-8 text-gray-500">
                            <i class="pi pi-inbox text-4xl mb-3"></i>
                            <p>Chưa có báo cáo nào</p>
                        </div>
                    </template>
                </DataTable>

                <!-- Pagination -->
                <Paginator
                    v-if="reports.data.length > 0"
                    :first="(reports.current_page - 1) * reports.per_page"
                    :rows="reports.per_page"
                    :totalRecords="reports.total"
                    @page="onPageChange"
                    class="mt-4"
                />
            </template>
        </Card>

        <!-- Delete Confirmation Dialog -->
        <Dialog
            v-model:visible="deleteDialog"
            header="Xác nhận xóa"
            :modal="true"
            :closable="false"
        >
            <div class="flex items-center gap-3">
                <i class="pi pi-exclamation-triangle text-3xl text-orange-500"></i>
                <span>Bạn có chắc chắn muốn xóa báo cáo <strong>{{ reportToDelete?.title }}</strong>?</span>
            </div>
            <template #footer>
                <Button label="Hủy" severity="secondary" @click="deleteDialog = false" />
                <Button label="Xóa" severity="danger" @click="deleteReport" :loading="deleting" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { InsuranceReportService } from '@/services/InsuranceReportService';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Paginator from 'primevue/paginator';
import Dialog from 'primevue/dialog';

const props = defineProps({
    reports: Object,
    filters: Object,
});

const loading = ref(false);
const deleteDialog = ref(false);
const reportToDelete = ref(null);
const deleting = ref(false);

// Filter options
const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 10 }, (_, i) => currentYear - i);

const statusOptions = [
    { label: 'Nháp', value: 'DRAFT' },
    { label: 'Đã hoàn tất', value: 'FINALIZED' },
];

const filters = ref({
    year: props.filters?.year || null,
    status: props.filters?.status || null,
});

// Methods
const applyFilters = () => {
    InsuranceReportService.index(filters.value, {
        onStart: () => loading.value = true,
        onFinish: () => loading.value = false,
    });
};

const clearFilters = () => {
    filters.value = { year: null, status: null };
    applyFilters();
};

const calculateProgress = (report) => {
    const total = getTotalCount(report);
    if (total === 0) return 100;
    const approved = getApprovedCount(report);
    return Math.round((approved / total) * 100);
};

const getTotalCount = (report) => {
    return report.total_increase + report.total_decrease + report.total_adjust;
};

const getApprovedCount = (report) => {
    return report.approved_increase + report.approved_decrease + report.approved_adjust;
};

const viewReport = (id) => {
    InsuranceReportService.show(id);
};

const exportReport = (id) => {
    InsuranceReportService.export(id);
};

const confirmDelete = (report) => {
    reportToDelete.value = report;
    deleteDialog.value = true;
};

const deleteReport = () => {
    deleting.value = true;
    InsuranceReportService.destroy(reportToDelete.value.id, {
        onFinish: () => {
            deleting.value = false;
            deleteDialog.value = false;
            reportToDelete.value = null;
        },
    });
};

const onPageChange = (event) => {
    InsuranceReportService.index({
        ...filters.value,
        page: event.page + 1,
    });
};
</script>
