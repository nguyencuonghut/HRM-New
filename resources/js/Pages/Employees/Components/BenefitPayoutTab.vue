<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Toolbar from 'primevue/toolbar'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Tag from 'primevue/tag'
import { EmployeeBenefitPayoutService } from '@/services/EmployeeBenefitPayoutService'
import { useConfirm } from 'primevue/useconfirm'
import ConfirmDialog from 'primevue/confirmdialog'
import { toYMD, formatDate as formatDateHelper } from '@/utils/dateHelper'

const confirm = useConfirm()
const page = usePage()

const props = defineProps({
  employee: {
    type: Object,
    required: true
  },
  benefitPayouts: {
    type: Array,
    default: () => []
  },
  benefitTypes: {
    type: Array,
    default: () => []
  },
  paymentMethods: {
    type: Array,
    default: () => []
  }
})

// Local filters (client-side)
const globalSearch = ref('')
const selectedYear = ref(null)

// Get unique years from payouts
const availableYears = computed(() => {
  const years = [...new Set(props.benefitPayouts.map(p => new Date(p.paid_date).getFullYear()))]
  return years.sort((a, b) => b - a)
})

// Filtered payouts
const filteredPayouts = computed(() => {
  let result = props.benefitPayouts

  // Filter by year
  if (selectedYear.value) {
    result = result.filter(p => new Date(p.paid_date).getFullYear() === selectedYear.value)
  }

  // Global search
  if (globalSearch.value.trim()) {
    const search = globalSearch.value.toLowerCase().trim()
    result = result.filter(p => {
      const benefitTypeName = p.benefit_type?.name?.toLowerCase() || ''
      const benefitTypeCode = p.benefit_type?.code?.toLowerCase() || ''
      const amount = p.amount?.toString() || ''
      const note = p.note?.toLowerCase() || ''
      const referenceNo = p.reference_no?.toLowerCase() || ''
      const date = formatDate(p.paid_date).toLowerCase()

      return benefitTypeName.includes(search) ||
             benefitTypeCode.includes(search) ||
             amount.includes(search) ||
             note.includes(search) ||
             referenceNo.includes(search) ||
             date.includes(search)
    })
  }

  return result
})

// Dialog state
const dialog = ref(false)
const isEditing = ref(false)
const formData = ref({
  id: null,
  benefit_type_id: null,
  paid_date: null,
  amount: null,
  currency: 'VND',
  payment_method: 'BANK_TRANSFER',
  reference_no: '',
  note: ''
})

const openCreateDialog = () => {
  isEditing.value = false
  formData.value = {
    id: null,
    benefit_type_id: null,
    paid_date: null,
    amount: null,
    currency: 'VND',
    payment_method: 'BANK_TRANSFER',
    reference_no: '',
    note: ''
  }
  dialog.value = true
}

const openEditDialog = (payout) => {
  isEditing.value = true
  formData.value = {
    id: payout.id,
    benefit_type_id: payout.benefit_type_id,
    paid_date: new Date(payout.paid_date),
    amount: payout.amount,
    currency: payout.currency,
    payment_method: payout.payment_method,
    reference_no: payout.reference_no || '',
    note: payout.note || ''
  }
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  formData.value = {
    id: null,
    benefit_type_id: null,
    paid_date: null,
    amount: null,
    currency: 'VND',
    payment_method: 'BANK_TRANSFER',
    reference_no: '',
    note: ''
  }
}

const saveRecord = () => {
  const data = {
    employee_id: props.employee.id,
    benefit_type_id: formData.value.benefit_type_id,
    paid_date: toYMD(formData.value.paid_date),
    amount: formData.value.amount,
    currency: formData.value.currency,
    payment_method: formData.value.payment_method,
    reference_no: formData.value.reference_no,
    note: formData.value.note,
    source: 'MANUAL'
  }

  if (isEditing.value) {
    EmployeeBenefitPayoutService.update(formData.value.id, data, {
      onSuccess: () => {
        closeDialog()
      }
    })
  } else {
    EmployeeBenefitPayoutService.store(data, {
      onSuccess: () => {
        closeDialog()
      }
    })
  }
}

const deleteRecord = (id) => {
  confirm.require({
    message: 'Bạn có chắc chắn muốn xóa khoản chi phúc lợi này?',
    header: 'Xác nhận xóa',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Xóa',
    rejectLabel: 'Hủy',
    accept: () => {
      EmployeeBenefitPayoutService.destroy(id)
    }
  })
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('vi-VN')
}

const formatCurrency = (amount, currency) => {
  if (!amount) return '0'
  return new Intl.NumberFormat('vi-VN').format(amount) + ' ' + currency
}

const getPaymentMethodLabel = (method) => {
  const labels = {
    'CASH': 'Tiền mặt',
    'BANK_TRANSFER': 'Chuyển khoản'
  }
  return labels[method] || method
}

const getPaymentMethodSeverity = (method) => {
  return method === 'CASH' ? 'success' : 'info'
}

// Calculate total benefits
const totalBenefits = computed(() => {
  return filteredPayouts.value.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0)
})
</script>

