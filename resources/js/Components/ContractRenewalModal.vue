<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="'Gia hạn hợp đồng: ' + (contract?.contract_number || '')"
    :style="{ width: '50rem' }"
    :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
  >
    <div class="flex flex-col gap-6">
      <!-- Contract Info -->
      <div class="bg-gray-50 p-4 rounded-lg">
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="font-semibold">Nhân viên:</span>
            {{ contract?.employee?.full_name }}
          </div>
          <div>
            <span class="font-semibold">Ngày bắt đầu:</span>
            {{ formatDate(contract?.start_date) }}
          </div>
          <div>
            <span class="font-semibold">Ngày kết thúc hiện tại:</span>
            {{ formatDate(contract?.end_date) || 'Không xác định' }}
          </div>
          <div>
            <span class="font-semibold">Loại hợp đồng:</span>
            {{ contract?.contract_type_label }}
          </div>
        </div>
      </div>

      <!-- Renewal Form -->
      <div class="flex flex-col gap-4">
        <!-- New End Date -->
        <div class="flex flex-col gap-2">
          <label class="font-semibold text-sm">
            Ngày kết thúc mới <span class="text-red-500">*</span>
          </label>
          <DatePicker
            v-model="form.new_end_date"
            dateFormat="dd/mm/yy"
            placeholder="Chọn ngày kết thúc mới"
            showIcon
            :class="{ 'p-invalid': errors.new_end_date }"
          />
          <small class="text-red-500" v-if="errors.new_end_date">{{ errors.new_end_date }}</small>
        </div>

        <!-- Title -->
        <div class="flex flex-col gap-2">
          <label class="font-semibold text-sm">Tiêu đề</label>
          <InputText
            v-model="form.title"
            placeholder="Phụ lục gia hạn hợp đồng"
            :class="{ 'p-invalid': errors.title }"
          />
          <small class="text-red-500" v-if="errors.title">{{ errors.title }}</small>
        </div>

        <!-- Summary -->
        <div class="flex flex-col gap-2">
          <label class="font-semibold text-sm">Mô tả thay đổi</label>
          <Textarea
            v-model="form.summary"
            rows="3"
            placeholder="Mô tả chi tiết về việc gia hạn hợp đồng..."
            :class="{ 'p-invalid': errors.summary }"
          />
          <small class="text-red-500" v-if="errors.summary">{{ errors.summary }}</small>
        </div>

        <!-- Optional: Salary Changes -->
        <div class="border-t pt-4">
          <div class="flex items-center gap-2 mb-4">
            <Checkbox v-model="showSalaryChanges" :binary="true" inputId="showSalary" />
            <label for="showSalary" class="font-semibold text-sm cursor-pointer">
              Điều chỉnh lương/phụ cấp
            </label>
          </div>

          <div v-if="showSalaryChanges" class="grid grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm">Lương cơ bản</label>
              <InputNumber
                v-model="form.base_salary"
                mode="currency"
                currency="VND"
                locale="vi-VN"
                placeholder="Lương cơ bản mới"
              />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm">Lương BHXH</label>
              <InputNumber
                v-model="form.insurance_salary"
                mode="currency"
                currency="VND"
                locale="vi-VN"
                placeholder="Lương BHXH mới"
              />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm">Phụ cấp chức vụ</label>
              <InputNumber
                v-model="form.position_allowance"
                mode="currency"
                currency="VND"
                locale="vi-VN"
                placeholder="Phụ cấp chức vụ"
              />
            </div>
          </div>
        </div>

        <!-- Optional: Department/Position Changes -->
        <div class="border-t pt-4">
          <div class="flex items-center gap-2 mb-4">
            <Checkbox v-model="showOrgChanges" :binary="true" inputId="showOrg" />
            <label for="showOrg" class="font-semibold text-sm cursor-pointer">
              Điều chỉnh phòng ban/chức danh
            </label>
          </div>

          <div v-if="showOrgChanges" class="grid grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm">Phòng ban</label>
              <Select
                v-model="form.department_id"
                :options="departments"
                optionLabel="name"
                optionValue="id"
                placeholder="Chọn phòng ban"
                filter
                showClear
              />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm">Chức danh</label>
              <Select
                v-model="form.position_id"
                :options="positions"
                optionLabel="title"
                optionValue="id"
                placeholder="Chọn chức danh"
                filter
                showClear
              />
            </div>
          </div>
        </div>

        <!-- Note -->
        <div class="flex flex-col gap-2">
          <label class="font-semibold text-sm">Ghi chú</label>
          <Textarea
            v-model="form.note"
            rows="2"
            placeholder="Ghi chú thêm (nếu có)..."
          />
        </div>
      </div>
    </div>

    <template #footer>
      <Button label="Hủy" severity="secondary" @click="visible = false" />
      <Button label="Gửi yêu cầu gia hạn" @click="submit" :loading="submitting" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
  modelValue: Boolean,
  contract: Object,
  departments: Array,
  positions: Array,
});

