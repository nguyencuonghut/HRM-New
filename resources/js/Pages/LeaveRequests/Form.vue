<template>
    <Head>
        <title>{{ mode === 'edit' ? 'Sửa đơn nghỉ phép' : 'Tạo đơn nghỉ phép' }}</title>
    </Head>

    <div>
        <div class="card">
            <div class="flex items-center gap-2 mb-6">
                <Button icon="pi pi-arrow-left" variant="text" @click="goBack" />
                <h2 class="text-2xl font-bold">{{ mode === 'edit' ? 'Sửa đơn nghỉ phép' : 'Tạo đơn nghỉ phép' }}</h2>
            </div>
            {{  }}

            <form @submit.prevent="handleSubmit">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Employee Selection - Admin can select, others see their own -->
                    <div>
                        <label class="block font-bold mb-2" :class="{ 'required-field': isAdmin }">Nhân viên</label>

                        <!-- Admin: Select dropdown -->
                        <Select
                            v-if="isAdmin"
                            v-model="form.employee_id"
                            :options="employees"
                            optionLabel="full_name"
                            optionValue="id"
                            placeholder="Chọn nhân viên"
                            :invalid="submitted && !form.employee_id"
                            fluid
                            showClear
                            filter
                            @change="onEmployeeChange"
                        >
                            <template #option="slotProps">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold">{{ slotProps.option.full_name }}</span>
                                    <span class="text-gray-500 text-sm">({{ slotProps.option.employee_code }})</span>
                                </div>
                            </template>
                        </Select>

                        <!-- Non-Admin: Read-only display -->
                        <InputText
                            v-else
                            :value="employee ? `${employee.full_name} (${employee.employee_code})` : ''"
                            disabled
                            fluid
                        />

                        <small v-if="isAdmin && submitted && !form.employee_id" class="p-error block mt-1">
                            Vui lòng chọn nhân viên
                        </small>
                    </div>

                    <!-- Leave Type -->
                    <div>
                        <label class="block font-bold mb-2 required-field">Loại phép</label>
                        <Select
                            v-model="form.leave_type_id"
                            :options="leaveTypes"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Chọn loại phép"
                            :invalid="submitted && !form.leave_type_id"
                            fluid
                            @change="onLeaveTypeChange"
                        >
                            <template #option="slotProps">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-3 h-3 rounded-full"
                                        :style="{ backgroundColor: slotProps.option.color }"
                                    ></span>
                                    <span>{{ slotProps.option.name }}</span>
                                    <span>{{ slotProps.option.description }}</span>
                                    <Badge
                                        v-if="slotProps.option.is_paid"
                                        value="Có lương"
                                        severity="success"
                                        size="small"
                                    />
                                </div>
                            </template>
                        </Select>
                        <small v-if="submitted && !form.leave_type_id" class="p-error block mt-1">
                            Vui lòng chọn loại phép
                        </small>
                    </div>

                    <!-- Available Days -->
                    <div v-if="selectedLeaveType">
                        <label class="block font-bold mb-2">Số ngày phép còn lại</label>
                        <div class="p-3 border rounded-md bg-gray-50">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-calendar text-blue-500"></i>
                                <span class="text-lg font-semibold">{{ remainingDays }}</span>
                                <span class="text-sm text-gray-600">ngày</span>
                            </div>
                        </div>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label class="block font-bold mb-2 required-field">Từ ngày</label>
                        <DatePicker
                            v-model="form.start_date"
                            showIcon
                            dateFormat="yy-mm-dd"
                            :invalid="submitted && !form.start_date"
                            fluid
                            @date-select="calculateDays"
                        />
                        <small v-if="submitted && !form.start_date" class="p-error block mt-1">
                            Vui lòng chọn ngày bắt đầu
                        </small>
                    </div>

                    <!-- End Date -->
                    <div>
                        <label class="block font-bold mb-2 required-field">Đến ngày</label>
                        <DatePicker
                            v-model="form.end_date"
                            showIcon
                            dateFormat="yy-mm-dd"
                            :invalid="submitted && !form.end_date"
                            :minDate="form.start_date"
                            fluid
                            @date-select="calculateDays"
                        />
                        <small v-if="submitted && !form.end_date" class="p-error block mt-1">
                            Vui lòng chọn ngày kết thúc
                        </small>
                    </div>

                    <!-- Calculated Days -->
                    <div v-if="calculatedDays > 0" class="md:col-span-2">
                        <div class="p-4 border-l-4 border-blue-500 bg-blue-50 rounded">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-info-circle text-blue-600"></i>
                                <span class="font-semibold">Số ngày nghỉ:</span>
                                <span class="text-xl font-bold text-blue-600">{{ calculatedDays }}</span>
                                <span class="text-gray-600">ngày làm việc (không tính thứ 7, CN)</span>
                            </div>
                            <div v-if="remainingDays !== null && calculatedDays > remainingDays" class="mt-2 text-red-600">
                                <i class="pi pi-exclamation-triangle"></i>
                                <span class="font-semibold">Vượt {{ calculatedDays - remainingDays }} ngày phép!</span>
                                <span class="text-sm block mt-1">
                                    Những ngày vượt quá sẽ bị trừ vào công và ảnh hưởng tới lương.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="md:col-span-2">
                        <label class="block font-bold mb-2">Lý do nghỉ</label>
                        <Textarea
                            v-model="form.reason"
                            rows="4"
                            placeholder="Nhập lý do nghỉ phép..."
                            fluid
                        />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 mt-6 pt-4 border-t">
                    <Button
                        label="Lưu nháp"
                        icon="pi pi-save"
                        severity="info"
                        @click="saveDraft"
                        :loading="saving"
                    />
                    <Button
                        label="Nộp đơn"
                        icon="pi pi-send"
                        @click="submitForApproval"
                        :loading="submitting"
                    />
                    <Button
                        label="Hủy"
                        icon="pi pi-times"
                        severity="secondary"
                        variant="outlined"
                        @click="goBack"
                    />
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import Badge from 'primevue/badge';
import { useToast } from 'primevue/usetoast';
import { LeaveRequestService } from '@/services/LeaveRequestService';
import { ToastService } from '@/services/ToastService';
import { calculateWorkingDays } from '@/utils/leaveHelpers';
import { toYMD } from '@/utils/dateHelper';

