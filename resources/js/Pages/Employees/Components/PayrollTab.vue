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
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';


const page = usePage();
const currentPayroll = computed(() => page.props.current_payroll);

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
  // PrimeVue Tabs v4: truyền sự kiện lên cha (EmployeeProfile)
  // Gọi custom event nếu PayrollTab được nhúng trong TabPanel
  // Sử dụng window eventBus hoặc emit nếu có setup, ở đây dùng dispatchEvent đơn giản:
  window.dispatchEvent(new CustomEvent('payroll-goto-contract-tab'));
}
</script>

<style scoped>
  .payroll-tab {
    padding: 1.5rem 1rem;
    max-width: 700px;
    margin: 0 auto;
  }
</style>
