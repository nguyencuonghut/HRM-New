<template>
    <Dialog
        :visible="visible"
        :header="getDialogTitle()"
        :modal="true"
        :closable="!processing"
        :style="{ width: '500px' }"
        @update:visible="$emit('update:visible', $event)"
    >
        <div class="space-y-4">
            <!-- Employee Info -->
            <div class="bg-gray-50 p-3 rounded-lg">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-gray-600">Mã NV:</span>
                        <strong class="ml-2">{{ record?.employee?.employee_code }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-600">Họ tên:</span>
                        <strong class="ml-2">{{ record?.employee?.full_name }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-600">Mã BHXH:</span>
                        <strong class="ml-2">{{ record?.employee?.si_number || '-' }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-600">Lý do:</span>
                        <strong class="ml-2">{{ record?.auto_reason_label || record?.system_notes }}</strong>
                    </div>
                </div>
            </div>

            <!-- Action: APPROVE -->
            <div v-if="record?.action === 'approve'">
                <Message severity="info" :closable="false">
                    Xác nhận duyệt bản ghi này?
                </Message>
            </div>

            <!-- Action: REJECT -->
            <div v-else-if="record?.action === 'reject'" class="space-y-2">
                <label class="block text-sm font-medium">
                    Lý do từ chối <span class="text-red-500">*</span>
                </label>
                <Textarea
                    v-model="form.reject_reason"
                    rows="3"
                    class="w-full"
                    placeholder="Nhập lý do từ chối..."
                    :class="{ 'border-red-500': errors.reject_reason }"
                />
                <span v-if="errors.reject_reason" class="text-red-500 text-sm">
                    {{ errors.reject_reason }}
                </span>
            </div>

            <!-- Action: ADJUST -->
            <div v-else-if="record?.action === 'adjust'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Lương BHXH hiện tại</label>
                    <InputNumber
                        :model-value="record?.insurance_salary"
                        :disabled="true"
                        :min-fraction-digits="0"
                        :max-fraction-digits="0"
                        locale="vi-VN"
                        class="w-full"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">
                        Lương BHXH mới <span class="text-red-500">*</span>
                    </label>
                    <InputNumber
                        v-model="form.adjusted_salary"
                        :min-fraction-digits="0"
                        :max-fraction-digits="0"
                        locale="vi-VN"
                        class="w-full"
                        :class="{ 'border-red-500': errors.adjusted_salary }"
                    />
                    <span v-if="errors.adjusted_salary" class="text-red-500 text-sm">
                        {{ errors.adjusted_salary }}
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Ghi chú điều chỉnh</label>
                    <Textarea
                        v-model="form.adjustment_note"
                        rows="2"
                        class="w-full"
                        placeholder="Nhập ghi chú (không bắt buộc)..."
                    />
                </div>
            </div>
        </div>

        <template #footer>
            <Button
                label="Hủy"
                severity="secondary"
                @click="closeDialog"
                :disabled="processing"
            />
            <Button
                :label="getConfirmLabel()"
                :severity="record?.action === 'reject' ? 'danger' : 'primary'"
                @click="handleSubmit"
                :loading="processing"
            />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { InsuranceRecordService } from '@/services/InsuranceRecordService';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Message from 'primevue/message';

const props = defineProps({
    visible: Boolean,
    record: Object,
});

const emit = defineEmits(['update:visible', 'approved']);

const form = ref({
    reject_reason: '',
    adjusted_salary: null,
    adjustment_note: '',
});

const errors = ref({});
const processing = ref(false);

// Watch for record changes to reset form
watch(() => props.record, (newRecord) => {
    if (newRecord) {
        form.value.reject_reason = '';
        form.value.adjusted_salary = newRecord.new_insurance_salary || newRecord.old_insurance_salary;
        form.value.adjustment_note = '';
        errors.value = {};
    }
}, { immediate: true });

// Methods
const getDialogTitle = () => {
    if (!props.record?.action) return '';

    const titles = {
        approve: 'Xác nhận duyệt',
        reject: 'Từ chối bản ghi',
        adjust: 'Điều chỉnh lương BHXH',
    };
    return titles[props.record.action] || '';
};

const getConfirmLabel = () => {
    if (!props.record?.action) return 'Xác nhận';

    const labels = {
        approve: 'Duyệt',
        reject: 'Từ chối',
        adjust: 'Điều chỉnh',
    };
    return labels[props.record.action] || 'Xác nhận';
};

const validate = () => {
    errors.value = {};

    if (props.record.action === 'reject') {
        if (!form.value.reject_reason?.trim()) {
            errors.value.reject_reason = 'Vui lòng nhập lý do từ chối';
            return false;
        }
    }

    if (props.record.action === 'adjust') {
        if (!form.value.adjusted_salary || form.value.adjusted_salary <= 0) {
            errors.value.adjusted_salary = 'Vui lòng nhập lương BHXH hợp lệ';
            return false;
        }
    }

    return true;
};

const handleSubmit = () => {
    if (!validate()) return;

    processing.value = true;

    const data = {};
    if (props.record.action === 'reject') {
        data.reject_reason = form.value.reject_reason;
    } else if (props.record.action === 'adjust') {
        data.adjusted_salary = form.value.adjusted_salary;
        data.adjustment_note = form.value.adjustment_note;
    }

    const actions = {
        approve: () => InsuranceRecordService.approve(props.record.id, {
            onSuccess: () => {
                closeDialog();
                emit('approved');
            },
            onError: (err) => {
                errors.value = err;
                processing.value = false;
            },
            onFinish: () => {
                processing.value = false;
            },
        }),
        reject: () => InsuranceRecordService.reject(props.record.id, data, {
            onSuccess: () => {
                closeDialog();
                emit('approved');
            },
            onError: (err) => {
                errors.value = err;
                processing.value = false;
            },
            onFinish: () => {
                processing.value = false;
            },
        }),
        adjust: () => InsuranceRecordService.adjust(props.record.id, data, {
            onSuccess: () => {
                closeDialog();
                emit('approved');
            },
            onError: (err) => {
                errors.value = err;
                processing.value = false;
            },
            onFinish: () => {
                processing.value = false;
            },
        }),
    };

    actions[props.record.action]();
};

const closeDialog = () => {
    emit('update:visible', false);
};
</script>
