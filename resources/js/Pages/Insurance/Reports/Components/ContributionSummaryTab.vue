<template>
    <div class="contribution-summary">
        <div v-if="loading" class="flex items-center justify-center py-8">
            <i class="pi pi-spin pi-spinner text-3xl text-gray-400"></i>
            <span class="ml-3 text-gray-600">Đang tải dữ liệu...</span>
        </div>

        <div v-else-if="error" class="text-center py-8">
            <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
            <p class="text-red-600 mt-2">{{ error }}</p>
        </div>

        <div v-else>
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-semibold">
                    Tổng hợp đóng BHXH tháng {{ reportMonth }}
                </h4>
                <Button
                    label="Xuất Excel"
                    icon="pi pi-download"
                    severity="success"
                    @click="exportToExcel"
                    :loading="exporting"
                />
            </div>

            <DataTable
                :value="contributions"
                :rows="10"
                :paginator="contributions.length > 10"
                showGridlines
                class="text-sm"
            >
                <!-- Fixed columns -->
                <Column field="employee_code" header="Mã NV" style="min-width: 100px" frozen />
                <Column field="employee_name" header="Họ tên" style="min-width: 180px" frozen />

                <Column header="Lương BH" style="min-width: 140px">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ formatCurrency(data.base_insurance_salary) }}</span>
                    </template>
                </Column>

                <!-- 5 Component Columns -->
                <Column header="BHXH Hưu trí - Tử tuất" style="min-width: 150px">
                    <template #body="{ data }">
                        {{ formatCurrency(getComponentAmount(data, 'RETIREMENT_SURVIVOR')) }}
                    </template>
                </Column>

                <Column header="BHXH Ốm đau - Thai sản" style="min-width: 150px">
                    <template #body="{ data }">
                        {{ formatCurrency(getComponentAmount(data, 'SICKNESS_MATERNITY')) }}
                    </template>
                </Column>

                <Column header="BHXH TNLĐ - BNN" style="min-width: 140px">
                    <template #body="{ data }">
                        {{ formatCurrency(getComponentAmount(data, 'OCC_ACCIDENT_DISEASE')) }}
                    </template>
                </Column>

                <Column header="BHTN" style="min-width: 140px">
                    <template #body="{ data }">
                        <div>
                            <div>{{ formatCurrency(getComponentAmount(data, 'UNEMPLOYMENT')) }}</div>
                            <div
                                v-if="getComponentBaseType(data, 'UNEMPLOYMENT') === 'FIXED_AMOUNT'"
                                class="text-xs text-gray-500 mt-1"
                            >
                                (Cố định: {{ formatCurrency(getComponentBaseUsed(data, 'UNEMPLOYMENT')) }})
                            </div>
                        </div>
                    </template>
                </Column>

                <Column header="BHYT" style="min-width: 130px">
                    <template #body="{ data }">
                        {{ formatCurrency(getComponentAmount(data, 'HEALTH')) }}
                    </template>
                </Column>

                <Column header="Tổng cộng" style="min-width: 150px" class="font-bold">
                    <template #body="{ data }">
                        <span class="font-bold text-blue-600 text-base">
                            {{ formatCurrency(data.total_amount) }}
                        </span>
                    </template>
                </Column>

                <!-- Footer with totals -->
                <template #footer>
                    <div class="grid grid-cols-9 gap-4 font-bold text-base bg-gray-50 p-3 rounded">
                        <div class="col-span-3 text-right flex items-center justify-end">
                            TỔNG CỘNG:
                        </div>
                        <div class="text-green-700">{{ formatCurrency(summary.total_bhxh_huu_tu) }}</div>
                        <div class="text-green-700">{{ formatCurrency(summary.total_bhxh_benh) }}</div>
                        <div class="text-green-700">{{ formatCurrency(summary.total_bhxh_tnld) }}</div>
                        <div class="text-green-700">{{ formatCurrency(summary.total_bhtn) }}</div>
                        <div class="text-green-700">{{ formatCurrency(summary.total_bhyt) }}</div>
                        <div class="text-blue-700 text-lg">{{ formatCurrency(summary.grand_total) }}</div>
                    </div>
                </template>

                <template #empty>
                    <div class="text-center py-8 text-gray-500">
                        Chưa có dữ liệu snapshot. Vui lòng hoàn tất báo cáo trước.
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import axios from 'axios';

