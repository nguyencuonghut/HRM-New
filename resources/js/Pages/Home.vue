<template>
    <Head>
        <title>Dashboard - Hệ thống HRM</title>
    </Head>

    <div>
        <!-- Welcome Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold">Chào mừng trở lại, {{ userName }}! 👋</h1>
            <p class="mt-2">Tổng quan hoạt động hệ thống HRM hôm nay</p>
        </div>

        <!-- Quick Stats Cards - Clickable -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 items-stretch">
            <div class="card h-full cursor-pointer hover:shadow-lg transition-shadow bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-cyan-900/10 dark:to-blue-900/10 border-t-2 border-cyan-400" @click="router.visit('/employees')">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-70 mb-1">Tổng nhân viên</p>
                        <p class="text-3xl font-bold">{{ stats.totalEmployees || 0 }}</p>
                        <p class="text-sm mt-1">
                            <span class="text-green-600 dark:text-green-400 font-semibold">+{{ stats.newEmployeesThisMonth || 0 }}</span> tháng này
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                        <i class="pi pi-users text-2xl text-cyan-600 dark:text-cyan-400"></i>
                    </div>
                </div>
            </div>

            <div class="card h-full cursor-pointer hover:shadow-lg transition-shadow bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/10 dark:to-purple-900/10 border-t-2 border-indigo-400" @click="router.visit('/leave-requests')">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-70 mb-1">Nghỉ phép hôm nay</p>
                        <p class="text-3xl font-bold">{{ stats.onLeaveToday || 0 }}</p>
                        <p class="text-sm mt-1">Đang chờ duyệt: {{ stats.pendingLeave || 0 }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <i class="pi pi-calendar text-2xl text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                </div>
            </div>

            <div class="card h-full cursor-pointer hover:shadow-lg transition-shadow bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/10 dark:to-orange-900/10 border-t-2 border-amber-400" @click="router.visit('/reports/contracts-expiring')">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-70 mb-1">HĐ sắp hết hạn</p>
                        <p class="text-3xl font-bold text-amber-600 dark:text-amber-500">{{ stats.expiringContracts || 0 }}</p>
                        <p class="text-sm mt-1">Trong 30 ngày tới</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <i class="pi pi-clock text-2xl text-amber-600 dark:text-amber-400"></i>
                    </div>
                </div>
            </div>

            <div class="card h-full cursor-pointer hover:shadow-lg transition-shadow bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/10 dark:to-pink-900/10 border-t-2 border-rose-400" @click="router.visit('/leave-approvals')">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-70 mb-1">Chờ xử lý</p>
                        <p class="text-3xl font-bold text-rose-600 dark:text-rose-500">{{ stats.pendingApprovals || 0 }}</p>
                        <p class="text-sm mt-1">Cần phê duyệt</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center">
                        <i class="pi pi-exclamation-circle text-2xl text-rose-600 dark:text-rose-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Priority Panel: Cần xử lý hôm nay -->
        <div class="card mb-6 border-l-4 border-rose-500 bg-gradient-to-r from-rose-50/50 to-transparent dark:from-rose-900/10 dark:to-transparent">
            <div class="flex items-center gap-2 mb-4">
                <i class="pi pi-bell text-xl text-rose-600 dark:text-rose-400"></i>
                <h2 class="text-xl font-semibold">Cần xử lý hôm nay</h2>
            </div>
            <div v-if="priorityItems && priorityItems.length > 0" class="space-y-3">
                <div
                    v-for="item in priorityItems"
                    :key="item.id"
                    @click="handlePriorityClick(item)"
                    class="flex items-center gap-3 p-3 border surface-border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition-colors"
                >
                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0', getPriorityBgClass(item.type)]">
                        <i :class="['pi', getPriorityIcon(item.type), getPriorityIconClass(item.type)]"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium">{{ item.title }}</p>
                        <p class="text-sm opacity-70">{{ item.description }}</p>
                    </div>
                    <Badge :value="item.count" :severity="getPrioritySeverity(item.type)" />
                </div>
            </div>
            <div v-else class="text-center py-8">
                <i class="pi pi-check-circle text-4xl text-green-500 mb-3"></i>
                <p class="font-medium">Tuyệt vời! Không có việc cần xử lý khẩn cấp.</p>
                <p class="text-sm opacity-70 mt-1">Bạn có thể tập trung vào các công việc khác.</p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Quick Actions & Chart (2/3 width) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Quick Actions - Moved to top -->
                <div class="card bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-900/30 dark:to-gray-900/30">
                    <h2 class="text-xl font-semibold mb-4">Thao tác nhanh</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <Button label="Thêm nhân viên" icon="pi pi-user-plus" class="w-full" severity="info" outlined @click="router.visit('/employees')" />
                        <Button label="Tạo hợp đồng" icon="pi pi-file-edit" class="w-full" severity="success" outlined @click="router.visit('/contracts')" />
                        <Button label="Đơn nghỉ phép" icon="pi pi-calendar" class="w-full" severity="warning" outlined @click="router.visit('/leave-requests')" />
                        <Button label="Báo cáo & Thống kê" icon="pi pi-chart-bar" class="w-full" severity="help" outlined @click="router.visit('/reports')" />
                    </div>
                </div>

                <!-- Employee Distribution Chart - Compact -->
                <div class="card">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-semibold">Nhân sự theo phòng ban</h2>
                        <Button icon="pi pi-refresh" text rounded size="small" @click="refreshData" />
                    </div>
                    <div v-if="departmentStats && departmentStats.length > 0">
                        <Chart type="bar" :data="departmentChartData" :options="chartOptions" class="h-48" />
                    </div>
                    <div v-else class="text-center py-8">
                        <i class="pi pi-chart-bar text-4xl opacity-30 mb-3"></i>
                        <p class="opacity-70">Chưa có dữ liệu phòng ban.</p>
                        <p class="text-sm opacity-50 mt-1">Hãy thêm nhân viên và phân công phòng ban để xem thống kê.</p>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="card">
                    <h2 class="text-lg font-semibold mb-4">Hoạt động gần đây</h2>
                    <div v-if="recentActivities && recentActivities.length > 0" class="space-y-3">
                        <div v-for="activity in recentActivities" :key="activity.id" class="flex items-start gap-3 p-3 border surface-border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0', getActivityBgClass(activity.type)]">
                                <i :class="['pi', getActivityIcon(activity.type), getActivityIconClass(activity.type)]"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium">{{ activity.description }}</p>
                                <p class="text-sm opacity-70">{{ activity.user }} • {{ formatDate(activity.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8">
                        <i class="pi pi-history text-4xl opacity-30 mb-3"></i>
                        <p class="opacity-70">Chưa có hoạt động nào.</p>
                        <p class="text-sm opacity-50 mt-1">Các hoạt động trong hệ thống sẽ được ghi nhận tại đây.</p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Upcoming Events (1/3 width) -->
            <div class="space-y-6">
                <!-- Upcoming Events -->
                <div class="card">
                    <h2 class="text-xl font-semibold mb-4">Sự kiện sắp tới</h2>
                    <div v-if="upcomingEvents && upcomingEvents.length > 0" class="space-y-3">
                        <div v-for="event in upcomingEvents" :key="event.id" class="flex items-start gap-3 p-3 border surface-border rounded-lg">
                            <div class="text-center flex-shrink-0">
                                <div class="text-2xl font-bold">{{ formatDay(event.date) }}</div>
                                <div class="text-xs opacity-70">Tháng {{ formatMonth(event.date) }}</div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium">{{ event.title }}</p>
                                <p class="text-sm opacity-70">{{ event.description }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8">
                        <i class="pi pi-calendar text-4xl opacity-30 mb-3"></i>
                        <p class="opacity-70">Không có sự kiện sắp tới.</p>
                        <p class="text-sm opacity-50 mt-1">Sinh nhật và hợp đồng hết hạn sẽ hiển thị tại đây.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Chart from 'primevue/chart';
import { useFlashMessages } from '../composables/useFlashMessages';

const { handleFlashMessages } = useFlashMessages();
const page = usePage();

// Props from backend (will be added later)
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalEmployees: 0,
            newEmployeesThisMonth: 0,
            onLeaveToday: 0,
            pendingLeave: 0,
            expiringContracts: 0,
            pendingApprovals: 0
        })
    },
    departmentStats: {
        type: Array,
        default: () => []
    },
    recentActivities: {
        type: Array,
        default: () => []
    },
    upcomingEvents: {
        type: Array,
        default: () => []
    },
    priorityItems: {
        type: Array,
        default: () => []
    }
});

