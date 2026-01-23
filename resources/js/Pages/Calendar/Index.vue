<template>
    <Head>
        <title>Lịch làm việc</title>
    </Head>

    <div>
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Lịch làm việc</h1>
                    <p class="mt-2 text-surface-600 dark:text-surface-400">
                        {{ viewTypeLabel }} - {{ currentMonthLabel }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button icon="pi pi-refresh" label="Làm mới" severity="secondary" outlined @click="loadEvents" />
                    <Button icon="pi pi-download" label="Xuất Excel" severity="success" outlined @click="exportCalendar" v-if="canExport" />
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Department Filter -->
                <div v-if="viewType !== 'department'">
                    <label class="block text-sm font-medium mb-2">Phòng/Ban</label>
                    <MultiSelect
                        v-model="filters.departments"
                        :options="departments"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Tất cả phòng ban"
                        display="chip"
                        :maxSelectedLabels="2"
                        @change="loadEvents"
                        class="w-full"
                    />
                </div>

                <!-- Position Filter -->
                <div v-if="viewType !== 'department'">
                    <label class="block text-sm font-medium mb-2">Chức vụ</label>
                    <MultiSelect
                        v-model="filters.positions"
                        :options="positions"
                        optionLabel="title"
                        optionValue="id"
                        placeholder="Tất cả chức vụ"
                        display="chip"
                        :maxSelectedLabels="2"
                        @change="loadEvents"
                        class="w-full"
                    />
                </div>

                <!-- Employee Filter -->
                <div v-if="viewType === 'company-wide'">
                    <label class="block text-sm font-medium mb-2">Nhân viên</label>
                    <MultiSelect
                        v-model="filters.employees"
                        :options="employees"
                        optionLabel="label"
                        optionValue="id"
                        placeholder="Tất cả nhân viên"
                        filter
                        display="chip"
                        :maxSelectedLabels="2"
                        @change="loadEvents"
                        class="w-full"
                    />
                </div>

                <!-- Month Navigation -->
                <div>
                    <label class="block text-sm font-medium mb-2">Tháng</label>
                    <div class="flex gap-2">
                        <Button icon="pi pi-chevron-left" severity="secondary" outlined @click="previousMonth" />
                        <DatePicker
                            v-model="currentDate"
                            view="month"
                            dateFormat="mm/yy"
                            @date-select="loadEvents"
                            class="flex-1"
                        />
                        <Button icon="pi pi-chevron-right" severity="secondary" outlined @click="nextMonth" />
                    </div>
                </div>
            </div>

            <!-- Event Type Filters -->
            <div class="mt-4 pt-4 border-t surface-border">
                <label class="block text-sm font-medium mb-3">Loại sự kiện</label>
                <div class="flex flex-wrap gap-2">
                    <Chip
                        v-for="eventType in eventTypes"
                        :key="eventType.key"
                        :label="eventType.label"
                        :icon="eventType.icon"
                        :class="[
                            'cursor-pointer transition-all',
                            filters.eventTypes.includes(eventType.key) ? eventType.activeClass : 'opacity-50'
                        ]"
                        @click="toggleEventType(eventType.key)"
                    />
                </div>
            </div>
        </div>

        <!-- Team Summary (Department Manager only) -->
        <div v-if="viewType === 'department' && teamSummary" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="card">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg bg-orange-100 dark:bg-orange-900/20 flex items-center justify-center">
                        <i class="pi pi-calendar-times text-2xl text-orange-600 dark:text-orange-400"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">{{ teamSummary.onLeaveToday?.length || 0 }}</div>
                        <div class="text-sm text-surface-600 dark:text-surface-400">Nghỉ phép hôm nay</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg bg-pink-100 dark:bg-pink-900/20 flex items-center justify-center">
                        <i class="pi pi-gift text-2xl text-pink-600 dark:text-pink-400"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">{{ teamSummary.birthdaysThisMonth?.length || 0 }}</div>
                        <div class="text-sm text-surface-600 dark:text-surface-400">Sinh nhật tháng này</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg bg-red-100 dark:bg-red-900/20 flex items-center justify-center">
                        <i class="pi pi-exclamation-triangle text-2xl text-red-600 dark:text-red-400"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">{{ teamSummary.contractsExpiring?.length || 0 }}</div>
                        <div class="text-sm text-surface-600 dark:text-surface-400">HĐ sắp hết hạn</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                        <i class="pi pi-users text-2xl text-green-600 dark:text-green-400"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">{{ teamSummary.teamCoverage?.coveragePercent || 0 }}%</div>
                        <div class="text-sm text-surface-600 dark:text-surface-400">Team Coverage</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar View -->
        <div class="card">
            <DataView :value="groupedEvents" layout="list">
                <template #header>
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold">{{ currentMonthLabel }}</h2>
                        <div class="flex gap-2">
                            <Button
                                v-for="view in viewModes"
                                :key="view.value"
                                :label="view.label"
                                :severity="currentView === view.value ? 'primary' : 'secondary'"
                                :outlined="currentView !== view.value"
                                size="small"
                                @click="currentView = view.value"
                            />
                        </div>
                    </div>
                </template>

                <template #list="slotProps">
                    <!-- Month View -->
                    <div v-if="currentView === 'month'" class="grid grid-cols-7 gap-1">
                        <!-- Day Headers -->
                        <div
                            v-for="day in dayHeaders"
                            :key="day"
                            class="text-center font-semibold py-2 border-b surface-border"
                        >
                            {{ day }}
                        </div>

                        <!-- Calendar Days -->
                        <div
                            v-for="day in calendarDays"
                            :key="day.dateStr"
                            :class="[
                                'min-h-[120px] p-2 border surface-border rounded transition-all',
                                day.isToday ? 'bg-blue-50 dark:bg-blue-900/10 border-blue-400' : '',
                                day.isOtherMonth ? 'opacity-40' : '',
                                'hover:border-primary cursor-pointer'
                            ]"
                            @click="showDayDetails(day)"
                        >
                            <div class="text-sm font-semibold mb-1">{{ day.day }}</div>
                            <div class="space-y-1">
                                <div
                                    v-for="event in day.events.slice(0, 3)"
                                    :key="event.id"
                                    :class="[
                                        'text-xs px-2 py-1 rounded truncate',
                                        getEventClass(event)
                                    ]"
                                    :title="event.title"
                                >
                                    {{ event.title }}
                                </div>
                                <div v-if="day.events.length > 3" class="text-xs text-surface-600 dark:text-surface-400">
                                    +{{ day.events.length - 3 }} sự kiện
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List View -->
                    <div v-else-if="currentView === 'list'" class="space-y-4">
                        <div v-for="(dayEvents, date) in groupedEventsByDate" :key="date" class="border surface-border rounded-lg p-4">
                            <h3 class="font-semibold mb-3 text-primary">{{ formatDate(date) }}</h3>
                            <div class="space-y-2">
                                <div
                                    v-for="event in dayEvents"
                                    :key="event.id"
                                    :class="[
                                        'flex items-start gap-3 p-3 rounded-lg cursor-pointer hover:bg-surface-50 dark:hover:bg-surface-800 transition-all',
                                        getEventBorderClass(event)
                                    ]"
                                    @click="showEventDetails(event)"
                                >
                                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0', getEventIconBg(event)]">
                                        <i :class="['pi', getEventIcon(event), getEventIconColor(event)]"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold">{{ event.title }}</div>
                                        <div v-if="event.extendedProps" class="text-sm text-surface-600 dark:text-surface-400 mt-1">
                                            <span v-if="event.extendedProps.employeeCode">
                                                [{{ event.extendedProps.employeeCode }}]
                                            </span>
                                            <span v-if="event.extendedProps.days">
                                                {{ event.extendedProps.days }} ngày
                                            </span>
                                            <span v-if="event.extendedProps.daysUntilExpiry">
                                                ({{ event.extendedProps.daysUntilExpiry }} ngày nữa)
                                            </span>
                                        </div>
                                    </div>
                                    <Badge v-if="event.type" :value="getEventTypeLabel(event.type)" :severity="getEventSeverity(event.type)" />
                                </div>
                            </div>
                        </div>
                        <div v-if="Object.keys(groupedEventsByDate).length === 0" class="text-center py-8 text-surface-600 dark:text-surface-400">
                            <i class="pi pi-calendar text-4xl mb-3"></i>
                            <p>Không có sự kiện nào trong tháng này</p>
                        </div>
                    </div>
                </template>
            </DataView>
        </div>

        <!-- Event Detail Dialog -->
        <Dialog v-model:visible="eventDetailVisible" modal :header="selectedEvent?.title" :style="{ width: '600px' }">
            <div v-if="selectedEvent" class="space-y-4">
                <div class="flex items-center gap-3 p-3 bg-surface-50 dark:bg-surface-800 rounded-lg">
                    <div :class="['w-12 h-12 rounded-lg flex items-center justify-center', getEventIconBg(selectedEvent)]">
                        <i :class="['pi', getEventIcon(selectedEvent), 'text-xl', getEventIconColor(selectedEvent)]"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-lg">{{ selectedEvent.title }}</div>
                        <div class="text-sm text-surface-600 dark:text-surface-400">{{ formatDate(selectedEvent.start) }}</div>
                    </div>
                </div>

                <div v-if="selectedEvent.extendedProps" class="space-y-3">
                    <div v-if="selectedEvent.extendedProps.employeeName" class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Nhân viên:</span>
                        <span class="font-semibold">
                            {{ selectedEvent.extendedProps.employeeName }}
                            <span v-if="selectedEvent.extendedProps.employeeCode" class="text-surface-500">
                                ({{ selectedEvent.extendedProps.employeeCode }})
                            </span>
                        </span>
                    </div>

                    <div v-if="selectedEvent.extendedProps.days" class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Số ngày:</span>
                        <span class="font-semibold">{{ selectedEvent.extendedProps.days }} ngày</span>
                    </div>

                    <div v-if="selectedEvent.extendedProps.leaveType" class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Loại phép:</span>
                        <Badge :value="selectedEvent.extendedProps.leaveType" />
                    </div>

                    <div v-if="selectedEvent.extendedProps.status" class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Trạng thái:</span>
                        <Badge :value="selectedEvent.extendedProps.status" :severity="getStatusSeverity(selectedEvent.extendedProps.status)" />
                    </div>

                    <div v-if="selectedEvent.extendedProps.contractType" class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Loại hợp đồng:</span>
                        <span class="font-semibold">{{ selectedEvent.extendedProps.contractType }}</span>
                    </div>

                    <div v-if="selectedEvent.extendedProps.expiryDate" class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Ngày hết hạn:</span>
                        <span class="font-semibold text-red-600">{{ formatDate(selectedEvent.extendedProps.expiryDate) }}</span>
                    </div>

                    <div v-if="selectedEvent.extendedProps.age" class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Tuổi:</span>
                        <span class="font-semibold">{{ selectedEvent.extendedProps.age }} tuổi</span>
                    </div>

                    <div v-if="selectedEvent.extendedProps.yearsOfService" class="flex justify-between">
                        <span class="text-surface-600 dark:text-surface-400">Thâm niên:</span>
                        <span class="font-semibold">{{ selectedEvent.extendedProps.yearsOfService }} năm</span>
                    </div>

                    <div v-if="selectedEvent.extendedProps.reason" class="pt-3 border-t surface-border">
                        <span class="text-surface-600 dark:text-surface-400 block mb-2">Lý do:</span>
                        <p class="text-sm">{{ selectedEvent.extendedProps.reason }}</p>
                    </div>

                    <div v-if="selectedEvent.extendedProps.note" class="pt-3 border-t surface-border">
                        <span class="text-surface-600 dark:text-surface-400 block mb-2">Ghi chú:</span>
                        <p class="text-sm">{{ selectedEvent.extendedProps.note }}</p>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Đóng" severity="secondary" @click="eventDetailVisible = false" />
                <Button
                    v-if="selectedEvent?.extendedProps?.employeeId"
                    label="Xem hồ sơ"
                    icon="pi pi-user"
                    @click="viewEmployeeProfile"
                />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import Button from 'primevue/button';
