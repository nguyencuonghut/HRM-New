<template>
    <Head>
        <title>Phê duyệt nghỉ phép</title>
    </Head>

    <div>
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold">Đơn nghỉ phép chờ duyệt</h2>
                <Badge
                    :value="`${pendingCount} đơn`"
                    severity="warning"
                    size="large"
                />
            </div>

            <DataTable
                :value="pendingRequests"
                :paginator="pendingRequests.length > 10"
                :rows="10"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} đơn"
            >
                <template #empty>
                    <div class="text-center py-8">
                        <i class="pi pi-check-circle text-6xl text-green-400 mb-3"></i>
                        <p class="text-lg text-gray-600">Không có đơn nào chờ duyệt</p>
                    </div>
                </template>

                <Column field="employee.employee_code" header="Mã NV" style="min-width: 8rem"></Column>
                <Column field="employee.full_name" header="Nhân viên" style="min-width: 12rem"></Column>
                <Column field="leave_type.name" header="Loại phép" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Badge
                            :value="slotProps.data.leave_type.name"
                            :style="{ backgroundColor: slotProps.data.leave_type.color }"
                        />
                    </template>
                </Column>
                <Column field="start_date" header="Từ ngày" style="min-width: 10rem"></Column>
                <Column field="end_date" header="Đến ngày" style="min-width: 10rem"></Column>
                <Column field="days" header="Số ngày" style="min-width: 8rem">
                    <template #body="slotProps">
                        <span class="font-semibold">{{ slotProps.data.days }}</span>
                    </template>
                </Column>
                <Column field="submitted_at" header="Ngày nộp" style="min-width: 10rem">
                    <template #body="slotProps">
                        {{ formatDateTime(slotProps.data.submitted_at) }}
                    </template>
                </Column>
                <Column header="Bước duyệt" style="min-width: 12rem">
                    <template #body="slotProps">
                        <div v-if="getCurrentStep(slotProps.data)">
                            <Badge
                                :value="`Bước ${getCurrentStep(slotProps.data).step}`"
                                severity="info"
                            />
                            <p class="text-sm text-gray-600 mt-1">
                                {{ getRoleLabel(getCurrentStep(slotProps.data).approver_role) }}
                            </p>
                        </div>
                    </template>
                </Column>
                <Column header="Thao tác" :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <div class="flex gap-2">
                            <Button
                                icon="pi pi-eye"
                                variant="outlined"
                                rounded
                                @click="viewDetail(slotProps.data)"
                                v-tooltip.top="'Xem chi tiết'"
                            />
                            <Button
                                icon="pi pi-check"
                                severity="success"
                                variant="outlined"
                                rounded
                                @click="showApproveDialog(slotProps.data)"
                                v-tooltip.top="'Phê duyệt'"
                            />
                            <Button
                                icon="pi pi-times"
                                severity="danger"
                                variant="outlined"
                                rounded
                                @click="showRejectDialog(slotProps.data)"
                                v-tooltip.top="'Từ chối'"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Approve Dialog -->
        <Dialog v-model:visible="approveDialog" header="Phê duyệt đơn nghỉ phép" :modal="true" :style="{ width: '500px' }">
            <div v-if="selectedRequest" class="space-y-4">
                <div class="p-4 border-l-4 border-green-500 bg-green-50 rounded">
                    <p class="font-semibold">{{ selectedRequest.employee.full_name }}</p>
                    <p class="text-sm text-gray-600">
                        {{ selectedRequest.leave_type.name }} - {{ selectedRequest.days }} ngày
                    </p>
                    <p class="text-sm text-gray-600">
                        {{ formatDate(selectedRequest.start_date) }} → {{ formatDate(selectedRequest.end_date) }}
                    </p>
                </div>
                <div v-if="selectedRequest.reason">
                    <label class="block font-semibold mb-2">Lý do nghỉ</label>
                    <p class="p-3 bg-gray-50 rounded border text-sm">{{ selectedRequest.reason }}</p>
                </div>
                <div>
                    <label class="block font-semibold mb-2">Nhận xét (tùy chọn)</label>
                    <Textarea v-model="approvalComment" rows="3" placeholder="Nhập nhận xét..." fluid />
                </div>
            </div>
            <template #footer>
                <Button label="Hủy" severity="secondary" @click="approveDialog = false" />
                <Button label="Phê duyệt" icon="pi pi-check" @click="approveRequest" :loading="approving" />
            </template>
        </Dialog>

        <!-- Reject Dialog -->
        <Dialog v-model:visible="rejectDialog" header="Từ chối đơn nghỉ phép" :modal="true" :style="{ width: '500px' }">
            <div v-if="selectedRequest" class="space-y-4">
                <div class="p-4 border-l-4 border-red-500 bg-red-50 rounded">
                    <p class="font-semibold">{{ selectedRequest.employee.full_name }}</p>
                    <p class="text-sm text-gray-600">
                        {{ selectedRequest.leave_type.name }} - {{ selectedRequest.days }} ngày
                    </p>
                    <p class="text-sm text-gray-600">
                        {{ formatDate(selectedRequest.start_date) }} → {{ formatDate(selectedRequest.end_date) }}
                    </p>
                </div>
                <div v-if="selectedRequest.reason">
                    <label class="block font-semibold mb-2">Lý do nghỉ</label>
                    <p class="p-3 bg-gray-50 rounded border text-sm">{{ selectedRequest.reason }}</p>
                </div>
                <div>
                    <label class="block font-semibold mb-2 required-field">Lý do từ chối</label>
                    <Textarea
                        v-model="rejectComment"
                        rows="3"
                        placeholder="Nhập lý do từ chối..."
                        :invalid="rejectSubmitted && !rejectComment"
                        fluid
                    />
                    <small v-if="rejectSubmitted && !rejectComment" class="p-error block mt-1">
                        Vui lòng nhập lý do từ chối
                    </small>
                </div>
            </div>
            <template #footer>
                <Button label="Hủy" severity="secondary" @click="rejectDialog = false" />
                <Button label="Từ chối" icon="pi pi-times" severity="danger" @click="rejectRequest" :loading="rejecting" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import { LeaveRequestService } from '@/services/LeaveRequestService';
