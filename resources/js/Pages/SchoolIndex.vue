<template>
  <Head><title>Quản lý Trường</title></Head>

  <div>
    <div class="card">
      <Toolbar class="mb-6">
        <template #start>
          <Button label="Thêm mới" icon="pi pi-plus" class="mr-2" @click="openNew" />
          <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined"
                  @click="confirmDeleteSelected" :disabled="!selectedRows || !selectedRows.length" />
        </template>
        <template #end>
          <Button label="Xuất dữ liệu" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
        </template>
      </Toolbar>

      <DataTable
        ref="dt"
        :value="rows"
        v-model:selection="selectedRows"
        dataKey="id"
        :paginator="true"
        :rows="10"
        :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Hiển thị {first} đến {last} trong tổng số {totalRecords} trường"
      >
        <template #header>
          <div class="flex flex-wrap gap-2 items-center justify-between">
            <h4 class="m-0">Danh sách Trường</h4>
            <IconField>
              <InputIcon><i class="pi pi-search" /></InputIcon>
              <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
            </IconField>
          </div>
        </template>

        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
        <Column field="code" header="Mã" :sortable="true" headerStyle="min-width:10rem;">
          <template #body="sp">{{ sp.data.code }}</template>
        </Column>
        <Column field="name" header="Tên trường" :sortable="true" headerStyle="min-width:12rem;">
          <template #body="sp">{{ sp.data.name }}</template>
        </Column>
        <Column field="created_at" header="Ngày tạo" :sortable="true" headerStyle="min-width:12rem;">
          <template #body="sp">{{ formatDate(sp.data.created_at) }}</template>
        </Column>
        <Column headerStyle="min-width:10rem;">
          <template #body="sp">
            <Button icon="pi pi-pencil" class="mr-2" outlined severity="success" rounded @click="editRow(sp.data)" />
            <Button icon="pi pi-trash" class="mt-2" outlined severity="danger" rounded @click="confirmDelete(sp.data)" />
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="dialogVisible" :style="{ width: '450px' }" header="Thông tin Trường" :modal="true">
      <div class="flex flex-col gap-6">
        <div>
          <label class="block font-bold mb-3">Mã</label>
          <InputText v-model.trim="form.code" class="w-full" placeholder="Mã (tùy chọn)" />
        </div>
        <div>
          <label class="block font-bold mb-3 required-field">Tên trường</label>
          <InputText v-model.trim="form.name" autofocus :invalid="submitted && !form.name" class="w-full" />
          <small class="text-red-500" v-if="submitted && !form.name">Tên là bắt buộc.</small>
        </div>
      </div>
      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
        <Button label="Lưu" icon="pi pi-check" @click="save" />
      </template>
    </Dialog>

    <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span v-if="current">Bạn có chắc chắn muốn xóa <b>{{ current.name }}</b>?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="remove" />
      </template>
    </Dialog>

    <Dialog v-model:visible="deleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span>Bạn có chắc chắn muốn xóa các trường đã chọn?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteManyDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="removeMany" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { SchoolService } from '@/services'

const props = defineProps({
  schools: { type: Array, required: true }
})

const dt = ref()
const rows = computed(() => props.schools || [])
const selectedRows = ref([])
const dialogVisible = ref(false)
const deleteDialog = ref(false)
const deleteManyDialog = ref(false)
const current = ref(null)
const submitted = ref(false)

const filters = ref({ global: { value: null, matchMode: 'contains' } })

const form = ref({ id: null, code: '', name: '' })
const isEdit = () => !!form.value.id

function openNew(){ submitted.value=false; form.value={id:null, code:'', name:''}; dialogVisible.value=true }
function editRow(r){ submitted.value=false; form.value={id:r.id, code:r.code, name:r.name}; dialogVisible.value=true }
function hideDialog(){ dialogVisible.value=false }
function formatDate(s){ return s ? new Date(s).toLocaleDateString('vi-VN') : '' }

function save(){
  submitted.value = true
  if (!form.value.name) return
  const payload = { code: form.value.code, name: form.value.name }

  if (!isEdit()) {
    SchoolService.store(payload, { onSuccess: () => { dialogVisible.value=false } })
  } else {
    SchoolService.update(form.value.id, payload, { onSuccess: () => { dialogVisible.value=false } })
  }
}

function confirmDelete(r){ current.value=r; deleteDialog.value=true }
function remove(){ SchoolService.destroy(current.value.id, { onSuccess:()=>{ deleteDialog.value=false } }) }

function confirmDeleteSelected(){ deleteManyDialog.value = true }
function removeMany(){
  const ids = selectedRows.value.map(x=>x.id)
  SchoolService.bulkDelete(ids, { onSuccess:()=>{ deleteManyDialog.value=false; selectedRows.value=[] } })
}

function exportCSV(){ dt.value?.exportCSV() }
</script>

<style scoped>
.required-field::after{ content:' *'; color:red; }
</style>
