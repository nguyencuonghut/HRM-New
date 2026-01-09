<template>
    <Head>
        <title>Tạo Config Mới</title>
    </Head>

    <div>
        <div class="card">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-2xl font-bold mb-2">Tạo Config Lương BHXH Mới</h3>
                    <p class="text-gray-500">Điền đầy đủ 4 mức lương vùng và 7 bậc lương</p>
                </div>
                <div class="flex gap-2">
                    <Button
                        label="Hủy"
                        icon="pi pi-times"
                        variant="outlined"
                        @click="handleCancel"
                    />
                    <Button
                        label="Tạo Config"
                        icon="pi pi-check"
                        :loading="saving"
                        @click="handleSubmit"
                    />
                </div>
            </div>

            <!-- Basic Info -->
            <Panel header="Thông tin cơ bản" class="mb-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 font-semibold">Mã Config <span class="text-red-500">*</span></label>
                        <InputText
                            v-model="form.code"
                            class="w-full"
                            placeholder="VD: VN_INS_2025_01"
                        />
                    </div>
                    <div>
                        <label class="block mb-2 font-semibold">Tên Config <span class="text-red-500">*</span></label>
                        <InputText
                            v-model="form.name"
                            class="w-full"
                            placeholder="VD: Hệ thống lương BHXH 01/2025"
                        />
                    </div>
                    <div>
                        <label class="block mb-2 font-semibold">Hiệu lực từ <span class="text-red-500">*</span></label>
                        <DatePicker
                            v-model="form.effective_from"
                            dateFormat="dd/mm/yy"
                            showIcon
                            fluid
                        />
                    </div>
                    <div>
                        <label class="block mb-2 font-semibold">Hiệu lực đến</label>
                        <DatePicker
                            v-model="form.effective_to"
                            dateFormat="dd/mm/yy"
                            showIcon
                            showClear
                            fluid
                        />
                    </div>
                    <div class="col-span-2">
                        <label class="block mb-2 font-semibold">Mô tả</label>
                        <Textarea
                            v-model="form.description"
                            class="w-full"
                            rows="3"
                            placeholder="Mô tả về config này..."
                        />
                    </div>
                </div>
            </Panel>

            <!-- Tabs for Minimum Wages and Salary Grades -->
            <Tabs value="0">
                <TabList>
                    <Tab value="0">
                        <div class="flex items-center gap-2">
                            <span>Mức lương tối thiểu vùng</span>
                            <Badge
                                :value="`${form.minimum_wages.length}/4`"
                                :severity="form.minimum_wages.length === 4 ? 'success' : 'warn'"
                            />
                        </div>
                    </Tab>
                    <Tab value="1">
                        <div class="flex items-center gap-2">
                            <span>Bậc lương</span>
                            <Badge
                                :value="`${form.salary_grades.length}/7`"
                                :severity="form.salary_grades.length === 7 ? 'success' : 'warn'"
                            />
                        </div>
                    </Tab>
                </TabList>

                <TabPanels>
                    <!-- Tab 1: Minimum Wages -->
                    <TabPanel value="0">

                    <Toolbar class="mb-4">
                        <template #start>
                            <Button
                                label="Thêm vùng"
                                icon="pi pi-plus"
                                size="small"
                                @click="addMinimumWage"
                                :disabled="form.minimum_wages.length >= 4"
                            />
                        </template>
                        <template #end>
                            <Button
                                label="Tự động điền 4 vùng"
                                icon="pi pi-bolt"
                                size="small"
                                severity="secondary"
                                variant="outlined"
                                @click="autoFillMinimumWages"
                            />
                        </template>
                    </Toolbar>

                    <DataTable
                        :value="form.minimum_wages"
                        dataKey="region"
                    >
                        <Column field="region" header="Vùng" style="width: 12rem">
                            <template #body="slotProps">
                                <Select
                                    v-model="slotProps.data.region"
                                    :options="regionOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full"
                                />
                            </template>
                        </Column>

                        <Column field="amount" header="Mức lương (VNĐ)" style="min-width: 15rem">
                            <template #body="slotProps">
                                <InputNumber
                                    v-model="slotProps.data.amount"
                                    mode="currency"
                                    currency="VND"
                                    locale="vi-VN"
                                    class="w-full"
                                    :allowEmpty="true"
                                />
                            </template>
                        </Column>

                        <Column field="note" header="Ghi chú" style="min-width: 20rem">
                            <template #body="slotProps">
                                <InputText
                                    v-model="slotProps.data.note"
                                    class="w-full"
                                    placeholder="VD: Nghị định 24/2023/NĐ-CP - Vùng I"
                                />
                            </template>
                        </Column>

                        <Column :exportable="false" style="width: 8rem">
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
                                <i class="pi pi-inbox text-4xl text-gray-400 mb-3"></i>
                                <p class="text-gray-500">Chưa có mức lương vùng nào</p>
                                <Button
                                    label="Thêm vùng đầu tiên"
                                    icon="pi pi-plus"
                                    size="small"
                                    class="mt-3"
                                    @click="addMinimumWage"
                                />
                            </div>
                        </template>
                    </DataTable>
                    </TabPanel>

                    <!-- Tab 2: Salary Grades -->
                    <TabPanel value="1">

                    <Toolbar class="mb-4">
                        <template #start>
                            <Button
                                label="Thêm bậc"
                                icon="pi pi-plus"
                                size="small"
                                @click="addSalaryGrade"
                                :disabled="form.salary_grades.length >= 7"
                            />
                        </template>
                        <template #end>
                            <Button
                                label="Tự động điền 7 bậc"
                                icon="pi pi-bolt"
                                size="small"
                                severity="secondary"
                                variant="outlined"
                                @click="autoFillSalaryGrades"
                            />
                        </template>
                    </Toolbar>

                    <DataTable
                        :value="form.salary_grades"
                        dataKey="grade"
                    >
                        <Column field="grade" header="Bậc" style="width: 10rem">
                            <template #body="slotProps">
                                <Select
                                    v-model="slotProps.data.grade"
                                    :options="gradeOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full"
                                />
                            </template>
                        </Column>

                        <Column field="name" header="Tên bậc" style="min-width: 12rem">
                            <template #body="slotProps">
                                <InputText
                                    v-model="slotProps.data.name"
                                    class="w-full"
                                    placeholder="VD: Bậc 1"
                                />
                            </template>
                        </Column>

                        <Column field="coefficient" header="Hệ số" style="min-width: 12rem">
                            <template #body="slotProps">
                                <InputNumber
                                    v-model="slotProps.data.coefficient"
                                    mode="decimal"
                                    :minFractionDigits="2"
                                    :maxFractionDigits="4"
                                    class="w-full"
                                />
                            </template>
                        </Column>

                        <Column field="description" header="Mô tả" style="min-width: 20rem">
                            <template #body="slotProps">
                                <InputText
                                    v-model="slotProps.data.description"
                                    class="w-full"
                                    placeholder="Mô tả về bậc lương này"
                                />
                            </template>
                        </Column>

                        <Column :exportable="false" style="width: 8rem">
                            <template #body="slotProps">
                                <Button
                                    icon="pi pi-trash"
                                    variant="outlined"
                                    severity="danger"
                                    size="small"
                                    @click="removeSalaryGrade(slotProps.index)"
                                />
                            </template>
                        </Column>

                        <template #empty>
                            <div class="text-center p-4">
                                <i class="pi pi-inbox text-4xl text-gray-400 mb-3"></i>
                                <p class="text-gray-500">Chưa có bậc lương nào</p>
                                <Button
                                    label="Thêm bậc đầu tiên"
                                    icon="pi pi-plus"
                                    size="small"
                                    class="mt-3"
                                    @click="addSalaryGrade"
                                />
                            </div>
                        </template>
                    </DataTable>
                    </TabPanel>
                </TabPanels>
            </Tabs>

            <!-- Summary -->
            <Message severity="info" class="mt-4">
                <strong>Lưu ý:</strong> Config mới sẽ được tạo ở trạng thái <b>DRAFT</b>.
                Bạn cần điền đủ 4 mức lương vùng và 7 bậc lương trước khi có thể kích hoạt.
            </Message>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head } from '@inertiajs/vue3';