import { LeaveApprovalService } from '@/services/LeaveApprovalService';
import { ToastService } from '@/services/ToastService';
import { getCurrentStep, getRoleLabel, formatDate, formatDateTime } from '@/utils/leaveHelpers';

const props = defineProps({
    pendingRequests: Array,
    pendingCount: Number,
});

const toast = useToast();
ToastService.init(toast);

const approveDialog = ref(false);
const rejectDialog = ref(false);
const selectedRequest = ref(null);
const approvalComment = ref('');
const rejectComment = ref('');
const rejectSubmitted = ref(false);
const approving = ref(false);
const rejecting = ref(false);

const viewDetail = (request) => {
    LeaveApprovalService.viewRequest(request.id);
};

const showApproveDialog = (request) => {
    selectedRequest.value = request;
    approvalComment.value = '';
    approveDialog.value = true;
};

const approveRequest = () => {
    if (!selectedRequest.value) return;

    approving.value = true;
    LeaveRequestService.approve(selectedRequest.value.id, {
        comment: approvalComment.value,
    }, {
        onSuccess: () => {
            approveDialog.value = false;
        },
        onFinish: () => {
            approving.value = false;
        },
    });
};

const showRejectDialog = (request) => {
    selectedRequest.value = request;
    rejectComment.value = '';
    rejectSubmitted.value = false;
    rejectDialog.value = true;
};

const rejectRequest = () => {
    rejectSubmitted.value = true;

    if (!rejectComment.value || !selectedRequest.value) return;

    rejecting.value = true;
    LeaveRequestService.reject(selectedRequest.value.id, {
        comment: rejectComment.value,
    }, {
        onSuccess: () => {
            rejectDialog.value = false;
        },
        onFinish: () => {
            rejecting.value = false;
        },
    });
};
</script>

<style scoped>
.required-field::after {
    content: ' *';
    color: red;
}
</style>