const userName = computed(() => page.props.auth?.user?.name || 'Admin');

// Chart data
const departmentChartData = computed(() => {
    if (!props.departmentStats || props.departmentStats.length === 0) {
        return {
            labels: ['Chưa có dữ liệu'],
            datasets: [{
                label: 'Số nhân viên',
                data: [0],
                backgroundColor: 'rgba(99, 102, 241, 0.7)',
                borderColor: 'rgba(99, 102, 241, 1)',
                borderWidth: 2,
                borderRadius: 8,
            }]
        };
    }

    // Gradient colors cho từng department (mở rộng thêm nhiều màu)
    const colors = [
        'rgba(34, 197, 94, 0.7)',   // green
        'rgba(59, 130, 246, 0.7)',  // blue
        'rgba(168, 85, 247, 0.7)',  // purple
        'rgba(236, 72, 153, 0.7)',  // pink
        'rgba(251, 146, 60, 0.7)',  // orange
        'rgba(14, 165, 233, 0.7)',  // cyan
        'rgba(245, 158, 11, 0.7)',  // amber
        'rgba(139, 92, 246, 0.7)',  // violet
        'rgba(20, 184, 166, 0.7)',  // teal
        'rgba(239, 68, 68, 0.7)',   // red
        'rgba(52, 211, 153, 0.7)',  // emerald
        'rgba(96, 165, 250, 0.7)',  // light-blue
        'rgba(192, 132, 252, 0.7)', // lavender
        'rgba(251, 113, 133, 0.7)', // rose
        'rgba(253, 186, 116, 0.7)', // peach
        'rgba(103, 232, 249, 0.7)', // sky
        'rgba(253, 224, 71, 0.7)',  // yellow
        'rgba(167, 139, 250, 0.7)', // indigo
        'rgba(134, 239, 172, 0.7)', // lime
        'rgba(251, 191, 36, 0.7)',  // gold
    ];

    const borderColors = [
        'rgba(34, 197, 94, 1)',
        'rgba(59, 130, 246, 1)',
        'rgba(168, 85, 247, 1)',
        'rgba(236, 72, 153, 1)',
        'rgba(251, 146, 60, 1)',
        'rgba(14, 165, 233, 1)',
        'rgba(245, 158, 11, 1)',
        'rgba(139, 92, 246, 1)',
        'rgba(20, 184, 166, 1)',
        'rgba(239, 68, 68, 1)',
        'rgba(52, 211, 153, 1)',
        'rgba(96, 165, 250, 1)',
        'rgba(192, 132, 252, 1)',
        'rgba(251, 113, 133, 1)',
        'rgba(253, 186, 116, 1)',
        'rgba(103, 232, 249, 1)',
        'rgba(253, 224, 71, 1)',
        'rgba(167, 139, 250, 1)',
        'rgba(134, 239, 172, 1)',
        'rgba(251, 191, 36, 1)',
    ];

    return {
        labels: props.departmentStats.map(d => d.name),
        datasets: [{
            label: 'Số nhân viên',
            data: props.departmentStats.map(d => d.count),
            backgroundColor: props.departmentStats.map((_, i) => colors[i % colors.length]),
            borderColor: props.departmentStats.map((_, i) => borderColors[i % borderColors.length]),
            borderWidth: 2,
            borderRadius: 8,
            hoverBackgroundColor: props.departmentStats.map((_, i) => borderColors[i % borderColors.length]),
        }]
    };
});

