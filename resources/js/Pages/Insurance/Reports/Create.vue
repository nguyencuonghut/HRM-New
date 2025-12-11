<template>
    <Head>
        <title>Tạo báo cáo Bảo hiểm</title>
    </Head>

    <div>
        <Card>
            <template #title>
                <div class="flex items-center gap-2">
                    <Button icon="pi pi-arrow-left" variant="text" @click="goBack" />
                    <span>Tạo báo cáo Bảo hiểm XH</span>
                </div>
            </template>

            <template #content>
                <form @submit.prevent="handleSubmit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl">
                        <!-- Year -->
                        <div>
                            <label class="block font-bold mb-2 required-field">Năm</label>
                            <Select
                                v-model="form.year"
                                :options="yearOptions"
                                placeholder="Chọn năm"
                                :invalid="submitted && !form.year"
                                fluid
                            />
                            <small v-if="submitted && !form.year" class="p-error block mt-1">
                                Vui lòng chọn năm
                            </small>
                        </div>

                        <!-- Month -->
                        <div>
                            <label class="block font-bold mb-2 required-field">Tháng</label>
                            <Select
                                v-model="form.month"
                                :options="monthOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Chọn tháng"
                                :invalid="submitted && !form.month"
                                fluid
                            />
                            <small v-if="submitted && !form.month" class="p-error block mt-1">
                                Vui lòng chọn tháng
                            </small>
                        </div>
                    </div>

                    <Message severity="info" class="mt-6">
                        <div class="flex flex-col gap-2">
                            <div class="font-semibold">Hệ thống sẽ tự động phát hiện:</div>
                            <ul class="list-disc list-inside ml-4 space-y-1">
                                <li><strong>TĂNG:</strong> Nhân viên mới, quay lại làm việc</li>
                                <li><strong>GIẢM:</strong> Nghỉ việc, nghỉ dài hạn (>30 ngày), thai sản</li>
                                <li><strong>ĐIỀU CHỈNH:</strong> Thay đổi lương BH từ Phụ lục HĐ</li>
                            </ul>
                            <div class="text-sm text-gray-600 mt-2">
                                Sau khi tạo, Admin cần duyệt từng thay đổi trước khi xuất báo cáo.
                            </div>
                        </div>
                    </Message>

                    <!-- Actions -->
                    <div class="flex gap-2 mt-6 pt-4 border-t">
                        <Button
                            label="Tạo báo cáo"
                            icon="pi pi-check"
                            type="submit"
                            :loading="creating"
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
            </template>
        </Card>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { InsuranceReportService } from '@/services/InsuranceReportService';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Message from 'primevue/message';

const props = defineProps({
    currentYear: Number,
    currentMonth: Number,
});

const form = ref({
    year: props.currentYear,
    month: props.currentMonth,
});

const submitted = ref(false);
const creating = ref(false);

// Year options (current year and 2 years back)
const yearOptions = Array.from({ length: 3 }, (_, i) => props.currentYear - i);

// Month options
const monthOptions = [
    { label: 'Tháng 1', value: 1 },
    { label: 'Tháng 2', value: 2 },
    { label: 'Tháng 3', value: 3 },
    { label: 'Tháng 4', value: 4 },
    { label: 'Tháng 5', value: 5 },
    { label: 'Tháng 6', value: 6 },
    { label: 'Tháng 7', value: 7 },
    { label: 'Tháng 8', value: 8 },
    { label: 'Tháng 9', value: 9 },
    { label: 'Tháng 10', value: 10 },
    { label: 'Tháng 11', value: 11 },
    { label: 'Tháng 12', value: 12 },
];

const handleSubmit = () => {
    submitted.value = true;

    if (!form.value.year || !form.value.month) {
        return;
    }

    creating.value = true;
    InsuranceReportService.store(form.value, {
        onFinish: () => {
            creating.value = false;
        },
    });
};

const goBack = () => {
    InsuranceReportService.index();
};
</script>

<style scoped>
.required-field::after {
    content: ' *';
    color: red;
}
</style>
