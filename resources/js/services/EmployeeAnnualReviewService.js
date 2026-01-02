import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';
import axios from 'axios';

export class EmployeeAnnualReviewService {
    /**
     * Get all annual reviews for index page
     * @param {Object} data - Query parameters for filtering
     * @param {Object} options - Additional options
     */
    static index(data = {}, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.get('/employee-annual-reviews', data, {
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
                    ToastService.error('Lỗi tải danh sách đánh giá cuối năm');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Store a new annual review
     * @param {Object} reviewData - Review data to store
     * @param {Object} options - Additional options
     */
    static store(reviewData, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post('/employee-annual-reviews', reviewData, {
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
                    ToastService.error('Lỗi tạo đánh giá cuối năm');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Update an existing annual review
     * @param {string} reviewId - Review ID to update
     * @param {Object} reviewData - Review data to update
     * @param {Object} options - Additional options
     */
    static update(reviewId, reviewData, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.put(`/employee-annual-reviews/${reviewId}`, reviewData, {
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
                    ToastService.error('Lỗi cập nhật đánh giá cuối năm');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Delete an annual review
     * @param {string} reviewId - Review ID to delete
     * @param {Object} options - Additional options
     */
    static destroy(reviewId, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete(`/employee-annual-reviews/${reviewId}`, {
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
                    ToastService.error('Lỗi xóa đánh giá cuối năm');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Bulk delete annual reviews
     * @param {Array} ids - Array of review IDs to delete
     * @param {Object} options - Additional options
     */
    static bulkDelete(ids, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete('/employee-annual-reviews/bulk-delete', {
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
                    ToastService.error('Lỗi xóa đánh giá cuối năm');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Calculate KPI average for employee in a year
     * @param {string} employeeId - Employee ID
     * @param {number} year - Year
     * @returns {Promise} - Promise with KPI average data
     */
    static async calculateKpiAverage(employeeId, year) {
        try {
            const response = await axios.get(`/employee-annual-reviews/calculate-kpi/${employeeId}/${year}`);
            return response.data;
        } catch (error) {
            ToastService.error('Lỗi tính điểm KPI trung bình');
            throw error;
        }
    }
}