const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1
            }
        }
    }
});

// Handle flash messages
onMounted(() => {
    handleFlashMessages();
});

const refreshData = () => {
    router.reload({ only: ['stats', 'departmentStats', 'recentActivities', 'upcomingEvents'] });
};

// Activity helpers
const getActivityIcon = (type) => {
    const icons = {
        'employee_created': 'pi-user-plus',
        'contract_created': 'pi-file-edit',
        'leave_approved': 'pi-check',
        'leave_rejected': 'pi-times',
        'default': 'pi-info-circle'
    };
    return icons[type] || icons.default;
};

const getActivityBgClass = (type) => {
    const classes = {
        'employee_created': 'bg-green-100 dark:bg-green-900/20',
        'contract_created': 'bg-blue-100 dark:bg-blue-900/20',
        'leave_approved': 'bg-green-100 dark:bg-green-900/20',
        'leave_rejected': 'bg-red-100 dark:bg-red-900/20',
        'default': 'bg-gray-100 dark:bg-gray-800'
    };
    return classes[type] || classes.default;
};

const getActivityIconClass = (type) => {
    const classes = {
        'employee_created': 'text-green-600 dark:text-green-400',
        'contract_created': 'text-blue-600 dark:text-blue-400',
        'leave_approved': 'text-green-600 dark:text-green-400',
        'leave_rejected': 'text-red-600 dark:text-red-400',
        'default': 'text-gray-600'
    };
    return classes[type] || classes.default;
};

