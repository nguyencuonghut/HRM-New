<template>
    <DataTable :value="records" :rows="10" :paginator="records.length > 10">
        <Column header="Mã NV" style="min-width: 100px">
            <template #body="{ data }">
                {{ data.employee?.employee_code || '-' }}
            </template>
        </Column>
        <Column header="Họ và tên" style="min-width: 180px">
            <template #body="{ data }">
                {{ data.employee?.full_name || '-' }}
            </template>
        </Column>
        <Column header="Mã BHXH" style="min-width: 120px">
            <template #body="{ data }">
                {{ data.employee?.si_number || '-' }}
            </template>
        </Column>
        <Column header="Lương BHXH" style="min-width: 130px">
            <template #body="{ data }">
                {{ formatCurrency(data.insurance_salary) }}
            </template>
        </Column>
        <Column v-if="changeType === 'ADJUST'" header="Lương mới" style="min-width: 130px">
            <template #body="{ data }">
                {{ formatCurrency(data.final_salary) }}
            </template>
        </Column>
        <Column header="Lý do" style="min-width: 150px">
            <template #body="{ data }">
                {{ data.auto_reason_label || data.system_notes || '-' }}
            </template>
        </Column>
        <Column header="Trạng thái" style="min-width: 120px">
            <template #body="{ data }">
                <Tag
                    :value="getStatusLabel(data.approval_status)"
                    :severity="getStatusSeverity(data.approval_status)"
                />
            </template>
        </Column>
        <Column v-if="!isFinalized && canApprove" header="Thao tác" style="min-width: 200px">
            <template #body="{ data }">
                <div v-if="data.approval_status === 'PENDING'" class="flex gap-2">
                    <Button
                        label="Duyệt"
                        icon="pi pi-check"
                        size="small"
                        @click="$emit('approve', { ...data, action: 'approve' })"
                    />
                    <Button
                        label="Từ chối"
                        icon="pi pi-times"
                        severity="danger"
                        size="small"
                        outlined
                        @click="$emit('approve', { ...data, action: 'reject' })"
                    />
                    <Button
                        v-if="changeType === 'ADJUST'"
                        label="Điều chỉnh"
                        icon="pi pi-pencil"
                        severity="secondary"
                        size="small"
                        outlined
                        @click="$emit('approve', { ...data, action: 'adjust' })"
                    />
                </div>
                <div v-else class="text-sm text-gray-600">
                    Đã {{ data.approval_status === 'APPROVED' ? 'duyệt' : 'từ chối' }}
                    <div v-if="data.reject_reason" class="text-red-600 mt-1">
                        {{ data.reject_reason }}
                    </div>
                </div>
            </template>
        </Column>
        <Column v-if="isFinalized" header="Người duyệt" style="min-width: 150px">
            <template #body="{ data }">
                {{ data.approved_by?.name || '-' }}
            </template>
        </Column>
        <Column v-if="isFinalized" header="Thời gian duyệt" style="min-width: 150px">
            <template #body="{ data }">
                <div v-if="data.approval_status !== 'PENDING'" class="text-sm">
                    {{ formatDate(data.approved_at) }}
                    <div v-if="data.reject_reason" class="text-red-600 mt-1">
                        {{ data.reject_reason }}
                    </div>
                </div>
            </template>
        </Column>

        <template #empty>
            <div class="text-center py-8 text-gray-500">
                Không có dữ liệu
            </div>
        </template>
    </DataTable>
</template>

<script setup>
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

defineProps({
    records: Array,
    canApprove: Boolean,
    isFinalized: Boolean,
    changeType: String, // 'INCREASE', 'DECREASE', 'ADJUST'
});

defineEmits(['approve']);

// Helper methods
const formatCurrency = (value) => {
    if (!value) return '-';
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const getStatusLabel = (status) => {
    const labels = {
        PENDING: 'Chờ duyệt',
        APPROVED: 'Đã duyệt',
        REJECTED: 'Đã từ chối',
        ADJUSTED: 'Đã điều chỉnh',
    };
    return labels[status] || status;
};

const getStatusSeverity = (status) => {
    const severities = {
        PENDING: 'warn',
        APPROVED: 'success',
        REJECTED: 'danger',
        ADJUSTED: 'info',
    };
    return severities[status] || 'secondary';
};
</script>
