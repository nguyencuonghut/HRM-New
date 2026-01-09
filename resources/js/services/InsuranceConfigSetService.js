import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';
import { useI18n } from '../composables/useI18n';

/**
 * Insurance Config Set Service
 *
 * Quản lý API calls cho Insurance Config Sets
 */
export class InsuranceConfigSetService {
    /**
     * Get list of config sets
     * @param {Object} filters - Filter parameters (search, status, per_page)
     * @param {Object} options - Callbacks
     */
    static index(filters = {}, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        const { t } = useI18n();

        router.get('/insurance-config-sets', filters, {
            preserveState: true,
            preserveScroll: true,
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(t(errors.message));
                } else {
                    ToastService.error('Lỗi khi tải danh sách config');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Show detail of a config set
     * @param {Number} id - Config set ID
     * @param {Object} options - Callbacks
     */
    static show(id, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        const { t } = useI18n();

        router.get(`/insurance-config-sets/${id}`, {}, {
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(t(errors.message));
                } else {
                    ToastService.error('Lỗi khi tải chi tiết config');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Navigate to create page
     */
    static create() {
        router.visit('/insurance-config-sets/create');
    }

    /**
     * Navigate to edit page
     * @param {Number} id - Config set ID
     */
    static edit(id) {
        router.visit(`/insurance-config-sets/${id}/edit`);
    }

    /**
     * Store a new config set
     * @param {Object} data - Config set data
     * @param {Object} options - Callbacks
     */
    static store(data, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        const { t } = useI18n();

        router.post('/insurance-config-sets', data, {
            preserveState: true,
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(t(errors.message));
                } else {
                    ToastService.error('Lỗi khi tạo config');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                ToastService.success('Tạo config thành công');
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Update a config set
     * @param {Number} id - Config set ID
     * @param {Object} data - Updated data
     * @param {Object} options - Callbacks
     */
    static update(id, data, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        const { t } = useI18n();

        router.put(`/insurance-config-sets/${id}`, data, {
            preserveState: true,
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(t(errors.message));
                } else {
                    ToastService.error('Lỗi khi cập nhật config');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                ToastService.success('Cập nhật config thành công');
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Delete a config set
     * @param {Number} id - Config set ID
     * @param {Object} options - Callbacks
     */
    static destroy(id, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        const { t } = useI18n();

        router.delete(`/insurance-config-sets/${id}`, {
            preserveState: true,
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(t(errors.message));
                } else {
                    ToastService.error('Lỗi khi xóa config');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                ToastService.success('Xóa config thành công');
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Activate a config set
     * @param {Number} id - Config set ID
     * @param {Object} options - Callbacks
     */
    static activate(id, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        const { t } = useI18n();

        router.post(`/insurance-config-sets/${id}/activate`, {}, {
            preserveState: true,
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(t(errors.message));
                } else {
                    ToastService.error('Lỗi khi kích hoạt config');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                ToastService.success('Kích hoạt config thành công');
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Archive a config set
     * @param {Number} id - Config set ID
     * @param {Object} options - Callbacks
     */
    static archive(id, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        const { t } = useI18n();

        router.post(`/insurance-config-sets/${id}/archive`, {}, {
            preserveState: true,
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(t(errors.message));
                } else {
                    ToastService.error('Lỗi khi lưu trữ config');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                ToastService.success('Lưu trữ config thành công');
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Clone a config set
     * @param {Number} id - Config set ID to clone
     * @param {Object} data - New config data (code, name, effective_from, effective_to)
     * @param {Object} options - Callbacks
     */
    static clone(id, data, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        const { t } = useI18n();

        router.post(`/insurance-config-sets/${id}/clone`, data, {
            preserveState: true,
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(t(errors.message));
                } else {
                    ToastService.error('Lỗi khi sao chép config');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                ToastService.success('Sao chép config thành công');
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Get current active config
     * @param {Object} options - Callbacks
     */
    static getCurrent(options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;
        const { t } = useI18n();

        router.get('/insurance-config-sets/current', {}, {
            preserveState: true,
            preserveScroll: true,
            onStart: () => {
                if (onStart) onStart();
            },
            onFinish: () => {
                if (onFinish) onFinish();
            },
            onError: (errors) => {
                if (errors.message) {
                    ToastService.error(t(errors.message));
                } else {
                    ToastService.error('Lỗi khi tải config hiện tại');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => {
                if (onSuccess) onSuccess();
            }
        });
    }
}
