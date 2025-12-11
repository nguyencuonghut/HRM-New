import { router } from '@inertiajs/vue3';

export class InsuranceReportService {
    static index(filters = {}, opts = {}) {
        router.get('/insurance-reports', filters, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    static create(opts = {}) {
        router.visit('/insurance-reports/create', {
            preserveState: true,
            ...opts
        });
    }

    static store(data, opts = {}) {
        router.post('/insurance-reports', data, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    static show(id, opts = {}) {
        router.visit(`/insurance-reports/${id}`, {
            preserveState: true,
            ...opts
        });
    }

    static destroy(id, opts = {}) {
        router.delete(`/insurance-reports/${id}`, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    static finalize(id, opts = {}) {
        router.post(`/insurance-reports/${id}/finalize`, {}, {
            preserveState: true,
            preserveScroll: true,
            ...opts
        });
    }

    static export(id) {
        window.location.href = `/insurance-reports/${id}/export`;
    }
}
