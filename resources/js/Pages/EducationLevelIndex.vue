<template>
  <Head><title>Quản lý Trình độ học vấn</title></Head>

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
        currentPageReportTemplate="Hiển thị {first} đến {last} trong tổng số {totalRecords} trình độ"
      >
        <template #header>
          <div class="flex flex-wrap gap-2 items-center justify-between">
            <h4 class="m-0">Danh sách Trình độ học vấn</h4>
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
        <Column field="name" header="Tên trình độ" :sortable="true" headerStyle="min-width:12rem;">
          <template #body="sp">{{ sp.data.name }}</template>
        </Column>
        <Column field="order_index" header="Thứ tự" :sortable="true" headerStyle="width:10rem;">
          <template #body="sp">{{ sp.data.order_index }}</template>
        </Column>
        <Column headerStyle="min-width:10rem;">
          <template #body="sp">
            <Button icon="pi pi-pencil" class="mr-2" outlined severity="success" rounded @click="editRow(sp.data)" />
            <Button icon="pi pi-trash" class="mt-2" outlined severity="danger" rounded @click="confirmDelete(sp.data)" />
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="dialogVisible" :style="{ width: '450px' }" header="Thông tin Trình độ" :modal="true">
      <div class="flex flex-col gap-6">
        <div>
          <label class="block font-bold mb-3">Mã</label>
          <InputText v-model="form.code" class="w-full" placeholder="Mã (tùy chọn)" />
        </div>
        <div>
          <label class="block font-bold mb-3 required-field">Tên trình độ</label>
          <InputText v-model="form.name" autofocus :invalid="submitted && !form.name" class="w-full" />
          <small class="text-red-500" v-if="submitted && !form.name">Tên là bắt buộc.</small>
        </div>
        <div>
          <label class="block font-bold mb-3">Thứ tự</label>
          <InputText v-model.number="form.order_index" class="w-full" placeholder="VD: 0,1,2..." />
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
        <span>Bạn có chắc chắn muốn xóa các trình độ đã chọn?</span>
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
import { EducationLevelService } from '@/services'

const props = defineProps({
  education_levels: { type: Array, required: true }
})

const dt = ref()
const rows = computed(() => props.education_levels || [])
const selectedRows = ref([])
const dialogVisible = ref(false)
const deleteDialog = ref(false)
const deleteManyDialog = ref(false)
const current = ref(null)
const submitted = ref(false)

const filters = ref({ global: { value: null, matchMode: 'contains' } })

const form = ref({ id: null, code: '', name: '', order_index: 0 })
const isEdit = () => !!form.value.id

function openNew() {
  submitted.value = false
  form.value = { id: null, code: '', name: '', order_index: 0 }
  dialogVisible.value = true
}
function editRow(r) {
  submitted.value = false
  form.value = { id: r.id, code: r.code, name: r.name, order_index: r.order_index ?? 0 }
  dialogVisible.value = true
}
function hideDialog(){ dialogVisible.value = false }

function save() {
  submitted.value = true
  if (!form.value.name) return
  const payload = { code: form.value.code, name: form.value.name, order_index: form.value.order_index ?? 0 }

  if (!isEdit()) {
    EducationLevelService.store(payload, { onSuccess: () => { dialogVisible.value=false } })
  } else {
    EducationLevelService.update(form.value.id, payload, { onSuccess: () => { dialogVisible.value=false } })
  }
}

function confirmDelete(r){ current.value = r; deleteDialog.value = true }
function remove(){
  EducationLevelService.destroy(current.value.id, { onSuccess: () => { deleteDialog.value=false } })
}

function confirmDeleteSelected(){ deleteManyDialog.value = true }
function removeMany(){
  const ids = selectedRows.value.map(x=>x.id)
  EducationLevelService.bulkDelete(ids, { onSuccess: () => { deleteManyDialog.value=false; selectedRows.value = [] } })
}

function exportCSV(){ dt.value?.exportCSV() }
</script>

<style scoped>
.required-field::after{ content:' *'; color:red; }
</style>
