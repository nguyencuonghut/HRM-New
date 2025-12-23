<template>
  <div>
    <Toolbar class="mb-4">
      <template #start>
        <Button label="Thêm kỹ năng" icon="pi pi-plus" class="mr-2" @click="openNew" />
        <Button
          label="Xóa"
          icon="pi pi-trash"
          severity="danger"
          variant="outlined"
          @click="confirmDeleteSelected"
          :disabled="!selected || !selected.length"
        />
      </template>
      <template #end>
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">Nhóm:</span>
          <Select
            v-model="selectedCategory"
            :options="categoryOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Tất cả nhóm"
            showClear
            class="w-56"
          />
        </div>
      </template>
    </Toolbar>

    <DataTable
      :value="filteredRows"
      v-model:selection="selected"
      dataKey="id"
      :paginator="false"
      :rowGroupMode="'subheader'"
      :groupRowsBy="'category_name'"
      :sortField="'category_order'"
      :sortOrder="1"
    >
      <template #groupheader="slotProps">
        <div class="flex items-center gap-3 py-2">
          <span class="font-semibold text-lg">{{ slotProps.data.category_name }}</span>
          <Badge :value="getCategoryCount(slotProps.data.category_name)" severity="secondary" />
        </div>
      </template>

      <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
      <Column field="skill_name" header="Kỹ năng" headerStyle="min-width:14rem;"></Column>
      <Column field="level" header="Mức (0-5)" headerStyle="min-width:12rem;">
        <template #body="slotProps">
          <div class="flex items-center gap-2">
            <span>{{ slotProps.data.level }}</span>
            <Rating :modelValue="slotProps.data.level" :cancel="false" readonly />
          </div>
        </template>
      </Column>
      <Column field="years" header="Số năm" headerStyle="min-width:8rem;"></Column>
      <Column field="note" header="Ghi chú" headerStyle="min-width:12rem;"></Column>
      <Column headerStyle="min-width:10rem;">
        <template #body="slotProps">
          <Button
            icon="pi pi-pencil"
            class="mr-2"
            outlined
            severity="success"
            rounded
            @click="openEdit(slotProps.data)"
          />
          <Button
            icon="pi pi-trash"
            class="mt-2"
            outlined
            severity="danger"
            rounded
            @click="confirmDelete(slotProps.data)"
          />
        </template>
      </Column>

      <template #empty>
        <div class="text-center p-8">
          <i class="pi pi-info-circle text-4xl text-gray-400 mb-3"></i>
          <p class="text-gray-600">Chưa có kỹ năng nào</p>
        </div>
      </template>
    </DataTable>

    <!-- Dialog Kỹ năng -->
    <Dialog v-model:visible="dialog" :style="{ width: '600px' }" header="Thông tin Kỹ năng" :modal="true">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-bold mb-2">Nhóm kỹ năng</label>
          <Select
            v-model="formCategoryFilter"
            :options="skillCategories"
            optionLabel="name"
            optionValue="id"
            placeholder="Chọn nhóm để lọc..."
            showClear
            filter
            fluid
          />
        </div>
        <div>
          <label class="block font-bold mb-2">Kỹ năng <span class="text-red-500">*</span></label>
          <Select
            v-model="form.skill_id"
            :options="filteredSkillsForForm"
            optionLabel="name"
            optionValue="id"
            placeholder="Chọn kỹ năng..."
            showClear
            filter
            fluid
          />
          <small class="text-gray-500">{{ filteredSkillsForForm.length }} kỹ năng khả dụng</small>
        </div>
        <div>
          <label class="block font-bold mb-2">Mức (0-5)</label>
          <InputText v-model.number="form.level" type="number" min="0" max="5" class="w-full" />
        </div>
        <div>
          <label class="block font-bold mb-2">Số năm</label>
          <InputText v-model.number="form.years" type="number" min="0" class="w-full" />
        </div>
        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Ghi chú</label>
          <Textarea v-model="form.note" autoResize rows="3" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="dialog=false" />
        <Button label="Lưu" icon="pi pi-check" @click="save" :loading="saving" />
      </template>
    </Dialog>

    <!-- Dialog xóa -->
    <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span>Bạn có chắc muốn xóa kỹ năng này?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="remove" :loading="deleting" />
      </template>
    </Dialog>

    <!-- Xóa nhiều -->
    <Dialog v-model:visible="deleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span>Bạn có chắc muốn xóa các kỹ năng đã chọn?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteManyDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="removeMany" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { EmployeeSkillService } from '@/services'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toolbar from 'primevue/toolbar'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Badge from 'primevue/badge'
