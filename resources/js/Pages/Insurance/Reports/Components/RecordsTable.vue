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
        <Column header="Tháng KK gợi ý" style="min-width: 120px">
            <template #body="{ data }">
                <Tag
                    :value="data.suggested_declaration_month || '-'"
                    severity="info"
                    class="font-mono"
                />
            </template>
        </Column>
        <Column header="Tháng KK chính thức" style="min-width: 150px">
            <template #body="{ data }">
                <div class="flex items-center gap-2">
                    <Select
                        v-if="!isFinalized && data.approval_status === 'PENDING'"
                        v-model="data.declaration_month"
                        :options="getAvailableMonths(data)"
                        placeholder="Chọn tháng"
                        class="w-28"
                        :class="{ 'border-yellow-500': data.declaration_month !== data.suggested_declaration_month }"
                        @change="onDeclarationMonthChange(data)"
                    />
                    <span v-else class="font-mono">{{ data.declaration_month || '-' }}</span>
                    <i
                        v-if="data.declaration_month !== data.suggested_declaration_month"
                        class="pi pi-exclamation-triangle text-yellow-500"
                        v-tooltip.top="'Đã thay đổi từ tháng gợi ý'"
                    />
                </div>
            </template>
        </Column>
        <Column header="Lý do thay đổi tháng KK" style="min-width: 200px">
            <template #body="{ data }">
                <InputText
                    v-if="!isFinalized && data.approval_status === 'PENDING' && data.declaration_month !== data.suggested_declaration_month"
                    v-model="data.declaration_override_reason"
                    placeholder="Nhập lý do (bắt buộc)"
                    class="w-full text-sm"
                    :class="{ 'border-red-500': !data.declaration_override_reason && data.declaration_month !== data.suggested_declaration_month }"
                    @blur="validateOverrideReason(data)"
                />
                <span v-else-if="data.declaration_override_reason" class="text-sm text-gray-600">
                    {{ data.declaration_override_reason }}
                </span>
                <span v-else class="text-gray-400">-</span>
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
import { ref } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const toast = useToast();

defineProps({
    records: Array,
    canApprove: Boolean,
    isFinalized: Boolean,
    changeType: String, // 'INCREASE', 'DECREASE', 'ADJUST'
});

defineEmits(['approve', 'declarationMonthUpdated']);

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

// Declaration month helpers
const getAvailableMonths = (record) => {
    // Generate 12 months from current year
    const currentDate = new Date();
    const year = currentDate.getFullYear();
    const months = [];

    for (let month = 1; month <= 12; month++) {
        const value = `${year}-${String(month).padStart(2, '0')}`;
        months.push(value);
    }

    return months;
};

const onDeclarationMonthChange = async (record) => {
    if (record.declaration_month === record.suggested_declaration_month) {
        // Cleared override - remove reason
        record.declaration_override_reason = null;
        await saveDeclarationMonth(record);
    } else {
        // Require reason
        if (!record.declaration_override_reason) {
            toast.add({
                severity: 'warn',
                summary: 'Yêu cầu lý do',
                detail: 'Vui lòng nhập lý do thay đổi tháng kê khai',
                life: 3000
            });
        }
    }
};

const validateOverrideReason = async (record) => {
    if (record.declaration_month !== record.suggested_declaration_month && record.declaration_override_reason) {
        await saveDeclarationMonth(record);
    }
};

const saveDeclarationMonth = async (record) => {
    // Validate
    if (record.declaration_month !== record.suggested_declaration_month && !record.declaration_override_reason) {
        toast.add({
            severity: 'error',
            summary: 'Lỗi',
            detail: 'Phải nhập lý do khi thay đổi tháng kê khai',
            life: 3000
        });
        return;
    }

    try {
        await axios.post(`/insurance-records/${record.id}/update-declaration-month`, {
            declaration_month: record.declaration_month,
            declaration_override_reason: record.declaration_override_reason
        });

        toast.add({
            severity: 'success',
            summary: 'Đã cập nhật',
            detail: 'Tháng kê khai đã được cập nhật',
            life: 2000
        });
    } catch (error) {
        console.error('Save declaration month error:', error);
        toast.add({
            severity: 'error',
            summary: 'Lỗi',
            detail: error.response?.data?.message || 'Không thể cập nhật tháng kê khai',
            life: 5000
        });
    }
};
</script>
