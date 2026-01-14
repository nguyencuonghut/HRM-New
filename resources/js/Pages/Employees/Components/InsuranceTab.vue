<template>
  <div class="insurance-tab">
    <!-- Header -->
    <div class="mb-6">
      <h3 class="text-xl font-bold text-gray-800 mb-2 flex items-center gap-2">
        <i class="pi pi-shield text-blue-600"></i>
        Bảo hiểm xã hội
      </h3>
      <p class="text-sm text-gray-600">Thông tin tham gia BHXH, BHYT, BHTN của nhân viên</p>
    </div>

    <!-- Current Participation -->
    <template v-if="currentParticipation">
      <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg shadow-lg p-6 border-2 border-blue-200 mb-6">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-4">
          <h4 class="font-bold text-lg text-blue-900 flex items-center gap-2">
            <i class="pi pi-shield text-blue-600"></i>
            Tham gia BHXH hiện tại
          </h4>
          <Tag :value="currentParticipation.status_label" :severity="currentParticipation.status === 'ACTIVE' ? 'success' : 'secondary'" />
        </div>

        <!-- Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="bg-white rounded-lg p-4 shadow">
            <div class="text-xs text-gray-500 font-bold mb-2 uppercase">Hợp đồng</div>
            <div class="font-semibold text-gray-900">{{ currentParticipation.contract_number }}</div>
          </div>
          <div class="bg-white rounded-lg p-4 shadow">
            <div class="text-xs text-gray-500 font-bold mb-2 uppercase">Lương đóng BH</div>
            <div class="font-bold text-blue-700 text-lg">{{ currentParticipation.insurance_salary_formatted }}</div>
          </div>
          <div class="bg-white rounded-lg p-4 shadow">
            <div class="text-xs text-gray-500 font-bold mb-2 uppercase">Thời gian hiệu lực</div>
            <div class="text-sm text-gray-900">
              {{ currentParticipation.participation_start_date }}
              <span v-if="currentParticipation.participation_end_date"> → {{ currentParticipation.participation_end_date }}</span>
              <span v-else class="text-green-600 font-semibold"> → Hiện tại</span>
            </div>
          </div>
        </div>

        <!-- Components Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b">
            <h5 class="font-semibold text-gray-700">Các thành phần tham gia ({{ currentParticipation.components?.length || 0 }})</h5>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Thành phần</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Tổng tỷ lệ</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Mức đóng</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Số tiền</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="comp in currentParticipation.components" :key="comp.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3">
                    <div class="font-medium text-gray-900">{{ comp.component_name }}</div>
                    <div v-if="comp.component_code === 'UNEMPLOYMENT' && comp.base_type === 'FIXED_AMOUNT'"
                         class="text-xs text-orange-600 mt-1">
                      <i class="pi pi-info-circle"></i> Mức cố định
                    </div>
                  </td>
                  <td class="px-4 py-3 text-right text-indigo-700 font-bold">{{ comp.rate_total_formatted }}</td>
                  <td class="px-4 py-3 text-right text-sm">
                    <span v-if="comp.base_type === 'FIXED_AMOUNT'" class="text-orange-600">
                      {{ comp.base_used_formatted }}
                    </span>
                    <span v-else class="text-gray-700">
                      {{ currentParticipation.insurance_salary_formatted }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-right text-blue-700 font-semibold">{{ comp.amount_total_formatted }}</td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50">
                <tr>
                  <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-700">Tổng cộng:</td>
                  <td class="px-4 py-3 text-right font-bold text-blue-800">
                    {{ formatCurrency(totalAmount) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </template>

    <!-- No Current Participation -->
    <template v-else>
      <div v-if="currentParticipation && currentParticipation.is_future" class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded-lg mb-6">
        <div class="flex items-start gap-4">
          <i class="pi pi-info-circle text-blue-600 text-3xl"></i>
          <div class="flex-1">
            <h4 class="font-bold text-blue-800 mb-2">Sắp tham gia BHXH</h4>
            <p class="text-sm text-blue-700 mb-3">
              Nhân viên sẽ bắt đầu tham gia BHXH từ ngày {{ currentParticipation.future_start_date }} theo hợp đồng mới.
            </p>
          </div>
        </div>
      </div>
      <div v-else class="bg-orange-50 border-l-4 border-orange-400 p-6 rounded-lg mb-6">
        <div class="flex items-start gap-4">
          <i class="pi pi-exclamation-triangle text-orange-600 text-3xl"></i>
          <div class="flex-1">
            <h4 class="font-bold text-orange-800 mb-2">Chưa tham gia BHXH</h4>
            <p class="text-sm text-orange-700 mb-3">
              Nhân viên chưa có thông tin tham gia BHXH hiện tại. Điều này có thể do:
            </p>
            <ul class="text-sm text-orange-700 list-disc list-inside space-y-1">
              <li>Chưa có hợp đồng lao động hiệu lực</li>
              <li>Hợp đồng không có thông tin BHXH</li>
              <li>Đã kết thúc tham gia BHXH</li>
            </ul>
          </div>
        </div>
      </div>
    </template>

    <!-- Participation History -->
    <div v-if="participationHistory && participationHistory.length > 0" class="mt-8">
      <div class="flex items-center justify-between mb-4">
        <h4 class="font-bold text-lg text-gray-800 flex items-center gap-2">
          <i class="pi pi-history text-gray-600"></i>
          Lịch sử tham gia BHXH
        </h4>
        <Tag :value="`${participationHistory.length} lần`" severity="info" />
      </div>

      <DataTable :value="participationHistory" stripedRows paginator :rows="5" class="text-sm">
        <Column field="participation_start_date" header="Thời gian" style="width: 200px">
          <template #body="slotProps">
            <div class="text-sm">
              <div class="font-medium">{{ slotProps.data.participation_start_date }}</div>
              <div class="text-xs text-gray-500">
                <span v-if="slotProps.data.participation_end_date">→ {{ slotProps.data.participation_end_date }}</span>
                <span v-else class="text-green-600">→ Hiện tại</span>
              </div>
            </div>
          </template>
        </Column>
        <Column field="contract_number" header="Hợp đồng" style="width: 150px" />
        <Column field="insurance_salary_formatted" header="Lương BH" style="width: 150px">
          <template #body="slotProps">
            <span class="font-semibold text-blue-700">{{ slotProps.data.insurance_salary_formatted }}</span>
          </template>
        </Column>
        <Column header="Thành phần" style="width: 250px">
          <template #body="slotProps">
            <div class="flex flex-wrap gap-1">
              <Tag v-for="comp in slotProps.data.components" :key="comp.id"
                   :value="comp.component_name"
                   severity="info"
                   class="text-xs" />
            </div>
          </template>
        </Column>
        <Column field="status_label" header="Trạng thái" style="width: 120px">
          <template #body="slotProps">
            <Tag :value="slotProps.data.status_label"
                 :severity="slotProps.data.status === 'ACTIVE' ? 'success' : 'secondary'" />
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Link to Insurance Reports -->
    <div v-if="canViewReports" class="mt-8 p-6 bg-gray-50 border border-gray-200 rounded-lg">
      <div class="flex items-center justify-between">
        <div>
          <h5 class="font-semibold text-gray-800 mb-1 flex items-center gap-2">
            <i class="pi pi-file-edit text-blue-600"></i>
            Báo cáo BHXH hàng tháng
          </h5>
          <p class="text-sm text-gray-600">Xem các báo cáo TĂNG/GIẢM/ĐIỀU CHỈNH có liên quan đến nhân viên này</p>
        </div>
        <a :href="route('insurance-reports.index')"
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition flex items-center gap-2">
          <i class="pi pi-external-link"></i>
          Xem báo cáo
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const page = usePage();
const { can } = usePermissions();

const props = defineProps({
  currentParticipation: {
    type: Object,
    default: null
  },
  participationHistory: {
    type: Array,
    default: () => []
  }
});

const canViewReports = computed(() => can('view insurance reports'));

const totalAmount = computed(() => {
  if (!props.currentParticipation?.components) return 0;
  return props.currentParticipation.components.reduce((sum, comp) => sum + comp.amount_total, 0);
});

function formatCurrency(value) {
  if (value == null || value === '') return '-';
  return Number(value).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
}

function route(name) {
  if (name === 'insurance-reports.index') return '/insurance-reports';
  // Add more named routes here if needed
  return '/';
}
</script>

<style scoped>
.insurance-tab {
  padding: 1.5rem 1rem;
  max-width: 1200px;
  margin: 0 auto;
}
</style>
