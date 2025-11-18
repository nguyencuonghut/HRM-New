<template>
  <Head><title>Chỉnh sửa Template: {{ tpl.name }}</title></Head>

  <div class="card">
    <div class="flex items-center justify-between mb-4">
      <h3 class="m-0">Chỉnh sửa Liquid: {{ tpl.name }}</h3>
      <div class="flex gap-2">
        <Button label="Xem trước" icon="pi pi-eye" @click="doPreview" :loading="previewing" />
        <Button label="Lưu" icon="pi pi-save" severity="success" @click="save" :loading="saving" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block font-bold mb-2">Nội dung (Liquid)</label>
        <Textarea v-model="content" rows="24" class="w-full font-mono text-sm" />
        <small class="text-gray-500">
          Biến: <code v-text="'{{ employee.full_name }}'"></code>,
          Filter: <code v-text="'{{ contract.base_salary | currency_vnd }}'"></code>,
          Ngày: <code v-text="'{{ contract.start_date | date_vn }}'"></code>
        </small>
      </div>

      <div>
        <label class="block font-bold mb-2">Dữ liệu mẫu (JSON)</label>
        <Textarea v-model="sampleJson" rows="24" class="w-full font-mono text-sm" />
        <div class="mt-3">
          <label class="block font-bold mb-2">Xem trước</label>
          <div class="border rounded h-[480px] overflow-auto p-3 bg-white" v-html="previewHtml || '<i>Nhấn Xem trước…</i>'"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'
import { ToastService } from '@/services/ToastService'

const props = defineProps({
  template: { type: Object, required: true },
  sampleData: { type: Object, required: true }
})
const tpl = computed(()=> props.template)
const content = ref(tpl.value.data.content || '')
const sampleJson = ref(JSON.stringify(props.sampleData || {}, null, 2))
const previewHtml = ref('')
const saving = ref(false)
const previewing = ref(false)

// Debug: log incoming props to verify content is present
const page = usePage()

onMounted(()=> {
  try {
    const p = page.props.template
    if ((!content.value || content.value === '') && p && p.content) {
      content.value = p.content
    }
  } catch(e) {
    // ignore
  }
})

function save(){
  saving.value = true
  router.put(`/contract-templates/${tpl.value.data.id}/content`, {
    engine: 'LIQUID',
    content: content.value
  }, {
    onFinish:()=> saving.value=false,
    onError:(errs)=> {
      if (errs?.content) ToastService.error(errs.content)
      else ToastService.error('Lưu template thất bại!')
    },
    onSuccess:()=> ToastService.success('Đã lưu template')
  })
}

async function doPreview(){
  let data = {}
  try { data = JSON.parse(sampleJson.value || '{}') } catch { ToastService.error('JSON dữ liệu mẫu không hợp lệ'); return }
  previewing.value = true
  try {
    const res = await fetch(`/contract-templates/${tpl.value.data.id}/preview`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content },
      body: JSON.stringify({ engine: 'LIQUID', content: content.value, data })
    })
    const txt = await res.text()
    if (!res.ok) {
      const msg = txt || `Preview failed with status ${res.status}`
      ToastService.error(msg)
      previewHtml.value = `<pre class="text-red-600">${msg}</pre>`
    } else {
      previewHtml.value = txt
    }
  } catch (e) {
    ToastService.error(e?.message || 'Xem trước thất bại')
  } finally {
    previewing.value = false
  }
}
</script>
