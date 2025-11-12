import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';

export class ContractAppendixService {
    static index(contractId, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.get(`/contracts/${contractId}/appendixes`, {}, {
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi xảy ra khi tải danh sách phụ lục!');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => { if (onSuccess) onSuccess(); }
        });
    }

    static store(contractId, data, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post(`/contracts/${contractId}/appendixes`, data, {
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors).length === 0) {
                    ToastService.error('Có lỗi xảy ra khi tạo phụ lục!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    static update(contractId, id, data, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.put(`/contracts/${contractId}/appendixes/${id}`, data, {
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors).length === 0) {
                    ToastService.error('Có lỗi xảy ra khi cập nhật phụ lục!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    static destroy(contractId, id, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete(`/contracts/${contractId}/appendixes/${id}`, {
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi xảy ra khi xóa phụ lục!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    static bulkDelete(contractId, ids, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post(`/contracts/${contractId}/appendixes/bulk-delete`, { ids }, {
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi xảy ra khi xóa các phụ lục!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    static approve(contractId, id, data = {}, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post(`/contracts/${contractId}/appendixes/${id}/approve`, data, {
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi xảy ra khi phê duyệt phụ lục!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }

    static reject(contractId, id, data = {}, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post(`/contracts/${contractId}/appendixes/${id}/reject`, data, {
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi xảy ra khi từ chối phụ lục!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            }
        });
    }
}