import MultiSelect from 'primevue/multiselect';
import DatePicker from 'primevue/datepicker';
import DataView from 'primevue/dataview';
import Chip from 'primevue/chip';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';

// Props
const props = defineProps({
    viewType: String, // 'company-wide', 'department', 'executive'
    departments: Array,
    positions: Array,
    employees: Array,
});

// State
const currentDate = ref(new Date());
const currentView = ref('month'); // 'month', 'list'
const events = ref([]);
const teamSummary = ref(null);
const eventDetailVisible = ref(false);
const selectedEvent = ref(null);
const loading = ref(false);

const filters = ref({
    departments: [],
    positions: [],
    employees: [],
    eventTypes: ['leaves', 'contracts', 'birthdays', 'anniversaries', 'holidays', 'reviews', 'benefits', 'rewards'],
});

// Constants
const viewModes = [
    { label: 'Tháng', value: 'month' },
    { label: 'Danh sách', value: 'list' },
];

const dayHeaders = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];

const eventTypes = [
    { key: 'leaves', label: 'Nghỉ phép', icon: 'pi-calendar-times', activeClass: 'bg-green-100 text-green-700' },
    { key: 'contracts', label: 'Hợp đồng', icon: 'pi-file-edit', activeClass: 'bg-orange-100 text-orange-700' },
    { key: 'birthdays', label: 'Sinh nhật', icon: 'pi-gift', activeClass: 'bg-pink-100 text-pink-700' },
    { key: 'anniversaries', label: 'Kỷ niệm', icon: 'pi-star', activeClass: 'bg-purple-100 text-purple-700' },
    { key: 'holidays', label: 'Ngày lễ', icon: 'pi-flag', activeClass: 'bg-red-100 text-red-700' },
    { key: 'reviews', label: 'Đánh giá', icon: 'pi-chart-line', activeClass: 'bg-blue-100 text-blue-700' },
    { key: 'benefits', label: 'Phúc lợi', icon: 'pi-heart', activeClass: 'bg-cyan-100 text-cyan-700' },
    { key: 'rewards', label: 'Khen thưởng/KL', icon: 'pi-trophy', activeClass: 'bg-yellow-100 text-yellow-700' },
];

