<template>
    <Head>
        <title>Chi tiết đơn nghỉ phép</title>
    </Head>

    <div>
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-2">
                    <Button icon="pi pi-arrow-left" variant="text" @click="goBack" />
                    <h2 class="text-2xl font-bold">Chi tiết đơn nghỉ phép</h2>
                </div>
                <Badge
                    :value="leaveRequest.status_label"
                    :severity="leaveRequest.status_color"
                    size="large"
                />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Employee & Leave Type -->
                    <div class="card border">
                        <h3 class="text-lg font-semibold mb-4">Thông tin chung</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Nhân viên</label>
                                <p class="font-medium">{{ leaveRequest.employee.full_name }}</p>
                                <p class="text-sm text-gray-500">{{ leaveRequest.employee.employee_code }}</p>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Loại phép</label>
                                <Badge
                                    :value="leaveRequest.leave_type.name"
                                    :style="{ backgroundColor: leaveRequest.leave_type.color }"
                                />
                                <Badge
                                    v-if="leaveRequest.leave_type.is_paid"
                                    value="Có lương"
                                    severity="success"
                                    size="small"
                                    class="ml-2"
                                />
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Từ ngày</label>
                                <p class="font-medium">{{ formatDate(leaveRequest.start_date) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Đến ngày</label>
                                <p class="font-medium">{{ formatDate(leaveRequest.end_date) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Số ngày nghỉ</label>
                                <p class="text-2xl font-bold text-blue-600">{{ leaveRequest.days }}</p>
                            </div>
                            <div v-if="leaveRequest.remaining_days !== undefined">
                                <label class="block text-sm text-gray-600 mb-1">Số ngày phép còn lại</label>
                                <p class="text-xl font-semibold">{{ leaveRequest.remaining_days }}</p>
                            </div>
                        </div>
                        <div v-if="leaveRequest.reason" class="mt-4">
                            <label class="block text-sm text-gray-600 mb-1">Lý do nghỉ</label>
                            <p class="p-3 bg-gray-50 rounded border">{{ leaveRequest.reason }}</p>
                        </div>
                        <div v-if="leaveRequest.note" class="mt-4">
                            <label class="block text-sm text-gray-600 mb-1">Ghi chú</label>
                            <p class="p-3 bg-yellow-50 rounded border border-yellow-200">{{ leaveRequest.note }}</p>
                        </div>
                    </div>

                    <!-- Event-based Leave Details -->
                    <div v-if="hasEventDetails" class="card border border-blue-200 bg-blue-50">
                        <h3 class="text-lg font-semibold mb-4 text-blue-900">Thông tin chi tiết</h3>

                        <!-- PERSONAL_PAID -->
                        <div v-if="leaveRequest.personal_leave_reason" class="space-y-2">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Lý do phép riêng</label>
                                <Badge :value="getPersonalLeaveReasonLabel(leaveRequest.personal_leave_reason)" severity="info" size="large" />
                            </div>
                        </div>

                        <!-- MATERNITY -->
                        <div v-if="leaveRequest.expected_due_date" class="space-y-3">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Ngày dự kiến sinh</label>
                                    <p class="font-medium text-pink-700">{{ formatDate(leaveRequest.expected_due_date) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Số con sinh</label>
                                    <p class="font-medium">{{ leaveRequest.twins_count || 1 }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Hình thức sinh</label>
                                    <Badge
                                        :value="leaveRequest.is_caesarean ? 'Sinh mổ' : 'Sinh thường'"
                                        :severity="leaveRequest.is_caesarean ? 'warning' : 'success'"
                                    />
                                </div>
                                <div v-if="leaveRequest.children_under_36_months">
                                    <label class="block text-sm text-gray-600 mb-1">Con dưới 36 tháng</label>
                                    <p class="font-medium">{{ leaveRequest.children_under_36_months }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- SICK -->
                        <div v-if="leaveRequest.medical_certificate_path" class="space-y-2">
                            <label class="block text-sm text-gray-600 mb-1">Giấy chứng nhận y tế</label>
                            <a
                                :href="`/storage/${leaveRequest.medical_certificate_path}`"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50"
                            >
                                <i class="pi pi-file-pdf text-red-500"></i>
                                <span class="text-sm font-medium">Xem giấy y tế</span>
                                <i class="pi pi-external-link text-xs text-gray-400"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Approval Timeline -->
                    <div v-if="leaveRequest.approvals && leaveRequest.approvals.length > 0" class="card border">
                        <h3 class="text-lg font-semibold mb-4">Quy trình phê duyệt</h3>
                        <Timeline :value="leaveRequest.approvals" align="left" class="customized-timeline">
                            <template #marker="slotProps">
                                <span
                                    class="flex w-8 h-8 items-center justify-center rounded-full z-10 shadow-sm"
                                    :class="getMarkerClass(slotProps.item.status)"
                                >
                                    <i :class="getMarkerIcon(slotProps.item.status)"></i>
                                </span>
                            </template>
                            <template #content="slotProps">
                                <div class="p-4 border rounded-md" :class="getCardClass(slotProps.item.status)">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-semibold text-sm text-gray-600">
                                                Bước {{ slotProps.item.step }} - {{ getRoleLabel(slotProps.item.approver_role) }}
                                            </p>
                                            <p class="font-medium text-lg">{{ slotProps.item.approver?.name || 'Chưa xác định' }}</p>
                                        </div>
                                        <Badge
                                            :value="slotProps.item.status_label"
                                            :severity="getStatusSeverity(slotProps.item.status)"
                                        />
                                    </div>
                                    <div v-if="slotProps.item.comment" class="mt-3 p-2 bg-gray-50 rounded text-sm">
                                        <i class="pi pi-comment mr-1 text-gray-500"></i>
                                        <span class="italic">{{ slotProps.item.comment }}</span>
                                    </div>
                                    <div v-if="slotProps.item.approved_at" class="mt-2 text-sm text-gray-500">
                                        <i class="pi pi-check-circle mr-1"></i>
                                        {{ formatDateTime(slotProps.item.approved_at) }}
                                    </div>
                                    <div v-if="slotProps.item.rejected_at" class="mt-2 text-sm text-gray-500">
                                        <i class="pi pi-times-circle mr-1"></i>
                                        {{ formatDateTime(slotProps.item.rejected_at) }}
                                    </div>
                                </div>
                            </template>
                        </Timeline>
                    </div>
                </div>

                <!-- Actions Sidebar -->
                <div class="space-y-4">
                    <!-- Timestamps -->
                    <div class="card border">
                        <h3 class="text-base font-semibold mb-3">Thời gian</h3>
                        <div class="space-y-2 text-sm">
                            <div>
                                <label class="text-gray-600">Tạo lúc:</label>
                                <p class="font-medium">{{ formatDateTime(leaveRequest.created_at) }}</p>
                            </div>
                            <div v-if="leaveRequest.submitted_at">
                                <label class="text-gray-600">Nộp đơn:</label>
                                <p class="font-medium">{{ formatDateTime(leaveRequest.submitted_at) }}</p>
                            </div>
                            <div v-if="leaveRequest.approved_at">
                                <label class="text-gray-600">Duyệt cuối:</label>
                                <p class="font-medium">{{ formatDateTime(leaveRequest.approved_at) }}</p>
                            </div>
                            <div v-if="leaveRequest.rejected_at">
                                <label class="text-gray-600">Từ chối:</label>
                                <p class="font-medium">{{ formatDateTime(leaveRequest.rejected_at) }}</p>
                            </div>
                            <div v-if="leaveRequest.cancelled_at">
                                <label class="text-gray-600">Hủy bỏ:</label>
                                <p class="font-medium">{{ formatDateTime(leaveRequest.cancelled_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card border">
                        <h3 class="text-base font-semibold mb-3">Thao tác</h3>
                        <div class="flex flex-col gap-2">
                            <Button
                                v-if="leaveRequest.can_edit"
                                label="Chỉnh sửa"
                                icon="pi pi-pencil"
                                severity="secondary"
                                @click="editRequest"
                                fluid
                            />
                            <Button
                                v-if="leaveRequest.status === 'DRAFT'"
                                label="Nộp đơn"
                                icon="pi pi-send"
                                @click="submitRequest"
                                :loading="submitting"
                                fluid
                            />
                            <Button
                                v-if="canApprove"
                                label="Phê duyệt"
                                icon="pi pi-check"
                                severity="success"
                                @click="showApproveDialog"
                                fluid
                            />
                            <Button
                                v-if="canApprove"
                                label="Từ chối"
                                icon="pi pi-times"
                                severity="danger"
                                @click="showRejectDialog"
                                fluid
                            />
                            <Button
                                v-if="leaveRequest.can_cancel"
                                label="Hủy đơn"
                                icon="pi pi-ban"
                                severity="warning"
                                variant="outlined"
                                @click="cancelRequest"
                                fluid
                            />
                            <Button
                                label="Quay lại"
                                icon="pi pi-arrow-left"
                                severity="secondary"
                                variant="outlined"
                                @click="goBack"
                                fluid
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve Dialog -->
        <Dialog v-model:visible="approveDialog" header="Phê duyệt đơn nghỉ phép" :modal="true" :style="{ width: '450px' }">
            <div class="space-y-4">
                <p>Bạn có chắc muốn phê duyệt đơn nghỉ phép này?</p>
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
        <Dialog v-model:visible="rejectDialog" header="Từ chối đơn nghỉ phép" :modal="true" :style="{ width: '450px' }">
            <div class="space-y-4">
                <p>Bạn có chắc muốn từ chối đơn nghỉ phép này?</p>
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
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Timeline from 'primevue/timeline';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { LeaveRequestService } from '@/services/LeaveRequestService';
import { ToastService } from '@/services/ToastService';
import {
    getRoleLabel,
    getMarkerIcon,
    getCardClass,
    getStatusSeverity,
    formatDate,
    formatDateTime
} from '@/utils/leaveHelpers';

const props = defineProps({
    leaveRequest: Object,
    canApprove: Boolean,
});

// Check if leave request has any event-based details
const hasEventDetails = computed(() => {
    return props.leaveRequest.personal_leave_reason ||
           props.leaveRequest.expected_due_date ||
           props.leaveRequest.medical_certificate_path;
});

// Label mapping for personal leave reasons
const getPersonalLeaveReasonLabel = (reason) => {
    const labels = {
        'MARRIAGE': 'Kết hôn (3 ngày)',
        'CHILD_MARRIAGE': 'Con kết hôn (1 ngày)',
        'PARENT_DEATH': 'Cha/mẹ/vợ/chồng mất (3 ngày)',
        'SIBLING_DEATH': 'Anh/chị/em ruột mất (1 ngày)',
        'CHILD_BIRTH': 'Vợ sinh con (5-14 ngày)',
    };
    return labels[reason] || reason;
};

const confirm = useConfirm();
const toast = useToast();
ToastService.init(toast);

const submitting = ref(false);
const approveDialog = ref(false);
const rejectDialog = ref(false);
const approvalComment = ref('');
const rejectComment = ref('');
const rejectSubmitted = ref(false);
const approving = ref(false);
const rejecting = ref(false);

const getMarkerClass = (status) => {
    if (status === 'APPROVED') return 'bg-green-500 text-white';
    if (status === 'REJECTED') return 'bg-red-500 text-white';
    return 'bg-gray-300 text-gray-600';
};

const goBack = () => {
    LeaveRequestService.back();
};

const editRequest = () => {
    LeaveRequestService.edit(props.leaveRequest.id);
};

const submitRequest = () => {
    submitting.value = true;
    LeaveRequestService.submit(props.leaveRequest.id, {
        onFinish: () => {
            submitting.value = false;
        },
    });
};

const showApproveDialog = () => {
    approveDialog.value = true;
    approvalComment.value = '';
};

const approveRequest = () => {
    approving.value = true;
    LeaveRequestService.approve(props.leaveRequest.id, {
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

const showRejectDialog = () => {
    rejectDialog.value = true;
    rejectComment.value = '';
    rejectSubmitted.value = false;
};

const rejectRequest = () => {
    rejectSubmitted.value = true;

    if (!rejectComment.value) return;

    rejecting.value = true;
    LeaveRequestService.reject(props.leaveRequest.id, {
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

const cancelRequest = () => {
    confirm.require({
        message: 'Bạn có chắc muốn hủy đơn nghỉ phép này?',
        header: 'Xác nhận hủy',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Không',
        acceptLabel: 'Hủy đơn',
        accept: () => {
            LeaveRequestService.cancel(props.leaveRequest.id);
        },
    });
};
</script>

<style scoped>
.required-field::after {
    content: ' *';
    color: red;
}

.customized-timeline :deep(.p-timeline-event-connector) {
    background-color: #e5e7eb;
}
</style>