const props = defineProps({
    leaveRequest: Object,
    leaveTypes: Array,
    employee: Object,
    mode: String,
    isAdmin: Boolean,
    employees: Array, // Only provided for Admin users
});

const toast = useToast();
ToastService.init(toast);
const page = usePage();

// Initialize form with proper date conversion
const initFormData = () => {
    // Access nested data from Resource
    const leaveData = props.leaveRequest?.data || props.leaveRequest;

    const data = {
        employee_id: leaveData?.employee_id || props.employee?.id || null,
        leave_type_id: leaveData?.leave_type_id || null,
        start_date: null,
        end_date: null,
        reason: leaveData?.reason || '',
    };

    // Convert date strings to Date objects for DatePicker
    if (leaveData?.start_date) {
        const dateStr = leaveData.start_date;
        if (typeof dateStr === 'string' && dateStr.includes('-')) {
            const [y, m, d] = dateStr.split('-').map(Number);
            data.start_date = new Date(y, m - 1, d);
        }
    }

    if (leaveData?.end_date) {
        const dateStr = leaveData.end_date;
        if (typeof dateStr === 'string' && dateStr.includes('-')) {
            const [y, m, d] = dateStr.split('-').map(Number);
            data.end_date = new Date(y, m - 1, d);
        }
    }

    return data;
};

const form = ref(initFormData());

const submitted = ref(false);
const saving = ref(false);
const submitting = ref(false);
const calculatedDays = ref(props.leaveRequest?.data?.days || props.leaveRequest?.days || 0);
const remainingDays = ref(null);

const selectedLeaveType = computed(() => {
    return props.leaveTypes.find(t => t.id === form.value.leave_type_id);
});

const onEmployeeChange = () => {
    // Reset leave type and balance when employee changes
    form.value.leave_type_id = null;
    remainingDays.value = null;
};

const onLeaveTypeChange = () => {
    loadRemainingDays();
};

const loadRemainingDays = async () => {
    if (!form.value.leave_type_id || !form.value.employee_id) return;

    try {
        const response = await LeaveRequestService.getBalance({
            employee_id: form.value.employee_id,
            year: new Date().getFullYear(),
        });

        const balance = response.find(b => b.leave_type_id === form.value.leave_type_id);
        remainingDays.value = balance ? balance.remaining_days : 0;
    } catch (error) {
        console.error('Failed to load balance:', error);
        remainingDays.value = 0;
    }
};

const calculateDays = () => {
    calculatedDays.value = calculateWorkingDays(form.value.start_date, form.value.end_date);
};

const saveDraft = () => {
    submitted.value = true;

    if (!validateForm()) return;

    saving.value = true;

    const submitData = {
        ...form.value,
        start_date: toYMD(form.value.start_date),
        end_date: toYMD(form.value.end_date),
        submit: false,
    };

    if (props.mode === 'edit') {
        const leaveId = props.leaveRequest?.data?.id || props.leaveRequest?.id;
        LeaveRequestService.update(leaveId, submitData, {
            onFinish: () => {
                saving.value = false;
            },
        });
    } else {
        LeaveRequestService.store(submitData, {
            onFinish: () => {
                saving.value = false;
            },
        });
    }
};

const submitForApproval = () => {
    submitted.value = true;

    if (!validateForm()) return;

    // Allow submission even if exceeds balance
    // The excess days will be marked as unpaid leave
    if (remainingDays.value !== null && calculatedDays.value > remainingDays.value) {
        const excessDays = calculatedDays.value - remainingDays.value;
        ToastService.warn(`Vượt ${excessDays} ngày phép. Những ngày này sẽ bị trừ vào công/lương.`);
    }

    submitting.value = true;

    const submitData = {
        ...form.value,
        start_date: toYMD(form.value.start_date),
        end_date: toYMD(form.value.end_date),
        submit: true,
    };

    if (props.mode === 'edit') {
        const leaveId = props.leaveRequest?.data?.id || props.leaveRequest?.id;
        LeaveRequestService.update(leaveId, submitData, {
            onFinish: () => {
                submitting.value = false;
            },
        });
    } else {
        LeaveRequestService.store(submitData, {
            onFinish: () => {
                submitting.value = false;
            },
        });
    }
};

const validateForm = () => {
    return form.value.leave_type_id && form.value.start_date && form.value.end_date;
};

const goBack = () => {
    LeaveRequestService.back();
};

// Load remaining days on mount
onMounted(() => {
    if (form.value.leave_type_id) {
        loadRemainingDays();
    }
    if (form.value.start_date && form.value.end_date) {
        calculateDays();
    }
});

// Watch for date changes
watch([() => form.value.start_date, () => form.value.end_date], calculateDays);
</script>

<style scoped>
.required-field::after {
    content: ' *';
    color: red;
}
</style>