// Computed
const viewTypeLabel = computed(() => {
    const labels = {
        'company-wide': 'Toàn công ty',
        'department': 'Phòng ban của tôi',
        'executive': 'Tổng quan điều hành',
    };
    return labels[props.viewType] || '';
});

const canExport = computed(() => {
    return props.viewType === 'company-wide';
});

const currentMonthLabel = computed(() => {
    return currentDate.value.toLocaleDateString('vi-VN', { month: 'long', year: 'numeric' });
});

const calendarDays = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    // Start from Monday of the week containing the first day
    const startDate = new Date(firstDay);
    const dayOfWeek = firstDay.getDay();
    const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
    startDate.setDate(firstDay.getDate() + diff);

    // End on Sunday of the week containing the last day
    const endDate = new Date(lastDay);
    const lastDayOfWeek = lastDay.getDay();
    const endDiff = lastDayOfWeek === 0 ? 0 : 7 - lastDayOfWeek;
    endDate.setDate(lastDay.getDate() + endDiff);

    const days = [];
    const current = new Date(startDate);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    while (current <= endDate) {
        const dateStr = current.toISOString().split('T')[0];
        const dayEvents = events.value.filter(event => {
            const eventDate = new Date(event.start).toISOString().split('T')[0];
            return eventDate === dateStr;
        });

        days.push({
            date: new Date(current),
            dateStr: dateStr,
            day: current.getDate(),
            isToday: current.getTime() === today.getTime(),
            isOtherMonth: current.getMonth() !== month,
            events: dayEvents,
        });

        current.setDate(current.getDate() + 1);
    }

    return days;
});

