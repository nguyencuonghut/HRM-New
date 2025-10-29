// resources/js/Pages/Departments/OrgService.js
import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';

export class OrgService {
    /**
     * Điều hướng tới trang Org Chart (Inertia page)
     */
    static index(options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.get('/departments/org', {}, {
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi xảy ra khi tải Sơ đồ tổ chức!');
                }
                if (onError) onError(errors);
            },
            onSuccess: () => { if (onSuccess) onSuccess(); }
        });
    }

    /**
     * Lấy danh sách root nodes (JSON, không điều hướng)
     */
    static roots(options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        if (onStart) onStart();
        return window.axios.get('/departments/tree')
            .then(({ data }) => {
                if (onSuccess) onSuccess(data);
                return data;
            })
            .catch((err) => {
                const msg = err?.response?.data?.message || err?.message || 'Có lỗi khi tải cây tổ chức (root)!';
                ToastService.error(msg);
                if (onError) onError(err);
                throw err;
            })
            .finally(() => { if (onFinish) onFinish(); });
    }

    /**
     * Lấy danh sách con của một node (JSON, không điều hướng)
     * @param {string} parentId
     */
    static children(parentId, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        if (onStart) onStart();
        return window.axios.get('/departments/children', { params: { parent_id: parentId } })
            .then(({ data }) => {
                if (onSuccess) onSuccess(data);
                return data;
            })
            .catch((err) => {
                const msg = err?.response?.data?.message || err?.message || 'Có lỗi khi tải đơn vị con!';
                ToastService.error(msg);
                if (onError) onError(err);
                throw err;
            })
            .finally(() => { if (onFinish) onFinish(); });
    }
}
