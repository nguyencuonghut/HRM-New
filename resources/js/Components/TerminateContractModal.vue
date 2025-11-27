<template>
    <Dialog v-model:visible="visible" modal :header="dialogTitle" :style="{ width: '600px' }" @hide="onHide">
        <div class="space-y-4">
            <!-- Thông tin hợp đồng -->
            <div class="bg-surface-50 dark:bg-surface-800 p-4 rounded-lg">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="font-semibold">Số HĐ:</span>
                        <span class="ml-2">{{ contract?.contract_number }}</span>
                    </div>
                    <div>
                        <span class="font-semibold">Nhân viên:</span>
                        <span class="ml-2">{{ contract?.employee?.full_name }}</span>
                    </div>
                    <div>
                        <span class="font-semibold">Loại HĐ:</span>
                        <span class="ml-2">{{ getContractTypeLabel(contract?.contract_type) }}</span>
                    </div>
                    <div>
                        <span class="font-semibold">Ngày bắt đầu:</span>
                        <span class="ml-2">{{ formatDate(contract?.start_date) }}</span>
                    </div>
                </div>
            </div>

            <!-- Form chấm dứt -->
            <form @submit.prevent="submitTermination">
                <!-- Lý do chấm dứt -->
                <div class="mb-4">
                    <label for="termination_reason" class="block font-semibold mb-2">
                        Lý do chấm dứt <span class="text-red-500">*</span>
                    </label>
                    <Select
                        v-model="form.termination_reason"
                        :options="terminationReasons"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Chọn lý do chấm dứt"
                        class="w-full"
                        :class="{ 'p-invalid': errors.termination_reason }"
                    />
                    <small v-if="errors.termination_reason" class="text-red-500">
                        {{ errors.termination_reason }}
                    </small>
                </div>

                <!-- Ngày chấm dứt -->
                <div class="mb-4">
                    <label for="terminated_at" class="block font-semibold mb-2">
                        Ngày chấm dứt <span class="text-red-500">*</span>
                    </label>
                    <DatePicker
                        v-model="form.terminated_at"
                        dateFormat="dd/mm/yy"
                        placeholder="Chọn ngày chấm dứt"
                        fluid
                        :class="{ 'p-invalid': errors.terminated_at }"
                        showIcon
                    />
                    <small v-if="errors.terminated_at" class="text-red-500">
                        {{ errors.terminated_at }}
                    </small>
                </div>

                <!-- Ghi chú -->
                <div class="mb-4">
                    <label for="termination_note" class="block font-semibold mb-2">
                        Ghi chú
                    </label>
                    <Textarea
                        v-model="form.termination_note"
                        rows="4"
                        placeholder="Nhập ghi chú về việc chấm dứt hợp đồng..."
                        class="w-full"
                        :class="{ 'p-invalid': errors.termination_note }"
                    />
                    <small v-if="errors.termination_note" class="text-red-500">
                        {{ errors.termination_note }}
                    </small>
                </div>

                <!-- Trợ cấp thôi việc (nếu có) -->
                <div v-if="severancePayInfo && severancePayInfo.eligible" class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-start gap-2">
                        <i class="pi pi-info-circle text-blue-600 mt-1"></i>
                        <div class="flex-1">
                            <p class="font-semibold text-blue-800 dark:text-blue-200 mb-2">Trợ cấp thôi việc</p>
                            <div class="text-sm space-y-1">
                                <p>Số tiền: <span class="font-semibold">{{ formatCurrency(severancePayInfo.amount) }}</span></p>
                                <p class="text-xs text-surface-600 dark:text-surface-400">
                                    {{ severancePayInfo.formula }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warning -->
                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg mb-4">
                    <div class="flex items-start gap-2">
                        <i class="pi pi-exclamation-triangle text-yellow-600 mt-1"></i>
                        <div class="flex-1 text-sm text-yellow-800 dark:text-yellow-200">
                            <p class="font-semibold mb-1">Cảnh báo</p>
                            <p>Hành động này sẽ chấm dứt hợp đồng và không thể hoàn tác. Vui lòng kiểm tra kỹ trước khi xác nhận.</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <template #footer>
            <Button label="Hủy" severity="secondary" @click="onHide" :disabled="loading" />
            <Button
                label="Xác nhận chấm dứt"
                severity="danger"
                @click="submitTermination"
                :loading="loading"
            />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    modelValue: Boolean,
    contract: Object,
});

const emit = defineEmits(['update:modelValue', 'terminated']);

const toast = useToast();
const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const dialogTitle = computed(() => `Chấm dứt hợp đồng: ${props.contract?.contract_number || ''}`);

const form = ref({
    termination_reason: null,
    terminated_at: new Date(),
    termination_note: '',
});

const errors = ref({});
const loading = ref(false);
const terminationReasons = ref([]);
const severancePayInfo = ref(null);

// Load termination reasons
const loadTerminationReasons = async () => {
    try {
        const response = await axios.get('/contracts/termination-reasons');
        terminationReasons.value = response.data.data;
    } catch (error) {
        console.error('Failed to load termination reasons:', error);
    }
};

// Calculate severance pay when reason changes
watch(() => form.value.termination_reason, async (newReason) => {
    if (newReason && props.contract) {
        try {
            const response = await axios.get(`/contracts/${props.contract.id}/calculate-severance-pay`, {
                params: { reason: newReason }
            });
            severancePayInfo.value = response.data.data;
        } catch (error) {
            console.error('Failed to calculate severance pay:', error);
            severancePayInfo.value = null;
        }
    } else {
        severancePayInfo.value = null;
    }
});

// Load reasons when modal opens
watch(() => props.modelValue, (isVisible) => {
    if (isVisible) {
        loadTerminationReasons();
        resetForm();
    }
});

const resetForm = () => {
    form.value = {
        termination_reason: null,
        terminated_at: new Date(),
        termination_note: '',
    };
    errors.value = {};
    severancePayInfo.value = null;
};

const submitTermination = async () => {
    if (!props.contract) return;

    errors.value = {};
    loading.value = true;

    try {
        const response = await axios.post(`/contracts/${props.contract.id}/terminate`, {
            termination_reason: form.value.termination_reason,
            terminated_at: formatDateForSubmit(form.value.terminated_at),
            termination_note: form.value.termination_note,
        });

        toast.add({
            severity: 'success',
            summary: 'Thành công',
            detail: response.data.message || 'Hợp đồng đã được chấm dứt thành công',
            life: 3000,
        });

        emit('terminated', response.data.data);
        onHide();

        // Reload page data
        router.reload();
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        }

        toast.add({
            severity: 'error',
            summary: 'Lỗi',
            detail: error.response?.data?.message || 'Không thể chấm dứt hợp đồng',
            life: 5000,
        });
    } finally {
        loading.value = false;
    }
};

const onHide = () => {
    visible.value = false;
    resetForm();
};

// Helper functions
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('vi-VN');
};

const formatDateForSubmit = (date) => {
    if (!date) return '';
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const formatCurrency = (amount) => {
    if (!amount) return '0 ₫';
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(amount);
};

const getContractTypeLabel = (type) => {
    const types = {
        'PROBATION': 'Thử việc',
        'FIXED_TERM': 'Xác định thời hạn',
        'INDEFINITE': 'Không xác định thời hạn',
        'SEASONAL': 'Theo mùa vụ',
        'PROJECT_BASED': 'Theo dự án',
    };
    return types[type] || type;
};
</script>
