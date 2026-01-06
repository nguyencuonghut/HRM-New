<template>
    <Card>
        <template #title>
            <div class="flex items-center justify-between">
                <span>{{ title }}</span>
                <div v-if="$slots.actions" class="flex gap-2">
                    <slot name="actions" />
                </div>
            </div>
        </template>

        <template #content>
            <DataTable
                :value="data"
                :paginator="paginator"
                :rows="rows"
                :loading="loading"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                :totalRecords="totalRecords"
                :lazy="lazy"
                @page="onPage"
                @sort="onSort"
                stripedRows
                showGridlines
                responsiveLayout="scroll"
                :class="tableClass"
            >
                <!-- Dynamic Columns -->
                <slot />

                <!-- Empty State -->
                <template #empty>
                    <div class="text-center py-8">
                        <i class="pi pi-inbox text-4xl mb-3"></i>
                        <p>{{ emptyMessage }}</p>
                    </div>
                </template>

                <!-- Loading State -->
                <template #loading>
                    <div class="text-center py-8">
                        <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
                    </div>
                </template>
            </DataTable>
        </template>
    </Card>
</template>

<script setup>
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';

// Props
const props = defineProps({
    title: {
        type: String,
        default: 'Dữ liệu',
    },
    data: {
        type: Array,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    paginator: {
        type: Boolean,
        default: true,
    },
    rows: {
        type: Number,
        default: 20,
    },
    totalRecords: {
        type: Number,
        default: 0,
    },
    lazy: {
        type: Boolean,
        default: false,
    },
    emptyMessage: {
        type: String,
        default: 'Không có dữ liệu',
    },
    tableClass: {
        type: String,
        default: '',
    },
});

// Emits
const emit = defineEmits(['page', 'sort']);

// Methods - Just pass events to parent
const onPage = (event) => {
    emit('page', event);
};

const onSort = (event) => {
    emit('sort', event);
};
</script>

<style scoped>
:deep(.p-datatable) {
    font-size: 0.875rem;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background-color: #f3f4f6;
    font-weight: 600;
}
</style>
