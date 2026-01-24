<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import InputNumber from 'primevue/inputnumber'
import SelectButton from 'primevue/selectbutton'
import Breadcrumb from 'primevue/breadcrumb'

const props = defineProps({
    employees: Array,
    statistics: Object,
    filters: Object,
    departments: Array,
})

const breadcrumbItems = ref([
    { label: 'Trang chủ', route: '/' },
    { label: 'Báo cáo', route: '/reports' },
    { label: 'Sinh nhật nhân viên' },
])

const filters = ref({
    month: props.filters.month,
    year: props.filters.year,
    quarter: props.filters.quarter,
    department_id: props.filters.department_id,
    age_min: props.filters.age_min,
    age_max: props.filters.age_max,
    view_mode: props.filters.view_mode,
})

const months = ref([
    { label: 'Tháng 1', value: 1 }, { label: 'Tháng 2', value: 2 },
    { label: 'Tháng 3', value: 3 }, { label: 'Tháng 4', value: 4 },
    { label: 'Tháng 5', value: 5 }, { label: 'Tháng 6', value: 6 },
    { label: 'Tháng 7', value: 7 }, { label: 'Tháng 8', value: 8 },
    { label: 'Tháng 9', value: 9 }, { label: 'Tháng 10', value: 10 },
    { label: 'Tháng 11', value: 11 }, { label: 'Tháng 12', value: 12 },
])

const viewModeOptions = ref([
    { label: 'Bảng', value: 'table', icon: 'pi pi-list' },
    { label: 'Lịch', value: 'calendar', icon: 'pi pi-calendar' },
])

const loadData = () => {
    router.get('/reports/birthdays', filters.value, { preserveState: true, preserveScroll: true })
}

// Reload data when page becomes visible (user returns from another page)
const handleVisibilityChange = () => {
    if (!document.hidden) {
        // Page is now visible, reload data to get latest updates
        loadData()
    }
}

onMounted(() => {
    document.addEventListener('visibilitychange', handleVisibilityChange)
})

onUnmounted(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange)
})

const exportReport = () => {
    window.open(`/reports/birthdays/export?${new URLSearchParams(filters.value).toString()}`)
}

const previousMonth = () => {
    if (filters.value.month === 1) {
        filters.value.month = 12
        filters.value.year--
    } else {
        filters.value.month--
    }
    loadData()
}

const nextMonth = () => {
    if (filters.value.month === 12) {
        filters.value.month = 1
        filters.value.year++
    } else {
        filters.value.month++
    }
    loadData()
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
}

const formatCurrency = (amount) => {
    if (!amount) return '0'
    return new Intl.NumberFormat('vi-VN').format(amount) + ' VND'
}

const getDaysUntilSeverity = (days) => {
    if (days === 0) return 'danger'
    if (days > 0 && days <= 7) return 'warning'
    if (days > 0 && days <= 14) return 'info'
    return 'secondary'
}

const getDaysUntilLabel = (days) => {
    if (days === 0) return 'Hôm nay! 🎉'
    if (days > 0) return `${days} ngày nữa`
    return 'Đã qua'
}

// Calendar view helpers
const calendarDays = computed(() => {
    if (!filters.value.year || !filters.value.month) return []

    const year = filters.value.year
    const month = filters.value.month
    const firstDay = new Date(year, month - 1, 1)
    const lastDay = new Date(year, month, 0)
    const daysInMonth = lastDay.getDate()
    const startDayOfWeek = firstDay.getDay() // 0 = Sunday

    const days = []

    // Add empty cells for days before month starts
    for (let i = 0; i < startDayOfWeek; i++) {
        days.push({ date: null, employees: [] })
    }

    // Add days of the month with birthdays
    for (let day = 1; day <= daysInMonth; day++) {
        const employeesOnDay = props.employees.filter(emp => {
            const dobDate = new Date(emp.dob)
            return dobDate.getDate() === day
        })

        days.push({
            date: day,
            employees: employeesOnDay,
            isToday: day === new Date().getDate() && month === new Date().getMonth() + 1 && year === new Date().getFullYear()
        })
    }

    return days
})

const weekDays = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
</script>

