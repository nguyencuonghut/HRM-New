<template>
    <Head>
        <title>{{ isEditMode ? 'Chỉnh sửa' : 'Chi tiết' }} Config - {{ configSet?.code }}</title>
    </Head>

    <div>
        <div class="card">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-2xl font-bold mb-2">
                        {{ isEditMode ? 'Chỉnh sửa Config' : 'Chi tiết Config' }}
                    </h3>
                    <div class="flex items-center gap-3">
                        <Tag
                            :value="getStatusLabel(configSet?.status)"
                            :severity="getStatusSeverity(configSet?.status)"
                        />
                        <span class="text-gray-500">{{ configSet?.code }}</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button
                        label="Quay lại"
                        icon="pi pi-arrow-left"
                        variant="outlined"
                        @click="handleBack"
                    />
                    <Button
                        v-if="!isEditMode && configSet?.status === 'DRAFT'"
                        label="Chỉnh sửa"
                        icon="pi pi-pencil"
                        severity="secondary"
                        @click="handleEdit"
                    />
                    <Button
                        v-if="isEditMode"
                        label="Lưu"
                        icon="pi pi-save"
                        :loading="saving"
                        @click="handleSave"
                    />
                </div>
            </div>

            <!-- Validation Status -->
            <Message v-if="validation && !validation.valid" severity="error" class="mb-4">
                <strong>Config chưa hợp lệ để kích hoạt:</strong>
                <ul class="mt-2 ml-4">
                    <li v-for="(error, index) in validation.errors" :key="index">{{ error }}</li>
                </ul>
            </Message>
            <Message v-else-if="validation && validation.valid" severity="success" class="mb-4">
                Config hợp lệ và sẵn sàng để kích hoạt
            </Message>

            <!-- Basic Info -->
            <Panel header="Thông tin cơ bản" class="mb-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 font-semibold">Mã Config <span class="text-red-500">*</span></label>
                        <InputText
                            v-model="form.code"
                            class="w-full"
                            :disabled="!isEditMode"
                            placeholder="VD: VN_INS_2025_01"
                        />
                    </div>
                    <div>
                        <label class="block mb-2 font-semibold">Tên Config <span class="text-red-500">*</span></label>
                        <InputText
                            v-model="form.name"
                            class="w-full"
                            :disabled="!isEditMode"
                            placeholder="VD: Hệ thống lương BHXH 01/2025"
                        />
                    </div>
                    <div>
                        <label class="block mb-2 font-semibold">Hiệu lực từ <span class="text-red-500">*</span></label>
                        <DatePicker
                            v-model="form.effective_from"
                            dateFormat="dd/mm/yy"
                            class="w-full"
                            showIcon
                            :disabled="!isEditMode"
                        />
                    </div>
                    <div>
                        <label class="block mb-2 font-semibold">Hiệu lực đến</label>
                        <DatePicker
                            v-model="form.effective_to"
                            dateFormat="dd/mm/yy"
                            class="w-full"
                            showIcon
                            showClear
                            :disabled="!isEditMode"
                        />
                    </div>
                    <div class="col-span-2">
                        <label class="block mb-2 font-semibold">Mô tả</label>
                        <Textarea
                            v-model="form.description"
                            class="w-full"
                            rows="3"
                            :disabled="!isEditMode"
                        />
                    </div>
                </div>
            </Panel>

            <!-- Tabs for Minimum Wages and Salary Grades -->
            <Tabs value="0">
                <TabList>
                    <Tab value="0">Mức lương tối thiểu vùng (4 vùng)</Tab>
                    <Tab value="1">Nhóm vị trí & Hệ số (tham khảo)</Tab>
                </TabList>

                <TabPanels>
                    <!-- Tab 1: Minimum Wages -->
                    <TabPanel value="0">
                    <Toolbar class="mb-4" v-if="isEditMode">
                        <template #start>
                            <Button
                                label="Thêm vùng"
                                icon="pi pi-plus"
                                size="small"
                                @click="addMinimumWage"
                                :disabled="form.minimum_wages.length >= 4"
                            />
                        </template>
                    </Toolbar>

                    <DataTable
                        :value="form.minimum_wages"
                        dataKey="region"
                        :loading="loading"
                    >
                        <Column field="region" header="Vùng" style="width: 10rem">
                            <template #body="slotProps">
                                <Select
                                    v-if="isEditMode"
                                    v-model="slotProps.data.region"
                                    :options="regionOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full"
                                />
                                <span v-else>{{ getRegionLabel(slotProps.data.region) }}</span>
                            </template>
                        </Column>

                        <Column field="amount" header="Mức lương (VNĐ)" style="min-width: 12rem">
                            <template #body="slotProps">
                                <InputNumber
                                    v-if="isEditMode"
                                    v-model="slotProps.data.amount"
                                    mode="currency"
                                    currency="VND"
                                    locale="vi-VN"
                                    class="w-full"
                                />
                                <span v-else>{{ formatCurrency(slotProps.data.amount) }}</span>
                            </template>
                        </Column>

                        <Column field="note" header="Ghi chú" style="min-width: 20rem">
                            <template #body="slotProps">
                                <InputText
                                    v-if="isEditMode"
                                    v-model="slotProps.data.note"
                                    class="w-full"
                                />
                                <span v-else>{{ slotProps.data.note }}</span>
                            </template>
                        </Column>

                        <Column v-if="isEditMode" :exportable="false" style="width: 8rem">
                            <template #body="slotProps">
                                <Button
                                    icon="pi pi-trash"
                                    variant="outlined"
                                    severity="danger"
                                    size="small"
                                    @click="removeMinimumWage(slotProps.index)"
                                />
                            </template>
                        </Column>

                        <template #empty>
                            <div class="text-center p-4">
                                <p class="text-gray-500">Chưa có mức lương vùng nào</p>
                            </div>
                        </template>
                    </DataTable>
                    </TabPanel>

                    <!-- Tab 2: Position Categories (Read-only) -->
                    <TabPanel value="1">
                    <Message severity="info" class="mb-4">
                        <small>Thông tin tham khảo về cấu trúc hệ số lương BHXH theo nhóm vị trí. Để chỉnh sửa, vui lòng vào quản lý Vị trí.</small>
                    </Message>

                    <div v-if="loadingCategories" class="text-center p-8">
                        <i class="pi pi-spin pi-spinner text-4xl text-gray-400"></i>
                        <p class="mt-4 text-gray-500">Đang tải dữ liệu...</p>
                    </div>

                    <div v-else-if="!positionCategories || positionCategories.length === 0" class="text-center p-8">
                        <i class="pi pi-info-circle text-4xl text-gray-400"></i>
                        <p class="mt-4 text-gray-500">Chưa có dữ liệu nhóm vị trí</p>
                    </div>

                    <Accordion v-else :value="['0']" multiple class="position-categories-accordion">
                        <AccordionPanel v-for="(category, index) in positionCategories" :key="category.name" :value="String(index)">
                            <AccordionHeader>
                                <div class="flex items-center justify-between w-full pr-4">
                                    <div>
                                        <strong class="text-lg">{{ category.name }}</strong>
                                        <span class="ml-3 text-gray-500 text-sm">({{ category.positions?.length || 0 }} vị trí)</span>
                                    </div>
                                    <Tag v-if="category.positions?.length" :value="`${category.positions.length} vị trí`" severity="secondary" />
                                </div>
                            </AccordionHeader>
                            <AccordionContent>
                                <div v-if="!category.positions || category.positions.length === 0" class="text-center p-4 text-gray-500">
                                    Chưa có vị trí nào trong nhóm này
                                </div>
                                <div v-else class="space-y-4">
                                    <div v-for="position in category.positions" :key="position.id" class="border rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-semibold text-base">{{ position.title }} - {{ position.department }}</h4>
                                            <Tag v-if="position.grades?.length" :value="`${position.grades.length} bậc`" severity="info" />
                                        </div>

                                        <div v-if="position.grades && position.grades.length > 0" class="overflow-x-auto">
                                            <table class="min-w-full text-sm">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left font-semibold">Bậc</th>
                                                        <th class="px-3 py-2 text-right font-semibold">Hệ số</th>
                                                        <th class="px-3 py-2 text-right font-semibold">Lương (Vùng I)</th>
                                                        <th class="px-3 py-2 text-right font-semibold">Lương (Vùng II)</th>
                                                        <th class="px-3 py-2 text-right font-semibold">Lương (Vùng III)</th>
                                                        <th class="px-3 py-2 text-right font-semibold">Lương (Vùng IV)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="grade in position.grades" :key="grade.grade" class="border-t">
                                                        <td class="px-3 py-2"><strong>Bậc {{ grade.grade }}</strong></td>
                                                        <td class="px-3 py-2 text-right">{{ grade.coefficient }}</td>
                                                        <td class="px-3 py-2 text-right text-green-600">{{ calculateSalary(grade.coefficient, 1) }}</td>
                                                        <td class="px-3 py-2 text-right text-blue-600">{{ calculateSalary(grade.coefficient, 2) }}</td>
                                                        <td class="px-3 py-2 text-right text-orange-600">{{ calculateSalary(grade.coefficient, 3) }}</td>
                                                        <td class="px-3 py-2 text-right text-red-600">{{ calculateSalary(grade.coefficient, 4) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div v-else class="text-gray-500 text-sm italic">
                                            Chưa có thông tin bậc lương
                                        </div>
                                    </div>
                                </div>
                            </AccordionContent>
                        </AccordionPanel>
                    </Accordion>
                    </TabPanel>
                </TabPanels>
            </Tabs>

            <!-- Actions -->
            <div class="flex justify-between mt-6" v-if="!isEditMode">
                <div class="flex gap-2">
                    <Button
                        v-if="configSet?.status === 'DRAFT'"
                        label="Kích hoạt"
                        icon="pi pi-check"
                        severity="success"
                        @click="confirmActivate"
                    />
                    <Button
                        v-if="configSet?.status === 'ACTIVE'"
                        label="Lưu trữ"
                        icon="pi pi-archive"
                        severity="warn"
                        @click="confirmArchive"
                    />
                    <Button
                        label="Sao chép"
                        icon="pi pi-copy"
                        severity="secondary"
                        @click="openCloneDialog"
                    />
                </div>
                <div>
                    <Button
                        v-if="configSet?.status === 'DRAFT'"
                        label="Xóa"
                        icon="pi pi-trash"
                        severity="danger"
                        variant="outlined"
                        @click="confirmDelete"
                    />
                </div>
            </div>
        </div>

        <!-- Activate Confirmation -->
        <Dialog
            v-model:visible="activateDialog"
            :style="{ width: '450px' }"
            header="Xác nhận kích hoạt"
            :modal="true"
        >
            <div class="flex items-center gap-4">
                <i class="pi pi-question-circle text-4xl text-yellow-500"></i>
                <span>
                    Bạn có chắc chắn muốn kích hoạt config này?<br>
                    <small class="text-gray-500">Config hiện tại đang active sẽ được lưu trữ.</small>
                </span>
            </div>
            <template #footer>
                <Button label="Hủy" icon="pi pi-times" variant="outlined" @click="activateDialog = false" />
                <Button label="Kích hoạt" icon="pi pi-check" severity="success" @click="handleActivate" />
            </template>
        </Dialog>

        <!-- Archive Confirmation -->
        <Dialog
            v-model:visible="archiveDialog"
            :style="{ width: '450px' }"
            header="Xác nhận lưu trữ"
            :modal="true"
        >
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-4xl text-orange-500"></i>
                <span>Bạn có chắc chắn muốn lưu trữ config này?</span>
            </div>
            <template #footer>
                <Button label="Hủy" icon="pi pi-times" variant="outlined" @click="archiveDialog = false" />
                <Button label="Lưu trữ" icon="pi pi-check" severity="warn" @click="handleArchive" />
            </template>
        </Dialog>

        <!-- Delete Confirmation -->
        <Dialog
            v-model:visible="deleteDialog"
            :style="{ width: '450px' }"
            header="Xác nhận xóa"
            :modal="true"
        >
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-4xl text-red-500"></i>
                <span>Bạn có chắc chắn muốn xóa config này?</span>
            </div>
            <template #footer>
                <Button label="Hủy" icon="pi pi-times" variant="outlined" @click="deleteDialog = false" />
                <Button label="Xóa" icon="pi pi-check" severity="danger" @click="handleDelete" />
            </template>
        </Dialog>

        <!-- Clone Dialog -->
        <Dialog
            v-model:visible="cloneDialog"
            :style="{ width: '550px' }"
            header="Sao chép Config Set"
            :modal="true"
        >
            <div class="flex flex-col gap-4 py-4">
                <div>
                    <label class="block mb-2 font-semibold">Mã Config <span class="text-red-500">*</span></label>
                    <InputText v-model="cloneForm.code" class="w-full" placeholder="VD: VN_INS_2025_01" />
                </div>
                <div>
                    <label class="block mb-2 font-semibold">Tên Config <span class="text-red-500">*</span></label>
                    <InputText v-model="cloneForm.name" class="w-full" placeholder="VD: Hệ thống lương BHXH 01/2025" />
                </div>
                <div>
                    <label class="block mb-2 font-semibold">Hiệu lực từ <span class="text-red-500">*</span></label>
                    <DatePicker v-model="cloneForm.effective_from" dateFormat="dd/mm/yy" class="w-full" showIcon />
                </div>
                <div>
                    <label class="block mb-2 font-semibold">Hiệu lực đến</label>
                    <DatePicker v-model="cloneForm.effective_to" dateFormat="dd/mm/yy" class="w-full" showIcon showClear />
                </div>
            </div>
            <template #footer>
                <Button label="Hủy" icon="pi pi-times" variant="outlined" @click="cloneDialog = false" />
                <Button label="Sao chép" icon="pi pi-copy" severity="success" @click="handleClone" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { InsuranceConfigSetService } from '@/services/InsuranceConfigSetService';
import axios from 'axios';
import Panel from 'primevue/panel';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import AccordionHeader from 'primevue/accordionheader';
import AccordionContent from 'primevue/accordioncontent';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';
import Message from 'primevue/message';

// Props
const props = defineProps({
    configSet: {
        type: Object,
        required: true
    },
    validation: {
        type: Object,
        default: () => ({ valid: false, errors: [] })
    },
    mode: {
        type: String,
        default: 'view' // 'view' or 'edit'
    }
});

// State
const loading = ref(false);
const saving = ref(false);
const loadingCategories = ref(false);
const positionCategories = ref([]);
const isEditMode = computed(() => props.mode === 'edit');
const activateDialog = ref(false);
const archiveDialog = ref(false);
const deleteDialog = ref(false);
const cloneDialog = ref(false);

const form = reactive({
    code: props.configSet?.code || '',
    name: props.configSet?.name || '',
    description: props.configSet?.description || '',
    effective_from: props.configSet?.effective_from ? new Date(props.configSet.effective_from) : null,
    effective_to: props.configSet?.effective_to ? new Date(props.configSet.effective_to) : null,
    minimum_wages: props.configSet?.minimum_wages || []
});

const cloneForm = reactive({
    code: '',
    name: '',
    effective_from: null,
    effective_to: null
});

const regionOptions = [
    { label: 'Vùng I', value: 1 },
    { label: 'Vùng II', value: 2 },
    { label: 'Vùng III', value: 3 },
    { label: 'Vùng IV', value: 4 }
];

// Methods
const fetchPositionCategories = async () => {
    loadingCategories.value = true;
    try {
        const response = await axios.get('/api/positions/categories-with-grades');
        positionCategories.value = response.data.data || [];
    } catch (error) {
        console.error('Error fetching position categories:', error);
        positionCategories.value = [];
    } finally {
        loadingCategories.value = false;
    }
};

const calculateSalary = (coefficient, region) => {
    const wage = form.minimum_wages.find(w => w.region === region);
    if (!wage || !wage.amount) return '-';
    const salary = wage.amount * coefficient;
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0
    }).format(salary);
};
const handleBack = () => {
    InsuranceConfigSetService.index();
};

