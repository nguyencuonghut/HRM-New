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
                            :invalid="(submitted && !form.employee_id) || hasError('employee_id')"
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

                        <small v-if="isAdmin && (submitted && !form.employee_id)" class="text-red-500">
                            Vui lòng chọn nhân viên
                        </small>
                        <small v-if="hasError('employee_id')" class="p-error block mt-1">
                            {{ getError('employee_id') }}
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
                            :invalid="(submitted && !form.leave_type_id) || hasError('leave_type_id')"
                            dataKey="id"
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
                        <small v-if="submitted && !form.leave_type_id" class="text-red-500">
                            Vui lòng chọn loại phép
                        </small>
                        <small v-if="hasError('leave_type_id')" class="p-error block mt-1">
                            {{ getError('leave_type_id') }}
                        </small>
                    </div>

                    <!-- Available Days - Only for ANNUAL leave -->
                    <div v-if="selectedLeaveType?.code === 'ANNUAL'">
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
                            :invalid="(submitted && !form.start_date) || hasError('start_date')"
                            fluid
                            @date-select="calculateDays"
                        />
                        <small v-if="submitted && !form.start_date" class="text-red-500">
                            Vui lòng chọn ngày bắt đầu
                        </small>
                        <small v-if="hasError('start_date')" class="p-error block mt-1">
                            {{ getError('start_date') }}
                        </small>
                    </div>

                    <!-- End Date -->
                    <div>
                        <label class="block font-bold mb-2 required-field">Đến ngày</label>
                        <DatePicker
                            v-model="form.end_date"
                            showIcon
                            dateFormat="yy-mm-dd"
                            :invalid="(submitted && !form.end_date) || hasError('end_date')"
                            :minDate="form.start_date"
                            fluid
                            @date-select="calculateDays"
                        />
                        <small v-if="submitted && !form.end_date" class="text-red-500">
                            Vui lòng chọn ngày kết thúc
                        </small>
                        <small v-if="hasError('end_date')" class="p-error block mt-1">
                            {{ getError('end_date') }}
                        </small>
                    </div>

                    <!-- Personal Leave Reason - Only for PERSONAL_PAID -->
                    <div v-if="selectedLeaveType?.code === 'PERSONAL_PAID'" class="md:col-span-2">
                        <label class="block font-bold mb-2 required-field">Lý do nghỉ phép</label>
                        <Select
                            v-model="form.personal_leave_reason"
                            :options="personalLeaveReasons"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Chọn lý do nghỉ phép"
                            :invalid="(submitted && !form.personal_leave_reason) || hasError('personal_leave_reason')"
                            fluid
                            @change="onPersonalReasonChange"
                        >
                            <template #option="slotProps">
                                <div class="flex justify-between items-center w-full">
                                    <span>{{ slotProps.option.label }}</span>
                                    <Badge :value="`${slotProps.option.days} ngày`" severity="info" />
                                </div>
                            </template>
                        </Select>
                        <small v-if="submitted && !form.personal_leave_reason" class="text-red-500">
                            Vui lòng chọn lý do nghỉ phép
                        </small>
                        <small v-if="hasError('personal_leave_reason')" class="p-error block mt-1">
                            {{ getError('personal_leave_reason') }}
                        </small>
                    </div>

                    <!-- Maternity Leave Fields - Only for MATERNITY -->
                    <template v-if="selectedLeaveType?.code === 'MATERNITY'">
                        <div class="md:col-span-2">
                            <label class="block font-bold mb-2 required-field">Ngày dự sinh</label>
                            <DatePicker
                                v-model="form.expected_due_date"
                                showIcon
                                dateFormat="yy-mm-dd"
                                :invalid="(submitted && !form.expected_due_date) || hasError('expected_due_date')"
                                fluid
                                @date-select="calculateMaternityDays"
                            />
                            <small v-if="submitted && !form.expected_due_date" class="text-red-500">
                                Vui lòng chọn ngày dự sinh
                            </small>
                            <small v-if="hasError('expected_due_date')" class="p-error block mt-1">
                                {{ getError('expected_due_date') }}
                            </small>
                        </div>

                        <div>
                            <label class="block font-bold mb-2">Số con sinh</label>
                            <InputNumber
                                v-model="form.twins_count"
                                :min="1"
                                :max="5"
                                showButtons
                                fluid
                                @input="calculateMaternityDays"
                            />
                            <small class="text-gray-500 block mt-1">
                                Sinh đôi, sinh ba... được cộng thêm 30 ngày/mỗi con từ con thứ 2
                            </small>
                        </div>

                        <div>
                            <label class="block font-bold mb-2">Số con dưới 36 tháng</label>
                            <InputNumber
                                v-model="form.children_under_36_months"
                                :min="0"
                                :max="5"
                                showButtons
                                fluid
                                @input="calculateMaternityDays"
                            />
                            <small class="text-gray-500 block mt-1">
                                Được cộng thêm 30 ngày nếu có con dưới 36 tháng
                            </small>
                        </div>

                        <div class="md:col-span-2">
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    v-model="form.is_caesarean"
                                    binary
                                    @change="calculateMaternityDays"
                                />
                                <label class="font-bold cursor-pointer" @click="form.is_caesarean = !form.is_caesarean">
                                    Sinh mổ (cộng thêm 15 ngày)
                                </label>
                            </div>
                        </div>
                    </template>

                    <!-- Medical Certificate - Only for SICK -->
                    <div v-if="selectedLeaveType?.code === 'SICK'" class="md:col-span-2">
                        <label class="block font-bold mb-2 required-field">Giấy xác nhận của bác sĩ</label>
                        <FileUpload
                            mode="basic"
                            accept=".pdf,.jpg,.jpeg,.png"
                            :maxFileSize="5000000"
                            chooseLabel="Chọn file"
                            :class="{ 'p-invalid': (submitted && !form.medical_certificate_path) || hasError('medical_certificate_path') }"
                            @select="onFileSelect"
                        />
                        <small class="text-gray-500 block mt-1">
                            Chấp nhận file PDF, JPG, PNG. Tối đa 5MB. (Công ty trả lương tối đa 30 ngày)
                        </small>
                        <small v-if="submitted && !form.medical_certificate_path" class="text-red-500">
                            Vui lòng tải lên giấy xác nhận của bác sĩ
                        </small>
                        <small v-if="hasError('medical_certificate_path')" class="p-error block mt-1">
                            {{ getError('medical_certificate_path') }}
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
                            <!-- Only show warning for ANNUAL leave -->
                            <div v-if="selectedLeaveType?.code === 'ANNUAL' && remainingDays !== null && calculatedDays > remainingDays" class="mt-2 text-red-600">
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
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import FileUpload from 'primevue/fileupload';
import Badge from 'primevue/badge';
import { useToast } from 'primevue/usetoast';
import { useFormValidation } from '@/composables/useFormValidation';
import { LeaveRequestService } from '@/services/LeaveRequestService';
import { ToastService } from '@/services/ToastService';
import { calculateWorkingDays } from '@/utils/leaveHelpers';
import { toYMD } from '@/utils/dateHelper';

