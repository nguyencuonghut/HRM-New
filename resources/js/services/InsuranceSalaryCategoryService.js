import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';

export class InsuranceSalaryCategoryService {
    /**
     * Get all salary categories for index page
     */
    static index(data = {}, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.get('/insurance-salary-categories', data, {
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
                    ToastService.error('Lỗi tải danh sách nhóm chức danh');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Store a new salary category
     */
    static store(categoryData, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post('/insurance-salary-categories', categoryData, {
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
                    ToastService.error('Lỗi tạo nhóm chức danh');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Update an existing salary category
     */
    static update(categoryId, categoryData, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.put(`/salary-categories/${categoryId}`, categoryData, {
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
                    ToastService.error('Lỗi cập nhật nhóm chức danh');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            },
            preserveScroll: true
        });
    }

    /**
     * Delete a salary category
     */
    static destroy(categoryId, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete(`/salary-categories/${categoryId}`, {
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
                    ToastService.error('Lỗi xóa nhóm chức danh');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            },
            preserveScroll: true
        });
    }

    /**
     * Bulk delete salary categories
     */
    static bulkDelete(ids, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete('/insurance-salary-categories/bulk-delete', {
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
                    ToastService.error('Lỗi xóa nhóm chức danh');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            },
            preserveScroll: true
        });
    }
}
