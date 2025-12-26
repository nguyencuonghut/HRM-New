<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Card from 'primevue/card';
import Select from 'primevue/select';
import ProgressBar from 'primevue/progressbar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const props = defineProps({
    employee: Object,
});

const balances = ref([]);
const recentLeaves = ref([]);
const selectedYear = ref(new Date().getFullYear());
const years = computed(() => {
    const currentYear = new Date().getFullYear();
    return [currentYear - 1, currentYear, currentYear + 1];
});
const loading = ref(false);

const loadBalances = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/employees/${props.employee.id}/leave-balances`, {
            params: { year: selectedYear.value }
        });
        balances.value = response.data.balances;
        recentLeaves.value = response.data.recent_leaves;
    } catch (error) {
        console.error('Error loading leave balances:', error);
    } finally {
        loading.value = false;
    }
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('vi-VN', { minimumFractionDigits: 1 }).format(value);
};

const getProgressColor = (percentage) => {
    if (percentage >= 80) return 'danger';
    if (percentage >= 50) return 'warn';
    return 'success';
};

const getStatusSeverity = (status) => {
    const map = {
        'APPROVED': 'success',
        'PENDING': 'warn',
        'REJECTED': 'danger',
        'CANCELLED': 'secondary',
    };
    return map[status] || 'secondary';
};

const getStatusLabel = (status) => {
    const map = {
        'APPROVED': 'Đã duyệt',
        'PENDING': 'Chờ duyệt',
        'REJECTED': 'Từ chối',
        'CANCELLED': 'Đã hủy',
    };
    return map[status] || status;
};

onMounted(() => {
    loadBalances();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Year Selector -->
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold">Số dư phép năm</h3>
            <Select
                v-model="selectedYear"
                :options="years"
                placeholder="Chọn năm"
                @change="loadBalances"
                class="w-32"
            />
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
            <i class="pi pi-spin pi-spinner text-4xl text-blue-500"></i>
        </div>

        <!-- Balance Cards -->
        <div v-else-if="balances.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Card v-for="balance in balances" :key="balance.id" class="shadow-sm">
                <template #title>
                    <div class="flex items-center justify-between">
                        <span class="text-base">{{ balance.leave_type.name }}</span>
                        <Tag
                            :style="{ backgroundColor: balance.leave_type.color, color: '#ffffff' }"
                            :value="balance.leave_type.code_label"
                            class="text-xs"
                        />
                    </div>
                </template>
                <template #content>
                    <div class="space-y-4">
                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ formatNumber(balance.total_days) }}</div>
                                <div class="text-xs text-gray-500">Tổng</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-red-600">{{ formatNumber(balance.used_days) }}</div>
                                <div class="text-xs text-gray-500">Đã dùng</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-green-600">{{ formatNumber(balance.remaining_days) }}</div>
                                <div class="text-xs text-gray-500">Còn lại</div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Tỷ lệ sử dụng</span>
                                <span class="font-medium">{{ balance.usage_percentage }}%</span>
                            </div>
                            <ProgressBar
                                :value="balance.usage_percentage"
                                :showValue="false"
                                class="h-2"
                                :severity="getProgressColor(balance.usage_percentage)"
                            />
                        </div>

                        <!-- Carried Forward -->
                        <div v-if="balance.carried_forward > 0" class="text-sm text-blue-600 bg-blue-50 p-2 rounded">
                            <i class="pi pi-arrow-right-arrow-left mr-1"></i>
                            Chuyển kỳ: +{{ formatNumber(balance.carried_forward) }} ngày
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <!-- No Data -->
        <div v-else class="text-center py-8 text-gray-500">
            <i class="pi pi-inbox text-4xl mb-2"></i>
            <p>Chưa có dữ liệu số dư phép cho năm {{ selectedYear }}</p>
        </div>

        <!-- Recent Leaves -->
        <div v-if="recentLeaves.length > 0" class="mt-8">
            <h4 class="text-lg font-semibold mb-4">Lịch sử nghỉ phép gần đây</h4>
            <Card class="shadow-sm">
                <template #content>
                    <DataTable :value="recentLeaves" class="p-datatable-sm">
                        <Column field="leave_type" header="Loại phép" style="min-width: 120px" />
                        <Column field="start_date" header="Từ ngày" style="min-width: 100px" />
                        <Column field="end_date" header="Đến ngày" style="min-width: 100px" />
                        <Column field="days" header="Số ngày" style="min-width: 80px">
                            <template #body="{ data }">
                                <span class="font-semibold">{{ formatNumber(data.days) }}</span>
                            </template>
                        </Column>
                        <Column field="status" header="Trạng thái" style="min-width: 100px">
                            <template #body="{ data }">
                                <Tag
                                    :value="getStatusLabel(data.status)"
                                    :severity="getStatusSeverity(data.status)"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
