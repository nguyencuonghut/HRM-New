import { router } from '@inertiajs/vue3';

/**
 * Report Service
 * Centralized service for all report-related navigation and data operations
 * Keep business logic here, Vue components only handle presentation
 */
export class ReportService {
    /**
     * Report titles mapping (Vietnamese)
     */
    static REPORT_TITLES = {
        'headcount': 'Biên chế nhân sự',
        'employee-list': 'Danh sách nhân viên',
        'data-completeness': 'Độ hoàn thiện hồ sơ',
        'employee-movement': 'Biến động nhân sự',
        'contracts-status': 'Tình trạng hợp đồng',
        'contracts-expiring': 'Hợp đồng sắp hết hạn',
        'contract-approval-sla': 'Thời gian phê duyệt hợp đồng',
        'leave-monthly': 'Tổng hợp nghỉ phép',
        'leave-balances': 'Số dư phép',
    };

    /**
     * Get report title by code
     */
    static getReportTitle(code) {
        return this.REPORT_TITLES[code] || 'Báo cáo';
    }

    /**
     * Navigate to Reports Hub
     */
    static hub(opts = {}) {
        router.visit('/reports', {
            preserveState: false,
            ...opts
        });
    }

    /**
     * Navigate to specific report
     */
    static viewReport(reportCode, params = {}, opts = {}) {
        router.get(`/reports/${reportCode}`, params, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    /**
     * Export report to Excel
     */
    static exportReport(reportCode, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        window.location.href = `/reports/${reportCode}/export${queryString ? '?' + queryString : ''}`;
    }

    // ========== Executive & People Reports ==========

    /**
     * RPT-001: Headcount Snapshot
     */
    static headcount(params = {}, opts = {}) {
        router.get('/reports/headcount', params, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    /**
     * RPT-010: Employee List
     */
    static employeeList(params = {}, opts = {}) {
        router.get('/reports/employee-list', params, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    /**
     * RPT-011: Data Completeness
     */
    static dataCompleteness(params = {}, opts = {}) {
        router.get('/reports/data-completeness', params, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    /**
     * RPT-002: Employee Movement
     */
    static employeeMovement(params = {}, opts = {}) {
        router.get('/reports/employee-movement', params, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    // ========== Contract Reports ==========

    /**
     * RPT-020: Contracts by Status
     */
    static contractsStatus(params = {}, opts = {}) {
        router.get('/reports/contracts-status', params, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    /**
     * RPT-021: Contracts Expiring
     */
    static contractsExpiring(params = {}, opts = {}) {
        router.get('/reports/contracts-expiring', params, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    /**
     * RPT-022: Contract Approval SLA
     */
    static contractApprovalSla(params = {}, opts = {}) {
        router.get('/reports/contract-approval-sla', params, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    // ========== Leave Reports ==========

    /**
     * RPT-030: Monthly Leave Summary
     */
    static leaveMonthly(params = {}, opts = {}) {
        router.get('/reports/leave-monthly', params, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    /**
     * RPT-031: Leave Balances
     */
    static leaveBalances(params = {}, opts = {}) {
        router.get('/reports/leave-balances', params, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    // ========== Helper Methods ==========

    /**
     * Format date for display
     */
    static formatDate(date, format = 'DD/MM/YYYY') {
        if (!date) return '';
        // Use your preferred date library (dayjs, moment, etc.)
        return date;
    }

    /**
     * Format number with locale
     */
    static formatNumber(number, decimals = 0) {
        if (number === null || number === undefined) return '0';
        return new Intl.NumberFormat('vi-VN', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    }

    /**
     * Format percentage
     */
    static formatPercent(value, decimals = 2) {
        if (value === null || value === undefined) return '0%';
        return `${this.formatNumber(value, decimals)}%`;
    }

    /**
     * Get urgency severity for PrimeVue Tag component
     */
    static getUrgencySeverity(urgency) {
        const severityMap = {
            'critical': 'danger',
            'warning': 'warn',
            'normal': 'success',
            'info': 'info'
        };
        return severityMap[urgency] || 'secondary';
    }

    /**
     * Get status severity for PrimeVue Tag component
     */
    static getStatusSeverity(status) {
        const severityMap = {
            'ACTIVE': 'success',
            'PENDING': 'warn',
            'DRAFT': 'secondary',
            'EXPIRED': 'danger',
            'TERMINATED': 'danger',
            'APPROVED': 'success',
            'REJECTED': 'danger',
        };
        return severityMap[status] || 'secondary';
    }

    /**
     * Get month options for Vietnamese locale
     */
    static getMonthOptions() {
        return [
            { label: 'Tháng 1', value: 1 },
            { label: 'Tháng 2', value: 2 },
            { label: 'Tháng 3', value: 3 },
            { label: 'Tháng 4', value: 4 },
            { label: 'Tháng 5', value: 5 },
            { label: 'Tháng 6', value: 6 },
            { label: 'Tháng 7', value: 7 },
            { label: 'Tháng 8', value: 8 },
            { label: 'Tháng 9', value: 9 },
            { label: 'Tháng 10', value: 10 },
            { label: 'Tháng 11', value: 11 },
            { label: 'Tháng 12', value: 12 },
        ];
    }

    /**
     * Get year options (current year and previous years)
     */
    static getYearOptions(yearsBack = 5) {
        const currentYear = new Date().getFullYear();
        const years = [];
        for (let i = 0; i <= yearsBack; i++) {
            years.push(currentYear - i);
        }
        return years;
    }

    /**
     * Get period options for date range filters
     */
    static getPeriodOptions() {
        return [
            { label: 'Hôm nay', value: 'today' },
            { label: 'Hôm qua', value: 'yesterday' },
            { label: 'Tuần này', value: 'this_week' },
            { label: 'Tuần trước', value: 'last_week' },
            { label: 'Tháng này', value: 'this_month' },
            { label: 'Tháng trước', value: 'last_month' },
            { label: 'Quý này', value: 'this_quarter' },
            { label: 'Quý trước', value: 'last_quarter' },
            { label: 'Năm nay', value: 'this_year' },
            { label: 'Năm ngoái', value: 'last_year' },
        ];
    }

    /**
     * Calculate percentage
     */
    static calculatePercentage(value, total) {
        if (!total || total === 0) return 0;
        return Math.round((value / total) * 100 * 100) / 100; // 2 decimals
    }

    /**
     * Get chart color palette
     */
    static getChartColors(count = 10) {
        const colors = [
            '#3B82F6', // blue
            '#10B981', // green
            '#F59E0B', // amber
            '#EF4444', // red
            '#8B5CF6', // purple
            '#EC4899', // pink
            '#14B8A6', // teal
            '#F97316', // orange
            '#6366F1', // indigo
            '#84CC16', // lime
        ];
        return colors.slice(0, count);
    }

    /**
     * Prepare chart data for PrimeVue Chart component
     */
    static prepareChartData(labels, datasets) {
        return {
            labels: labels,
            datasets: datasets.map((dataset, index) => ({
                label: dataset.label,
                data: dataset.data,
                backgroundColor: dataset.backgroundColor || this.getChartColors()[index],
                borderColor: dataset.borderColor || this.getChartColors()[index],
                borderWidth: dataset.borderWidth || 1,
            }))
        };
    }

    /**
     * Get default chart options for PrimeVue Chart
     */
    static getDefaultChartOptions(type = 'bar') {
        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                },
            },
        };

        if (type === 'bar') {
            return {
                ...baseOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            };
        }

        if (type === 'pie' || type === 'doughnut') {
            return {
                ...baseOptions,
                plugins: {
                    ...baseOptions.plugins,
                    legend: {
                        display: true,
                        position: 'right',
                    },
                },
            };
        }

        return baseOptions;
    }
}

// Export as default for easier imports
export default ReportService;