import { InsuranceConfigSetService } from '@/services/InsuranceConfigSetService';
import Panel from 'primevue/panel';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Badge from 'primevue/badge';
import DatePicker from 'primevue/datepicker';
import Message from 'primevue/message';

// State
const saving = ref(false);

const form = reactive({
    code: '',
    name: '',
    description: '',
    effective_from: null,
    effective_to: null,
    minimum_wages: [],
    salary_grades: []
});

const regionOptions = [
    { label: 'Vùng I', value: 1 },
    { label: 'Vùng II', value: 2 },
    { label: 'Vùng III', value: 3 },
    { label: 'Vùng IV', value: 4 }
];

const gradeOptions = [
    { label: 'Bậc 1', value: 1 },
    { label: 'Bậc 2', value: 2 },
    { label: 'Bậc 3', value: 3 },
    { label: 'Bậc 4', value: 4 },
    { label: 'Bậc 5', value: 5 },
    { label: 'Bậc 6', value: 6 },
    { label: 'Bậc 7', value: 7 }
];

// Methods
const handleCancel = () => {
    InsuranceConfigSetService.index();
};

const handleSubmit = () => {
    InsuranceConfigSetService.store(form, {
        onStart: () => { saving.value = true; },
        onFinish: () => { saving.value = false; }
    });
};

