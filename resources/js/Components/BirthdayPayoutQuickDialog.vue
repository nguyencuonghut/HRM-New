<script setup>
import { ref, computed } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'

const props = defineProps({
    visible: Boolean,
    employee: Object,
})

const emit = defineEmits(['update:visible', 'success'])

const toast = useToast()
const loading = ref(false)

const formData = ref({
    amount: 200000, // Default amount
    paid_date: new Date(),
    payment_method: 'BANK_TRANSFER',
    reference_no: '',
    note: '',
})

const paymentMethods = ref([
    { label: 'Tiền mặt', value: 'CASH' },
    { label: 'Chuyển khoản', value: 'BANK_TRANSFER' },
])

const closeDialog = () => {
    emit('update:visible', false)
    // Reset form
    formData.value = {
        amount: 200000,
        paid_date: new Date(),
        payment_method: 'BANK_TRANSFER',
        reference_no: '',
        note: '',
    }
}

const saveRecord = async () => {
    if (!props.employee) return

    loading.value = true
    try {
        const response = await axios.post('/employee-benefit-payouts/quick-store', {
            employee_id: props.employee.id,
            amount: formData.value.amount,
            paid_date: formatDateForBackend(formData.value.paid_date),
            payment_method: formData.value.payment_method,
            reference_no: formData.value.reference_no,
            note: formData.value.note,
        })

        if (response.data.success) {
            toast.add({
                severity: 'success',
                summary: 'Thành công',
                detail: response.data.message,
                life: 3000
            })
            emit('success')
            closeDialog()
        }
    } catch (error) {
        console.error('Error saving payout:', error)
        toast.add({
            severity: 'error',
            summary: 'Lỗi',
            detail: error.response?.data?.message || 'Có lỗi xảy ra khi ghi nhận chi trả',
            life: 5000
        })
    } finally {
        loading.value = false
    }
}

const formatDateForBackend = (date) => {
    if (!date) return null
    const d = new Date(date)
    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="$emit('update:visible', $event)"
        header="Ghi nhận chi trả sinh nhật"
        modal
        :style="{ width: '500px' }"
    >
        <div v-if="employee" class="space-y-4 py-4">
            <!-- Employee Info (readonly) -->
            <div class="p-4 bg-surface-50 dark:bg-surface-800 rounded-lg">
                <div class="flex items-center gap-3 mb-2">
                    <div class="font-semibold text-lg">{{ employee.full_name }}</div>
                    <span class="text-sm text-surface-500">{{ employee.employee_code }}</span>
                </div>
                <div class="text-sm text-surface-600 dark:text-surface-400">
                    <div>Sinh nhật: {{ formatDate(employee.dob) }}</div>
                    <div>Phòng/Ban: {{ employee.department || '-' }}</div>
                </div>
            </div>

            <!-- Amount -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Số tiền <span class="text-red-500">*</span>
                </label>
                <InputNumber
                    v-model="formData.amount"
                    :min="0"
                    :step="50000"
                    class="w-full"
                    placeholder="200,000"
                    suffix=" VND"
                />
            </div>

            <!-- Paid Date -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Ngày chi <span class="text-red-500">*</span>
                </label>
                <DatePicker
                    v-model="formData.paid_date"
                    dateFormat="dd/mm/yy"
                    placeholder="Chọn ngày"
                    class="w-full"
                    showIcon
                    fluid
                />
            </div>

            <!-- Payment Method -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Phương thức <span class="text-red-500">*</span>
                </label>
                <Select
                    v-model="formData.payment_method"
                    :options="paymentMethods"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Chọn phương thức"
                    class="w-full"
                />
            </div>

            <!-- Reference No -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Mã tham chiếu
                </label>
                <InputText
                    v-model="formData.reference_no"
                    class="w-full"
                    placeholder="VD: TXN123456"
                />
            </div>

            <!-- Note -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Ghi chú
                </label>
                <Textarea
                    v-model="formData.note"
                    class="w-full"
                    rows="2"
                    placeholder="Ghi chú thêm về khoản chi này..."
                />
            </div>
        </div>

        <template #footer>
            <Button
                label="Hủy"
                icon="pi pi-times"
                text
                @click="closeDialog"
                :disabled="loading"
            />
            <Button
                label="Lưu"
                icon="pi pi-check"
                @click="saveRecord"
                :loading="loading"
            />
        </template>
    </Dialog>
</template>