// Date helpers
const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    const now = new Date();
    const diffTime = Math.abs(now - d);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Hôm nay';
    if (diffDays === 1) return 'Hôm qua';
    if (diffDays < 7) return `${diffDays} ngày trước`;

    return d.toLocaleDateString('vi-VN');
};

const formatDay = (date) => {
    return new Date(date).getDate();
};

const formatMonth = (date) => {
    return new Date(date).getMonth() + 1;
};

// Priority panel helpers
const getPriorityIcon = (type) => {
    const icons = {
        'contract_expiring_soon': 'pi-clock',
        'pending_leave': 'pi-calendar',
        'pending_contract': 'pi-file-edit',
        'incomplete_profile': 'pi-exclamation-triangle',
        'default': 'pi-info-circle'
    };
    return icons[type] || icons.default;
};

const getPriorityBgClass = (type) => {
    const classes = {
        'contract_expiring_soon': 'bg-orange-100 dark:bg-orange-900/20',
        'pending_leave': 'bg-purple-100 dark:bg-purple-900/20',
        'pending_contract': 'bg-blue-100 dark:bg-blue-900/20',
        'incomplete_profile': 'bg-red-100 dark:bg-red-900/20',
        'default': 'bg-gray-100 dark:bg-gray-800'
    };
    return classes[type] || classes.default;
};

const getPriorityIconClass = (type) => {
    const classes = {
        'contract_expiring_soon': 'text-orange-600 dark:text-orange-400',
        'pending_leave': 'text-purple-600 dark:text-purple-400',
        'pending_contract': 'text-blue-600 dark:text-blue-400',
        'incomplete_profile': 'text-red-600 dark:text-red-400',
        'default': 'text-gray-600'
    };
    return classes[type] || classes.default;
};

const getPrioritySeverity = (type) => {
    const severity = {
        'contract_expiring_soon': 'warn',
        'pending_leave': 'info',
        'pending_contract': 'info',
        'incomplete_profile': 'danger',
        'default': 'secondary'
    };
    return severity[type] || severity.default;
};

const handlePriorityClick = (item) => {
    const routes = {
        'contract_expiring_soon': '/reports/contracts-expiring',
        'pending_leave': '/leave-approvals',
        'pending_contract': '/contracts',
        'incomplete_profile': '/employees',
    };
    const route = routes[item.type] || '/';
    router.visit(route);
};
</script>