<template>
    <Head><title>Báo cáo sinh nhật</title></Head>

    <div class="p-6">
        <!-- Breadcrumb -->
        <div class="mb-4">
            <Breadcrumb :home="{ icon: 'pi pi-home', route: '/' }">
                <template #item="{ item }">
                    <a v-if="item.route" :href="item.route" class="hover:underline">{{ item.label }}</a>
                    <span v-else>{{ item.label }}</span>
                </template>
            </Breadcrumb>
        </div>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold">Báo cáo sinh nhật nhân viên</h1>
                <p class="text-surface-600 dark:text-surface-400 mt-2">Theo dõi sinh nhật để chúc mừng và chi trả phúc lợi</p>
            </div>
            <Button icon="pi pi-download" label="Xuất Excel" @click="exportReport" severity="success" outlined />
        </div>

        <!-- Filters Card -->
        <div class="card mb-6">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <!-- Month Selector -->
                <div>
                    <label class="block text-sm font-medium mb-2">Tháng</label>
                    <Select v-model="filters.month" :options="months" optionLabel="label" optionValue="value" placeholder="Chọn tháng" class="w-full" @change="loadData" />
                </div>

                <!-- Year -->
                <div>
                    <label class="block text-sm font-medium mb-2">Năm</label>
                    <InputNumber v-model="filters.year" :min="2000" :max="2050" :useGrouping="false" class="w-full" @input="loadData" />
                </div>

                <!-- Department Filter -->
                <div>
                    <label class="block text-sm font-medium mb-2">Phòng/Ban</label>
                    <Select v-model="filters.department_id" :options="departments" filter optionLabel="name" optionValue="id" placeholder="Tất cả" class="w-full" showClear @change="loadData" />
                </div>

                <!-- Age Min -->
                <div>
                    <label class="block text-sm font-medium mb-2">Tuổi từ</label>
                    <InputNumber v-model="filters.age_min" :min="0" :max="100" placeholder="Từ" class="w-full" @input="loadData" />
                </div>

                <!-- Age Max -->
                <div>
                    <label class="block text-sm font-medium mb-2">Tuổi đến</label>
                    <InputNumber v-model="filters.age_max" :min="0" :max="100" placeholder="Đến" class="w-full" @input="loadData" />
                </div>

                <!-- View Mode Toggle -->
                <div>
                    <label class="block text-sm font-medium mb-2">Chế độ xem</label>
                    <SelectButton v-model="filters.view_mode" :options="viewModeOptions" optionLabel="label" optionValue="value" @change="loadData" class="w-full">
                        <template #option="{ option }">
                            <i :class="option.icon"></i>
                        </template>
                    </SelectButton>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
            <div class="card">
                <div class="text-surface-600 dark:text-surface-400 text-sm mb-2">Tổng sinh nhật</div>
                <div class="text-3xl font-bold text-blue-600">{{ statistics.total_count }}</div>
            </div>
            <div class="card">
                <div class="text-surface-600 dark:text-surface-400 text-sm mb-2">7 ngày tới</div>
                <div class="text-3xl font-bold text-green-600">{{ statistics.upcoming_7_days }}</div>
            </div>
            <div class="card">
                <div class="text-surface-600 dark:text-surface-400 text-sm mb-2">14 ngày tới</div>
                <div class="text-3xl font-bold text-orange-600">{{ statistics.upcoming_14_days }}</div>
            </div>
            <div class="card">
                <div class="text-surface-600 dark:text-surface-400 text-sm mb-2">30 ngày tới</div>
                <div class="text-3xl font-bold text-purple-600">{{ statistics.upcoming_30_days }}</div>
            </div>
            <div class="card">
                <div class="text-surface-600 dark:text-surface-400 text-sm mb-2">Đã chi trả</div>
                <div class="text-3xl font-bold text-cyan-600">{{ statistics.paid_count }}</div>
            </div>
            <div class="card">
                <div class="text-surface-600 dark:text-surface-400 text-sm mb-2">Chưa chi trả</div>
                <div class="text-3xl font-bold text-red-600">{{ statistics.unpaid_count }}</div>
            </div>
        </div>

        <!-- Table View -->
        <div v-if="filters.view_mode === 'table'" class="card">
            <DataTable :value="employees" :rows="50" :paginator="true" responsiveLayout="scroll" stripedRows showGridlines>
                <Column field="employee_code" header="Mã NV" style="min-width: 100px" sortable />

                <Column header="Nhân viên" style="min-width: 200px" sortable>
                    <template #body="{ data }">
                        <div class="flex items-center gap-3">
                            <Avatar :label="data.full_name[0]" shape="circle" />
                            <div>
                                <div class="font-medium">{{ data.full_name }}</div>
                                <div class="text-sm text-surface-500">{{ data.company_email }}</div>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="department" header="Phòng/Ban" style="min-width: 150px" sortable />

                <Column header="Ngày sinh" style="min-width: 100px" sortable field="dob">
                    <template #body="{ data }">
                        {{ formatDate(data.dob) }}
                    </template>
                </Column>

                <Column field="age" header="Tuổi" style="min-width: 80px" sortable />

                <Column header="Còn lại" style="min-width: 120px" sortable field="days_until">
                    <template #body="{ data }">
                        <Tag :severity="getDaysUntilSeverity(data.days_until)" :value="getDaysUntilLabel(data.days_until)" />
                    </template>
                </Column>

                <Column field="phone" header="Điện thoại" style="min-width: 120px" />

                <Column header="Chi trả phúc lợi" style="min-width: 200px">
                    <template #body="{ data }">
                        <!-- Đã chi trả -->
                        <div v-if="data.birthday_benefit_payout" class="flex items-center gap-2">
                            <i class="pi pi-check-circle text-green-500"></i>
                            <div class="text-sm">
                                <div class="font-medium">{{ formatCurrency(data.birthday_benefit_payout.amount) }}</div>
                                <div class="text-surface-500">{{ formatDate(data.birthday_benefit_payout.paid_date) }}</div>
                            </div>
                        </div>

                        <!-- Chưa chi trả -->
                        <div v-else class="flex items-center gap-2">
                            <Tag severity="secondary" value="Chưa chi trả" />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-surface-500">
                        <i class="pi pi-inbox text-4xl mb-3 block"></i>
                        <p>Không có sinh nhật nào trong kỳ này</p>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Calendar View -->
        <div v-else class="card">
            <!-- Calendar Header -->
            <div class="flex justify-between items-center mb-4 pb-4 border-b">
                <h2 class="text-xl font-semibold">
                    Tháng {{ filters.month }}, {{ filters.year }}
                </h2>
                <div class="flex gap-2">
                    <Button icon="pi pi-chevron-left" outlined size="small" @click="previousMonth" />
                    <Button icon="pi pi-chevron-right" outlined size="small" @click="nextMonth" />
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-grid">
                <!-- Week day headers -->
                <div v-for="day in weekDays" :key="day" class="calendar-header text-center font-semibold py-2 text-surface-600 dark:text-surface-400">
                    {{ day }}
                </div>

                <!-- Calendar days -->
                <div
                    v-for="(dayData, index) in calendarDays"
                    :key="index"
                    class="calendar-day border border-surface-200 dark:border-surface-700 min-h-[120px] p-2"
                    :class="{
                        'bg-surface-50 dark:bg-surface-800': !dayData.date,
                        'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-600': dayData.isToday,
                    }"
                >
                    <!-- Day number -->
                    <div v-if="dayData.date" class="flex justify-between items-start mb-2">
                        <span class="text-sm font-medium" :class="{ 'text-blue-600 dark:text-blue-400 font-bold': dayData.isToday }">
                            {{ dayData.date }}
                        </span>
                        <Tag v-if="dayData.employees.length > 0" :value="dayData.employees.length" severity="info" rounded />
                    </div>

                    <!-- Employees with birthdays -->
                    <div v-if="dayData.employees.length > 0" class="space-y-1">
                        <div
                            v-for="employee in dayData.employees.slice(0, 3)"
                            :key="employee.id"
                            class="text-xs p-2 rounded transition-all"
                            :class="{
                                'bg-green-100 dark:bg-green-900/30': employee.birthday_benefit_payout,
                                'bg-orange-100 dark:bg-orange-900/30': !employee.birthday_benefit_payout && employee.days_until >= 0 && employee.days_until <= 7,
                                'bg-red-100 dark:bg-red-900/30': !employee.birthday_benefit_payout && employee.days_until < 0,
                                'bg-surface-100 dark:bg-surface-700': !employee.birthday_benefit_payout && employee.days_until > 7,
                            }"
                        >
                            <div class="flex items-center gap-1">
                                <i v-if="employee.birthday_benefit_payout" class="pi pi-check-circle text-green-600 dark:text-green-400 text-xs"></i>
                                <i v-else-if="employee.days_until >= 0 && employee.days_until <= 7" class="pi pi-gift text-orange-600 dark:text-orange-400 text-xs"></i>
                                <i v-else-if="employee.days_until < 0" class="pi pi-exclamation-triangle text-red-600 dark:text-red-400 text-xs"></i>
                                <span class="font-medium truncate">{{ employee.full_name }}</span>
                            </div>
                            <div class="text-[10px] text-surface-600 dark:text-surface-400 truncate">
                                {{ employee.department || 'N/A' }}
                            </div>
                        </div>

                        <!-- Show more indicator -->
                        <div v-if="dayData.employees.length > 3" class="text-xs text-center text-surface-500 dark:text-surface-400 pt-1">
                            +{{ dayData.employees.length - 3 }} người khác
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap gap-4 mt-4 pt-4 border-t text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded"></div>
                    <span class="text-surface-700 dark:text-surface-300">Đã chi trả</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-orange-100 dark:bg-orange-900/30 border border-orange-300 dark:border-orange-700 rounded"></div>
                    <span class="text-surface-700 dark:text-surface-300">Sắp tới (7 ngày)</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded"></div>
                    <span class="text-surface-700 dark:text-surface-300">Đã qua - chưa chi trả</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-300 dark:border-blue-600 rounded"></div>
                    <span class="text-surface-700 dark:text-surface-300">Hôm nay</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0;
}

.calendar-day {
    transition: all 0.2s;
}

.calendar-day:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}
</style>
