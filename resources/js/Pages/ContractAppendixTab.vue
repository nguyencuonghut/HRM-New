<template>
  <div class="card">
    <Toolbar class="mb-4">
      <template #start>
        <Button label="Thêm phụ lục" icon="pi pi-plus" class="mr-2" @click="openNew" />
        <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined" :disabled="!selected?.length" @click="confirmDeleteSelected" />
      </template>
      <template #end>
        <Button label="Xuất dữ liệu" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
      </template>
    </Toolbar>

    <DataTable ref="dt" :value="rows" v-model:selection="selected" dataKey="id" :paginator="true" :rows="10"
      :filters="filters" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      :rowsPerPageOptions="[5,10,25]" currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} phụ lục">
      <template #header>
        <div class="flex items-center justify-between gap-2">
          <h4 class="m-0">Danh sách Phụ lục</h4>
          <IconField><InputIcon><i class="pi pi-search" /></InputIcon><InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." /></IconField>
        </div>
      </template>

      <Column selectionMode="multiple" headerStyle="width:3rem" />
      <Column field="appendix_no" header="Số PL" sortable headerStyle="min-width:10rem;" />
      <Column field="appendix_type" header="Loại" headerStyle="min-width:10rem;" />
      <Column field="effective_date" header="Hiệu lực" headerStyle="min-width:10rem;">
        <template #body="sp">{{ formatDate(sp.data.effective_date) }}</template>
      </Column>
      <Column field="status" header="Trạng thái" headerStyle="min-width:10rem;">
        <template #body="sp"><Tag :value="sp.data.status" :severity="statusSeverity(sp.data.status)" /></template>
      </Column>
      <Column header="Thao tác" headerStyle="min-width:14rem;">
        <template #body="sp">
          <div class="flex gap-2">
            <Button icon="pi pi-pencil" outlined severity="success" rounded @click="edit(sp.data)" />
            <Button icon="pi pi-trash" outlined severity="danger" rounded @click="confirmDelete(sp.data)" />
            <Button v-if="sp.data.status==='PENDING_APPROVAL'" icon="pi pi-check" outlined severity="success" rounded @click="approve(sp.data)" />
            <Button v-if="sp.data.status==='PENDING_APPROVAL'" icon="pi pi-times" outlined severity="danger" rounded @click="reject(sp.data)" />
          </div>
        </template>
      </Column>
    </DataTable>
  </div>

  <!-- Dialog tạo/sửa phụ lục -->
  <Dialog v-model:visible="dialog" :style="{ width: '800px' }" header="Phụ lục hợp đồng" :modal="true">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div><label class="block font-bold mb-2">Số PL</label><InputText v-model.trim="form.appendix_no" class="w-full" /></div>
      <div>
        <label class="block font-bold mb-2">Loại</label>
        <Select v-model="form.appendix_type" :options="typeOptions" optionLabel="label" optionValue="value" showClear fluid />
      </div>
      <div><label class="block font-bold mb-2">Hiệu lực từ</label><DatePicker v-model="form.effective_date" dateFormat="yy-mm-dd" showIcon fluid /></div>
      <div><label class="block font-bold mb-2">Đến</label><DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" showIcon fluid /></div>

      <div><label class="block font-bold mb-2">Lương cơ bản</label><InputText type="number" v-model.number="form.base_salary" class="w-full" /></div>
      <div><label class="block font-bold mb-2">Lương BH</label><InputText type="number" v-model.number="form.insurance_salary" class="w-full" /></div>
      <div><label class="block font-bold mb-2">PC vị trí</label><InputText type="number" v-model.number="form.position_allowance" class="w-full" /></div>
      <div class="md:col-span-2"><label class="block font-bold mb-2">Thời gian làm việc</label><InputText v-model.trim="form.working_time" class="w-full" /></div>
      <div class="md:col-span-2"><label class="block font-bold mb-2">Địa điểm</label><InputText v-model.trim="form.work_location" class="w-full" /></div>

      <div class="md:col-span-2"><label class="block font-bold mb-2">Tóm tắt</label><Textarea v-model.trim="form.summary" autoResize rows="2" class="w-full" /></div>
      <div class="md:col-span-2"><label class="block font-bold mb-2">Ghi chú</label><Textarea v-model.trim="form.note" autoResize rows="3" class="w-full" /></div>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="closeDialog" />
      <Button label="Lưu" icon="pi pi-check" @click="save" :loading="saving" />
    </template>
  </Dialog>

  <!-- Dialog xác nhận xoá / xoá nhiều... giống các màn khác -->
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import { ContractAppendixService } from '@/services/ContractAppendixService'

const props = defineProps({ contractId: { type: String, required: true }, appendixes: { type: Array, default: () => [] } })
const rows = computed(()=> props.appendixes || [])
const dt = ref(); const selected = ref([]); const dialog = ref(false); const saving = ref(false)
const filters = ref({ global: { value: null, matchMode: 'contains' } })
const form = ref({
  id:null, appendix_no:'', appendix_type:null, effective_date:null, end_date:null,
  base_salary:null, insurance_salary:null, position_allowance:null, working_time:'', work_location:'',
  summary:'', note:''
})
const typeOptions = [
  {value:'SALARY',label:'Điều chỉnh lương'}, {value:'ALLOWANCE',label:'Điều chỉnh phụ cấp'},
  {value:'POSITION',label:'Điều chỉnh chức danh'}, {value:'DEPARTMENT',label:'Điều chuyển đơn vị'},
  {value:'WORKING_TERMS',label:'Thời gian/địa điểm làm việc'}, {value:'EXTENSION',label:'Gia hạn HĐ'},
  {value:'OTHER',label:'Khác'}
]
const statusSeverity = (s)=>({ DRAFT:'secondary', PENDING_APPROVAL:'warning', ACTIVE:'success', REJECTED:'danger', CANCELLED:'contrast' }[s]||'info')
const formatDate = (d)=> d ? new Date(d).toLocaleDateString('vi-VN') : ''
const exportCSV = ()=> dt.value?.exportCSV()

function openNew(){ reset(); dialog.value=true }
function edit(row){ form.value={...row}; dialog.value=true }
function reset(){ form.value={ id:null, appendix_no:'', appendix_type:null, effective_date:null, end_date:null, base_salary:null, insurance_salary:null, position_allowance:null, working_time:'', work_location:'', summary:'', note:'' } }
function closeDialog(){ dialog.value=false }

function save(){
  saving.value = true
  const payload = { ...form.value }
  const opts = { onFinish:()=> saving.value=false, onSuccess:()=> dialog.value=false }
  if (!form.value.id) ContractAppendixService.store(props.contractId, payload, opts)
  else ContractAppendixService.update(props.contractId, form.value.id, payload, opts)
}
function confirmDelete(row){ /* giống pattern Ward */ }
function confirmDeleteSelected(){ /* giống pattern Ward */ }
function approve(row){ ContractAppendixService.approve(props.contractId, row.id, {}) }
function reject(row){ ContractAppendixService.reject(props.contractId, row.id, {}) }
</script>
