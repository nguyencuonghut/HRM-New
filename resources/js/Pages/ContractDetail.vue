<template>
  <Head>
    <title>Hồ sơ Hợp đồng - {{ contract.contract_number }}</title>
  </Head>

  <div class="card">
    <div class="mb-4">
      <h2 class="text-xl font-semibold">
        Hợp đồng: {{ contract.contract_number }} - {{ contract.employee?.full_name }}
      </h2>
      <p class="text-sm text-gray-600">
        Loại: {{ contract.contract_type_label }} | Trạng thái: {{ contract.status_label }}
      </p>
    </div>

    <!-- Dùng v-model cho Tabs -->
    <Tabs :value="activeTabIndex">
      <TabList>
        <Tab :value="0">Thông tin chung</Tab>
        <Tab :value="1">Phụ lục</Tab>
      </TabList>

      <TabPanel :value="0">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><b>Nhân viên:</b> {{ contract.employee?.full_name }} ({{ contract.employee?.employee_code }})</div>
          <div><b>Đơn vị:</b> {{ contract.department?.name }}</div>
          <div><b>Chức danh:</b> {{ contract.position?.title }}</div>
          <div><b>Loại HĐ:</b> {{ contract.contract_type_label }}</div>
          <div><b>Bắt đầu:</b> {{ formatDate(contract.start_date) }}</div>
          <div><b>Kết thúc:</b> {{ formatDate(contract.end_date) || '—' }}</div>
        </div>
      </TabPanel>

      <TabPanel :value="1">
        <ContractAppendixTab
          :contract-id="contract.id"
          :appendixes="appendixes"
        />
      </TabPanel>
    </Tabs>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanel from 'primevue/tabpanel'
import ContractAppendixTab from './ContractAppendixTab.vue'
import { formatDate } from '@/utils/dateHelper'

const props = defineProps({
  contract:   { type: Object, required: true },
  appendixes: { type: Array,  default: () => [] },
  activeTab:  { type: String, default: 'general' } // nhận từ BE
})

const contract   = props.contract
const appendixes = props.appendixes

// Convert string tab to index
const activeTabIndex = computed(() => {
  const tab = props.activeTab || 'general'
  return tab === 'appendixes' ? 1 : 0
})
</script>
