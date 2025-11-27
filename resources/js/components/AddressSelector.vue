<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <!-- Province Dropdown -->
        <div>
            <Select
                v-model="selectedProvince"
                :options="provinces"
                optionLabel="name"
                optionValue="id"
                placeholder="Chọn Tỉnh/Thành phố"
                :loading="loadingProvinces"
                @change="onProvinceChange"
                showClear
                filter
                fluid
            />
        </div>

        <!-- Ward Dropdown -->
        <div>
            <Select
                v-model="selectedWard"
                :options="wards"
                optionLabel="name"
                optionValue="id"
                placeholder="Chọn Phường/Xã"
                :loading="loadingWards"
                :disabled="!selectedProvince"
                @change="onWardChange"
                showClear
                filter
                fluid
            />
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import Select from 'primevue/select'

const props = defineProps({
    modelValue: {
        type: String,
        default: null
    },
    provinceId: {
        type: String,
        default: null
    }
})

const emit = defineEmits(['update:modelValue', 'update:provinceId'])

const provinces = ref([])
const wards = ref([])
const selectedProvince = ref(props.provinceId)
const selectedWard = ref(props.modelValue)
const loadingProvinces = ref(false)
const loadingWards = ref(false)

// Load provinces on mount
onMounted(async () => {
    await loadProvinces()

    // If initial values are provided, load wards
    if (props.provinceId) {
        await loadWards(props.provinceId)
    }
})

// Watch for external changes
watch(() => props.modelValue, (newVal) => {
    selectedWard.value = newVal
})

watch(() => props.provinceId, (newVal) => {
    selectedProvince.value = newVal
    if (newVal) {
        loadWards(newVal)
    }
})

async function loadProvinces() {
    loadingProvinces.value = true
    try {
        const response = await fetch('/api/provinces')
        provinces.value = await response.json()
    } catch (error) {
        console.error('Error loading provinces:', error)
    } finally {
        loadingProvinces.value = false
    }
}

async function loadWards(provinceId) {
    if (!provinceId) {
        wards.value = []
        return
    }

    loadingWards.value = true
    try {
        const response = await fetch(`/api/provinces/${provinceId}/wards`)
        wards.value = await response.json()
    } catch (error) {
        console.error('Error loading wards:', error)
        wards.value = []
    } finally {
        loadingWards.value = false
    }
}

function onProvinceChange() {
    // Clear ward when province changes
    selectedWard.value = null
    wards.value = []

    emit('update:provinceId', selectedProvince.value)
    emit('update:modelValue', null)

    // Load wards for new province
    if (selectedProvince.value) {
        loadWards(selectedProvince.value)
    }
}

function onWardChange() {
    emit('update:modelValue', selectedWard.value)
}
</script>
