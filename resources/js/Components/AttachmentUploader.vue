<template>
  <div class="attachment-uploader">
    <div class="flex items-center justify-between mb-3">
      <label class="block font-bold">Tệp đính kèm</label>
      <Button
        v-if="!readonly"
        label="Chọn file"
        icon="pi pi-upload"
        size="small"
        outlined
        @click="fileInput.click()"
      />
    </div>

    <input
      ref="fileInput"
      type="file"
      multiple
      class="hidden"
      @change="handleFileSelect"
      accept="*/*"
    />

    <!-- Existing attachments (when editing) -->
    <div v-if="existingFiles.length > 0" class="mb-3">
      <div class="text-sm font-semibold mb-2 text-gray-600">File hiện có:</div>
      <div class="space-y-2">
        <div
          v-for="file in existingFiles"
          :key="file.id"
          class="flex items-center gap-3 p-2 border rounded hover:bg-gray-50"
        >
          <i :class="getFileIcon(file.mime_type)" class="text-xl"></i>
          <div class="flex-1">
            <a
              :href="file.download_url"
              target="_blank"
              class="text-primary hover:underline text-sm font-medium"
            >
              {{ file.file_name }}
            </a>
            <div class="text-xs text-gray-500">
              {{ formatFileSize(file.file_size) }} • {{ formatDate(file.created_at) }}
            </div>
          </div>
          <Checkbox
            v-if="!readonly"
            v-model="deleteIds"
            :value="file.id"
            binary
            v-tooltip.top="'Xóa file này'"
          />
        </div>
      </div>
    </div>

    <!-- New files to upload -->
    <div v-if="newFiles.length > 0" class="mb-3">
      <div class="text-sm font-semibold mb-2 text-gray-600">File mới:</div>
      <div class="space-y-2">
        <div
          v-for="(file, index) in newFiles"
          :key="index"
          class="flex items-center gap-3 p-2 border rounded bg-blue-50"
        >
          <i :class="getFileIcon(file.type)" class="text-xl text-blue-600"></i>
          <div class="flex-1">
            <div class="text-sm font-medium">{{ file.name }}</div>
            <div class="text-xs text-gray-600">{{ formatFileSize(file.size) }}</div>
          </div>
          <Button
            icon="pi pi-times"
            text
            rounded
            size="small"
            severity="danger"
            @click="removeNewFile(index)"
          />
        </div>
      </div>
    </div>

    <div v-if="!existingFiles.length && !newFiles.length" class="text-center py-4 text-gray-400 border-2 border-dashed rounded">
      <i class="pi pi-paperclip text-2xl mb-2"></i>
      <p class="text-sm">Chưa có file đính kèm</p>
      <p class="text-xs" v-if="!readonly">Click "Chọn file" để thêm</p>
    </div>

    <small class="text-gray-500 block mt-2">
      <i class="pi pi-info-circle mr-1"></i>
      Chấp nhận mọi loại file, tối đa 10MB/file
    </small>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'

const props = defineProps({
  existingAttachments: {
    type: Array,
    default: () => []
  },
  readonly: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:newFiles', 'update:deleteIds'])

const toast = useToast()
const fileInput = ref(null)
const newFiles = ref([])
const deleteIds = ref([])
const existingFiles = ref([...props.existingAttachments])

watch(() => props.existingAttachments, (val) => {
  existingFiles.value = [...val]
}, { deep: true })

watch(newFiles, (val) => {
  emit('update:newFiles', val)
}, { deep: true })

watch(deleteIds, (val) => {
  emit('update:deleteIds', val)
}, { deep: true })

function handleFileSelect(event) {
  const files = Array.from(event.target.files)

  for (const file of files) {
    // Validate file size (max 10MB)
    if (file.size > 10 * 1024 * 1024) {
      toast.add({
        severity: 'error',
        summary: 'Lỗi',
        detail: `File "${file.name}" vượt quá 10MB`,
        life: 3000
      })
      continue
    }

    newFiles.value.push(file)
  }

  // Reset input
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function removeNewFile(index) {
  newFiles.value.splice(index, 1)
}

function getFileIcon(mimeType) {
  if (!mimeType) return 'pi pi-file text-gray-500'

  if (mimeType.includes('pdf')) return 'pi pi-file-pdf text-red-600'
  if (mimeType.includes('word') || mimeType.includes('document')) return 'pi pi-file-word text-blue-600'
  if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'pi pi-file-excel text-green-600'
  if (mimeType.includes('image')) return 'pi pi-image text-purple-600'
  if (mimeType.includes('zip') || mimeType.includes('rar')) return 'pi pi-folder text-yellow-600'

  return 'pi pi-file text-gray-500'
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

// Expose methods for parent component
defineExpose({
  getNewFiles: () => newFiles.value,
  getDeleteIds: () => deleteIds.value,
  reset: () => {
    newFiles.value = []
    deleteIds.value = []
  }
})
</script>

<style scoped>
.hidden {
  display: none;
}
</style>
