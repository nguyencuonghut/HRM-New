import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';

export class DepartmentService {
    /**
     * Get all departments for index page
     * @param {Object} data - Query parameters for filtering
     * @param {Object} options - Additional options
     * @param {Function} options.onStart - Callback when request starts
     * @param {Function} options.onFinish - Callback when request finishes
     * @param {Function} options.onError - Callback when request has errors
     * @param {Function} options.onSuccess - Callback when request succeeds
     */
    static index(data = {}, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.get('/departments', data, {
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
                    ToastService.error('Lỗi tải danh sách phòng/ban');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Store a new department
     * @param {Object} departmentData - Department data to store
     * @param {Object} options - Additional options
     */
    static store(departmentData, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post('/departments', departmentData, {
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                // Chỉ hiển thị toast cho general errors, không cho field validation errors
                if (errors.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors).length === 0) {
                    ToastService.error('Lỗi tạo phòng/ban');
                }
                // Field validation errors sẽ được hiển thị dưới form
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                // Success message sẽ được hiển thị qua flash message từ backend
                // Không cần hiển thị toast ở đây để tránh duplicate
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Update an existing department
     * @param {number} departmentId - Department ID to update
     * @param {Object} departmentData - Department data to update
     * @param {Object} options - Additional options
     */
    static update(departmentId, departmentData, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.put(`/departments/${departmentId}`, departmentData, {
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                // Chỉ hiển thị toast cho general errors, không cho field validation errors
                if (errors.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors).length === 0) {
                    ToastService.error('Lỗi cập nhật phòng/ban');
                }
                // Field validation errors sẽ được hiển thị dưới form
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                // Success message sẽ được hiển thị qua flash message từ backend
                // Không cần hiển thị toast ở đây để tránh duplicate
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Delete a department
     * @param {number} departmentId - Department ID to delete
     * @param {Object} options - Additional options
     */
    static destroy(departmentId, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete(`/departments/${departmentId}`, {
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
                    ToastService.error('Lỗi xóa phòng/ban');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                // Success message sẽ được hiển thị qua flash message từ backend
                // Không cần hiển thị toast ở đây để tránh duplicate
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Delete multiple departments
     * @param {Array} departmentIds - Array of department IDs to delete
     * @param {Object} options - Additional options
     */
    static bulkDestroy(departmentIds, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete('/departments-bulk', {
            data: {
                ids: departmentIds, // Gửi mảng IDs
            },
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
                    ToastService.error('Lỗi xóa nhiều phòng/ban');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                // Success message sẽ được hiển thị qua flash message từ backend
                // Không cần hiển thị toast ở đây để tránh duplicate
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Show a specific department
     * @param {number} departmentId - Department ID to show
     * @param {Object} options - Additional options
     */
    static show(departmentId, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.get(`/departments/${departmentId}`, {}, {
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
                    ToastService.error('Lỗi tải phòng/ban');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Restore a soft deleted department
     * @param {number} departmentId - Department ID to restore
     * @param {Object} options - Additional options
     */
    static restore(departmentId, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.patch(`/departments/${departmentId}/restore`, {}, {
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
                    ToastService.error('Lỗi khôi phục phòng/ban');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                // Success message sẽ được hiển thị qua flash message từ backend
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Permanently delete a department (force delete)
     * @param {number} departmentId - Department ID to permanently delete
     * @param {Object} options - Additional options
     */
    static forceDelete(departmentId, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete(`/departments/${departmentId}/force`, {
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
                    ToastService.error('Lỗi xóa phòng/ban');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                // Success message sẽ được hiển thị qua flash message từ backend
                if (onSuccess) onSuccess(page);
            }
        });
    }
}