const handleEdit = () => {
    InsuranceConfigSetService.edit(props.configSet.id);
};

const handleSave = () => {
    if (props.configSet?.id) {
        InsuranceConfigSetService.update(props.configSet.id, form, {
            onStart: () => { saving.value = true; },
            onFinish: () => { saving.value = false; },
            onSuccess: () => {
                InsuranceConfigSetService.show(props.configSet.id);
            }
        });
    }
};

const addMinimumWage = () => {
    const nextRegion = form.minimum_wages.length + 1;
    form.minimum_wages.push({
        region: nextRegion,
        amount: 0,
        note: ''
    });
};

const removeMinimumWage = (index) => {
    form.minimum_wages.splice(index, 1);
};

const confirmActivate = () => {
    activateDialog.value = true;
};

const handleActivate = () => {
    InsuranceConfigSetService.activate(props.configSet.id, {
        onSuccess: () => {
            activateDialog.value = false;
            InsuranceConfigSetService.show(props.configSet.id);
        }
    });
};

const confirmArchive = () => {
    archiveDialog.value = true;
};

const handleArchive = () => {
    InsuranceConfigSetService.archive(props.configSet.id, {
        onSuccess: () => {
            archiveDialog.value = false;
            InsuranceConfigSetService.show(props.configSet.id);
        }
    });
};