const props = defineProps({
    leaveRequest: Object,
    leaveTypes: Array,
    personalLeaveReasons: Array, // Personal paid leave reasons
    employee: Object,
    mode: String,
    isAdmin: Boolean,
    employees: Array, // Only provided for Admin users
});

const toast = useToast();
ToastService.init(toast);
const page = usePage();
const { errors, hasError, getError } = useFormValidation();

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
        note: leaveData?.note || '',
        personal_leave_reason: leaveData?.personal_leave_reason || null,
        expected_due_date: null,
        twins_count: leaveData?.twins_count || 1,
        is_caesarean: leaveData?.is_caesarean || false,
        children_under_36_months: leaveData?.children_under_36_months || 0,
        medical_certificate_path: null,
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

    if (leaveData?.expected_due_date) {
        const dateStr = leaveData.expected_due_date;
        if (typeof dateStr === 'string' && dateStr.includes('-')) {
            const [y, m, d] = dateStr.split('-').map(Number);
            data.expected_due_date = new Date(y, m - 1, d);
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
    if (selectedLeaveType.value?.code === 'PERSONAL_PAID') {
        // Days are determined by personal_leave_reason
        const reason = props.personalLeaveReasons?.find(r => r.value === form.value.personal_leave_reason);
        calculatedDays.value = reason?.days || 0;
    } else if (selectedLeaveType.value?.code === 'MATERNITY') {
        // Will be calculated by calculateMaternityDays
        return;
    } else {
        // Normal working days calculation
        calculatedDays.value = calculateWorkingDays(form.value.start_date, form.value.end_date);
    }
};

const onPersonalReasonChange = () => {
    const reason = props.personalLeaveReasons?.find(r => r.value === form.value.personal_leave_reason);
    if (reason) {
        calculatedDays.value = reason.days;
        // Auto-calculate end date
        if (form.value.start_date) {
            const startDate = new Date(form.value.start_date);
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + reason.days - 1);
            form.value.end_date = endDate;
        }
    }
};