const groupedEvents = computed(() => {
    return calendarDays.value;
});

const groupedEventsByDate = computed(() => {
    const grouped = {};
    events.value.forEach(event => {
        const dateStr = new Date(event.start).toISOString().split('T')[0];
        if (!grouped[dateStr]) {
            grouped[dateStr] = [];
        }
        grouped[dateStr].push(event);
    });
    return grouped;
});

// Methods
const loadEvents = async () => {
    loading.value = true;
    try {
        const startDate = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), 1);
        const endDate = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 0);

        const params = {
            start: startDate.toISOString().split('T')[0],
            end: endDate.toISOString().split('T')[0],
            departments: filters.value.departments,
            positions: filters.value.positions,
            employees: filters.value.employees,
        };

        // Add event type filters
        filters.value.eventTypes.forEach(type => {
            params[`filter_${type}`] = true;
        });

        const response = await axios.get('/calendar/events', { params });
        events.value = response.data.events || [];

        // Load team summary if department manager
        if (props.viewType === 'department') {
            const summaryResponse = await axios.get('/calendar/team-summary');
            teamSummary.value = summaryResponse.data;
        }
    } catch (error) {
        console.error('Failed to load events:', error);
    } finally {
        loading.value = false;
    }
};

const previousMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
    loadEvents();
};

const nextMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
    loadEvents();
};

const toggleEventType = (type) => {
    const index = filters.value.eventTypes.indexOf(type);
    if (index > -1) {
        filters.value.eventTypes.splice(index, 1);
    } else {
        filters.value.eventTypes.push(type);
    }
    loadEvents();
};

const showDayDetails = (day) => {
    if (day.events.length > 0) {
        selectedEvent.value = day.events[0];
        eventDetailVisible.value = true;
    }
};

const showEventDetails = (event) => {
    selectedEvent.value = event;
    eventDetailVisible.value = true;
};

const viewEmployeeProfile = () => {
    if (selectedEvent.value?.extendedProps?.employeeId) {
        router.visit(route('employees.profile', selectedEvent.value.extendedProps.employeeId));
    }
};

const exportCalendar = () => {
    // TODO: Implement Excel export
    console.log('Export calendar to Excel');
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('vi-VN', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

// Event styling helpers
const getEventClass = (event) => {
    const classes = {
        'leave': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'contract_expiry_warning': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
        'contract_expiry_urgent': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'birthday': 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-300',
        'work_anniversary': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        'company_holiday': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'performance_review_reminder': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'benefit_payout': 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300',
        'reward': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        'discipline': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };
    return classes[event.type] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
};

const getEventBorderClass = (event) => {
    const classes = {
        'leave': 'border-l-4 border-l-green-500',
        'contract_expiry_warning': 'border-l-4 border-l-orange-500',
        'contract_expiry_urgent': 'border-l-4 border-l-red-500',
        'birthday': 'border-l-4 border-l-pink-500',
        'work_anniversary': 'border-l-4 border-l-purple-500',
        'company_holiday': 'border-l-4 border-l-red-600',
        'performance_review_reminder': 'border-l-4 border-l-blue-500',
        'benefit_payout': 'border-l-4 border-l-cyan-500',
        'reward': 'border-l-4 border-l-yellow-500',
        'discipline': 'border-l-4 border-l-red-500',
    };
    return classes[event.type] || 'border-l-4 border-l-gray-500';
};

const getEventIconBg = (event) => {
    const classes = {
        'leave': 'bg-green-100 dark:bg-green-900/20',
        'contract_expiry_warning': 'bg-orange-100 dark:bg-orange-900/20',
        'contract_expiry_urgent': 'bg-red-100 dark:bg-red-900/20',
        'birthday': 'bg-pink-100 dark:bg-pink-900/20',
        'work_anniversary': 'bg-purple-100 dark:bg-purple-900/20',
        'company_holiday': 'bg-red-100 dark:bg-red-900/20',
        'performance_review_reminder': 'bg-blue-100 dark:bg-blue-900/20',
        'benefit_payout': 'bg-cyan-100 dark:bg-cyan-900/20',
        'reward': 'bg-yellow-100 dark:bg-yellow-900/20',
        'discipline': 'bg-red-100 dark:bg-red-900/20',
    };
    return classes[event.type] || 'bg-gray-100 dark:bg-gray-900/20';
};

const getEventIcon = (event) => {
    const icons = {
        'leave': 'pi-calendar-times',
        'contract_expiry_warning': 'pi-exclamation-triangle',
        'contract_expiry_urgent': 'pi-exclamation-circle',
        'birthday': 'pi-gift',
        'work_anniversary': 'pi-star',
        'company_holiday': 'pi-flag',
        'performance_review_reminder': 'pi-chart-line',
        'benefit_payout': 'pi-heart',
        'reward': 'pi-trophy',
        'discipline': 'pi-exclamation-triangle',
    };
    return icons[event.type] || 'pi-calendar';
};

const getEventIconColor = (event) => {
    const classes = {
        'leave': 'text-green-600 dark:text-green-400',
        'contract_expiry_warning': 'text-orange-600 dark:text-orange-400',
        'contract_expiry_urgent': 'text-red-600 dark:text-red-400',
        'birthday': 'text-pink-600 dark:text-pink-400',
        'work_anniversary': 'text-purple-600 dark:text-purple-400',
        'company_holiday': 'text-red-600 dark:text-red-400',
        'performance_review_reminder': 'text-blue-600 dark:text-blue-400',
        'benefit_payout': 'text-cyan-600 dark:text-cyan-400',
        'reward': 'text-yellow-600 dark:text-yellow-400',
        'discipline': 'text-red-600 dark:text-red-400',
    };
    return classes[event.type] || 'text-gray-600 dark:text-gray-400';
};

const getEventTypeLabel = (type) => {
    const labels = {
        'leave': 'Nghỉ phép',
        'contract_expiry_warning': 'HĐ sắp hết hạn',
        'contract_expiry_urgent': 'HĐ hết hạn gấp',
        'birthday': 'Sinh nhật',
        'work_anniversary': 'Kỷ niệm',
        'company_holiday': 'Ngày lễ',
        'performance_review_reminder': 'Đánh giá',
        'benefit_payout': 'Phúc lợi',
        'reward': 'Khen thưởng',
        'discipline': 'Kỷ luật',
    };
    return labels[type] || type;
};

const getEventSeverity = (type) => {
    const severities = {
        'leave': 'success',
        'contract_expiry_warning': 'warn',
        'contract_expiry_urgent': 'danger',
        'birthday': 'info',
        'work_anniversary': 'info',
        'company_holiday': 'danger',
        'performance_review_reminder': 'info',
        'benefit_payout': 'info',
        'reward': 'success',
        'discipline': 'danger',
    };
    return severities[type] || 'info';
};

const getStatusSeverity = (status) => {
    const severities = {
        'APPROVED': 'success',
        'PENDING': 'warn',
        'REJECTED': 'danger',
        'CANCELLED': 'secondary',
    };
    return severities[status] || 'info';
};

// Lifecycle
onMounted(() => {
    loadEvents();
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
