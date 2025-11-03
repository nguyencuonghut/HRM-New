import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';

export class PositionService {
    static index(options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.get('/positions', {}, {
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi xảy ra khi tải danh sách chức vụ!');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => { if (onSuccess) onSuccess(); }
        });
    }

    static store(data, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post('/positions', data, {
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                // Chỉ hiển thị toast cho general errors, không cho field validation errors
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors).length === 0) {
                    ToastService.error('Có lỗi xảy ra khi tạo chức vụ!');
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

    static update(id, data, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.put(`/positions/${id}`, data, {
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                // Chỉ hiển thị toast cho general errors, không cho field validation errors
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors).length === 0) {
                    ToastService.error('Có lỗi xảy ra khi cập nhật chức vụ!');
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

    static destroy(id, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete(`/positions/${id}`, {
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi xảy ra khi xóa chức vụ!');
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

    static bulkDelete(ids, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete('/positions/bulk-delete', {
            data: { ids },
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi xảy ra khi xóa các chức vụ!');
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
}
