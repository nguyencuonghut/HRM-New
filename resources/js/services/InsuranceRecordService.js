import { router } from '@inertiajs/vue3';

export class InsuranceRecordService {
    static approve(id, opts = {}) {
        router.post(`/insurance-records/${id}/approve`, {}, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    static reject(id, data, opts = {}) {
        router.post(`/insurance-records/${id}/reject`, data, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    static adjust(id, data, opts = {}) {
        router.post(`/insurance-records/${id}/adjust`, data, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }
}