const emit = defineEmits(['update:modelValue', 'renewed']);

const toast = useToast();

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const form = ref({
  new_end_date: null,
  title: '',
  summary: '',
  note: '',
  base_salary: null,
  insurance_salary: null,
  position_allowance: null,
  department_id: null,
  position_id: null,
});

const errors = ref({});
const submitting = ref(false);
const showSalaryChanges = ref(false);
const showOrgChanges = ref(false);

// Reset form when contract changes
watch(() => props.contract, (newContract) => {
  if (newContract) {
    form.value = {
      new_end_date: null,
      title: '',
      summary: '',
      note: '',
      base_salary: null,
      insurance_salary: null,
      position_allowance: null,
      department_id: null,
      position_id: null,
    };
    errors.value = {};
    showSalaryChanges.value = false;
    showOrgChanges.value = false;
  }
});

const formatDate = (dateString) => {
  if (!dateString) return null;
  const date = new Date(dateString);
  return date.toLocaleDateString('vi-VN');
};

const submit = () => {
  errors.value = {};

  // Validation
  if (!form.value.new_end_date) {
    errors.value.new_end_date = 'Ngày kết thúc mới là bắt buộc';
    return;
  }

  const currentEndDate = props.contract?.end_date
    ? new Date(props.contract.end_date)
    : new Date(props.contract.start_date);

  if (form.value.new_end_date <= currentEndDate) {
    errors.value.new_end_date = 'Ngày kết thúc mới phải sau ngày kết thúc hiện tại';
    return;
  }

  submitting.value = true;

  // Prepare payload
  const payload = {
    new_end_date: formatDateForBackend(form.value.new_end_date),
    title: form.value.title || undefined,
    summary: form.value.summary || undefined,
    note: form.value.note || undefined,
  };

  // Add optional fields if checkboxes are enabled
  if (showSalaryChanges.value) {
    if (form.value.base_salary) payload.base_salary = form.value.base_salary;
    if (form.value.insurance_salary) payload.insurance_salary = form.value.insurance_salary;
    if (form.value.position_allowance) payload.position_allowance = form.value.position_allowance;
  }

  if (showOrgChanges.value) {
    if (form.value.department_id) payload.department_id = form.value.department_id;
    if (form.value.position_id) payload.position_id = form.value.position_id;
  }

  router.post(
    `/contracts/${props.contract.id}/renew`,
    payload,
    {
      onSuccess: (page) => {
        const response = page.props.flash?.success || page.props.flash;
        toast.add({
          severity: 'success',
          summary: 'Thành công',
          detail: 'Yêu cầu gia hạn hợp đồng đã được gửi và đang chờ phê duyệt',
          life: 3000,
        });
        visible.value = false;
        emit('renewed');
      },
      onError: (errors) => {
        if (typeof errors === 'object') {
          errors.value = errors;
        }
        toast.add({
          severity: 'error',
          summary: 'Lỗi',
          detail: errors.message || 'Có lỗi xảy ra khi gia hạn hợp đồng',
          life: 3000,
        });
      },
      onFinish: () => {
        submitting.value = false;
      },
    }
  );
};

const formatDateForBackend = (date) => {
  if (!date) return null;
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};
</script>
