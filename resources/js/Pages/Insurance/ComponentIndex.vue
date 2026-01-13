<template>
    <Head>
        <title>Quản lý cấu hình BHXH</title>
    </Head>

    <div>
        <div class="card">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Quản lý tỷ lệ đóng BHXH</h2>
            </div>

            <Message severity="warn" :closable="false" class="mb-4">
                <div class="font-semibold mb-2">⚠️ Lưu ý quan trọng:</div>
                <ul class="list-disc ml-6 space-y-1 text-sm">
                    <li>Thay đổi tỷ lệ chỉ ảnh hưởng đến hợp đồng <strong>MỚI</strong> sau này</li>
                    <li>Hợp đồng và participation đã tạo sẽ <strong>GIỮ NGUYÊN</strong> tỷ lệ cũ</li>
                    <li>Chỉ Admin được phép thay đổi tỷ lệ đóng</li>
                </ul>
            </Message>

            <DataTable :value="components" :loading="loading" class="text-sm">
                <Column field="code" header="Mã" style="min-width: 120px" frozen>
                    <template #body="{ data }">
                        <span class="font-mono font-semibold">{{ data.code }}</span>
                    </template>
                </Column>

                <Column field="name_vi" header="Tên tiếng Việt" style="min-width: 220px" />

                <Column header="Tỷ lệ đóng mặc định" style="min-width: 280px">
                    <template #body="{ data }">
                        <div class="space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Người lao động:</span>
                                <strong class="text-blue-600">{{ formatPercent(data.default_rate_employee) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Người sử dụng:</span>
                                <strong class="text-green-600">{{ formatPercent(data.default_rate_employer) }}</strong>
                            </div>
                            <div class="flex justify-between pt-1 border-t">
                                <span class="font-semibold">Tổng:</span>
                                <strong class="text-indigo-700 text-base">{{ formatPercent(data.default_rate_total) }}</strong>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="is_active" header="Trạng thái" style="min-width: 120px">
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_active ? 'Đang dùng' : 'Ngừng'"
                            :severity="data.is_active ? 'success' : 'danger'"
                        />
                    </template>
                </Column>

                <Column header="Cập nhật" style="min-width: 150px">
                    <template #body="{ data }">
                        <div class="text-xs text-gray-500">
                            {{ formatDate(data.updated_at) }}
                        </div>
                    </template>
                </Column>

                <Column header="Thao tác" style="min-width: 100px">
                    <template #body="{ data }">
                        <Button
                            icon="pi pi-pencil"
                            rounded
                            outlined
                            severity="secondary"
                            size="small"
                            @click="openEditDialog(data)"
                            v-tooltip.top="'Chỉnh sửa tỷ lệ'"
                        />
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-gray-500">
                        Không có dữ liệu
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Edit Dialog -->
        <Dialog
            v-model:visible="dialogVisible"
            header="Chỉnh sửa tỷ lệ đóng BHXH"
            :modal="true"
            :style="{ width: '600px' }"
        >
            <div class="space-y-4">
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center gap-2 text-blue-900">
                        <i class="pi pi-info-circle"></i>
                        <span class="font-semibold">{{ editForm.name_vi }}</span>
                    </div>
                    <div class="text-sm text-blue-700 mt-1">
                        Mã: <span class="font-mono">{{ editForm.code }}</span>
                    </div>
                </div>

                <div>
                    <label class="block font-medium mb-2">Tỷ lệ người lao động (%)</label>
                    <InputNumber
                        v-model="editForm.default_rate_employee_percent"
                        :min-fraction-digits="1"
                        :max-fraction-digits="3"
                        :min="0"
                        :max="100"
                        suffix="%"
                        class="w-full"
                        @input="calculateTotalRate"
                    />
                    <small class="text-gray-500">Tỷ lệ do người lao động đóng</small>
                </div>

                <div>
                    <label class="block font-medium mb-2">Tỷ lệ người sử dụng lao động (%)</label>
                    <InputNumber
                        v-model="editForm.default_rate_employer_percent"
                        :min-fraction-digits="1"
                        :max-fraction-digits="3"
                        :min="0"
                        :max="100"
                        suffix="%"
                        class="w-full"
                        @input="calculateTotalRate"
                    />
                    <small class="text-gray-500">Tỷ lệ do công ty đóng</small>
                </div>

                <div class="p-4 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg border border-indigo-200">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-indigo-900">Tổng tỷ lệ đóng:</span>
                        <span class="text-2xl font-bold text-indigo-700">
                            {{ totalRatePercent }}%
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <Checkbox v-model="editForm.is_active" binary inputId="is_active" />
                    <label for="is_active" class="cursor-pointer">
                        Đang hoạt động (áp dụng cho hợp đồng mới)
                    </label>
                </div>

                <Message severity="info" :closable="false" class="text-sm">
                    <i class="pi pi-exclamation-circle mr-2"></i>
                    Thay đổi sẽ chỉ áp dụng cho hợp đồng tạo mới sau khi lưu.
                </Message>
            </div>

            <template #footer>
                <Button
                    label="Hủy"
                    icon="pi pi-times"
                    text
                    @click="dialogVisible = false"
                />
                <Button
                    label="Lưu thay đổi"
                    icon="pi pi-check"
                    @click="saveComponent"
                    :loading="saving"
                />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const toast = useToast();

const loading = ref(false);
const dialogVisible = ref(false);
const saving = ref(false);
const components = ref([]);

const editForm = ref({
    id: null,
    code: '',
    name_vi: '',
    default_rate_employee_percent: 0,
    default_rate_employer_percent: 0,
    is_active: true
});

const totalRatePercent = computed(() => {
    const total = editForm.value.default_rate_employee_percent + editForm.value.default_rate_employer_percent;
    return total.toFixed(2);
});

// Helper methods
const formatPercent = (value) => {
    if (!value) return '0%';
    return `${(value * 100).toFixed(2)}%`;
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const calculateTotalRate = () => {
    // Auto-calculate, bound by computed property
};

// CRUD operations
const loadComponents = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/insurance-components');
        components.value = response.data;
    } catch (error) {
        console.error('Failed to load components:', error);
        toast.add({
            severity: 'error',
            summary: 'Lỗi',
            detail: 'Không thể tải danh sách cấu hình BHXH',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const openEditDialog = (component) => {
    editForm.value = {
        id: component.id,
        code: component.code,
        name_vi: component.name_vi,
        default_rate_employee_percent: component.default_rate_employee * 100,
        default_rate_employer_percent: component.default_rate_employer * 100,
        is_active: component.is_active
    };
    dialogVisible.value = true;
};

const saveComponent = async () => {
    saving.value = true;

    try {
        // Convert percentages back to decimals
        const payload = {
            default_rate_employee: editForm.value.default_rate_employee_percent / 100,
            default_rate_employer: editForm.value.default_rate_employer_percent / 100,
            is_active: editForm.value.is_active
        };

        await axios.put(`/insurance-components/${editForm.value.id}`, payload);

        toast.add({
            severity: 'success',
            summary: 'Thành công',
            detail: 'Đã cập nhật tỷ lệ đóng BHXH',
            life: 3000
        });

        dialogVisible.value = false;
        await loadComponents(); // Reload data
    } catch (error) {
        console.error('Save failed:', error);
        toast.add({
            severity: 'error',
            summary: 'Lỗi',
            detail: error.response?.data?.message || 'Không thể lưu thay đổi',
            life: 5000
        });
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    loadComponents();
});
</script>

<style scoped>
.card {
    @apply bg-white rounded-lg shadow-sm p-6;
}
</style>