<template>
  <div>
    <ConfirmDialog />

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-4">
      <div class="card bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500">
        <div class="p-4">
          <div class="text-sm text-blue-600 dark:text-blue-400 font-medium">Tổng số lần</div>
          <div class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">{{ filteredPayouts.length }}</div>
        </div>
      </div>

      <div class="card bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500">
        <div class="p-4">
          <div class="text-sm text-green-600 dark:text-green-400 font-medium">Tổng tiền</div>
          <div class="text-2xl font-bold text-green-700 dark:text-green-300 mt-1">
            {{ formatCurrency(totalBenefits, 'VND') }}
          </div>
        </div>
      </div>

      <div class="card bg-purple-50 dark:bg-purple-900/20 border-l-4 border-purple-500">
        <div class="p-4">
          <div class="text-sm text-purple-600 dark:text-purple-400 font-medium">Loại phúc lợi</div>
          <div class="text-2xl font-bold text-purple-700 dark:text-purple-300 mt-1">
            {{ [...new Set(filteredPayouts.map(p => p.benefit_type_id))].length }}
          </div>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="card mb-4">
      <Toolbar>
        <template #start>
          <Button label="Thêm mới" icon="pi pi-plus" severity="success" @click="openCreateDialog" />
        </template>

        <template #end>
          <div class="flex gap-2">
            <Select v-model="selectedYear" :options="availableYears" placeholder="Chọn năm" class="w-32" showClear />
            <InputText v-model="globalSearch" placeholder="Tìm kiếm..." class="w-64" />
          </div>
        </template>
      </Toolbar>
    </div>

    <!-- DataTable -->
    <div class="card">
      <DataTable :value="filteredPayouts" stripedRows showGridlines>
        <Column field="benefit_type.name" header="Loại phúc lợi" sortable style="min-width: 180px">
          <template #body="{ data }">
            <div>
              <div>{{ data.benefit_type?.name }}</div>
              <div class="text-sm text-gray-500">{{ data.benefit_type?.code }}</div>
            </div>
          </template>
        </Column>

        <Column field="paid_date" header="Ngày chi" sortable style="min-width: 120px">
          <template #body="{ data }">
            {{ formatDate(data.paid_date) }}
          </template>
        </Column>

        <Column field="amount" header="Số tiền" sortable style="min-width: 140px; text-align: right">
          <template #body="{ data }">
            <span class="font-semibold text-green-600">{{ formatCurrency(data.amount, data.currency) }}</span>
          </template>
        </Column>

        <Column field="payment_method" header="Phương thức" sortable style="min-width: 130px">
          <template #body="{ data }">
            <Tag :value="getPaymentMethodLabel(data.payment_method)" :severity="getPaymentMethodSeverity(data.payment_method)" />
          </template>
        </Column>

        <Column field="reference_no" header="Mã tham chiếu" style="min-width: 140px">
          <template #body="{ data }">
            <span class="text-gray-600">{{ data.reference_no || '-' }}</span>
          </template>
        </Column>

        <Column field="note" header="Ghi chú" style="min-width: 200px">
          <template #body="{ data }">
            <span class="text-gray-600">{{ data.note || '-' }}</span>
          </template>
        </Column>

        <Column header="Thao tác" style="min-width: 150px">
          <template #body="{ data }">
            <div class="flex gap-2">
              <Button icon="pi pi-pencil" severity="info" text rounded @click="openEditDialog(data)" />
              <Button icon="pi pi-trash" severity="danger" text rounded @click="deleteRecord(data.id)" />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="text-center py-4 text-gray-500">Chưa có dữ liệu phúc lợi</div>
        </template>
      </DataTable>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog v-model:visible="dialog" :header="isEditing ? 'Cập nhật khoản chi phúc lợi' : 'Thêm khoản chi phúc lợi mới'" modal :style="{ width: '700px' }">
      <div class="space-y-4 py-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">Loại phúc lợi <span class="text-red-500">*</span></label>
            <Select v-model="formData.benefit_type_id" :options="benefitTypes" optionLabel="name" optionValue="id" placeholder="Chọn loại" class="w-full" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Ngày chi <span class="text-red-500">*</span></label>
            <DatePicker v-model="formData.paid_date" dateFormat="dd/mm/yy" placeholder="Chọn ngày" class="w-full" fluid />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">Số tiền <span class="text-red-500">*</span></label>
            <InputNumber v-model="formData.amount" :min="0" class="w-full" placeholder="0" fluid />
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Phương thức thanh toán <span class="text-red-500">*</span></label>
            <Select v-model="formData.payment_method" :options="paymentMethods" optionLabel="label" optionValue="value" placeholder="Chọn phương thức" class="w-full" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">Mã tham chiếu</label>
          <InputText v-model="formData.reference_no" class="w-full" placeholder="VD: TXN123456" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">Ghi chú</label>
          <Textarea v-model="formData.note" class="w-full" rows="3" placeholder="Ghi chú thêm..." />
        </div>
      </div>

      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="closeDialog" />
        <Button label="Lưu" icon="pi pi-check" @click="saveRecord" />
      </template>
    </Dialog>
  </div>
</template>
