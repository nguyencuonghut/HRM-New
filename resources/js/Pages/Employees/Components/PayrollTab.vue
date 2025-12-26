<template>
  <div class="payroll-tab">
    <template v-if="currentPayroll">
      <!-- Header: Nguồn dữ liệu lương -->
      <div class="mb-4 bg-blue-50 border-l-4 border-blue-400 p-3 rounded flex items-center justify-between">
        <div>
          <div class="text-sm text-gray-600 flex items-center gap-2">
            <span class="font-bold text-blue-700">💡 Lương hiện tại được áp dụng theo:</span>
            <span class="font-semibold">HĐ số {{ currentPayroll.number }}</span>
            <span v-if="currentPayroll.title" class="text-gray-500">({{ currentPayroll.title }})</span>
            <span class="inline-flex items-center gap-1 ml-2">
              <span v-if="isActive" class="inline-flex items-center px-2 py-0.5 rounded bg-green-200 text-green-700 text-xs font-semibold">
                ⏳ Hiệu lực từ: {{ currentPayroll.effective_date }}
              </span>
              <span v-else class="inline-flex items-center px-2 py-0.5 rounded bg-yellow-100 text-yellow-800 text-xs font-semibold">
                ⏳ Hiệu lực từ: {{ currentPayroll.effective_date }}
              </span>
            </span>
          </div>
        </div>
        <button type="button" @click="goToContractTab" class="text-blue-600 hover:underline text-sm font-medium">Xem hợp đồng</button>
      </div>

      <!-- Card: Cấu trúc lương hiện tại -->
      <div class="bg-white rounded shadow p-6 mb-6 border">
        <h4 class="font-semibold text-base mb-4">Cấu trúc lương hiện tại</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <div class="text-xs text-gray-500 font-semibold mb-1 mt-2">LƯƠNG CHÍNH</div>
            <div class="mb-2 flex justify-between">
              <span class="text-gray-600">Lương cơ bản:</span>
              <span class="font-bold">{{ formatCurrency(currentPayroll.base_salary) }}</span>
            </div>
            <div class="mb-2 flex justify-between">
              <span class="text-gray-600">Phụ cấp chức vụ:</span>
              <span class="font-bold">{{ formatCurrency(currentPayroll.position_allowance) }}</span>
            </div>
            <div class="border-t my-2"></div>
            <div class="text-xs text-gray-500 font-semibold mb-1 mt-2">BẢO HIỂM</div>
            <div class="mb-2 flex justify-between">
              <span class="text-gray-600">Lương đóng BHXH:</span>
              <span class="font-bold">{{ formatCurrency(currentPayroll.insurance_salary) }}</span>
            </div>
          </div>
          <div>
            <div class="text-xs text-gray-500 font-semibold mb-1 mt-2">THU NHẬP</div>
            <div class="mb-2 flex justify-between">
              <span class="text-gray-600">Tổng thu nhập theo hợp đồng:</span>
              <span class="font-bold text-green-700">{{ formatCurrency(totalIncome) }}</span>
            </div>
            <div class="mb-2">
              <span class="text-gray-600">Phụ cấp khác:</span>
              <div v-if="otherAllowancesArr.length" class="mt-1">
                <table class="min-w-full text-xs border">
                  <tbody>
                    <tr v-for="(item, idx) in otherAllowancesArr" :key="idx">
                      <td class="px-2 py-1 text-gray-700">{{ item.label || 'Phụ cấp khác' }}</td>
                      <td class="px-2 py-1 text-right">{{ formatCurrency(item.value) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="text-gray-400 italic">Chưa có phụ cấp khác</div>
            </div>

          </div>
        </div>
      </div>
      <!-- Card phụ: Bảo hiểm & điều kiện làm việc -->
      <div class="bg-gray-50 rounded shadow p-6 border mb-6">
        <h4 class="font-semibold text-base mb-4">Bảo hiểm & điều kiện làm việc</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <div class="mb-2 flex justify-between">
              <span class="text-gray-600">BHXH:</span>
              <span>{{ currentPayroll.social_insurance ? 'Có' : 'Không' }}</span>
            </div>
            <div class="mb-2 flex justify-between">
              <span class="text-gray-600">BHYT:</span>
              <span>{{ currentPayroll.health_insurance ? 'Có' : 'Không' }}</span>
            </div>
            <div class="mb-2 flex justify-between">
              <span class="text-gray-600">BHTN:</span>
              <span>{{ currentPayroll.unemployment_insurance ? 'Có' : 'Không' }}</span>
            </div>
          </div>
          <div>
            <div class="mb-2 flex justify-between">
              <span class="text-gray-600">Thời gian làm việc:</span>
              <span>{{ currentPayroll.working_time || '-' }}</span>
            </div>
            <div class="mb-2 flex justify-between">
              <span class="text-gray-600">Địa điểm làm việc:</span>
              <span>{{ currentPayroll.work_location || '-' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ========== CARD: Lương BHXH Theo Thang-Bậc-Hệ Số ========== -->
      <div v-if="insuranceData && insuranceData.has_profile" class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded shadow-lg p-6 border-2 border-blue-200 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h4 class="font-bold text-lg text-blue-900 flex items-center gap-2">
            <i class="pi pi-shield text-blue-600"></i>
            Lương BHXH Theo Thang-Bậc-Hệ Số
          </h4>
          <span class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded-full">{{ insuranceData.region_name }}</span>
        </div>

        <!-- Thông tin chính -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <!-- Cột trái: Thông tin bậc -->
          <div class="bg-white rounded-lg p-4 shadow">
            <div class="text-xs text-gray-500 font-bold mb-3 uppercase">Thông tin bậc lương</div>

            <div class="mb-3 flex justify-between items-center">
              <span class="text-gray-600 text-sm">Vị trí:</span>
              <span class="font-semibold text-gray-900">{{ insuranceData.position || '-' }}</span>
            </div>

            <div class="mb-3 flex justify-between items-center">
              <span class="text-gray-600 text-sm">Bậc hiện tại:</span>
              <span class="px-3 py-1 bg-indigo-100 text-indigo-800 font-bold rounded-lg text-lg">
                Bậc {{ insuranceData.grade }}/7
              </span>
            </div>

            <div class="mb-3 flex justify-between items-center">
              <span class="text-gray-600 text-sm">Hệ số:</span>
              <span class="font-bold text-blue-700 text-lg">{{ insuranceData.coefficient }}</span>
            </div>

            <div class="mb-3 flex justify-between items-center">
              <span class="text-gray-600 text-sm">Áp dụng từ:</span>
              <span class="text-gray-900 font-medium">{{ insuranceData.applied_from }}</span>
            </div>
          </div>

          <!-- Cột phải: Tính toán lương -->
          <div class="bg-white rounded-lg p-4 shadow">
            <div class="text-xs text-gray-500 font-bold mb-3 uppercase">Tính toán lương BHXH</div>

            <div class="mb-3 pb-3 border-b border-gray-200">
              <div class="flex justify-between items-center mb-1">
                <span class="text-gray-600 text-sm">Lương tối thiểu vùng:</span>
                <span class="font-semibold text-gray-900">{{ insuranceData.minimum_wage_formatted }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Hệ số bậc {{ insuranceData.grade }}:</span>
                <span class="font-semibold text-blue-700">× {{ insuranceData.coefficient }}</span>
              </div>
            </div>

            <div class="flex justify-between items-center mb-2">
              <span class="text-gray-700 font-semibold">Lương BHXH:</span>
              <span class="text-2xl font-bold text-green-600">{{ insuranceData.amount_formatted }}</span>
            </div>

            <div class="text-xs text-gray-500 text-right">
              {{ insuranceData.formula }}
            </div>
          </div>
        </div>

        <!-- Đề xuất tăng bậc (nếu có) -->
        <div v-if="insuranceData.suggestion && insuranceData.suggestion.eligible"
             class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded mb-4">
          <div class="flex items-start gap-3">
            <i class="pi pi-info-circle text-yellow-600 text-xl mt-0.5"></i>
            <div class="flex-1">
              <div class="font-bold text-yellow-800 mb-1">
                Đề xuất tăng bậc
              </div>
              <div class="text-sm text-yellow-700 mb-2">
                Nhân viên đã có <strong>{{ insuranceData.suggestion.tenure_years }} năm</strong> thâm niên tại vị trí hiện tại.
                Đủ điều kiện tăng từ <strong>Bậc {{ insuranceData.suggestion.current_grade }}</strong>
                lên <strong>Bậc {{ insuranceData.suggestion.suggested_grade }}</strong>.
              </div>
              <button @click="handleCreateGradeRaiseAppendix"
                      class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold rounded shadow transition">
                <i class="pi pi-file-plus mr-2"></i>
                Tạo phụ lục tăng bậc
              </button>
            </div>
          </div>
        </div>

        <!-- Lịch sử thay đổi bậc (collapsible) -->
        <div v-if="insuranceHistory && insuranceHistory.length > 1" class="mt-4">
          <button @click="showHistory = !showHistory"
                  class="flex items-center gap-2 text-blue-700 hover:text-blue-900 font-semibold text-sm">
            <i :class="showHistory ? 'pi pi-chevron-down' : 'pi pi-chevron-right'"></i>
            Lịch sử thay đổi bậc ({{ insuranceHistory.length }} lần)
          </button>

          <div v-if="showHistory" class="mt-3 bg-white rounded-lg p-4 shadow">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b">
                  <th class="text-left py-2 px-2 text-gray-600 font-semibold">Thời gian</th>
                  <th class="text-left py-2 px-2 text-gray-600 font-semibold">Vị trí</th>
                  <th class="text-center py-2 px-2 text-gray-600 font-semibold">Bậc</th>
                  <th class="text-left py-2 px-2 text-gray-600 font-semibold">Lý do</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="record in insuranceHistory" :key="record.id" class="border-b hover:bg-gray-50">
                  <td class="py-2 px-2 text-gray-800">{{ record.period }}</td>
                  <td class="py-2 px-2 text-gray-800">{{ record.position || '-' }}</td>
                  <td class="py-2 px-2 text-center">
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 font-bold rounded">{{ record.grade }}</span>
                  </td>
                  <td class="py-2 px-2 text-gray-600 text-xs">{{ record.reason_display }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Thông báo: Chưa có hồ sơ BHXH -->
      <div v-else-if="insuranceData && !insuranceData.has_profile"
           class="bg-orange-50 border-l-4 border-orange-400 p-4 rounded mb-6 flex items-center gap-3">
        <i class="pi pi-exclamation-triangle text-orange-600 text-2xl"></i>
        <div class="flex-1">
          <div class="font-bold text-orange-800 mb-1">Chưa khởi tạo hồ sơ BHXH</div>
          <div class="text-sm text-orange-700">
            Nhân viên chưa có hồ sơ bậc lương BHXH. Vui lòng khởi tạo để áp dụng hệ thống thang-bậc-hệ số.
          </div>
        </div>
        <button @click="handleInitializeInsurance"
                class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded shadow transition">
          <i class="pi pi-plus mr-2"></i>
          Khởi tạo
        </button>
      </div>
    </template>
    <template v-else>
      <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <i class="pi pi-exclamation-triangle text-yellow-600 text-2xl"></i>
          <span class="text-yellow-800 font-semibold">Nhân viên hiện chưa có hợp đồng hiệu lực. Không thể xác định thông tin lương.</span>
        </div>
        <button type="button" @click="goToContractTab" class="text-blue-600 hover:underline text-sm font-medium">Chuyển sang tab Hợp đồng</button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';


const page = usePage();
const currentPayroll = computed(() => page.props.current_payroll);
const insuranceData = computed(() => page.props.insurance_data);
const insuranceHistory = computed(() => page.props.insurance_history);

const showHistory = ref(false);

// Xác định trạng thái hiệu lực (active = ngày hiệu lực <= hôm nay)
const isActive = computed(() => {
  if (!currentPayroll.value) return false;
  const today = new Date();
  const eff = new Date(currentPayroll.value.effective_date);
  return eff <= today;
});

// Phụ cấp khác: dạng array [{label, value}] hoặc object
const otherAllowancesArr = computed(() => {
  const raw = currentPayroll.value?.other_allowances;
  if (!raw) return [];
  if (Array.isArray(raw)) return raw.filter(x => x && x.value).map(x => ({ label: x.label, value: x.value }));
  if (typeof raw === 'object') {
    return Object.entries(raw).map(([label, value]) => ({ label, value }));
  }
  return [];
});

const totalIncome = computed(() => {
  const c = currentPayroll.value;
  if (!c) return 0;
  let total = 0;
  total += Number(c.base_salary || 0);
  total += Number(c.insurance_salary || 0);
  total += Number(c.position_allowance || 0);
  if (Array.isArray(c.other_allowances)) {
    total += c.other_allowances.reduce((sum, x) => sum + Number(x.value || 0), 0);
  } else if (typeof c.other_allowances === 'object' && c.other_allowances !== null) {
    total += Object.values(c.other_allowances).reduce((sum, v) => sum + Number(v || 0), 0);
  }
  return total;
});

function formatCurrency(value) {
  if (value == null || value === '') return '-';
  return Number(value).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
}

// Chuyển tab cha sang 'contracts' khi click
function goToContractTab() {
  window.dispatchEvent(new CustomEvent('payroll-goto-contract-tab'));
}

// Handler: Tạo phụ lục tăng bậc (TODO: implement)
function handleCreateGradeRaiseAppendix() {
  alert('Chức năng "Tạo phụ lục tăng bậc" đang được phát triển...');
  // TODO: Mở modal hoặc navigate đến form tạo appendix với prefill data
}

// Handler: Khởi tạo hồ sơ BHXH (TODO: implement)
function handleInitializeInsurance() {
  alert('Chức năng "Khởi tạo hồ sơ BHXH" đang được phát triển...');
  // TODO: Mở modal hoặc API call để khởi tạo insurance profile
}
</script>

<style scoped>
  .payroll-tab {
    padding: 1.5rem 1rem;
    max-width: 700px;
    margin: 0 auto;
  }
</style>