const calculateMaternityDays = () => {
    let days = 180; // Base 180 days

    // Additional 30 days for twins/triplets (from 2nd child onwards)
    if (form.value.twins_count > 1) {
        days += (form.value.twins_count - 1) * 30;
    }

    // Additional 15 days for caesarean
    if (form.value.is_caesarean) {
        days += 15;
    }

    // Additional 30 days if having children under 36 months
    if (form.value.children_under_36_months > 0) {
        days += 30;
    }

    calculatedDays.value = days;

    // Auto-calculate dates: 60 days before due date, remaining after
    if (form.value.expected_due_date) {
        const dueDate = new Date(form.value.expected_due_date);

        // Start date: 60 days before due date
        const startDate = new Date(dueDate);
        startDate.setDate(startDate.getDate() - 60);
        form.value.start_date = startDate;

        // End date: start + total days
        const endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + days - 1);
        form.value.end_date = endDate;
    }
};

const onFileSelect = (event) => {
    form.value.medical_certificate_path = event.files[0];
};

const saveDraft = () => {
    submitted.value = true;

    if (!validateForm()) {
        return;
    }

    saving.value = true;

    const submitData = prepareSubmitData(false);

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
    console.log('submitForApproval called');
    submitted.value = true;

    if (!validateForm()) {
        console.log('Validation failed in submitForApproval');
        return;
    }

    // Allow submission even if exceeds balance (only for ANNUAL leave)
    // The excess days will be marked as unpaid leave
    if (selectedLeaveType.value?.code === 'ANNUAL' && remainingDays.value !== null && calculatedDays.value > remainingDays.value) {
        const excessDays = calculatedDays.value - remainingDays.value;
        ToastService.warn(`Vượt ${excessDays} ngày phép. Những ngày này sẽ bị trừ vào công/lương.`);
    }

    console.log('About to prepare submit data');
    submitting.value = true;

    const submitData = prepareSubmitData(true);
    console.log('Submit data prepared:', submitData);

    // Debug FormData contents
    for (let pair of submitData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }

    if (props.mode === 'edit') {
        console.log('Mode: edit');
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

const prepareSubmitData = (submit = false) => {
    // Create FormData for file upload
    const formData = new FormData();

    // Add basic fields
    if (form.value.employee_id) formData.append('employee_id', form.value.employee_id);
    formData.append('leave_type_id', form.value.leave_type_id);
    formData.append('start_date', toYMD(form.value.start_date));
    formData.append('end_date', toYMD(form.value.end_date));
    formData.append('days', calculatedDays.value);
    if (form.value.reason) formData.append('reason', form.value.reason);
    if (form.value.note) formData.append('note', form.value.note);
    formData.append('submit', submit ? '1' : '0'); // Convert boolean to "1" or "0" for FormData

    // Add conditional fields based on leave type
    if (selectedLeaveType.value?.code === 'PERSONAL_PAID' && form.value.personal_leave_reason) {
        formData.append('personal_leave_reason', form.value.personal_leave_reason);
    }

    if (selectedLeaveType.value?.code === 'MATERNITY') {
        if (form.value.expected_due_date) {
            formData.append('expected_due_date', toYMD(form.value.expected_due_date));
        }
        formData.append('twins_count', form.value.twins_count);
        formData.append('is_caesarean', form.value.is_caesarean ? '1' : '0');
        formData.append('children_under_36_months', form.value.children_under_36_months);
    }

    if (selectedLeaveType.value?.code === 'SICK' && form.value.medical_certificate_path) {
        formData.append('medical_certificate_path', form.value.medical_certificate_path);
    }

    // Add _method for PUT/PATCH when editing
    if (props.mode === 'edit') {
        formData.append('_method', 'PUT');
    }

    return formData;
};

const validateForm = () => {
    if (!form.value.leave_type_id || !form.value.start_date || !form.value.end_date) {
        return false;
    }

    // Additional validation for PERSONAL_PAID
    if (selectedLeaveType.value?.code === 'PERSONAL_PAID' && !form.value.personal_leave_reason) {
        return false;
    }

    // Additional validation for MATERNITY
    if (selectedLeaveType.value?.code === 'MATERNITY' && !form.value.expected_due_date) {
        return false;
    }

    // Additional validation for SICK
    if (selectedLeaveType.value?.code === 'SICK' && !form.value.medical_certificate_path) {
        return false;
    }

    return true;
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