const confirmDelete = () => {
    deleteDialog.value = true;
};

const handleDelete = () => {
    InsuranceConfigSetService.destroy(props.configSet.id, {
        onSuccess: () => {
            deleteDialog.value = false;
            InsuranceConfigSetService.index();
        }
    });
};

const openCloneDialog = () => {
    cloneForm.code = '';
    cloneForm.name = `${props.configSet.name} (Copy)`;
    cloneForm.effective_from = null;
    cloneForm.effective_to = null;
    cloneDialog.value = true;
};

const handleClone = () => {
    InsuranceConfigSetService.clone(props.configSet.id, cloneForm, {
        onSuccess: () => {
            cloneDialog.value = false;
        }
    });
};

const getStatusLabel = (status) => {
    const labels = {
        'DRAFT': 'Nháp',
        'ACTIVE': 'Đang áp dụng',
        'ARCHIVED': 'Đã lưu trữ'
    };
    return labels[status] || status;
};

const getStatusSeverity = (status) => {
    const severities = {
        'DRAFT': 'secondary',
        'ACTIVE': 'success',
        'ARCHIVED': 'warn'
    };
    return severities[status] || 'secondary';
};

const getRegionLabel = (region) => {
    const labels = {
        1: 'Vùng I',
        2: 'Vùng II',
        3: 'Vùng III',
        4: 'Vùng IV'
    };
    return labels[region] || `Vùng ${region}`;
};

const formatCurrency = (value) => {
    if (!value) return '0 VNĐ';
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(value);
};

onMounted(() => {
    // Data loaded by Inertia
    fetchPositionCategories();
});
</script>