const addMinimumWage = () => {
    const nextRegion = form.minimum_wages.length + 1;
    if (nextRegion <= 4) {
        form.minimum_wages.push({
            region: nextRegion,
            amount: 0,
            note: ''
        });
    }
};

const removeMinimumWage = (index) => {
    form.minimum_wages.splice(index, 1);
};

const autoFillMinimumWages = () => {
    // Default wages based on Nghị định 24/2023/NĐ-CP
    form.minimum_wages = [
        {
            region: 1,
            amount: 4960000,
            note: 'Nghị định 24/2023/NĐ-CP - Vùng I (thành phố lớn như Hà Nội, TP.HCM)'
        },
        {
            region: 2,
            amount: 4410000,
            note: 'Nghị định 24/2023/NĐ-CP - Vùng II'
        },
        {
            region: 3,
            amount: 3860000,
            note: 'Nghị định 24/2023/NĐ-CP - Vùng III'
        },
        {
            region: 4,
            amount: 3450000,
            note: 'Nghị định 24/2023/NĐ-CP - Vùng IV (vùng sâu, vùng xa)'
        }
    ];
};

const addSalaryGrade = () => {
    const nextGrade = form.salary_grades.length + 1;
    if (nextGrade <= 7) {
        form.salary_grades.push({
            grade: nextGrade,
            name: `Bậc ${nextGrade}`,
            coefficient: 1.0 + (nextGrade - 1) * 0.05,
            description: `Hệ số bậc ${nextGrade}`
        });
    }
};

const removeSalaryGrade = (index) => {
    form.salary_grades.splice(index, 1);
};

const autoFillSalaryGrades = () => {
    // Default 7 grades with incremental coefficients
    form.salary_grades = [
        { grade: 1, name: 'Bậc 1', coefficient: 1.00, description: 'Hệ số bậc 1' },
        { grade: 2, name: 'Bậc 2', coefficient: 1.05, description: 'Hệ số bậc 2' },
        { grade: 3, name: 'Bậc 3', coefficient: 1.10, description: 'Hệ số bậc 3' },
        { grade: 4, name: 'Bậc 4', coefficient: 1.16, description: 'Hệ số bậc 4' },
        { grade: 5, name: 'Bậc 5', coefficient: 1.22, description: 'Hệ số bậc 5' },
        { grade: 6, name: 'Bậc 6', coefficient: 1.29, description: 'Hệ số bậc 6' },
        { grade: 7, name: 'Bậc 7', coefficient: 1.37, description: 'Hệ số bậc 7' }
    ];
};
</script>
