import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';

export class SkillCategoryService {
    /**
     * Get all skill categories
     */
    static index(data = {}, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.get('/skill-categories', data, {
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
                    ToastService.error('Lỗi tải danh sách danh mục kỹ năng');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Store a new skill category
     */
    static store(categoryData, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post('/skill-categories', categoryData, {
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
                    ToastService.error('Lỗi tạo danh mục kỹ năng');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Update an existing skill category
     */
    static update(categoryId, categoryData, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.put(`/skill-categories/${categoryId}`, categoryData, {
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
                    ToastService.error('Lỗi cập nhật danh mục kỹ năng');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Delete a skill category
     */
    static destroy(categoryId, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete(`/skill-categories/${categoryId}`, {
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
                    ToastService.error('Lỗi xóa danh mục kỹ năng');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    /**
     * Bulk delete skill categories
     */
    static bulkDelete(ids, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post('/skill-categories/bulk-delete', { ids }, {
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
                    ToastService.error('Lỗi xóa nhiều danh mục kỹ năng');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }
}
