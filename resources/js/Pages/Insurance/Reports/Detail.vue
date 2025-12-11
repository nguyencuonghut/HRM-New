<template>
    <Head>
        <title>{{ report.title }}</title>
    </Head>

    <div>
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-3">
                <Button icon="pi pi-arrow-left" variant="text" @click="goBack" />
                <div>
                    <h2 class="text-2xl font-bold">{{ report.title }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <Tag
                            :value="report.status === 'DRAFT' ? 'Nháp' : 'Đã hoàn tất'"
                            :severity="report.status === 'DRAFT' ? 'warn' : 'success'"
                        />
                        <span v-if="!report.is_finalized" class="text-sm text-gray-500">
                            {{ getApprovedCount() }}/{{ getTotalCount() }} đã duyệt
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex gap-2">
                <Button
                    v-if="report.is_finalized"
                    label="Xuất Excel"
                    icon="pi pi-download"
                    severity="success"
                    @click="exportReport"
                />
                <Button
                    v-if="!report.is_finalized && report.all_approved"
                    label="Hoàn tất báo cáo"
                    icon="pi pi-check-circle"
                    @click="confirmFinalize"
                />
                <Button
                    v-if="!report.is_finalized"
                    label="Xóa báo cáo"
                    icon="pi pi-trash"
                    severity="danger"
                    outlined
                    @click="confirmDelete"
                />
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <Card>
                <template #content>
                    <div class="flex items-center gap-3">
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="pi pi-arrow-up text-2xl text-green-600"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-600">
                                {{ report.approved_increase || 0 }}/{{ report.total_increase || 0 }}
                            </div>
                            <div class="text-sm text-gray-600">Tăng lao động</div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #content>
                    <div class="flex items-center gap-3">
                        <div class="bg-red-100 p-3 rounded-lg">
                            <i class="pi pi-arrow-down text-2xl text-red-600"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-red-600">
                                {{ report.approved_decrease || 0 }}/{{ report.total_decrease || 0 }}
                            </div>
                            <div class="text-sm text-gray-600">Giảm lao động</div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #content>
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="pi pi-sync text-2xl text-blue-600"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ report.approved_adjust || 0 }}/{{ report.total_adjust || 0 }}
                            </div>
                            <div class="text-sm text-gray-600">Điều chỉnh</div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Tabs -->
        <Card>
            <template #content>
                <Tabs v-model:value="activeTab">
                    <TabList>
                        <Tab value="0">TĂNG LAO ĐỘNG</Tab>
                        <Tab value="1">GIẢM</Tab>
                        <Tab value="2">ĐIỀU CHỈNH</Tab>
                    </TabList>
                    <TabPanels>
                        <!-- Tab 1: TĂNG -->
                        <TabPanel value="0">
                            <RecordsTable
                                :records="increaseRecords"
                                :can-approve="canApprove"
                                :is-finalized="report.is_finalized"
                                change-type="INCREASE"
                                @approve="openApprovalDialog"
                            />
                        </TabPanel>

                        <!-- Tab 2: GIẢM -->
                        <TabPanel value="1">
                            <RecordsTable
                                :records="decreaseRecords"
                                :can-approve="canApprove"
                                :is-finalized="report.is_finalized"
                                change-type="DECREASE"
                                @approve="openApprovalDialog"
                            />
                        </TabPanel>

                        <!-- Tab 3: ĐIỀU CHỈNH -->
                        <TabPanel value="2">
                            <RecordsTable
                                :records="adjustRecords"
                                :can-approve="canApprove"
                                :is-finalized="report.is_finalized"
                                change-type="ADJUST"
                                @approve="openApprovalDialog"
                            />
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </template>
        </Card>

        <!-- Approval Dialog -->
        <ApprovalDialog
            v-model:visible="approvalDialog"
            :record="selectedRecord"
            @approved="handleApproved"
        />

        <!-- Finalize Confirmation -->
        <Dialog
            v-model:visible="finalizeDialog"
            header="Xác nhận hoàn tất báo cáo"
            :modal="true"
            :closable="false"
        >
            <div class="flex items-start gap-3">
                <i class="pi pi-exclamation-triangle text-3xl text-orange-500"></i>
                <div>
                    <p class="mb-2">Sau khi hoàn tất, báo cáo sẽ bị <strong>khóa</strong> và không thể chỉnh sửa.</p>
                    <p>Bạn có chắc chắn muốn hoàn tất báo cáo này?</p>
                </div>
            </div>
            <template #footer>
                <Button label="Hủy" severity="secondary" @click="finalizeDialog = false" />
                <Button label="Hoàn tất" @click="finalizeReport" :loading="finalizing" />
            </template>
        </Dialog>

        <!-- Delete Confirmation -->
        <Dialog
            v-model:visible="deleteDialog"
            header="Xác nhận xóa"
            :modal="true"
            :closable="false"
        >
            <div class="flex items-center gap-3">
                <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
                <span>Bạn có chắc chắn muốn xóa báo cáo này?</span>
            </div>
            <template #footer>
                <Button label="Hủy" severity="secondary" @click="deleteDialog = false" />
                <Button label="Xóa" severity="danger" @click="deleteReport" :loading="deleting" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { InsuranceReportService } from '@/services/InsuranceReportService';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Dialog from 'primevue/dialog';
import RecordsTable from './Components/RecordsTable.vue';
import ApprovalDialog from './Components/ApprovalDialog.vue';

const props = defineProps({
    report: Object,
    increaseRecords: Array,
    decreaseRecords: Array,
    adjustRecords: Array,
    canApprove: Boolean,
});

// Debug: log props to console
console.log('Detail Page Props:', {
    report: props.report,
    increaseRecords: props.increaseRecords,
    decreaseRecords: props.decreaseRecords,
    adjustRecords: props.adjustRecords
});

const activeTab = ref('0');
const approvalDialog = ref(false);
const selectedRecord = ref(null);
const finalizeDialog = ref(false);
const finalizing = ref(false);
const deleteDialog = ref(false);
const deleting = ref(false);

// Methods
const getTotalCount = () => {
    return props.report.total_increase + props.report.total_decrease + props.report.total_adjust;
};

const getApprovedCount = () => {
    return props.report.approved_increase + props.report.approved_decrease + props.report.approved_adjust;
};

const openApprovalDialog = (record) => {
    selectedRecord.value = record;
    approvalDialog.value = true;
};

const handleApproved = () => {
    // Reload page to get updated data
    router.reload({ only: ['report', 'increaseRecords', 'decreaseRecords', 'adjustRecords'] });
};

const confirmFinalize = () => {
    finalizeDialog.value = true;
};

const finalizeReport = () => {
    finalizing.value = true;
    InsuranceReportService.finalize(props.report.id, {
        onFinish: () => {
            finalizing.value = false;
            finalizeDialog.value = false;
        },
    });
};

const confirmDelete = () => {
    deleteDialog.value = true;
};

const deleteReport = () => {
    deleting.value = true;
    InsuranceReportService.destroy(props.report.id, {
        onFinish: () => {
            deleting.value = false;
            deleteDialog.value = false;
        },
    });
};

const exportReport = () => {
    InsuranceReportService.export(props.report.id);
};

const goBack = () => {
    InsuranceReportService.index();
};
</script>