import Rating from 'primevue/rating'

const props = defineProps({
  employeeId: { type: String, required: true },
  employeeSkills: { type: Array, default: () => [] },
  skills: { type: Array, default: () => [] },
  skillCategories: { type: Array, default: () => [] },
})

const selected = ref([])
const selectedCategory = ref(null)
const formCategoryFilter = ref(null)

const dialog = ref(false)
const deleteDialog = ref(false)
const deleteManyDialog = ref(false)
const currentItem = ref(null)
const saving = ref(false)
const deleting = ref(false)

const form = ref({
  id: null,
  skill_id: null,
  level: 0,
  years: 0,
  note: ''
})

// Category options for filter
const categoryOptions = computed(() => {
  return props.skillCategories.map(cat => ({
    label: cat.name,
    value: cat.id
  }))
})

// Enhanced skill rows with category info
const enhancedRows = computed(() => {
  return props.employeeSkills.map(empSkill => {
    const skill = props.skills.find(s => s.id === empSkill.skill_id)
    const category = skill?.category
    const categoryName = category?.name || 'Chưa phân loại'
    const categoryOrder = category?.order_index || 999

    return {
      ...empSkill,
      skill_name: skill?.name || empSkill.skill_id,
      category_name: categoryName,
      category_order: categoryOrder,
      category_id: category?.id || null
    }
  }).sort((a, b) => {
    if (a.category_order !== b.category_order) return a.category_order - b.category_order
    return (a.skill_name || '').localeCompare(b.skill_name || '')
  })
})

// Filtered rows based on selected category
const filteredRows = computed(() => {
  if (!selectedCategory.value) return enhancedRows.value
  return enhancedRows.value.filter(row => row.category_id === selectedCategory.value)
})

// Get count per category
const getCategoryCount = (categoryName) => {
  return filteredRows.value.filter(row => row.category_name === categoryName).length
}

// Filtered skills for form dropdown
const filteredSkillsForForm = computed(() => {
  if (!formCategoryFilter.value) return props.skills
  return props.skills.filter(s => s.category_id === formCategoryFilter.value)
})

function resetForm() {
  form.value = {
    id: null,
    skill_id: null,
    level: 0,
    years: 0,
    note: ''
  }
}

function openNew() {
  resetForm()
  formCategoryFilter.value = null
  dialog.value = true
}

function openEdit(row) {
  const skill = props.skills.find(s => s.id === row.skill_id)
  formCategoryFilter.value = skill?.category_id || null

  form.value = {
    id: row.id,
    skill_id: row.skill_id,
    level: row.level,
    years: row.years,
    note: row.note
  }
  dialog.value = true
}

function save() {
  saving.value = true
  const opts = {
    onFinish: () => saving.value = false,
    onSuccess: () => { dialog.value = false }
  }
  if (!form.value.id) {
    EmployeeSkillService.store(props.employeeId, form.value, opts)
  } else {
    EmployeeSkillService.update(props.employeeId, form.value.id, form.value, opts)
  }
}

function confirmDelete(row) {
  currentItem.value = row
  deleteDialog.value = true
}

function remove() {
  deleting.value = true
  EmployeeSkillService.destroy(props.employeeId, currentItem.value.id, {
    onFinish: () => {
      deleting.value = false
      deleteDialog.value = false
    }
  })
}

function confirmDeleteSelected() {
  deleteManyDialog.value = true
}

function removeMany() {
  const ids = selected.value.map(x => x.id)
  deleting.value = true
  EmployeeSkillService.bulkDelete(props.employeeId, ids, {
    onFinish: () => {
      deleting.value = false
      deleteManyDialog.value = false
      selected.value = []
    }
  })
}
</script>
