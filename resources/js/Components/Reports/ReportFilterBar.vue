<template>
    <Card class="mb-6 filter-bar">
        <template #content>
            <div class="flex flex-wrap items-end gap-4">
                <!-- Dynamic Filters -->
                <slot />

                <!-- Action Buttons -->
                <div class="ml-auto flex gap-2">
                    <Button
                        v-if="showClearButton"
                        label="Xóa bộ lọc"
                        icon="pi pi-times"
                        severity="secondary"
                        outlined
                        @click="$emit('clear')"
                    />
                    <Button
                        label="Áp dụng"
                        icon="pi pi-check"
                        @click="$emit('apply')"
                    />
                    <Button
                        v-if="showExportButton"
                        label="Xuất Excel"
                        icon="pi pi-download"
                        severity="success"
                        outlined
                        @click="$emit('export')"
                    />
                </div>
            </div>
        </template>
    </Card>
</template>

<script setup>
import Card from 'primevue/card';
import Button from 'primevue/button';

// Props
defineProps({
    showClearButton: {
        type: Boolean,
        default: true,
    },
    showExportButton: {
        type: Boolean,
        default: true,
    },
});

// Emits
defineEmits(['apply', 'clear', 'export']);
</script>

<style scoped>
.filter-bar :deep(.p-card-body) {
    padding: 1rem;
}

.filter-bar :deep(.p-card-content) {
    padding: 0;
}
</style>
