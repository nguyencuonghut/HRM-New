import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';

export class CompanyRegionService {
    /**
     * Get all company regions for index page
     */
    static index(data = {}, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.get('/company-regions', data, {
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
                    ToastService.error('Lỗi tải danh sách vùng BHXH');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Store a new company region
     */
    static store(regionData, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post('/company-regions', regionData, {
            preserveScroll: true,
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Update an existing company region
     */
    static update(id, regionData, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.put(`/company-regions/${id}`, regionData, {
            preserveScroll: true,
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Delete a company region
     */
    static destroy(id, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete(`/company-regions/${id}`, {
            preserveScroll: true,
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(errors.message);
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }
}
