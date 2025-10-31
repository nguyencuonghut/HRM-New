import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';

export class EmployeeService {
    static index(options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        router.get('/employees', {}, {
            onStart: () => { onStart && onStart(); },
            onFinish: () => { onFinish && onFinish(); },
            onError: (errors) => {
                ToastService.error(errors?.message || 'Có lỗi xảy ra khi tải danh sách nhân viên!');
                onError && onError(errors);
            },
            onSuccess: () => { onSuccess && onSuccess(); }
        });
    }

    static store(payload, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        router.post('/employees', payload, {
            onStart: () => { onStart && onStart(); },
            onFinish: () => { onFinish && onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors || {}).length === 0) {
                    ToastService.error('Có lỗi xảy ra khi tạo nhân viên!');
                }
                onError && onError(errors);
            },
            onSuccess: (page) => { onSuccess && onSuccess(page); }
        });
    }

    static update(id, payload, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        router.put(`/employees/${id}`, payload, {
            onStart: () => { onStart && onStart(); },
            onFinish: () => { onFinish && onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors || {}).length === 0) {
                    ToastService.error('Có lỗi xảy ra khi cập nhật nhân viên!');
                }
                onError && onError(errors);
            },
            onSuccess: (page) => { onSuccess && onSuccess(page); }
        });
    }

    static destroy(id, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        router.delete(`/employees/${id}`, {
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
                    ToastService.error('Có lỗi xảy ra khi xóa nhân viên!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    static show(id, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        router.get(`/employees/${id}`, {}, {
            onStart: () => { onStart && onStart(); },
            onFinish: () => { onFinish && onFinish(); },
            onError: (errors) => {
                ToastService.error(errors?.message || 'Có lỗi xảy ra khi tải thông tin nhân viên!');
                onError && onError(errors);
            },
            onSuccess: (page) => { onSuccess && onSuccess(page); }
        });
    }
}
