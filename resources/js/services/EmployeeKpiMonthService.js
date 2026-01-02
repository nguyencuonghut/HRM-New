import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';

export class EmployeeKpiMonthService {
    /**
     * Get all KPI months for index page
     * @param {Object} data - Query parameters for filtering
     * @param {Object} options - Additional options
     */
    static index(data = {}, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.get('/employee-kpi-months', data, {
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Lỗi tải danh sách KPI tháng');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Store a new KPI month
     * @param {Object} kpiData - KPI data to store
     * @param {Object} options - Additional options
     */
    static store(kpiData, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post('/employee-kpi-months', kpiData, {
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors).length === 0) {
                    ToastService.error('Lỗi tạo KPI tháng');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Update an existing KPI month
     * @param {string} kpiId - KPI ID to update
     * @param {Object} kpiData - KPI data to update
     * @param {Object} options - Additional options
     */
    static update(kpiId, kpiData, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.put(`/employee-kpi-months/${kpiId}`, kpiData, {
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors).length === 0) {
                    ToastService.error('Lỗi cập nhật KPI tháng');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Delete a KPI month
     * @param {string} kpiId - KPI ID to delete
     * @param {Object} options - Additional options
     */
    static destroy(kpiId, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete(`/employee-kpi-months/${kpiId}`, {
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Lỗi xóa KPI tháng');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Bulk delete KPI months
     * @param {Array} ids - Array of KPI IDs to delete
     * @param {Object} options - Additional options
     */
    static bulkDelete(ids, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete('/employee-kpi-months/bulk-delete', {
            data: { ids },
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Lỗi xóa KPI tháng');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }
}