const props = defineProps({
    report: {
        type: Object,
        required: true
    }
});

const loading = ref(false);
const exporting = ref(false);
const error = ref(null);
const contributions = ref([]);

const reportMonth = computed(() => {
    return `${props.report.month}/${props.report.year}`;
});

const summary = computed(() => {
    const totals = {
        total_bhxh_huu_tu: 0,
        total_bhxh_benh: 0,
        total_bhxh_tnld: 0,
        total_bhtn: 0,
        total_bhyt: 0,
        grand_total: 0
    };

    contributions.value.forEach(c => {
        totals.total_bhxh_huu_tu += getComponentAmount(c, 'BHXH_HUU_TU');
        totals.total_bhxh_benh += getComponentAmount(c, 'BHXH_BENH');
        totals.total_bhxh_tnld += getComponentAmount(c, 'BHXH_TNLD');
        totals.total_bhtn += getComponentAmount(c, 'BHTN');
        totals.total_bhyt += getComponentAmount(c, 'BHYT');
        totals.grand_total += parseFloat(c.total_amount || 0);
    });

    return totals;
});

// Helper methods
const formatCurrency = (value) => {
    if (!value || value === 0) return '-';
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};

const getComponentAmount = (contribution, componentCode) => {
    if (!contribution.items || !Array.isArray(contribution.items)) {
        return 0;
    }
    const item = contribution.items.find(i => i.component_code === componentCode);
    return parseFloat(item?.amount || 0);
};

const getComponentBaseType = (contribution, componentCode) => {
    if (!contribution.items || !Array.isArray(contribution.items)) {
        return null;
    }
    const item = contribution.items.find(i => i.component_code === componentCode);
    return item?.base_type || null;
};

const getComponentBaseUsed = (contribution, componentCode) => {
    if (!contribution.items || !Array.isArray(contribution.items)) {
        return 0;
    }
    const item = contribution.items.find(i => i.component_code === componentCode);
    return parseFloat(item?.base_used || 0);
};

const loadContributions = async () => {
    if (props.report.status !== 'FINALIZED') {
        error.value = 'Báo cáo chưa được hoàn tất. Không thể xem dữ liệu snapshot.';
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get(`/insurance-reports/${props.report.id}/snapshot`);

        // Transform data to match table structure
        contributions.value = response.data.contributions.map(contrib => ({
            employee_code: contrib.employee?.employee_code || '-',
            employee_name: contrib.employee?.full_name || '-',
            base_insurance_salary: contrib.base_insurance_salary,
            total_amount: contrib.total_amount,
            items: contrib.items || []
        }));
    } catch (err) {
        console.error('Failed to load contributions:', err);
        error.value = err.response?.data?.message || 'Không thể tải dữ liệu. Vui lòng thử lại.';
    } finally {
        loading.value = false;
    }
};

const exportToExcel = async () => {
    exporting.value = true;
    try {
        // Trigger download via backend
        window.location.href = `/insurance-reports/${props.report.id}/export-excel`;
    } catch (err) {
        console.error('Export failed:', err);
        error.value = 'Không thể xuất Excel. Vui lòng thử lại.';
    } finally {
        exporting.value = false;
    }
};

onMounted(() => {
    if (props.report.status === 'FINALIZED') {
        loadContributions();
    }
});
</script>

<style scoped>
.contribution-summary :deep(.p-datatable-footer) {
    background-color: #f9fafb;
    border-top: 2px solid #e5e7eb;
}

.contribution-summary :deep(.p-datatable-thead > tr > th) {
    background-color: #1e40af;
    color: white;
    font-weight: 600;
    text-align: center;
}

.contribution-summary :deep(.p-column-frozen) {
    background-color: #f3f4f6;
}
</style>
