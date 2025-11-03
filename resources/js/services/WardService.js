import { router } from '@inertiajs/vue3';

export class WardService {
    static index(options = {}) {
        const { onSuccess, onError } = options;

        router.get('wards', {}, {
                onSuccess: (page) => {
                    if (onSuccess) onSuccess(page);
                },
                onError: (errors) => {
                    if (onError) onError(errors);
                },
                preserveState: true,
                preserveScroll: true
            }
        );
    }

    static store(data, options = {}) {
        const { onSuccess, onError } = options;

        router.post('wards', data, {
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            },
                onError: (errors) => {
                    if (onError) onError(errors);
                },
                preserveState: true,
                preserveScroll: true
            }
        );
    }

    static update(id, data, options = {}) {
        const { onSuccess, onError } = options;

        router.put(`wards/${id}`, data, {
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            },
                onError: (errors) => {
                    if (onError) onError(errors);
                },
                preserveState: true,
                preserveScroll: true
            }
        );
    }

    static destroy(id, options = {}) {
        const { onSuccess, onError } = options;

        router.delete(`wards/${id}`, {
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            },
            onError: (errors) => {
                    if (onError) onError(errors);
                },
                preserveState: true,
                preserveScroll: true
            }
        );
    }

    static bulkDelete(ids, options = {}) {
        const { onSuccess, onError } = options;

        router.post('wards/bulk-delete', { ids }, {
            onSuccess: (page) => {
                if (onSuccess) onSuccess(page);
            },
                onError: (errors) => {
                    if (onError) onError(errors);
                },
                preserveState: true,
                preserveScroll: true
            }
        );
    }
};

