import { router } from '@inertiajs/vue3';

export class ProvinceService {
    /**
     * Get all provinces for index page
     * @param {Object} options - Additional options
     * @param {Function} options.onStart - Callback when request starts
     * @param {Function} options.onFinish - Callback when request finishes
     * @param {Function} options.onError - Callback when request has errors
     * @param {Function} options.onSuccess - Callback when request succeeds
     */
    static index(options = {}) {
        const { onSuccess, onError } = options;

        router.get('provinces', {}, {
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

    /**
     * Store a new province
     * @param {Object} data - The province data
     * @param {Object} options - Additional options
     * @param {Function} options.onStart - Callback when request starts
     * @param {Function} options.onFinish - Callback when request finishes
     * @param {Function} options.onError - Callback when request has errors
     * @param {Function} options.onSuccess - Callback when request succeeds
     */
    static store(data, options = {}) {
        const { onSuccess, onError } = options;

        router.post('provinces', data, {
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

    /**
     * Update an existing province
     * @param {number} id - The province ID
     * @param {Object} data - The updated province data
     * @param {Object} options - Additional options
     * @param {Function} options.onStart - Callback when request starts
     * @param {Function} options.onFinish - Callback when request finishes
     * @param {Function} options.onError - Callback when request has errors
     * @param {Function} options.onSuccess - Callback when request succeeds
     */
    static update(id, data, options = {}) {
        const { onSuccess, onError } = options;

        router.put(`provinces/${id}`, data, {
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

    /**
     * Delete a province
     * @param {number} id - The province ID
     * @param {Object} options - Additional options
     * @param {Function} options.onStart - Callback when request starts
     * @param {Function} options.onFinish - Callback when request finishes
     * @param {Function} options.onError - Callback when request has errors
     * @param {Function} options.onSuccess - Callback when request succeeds
     */
    static destroy(id, options = {}) {
        const { onSuccess, onError } = options;

        router.delete(`provinces/${id}`, {
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

    /**
     * Bulk delete provinces
     * @param {Array<number>} ids - The province IDs
     * @param {Object} options - Additional options
     * @param {Function} options.onStart - Callback when request starts
     * @param {Function} options.onFinish - Callback when request finishes
     * @param {Function} options.onError - Callback when request has errors
     * @param {Function} options.onSuccess - Callback when request succeeds
     */
    static bulkDelete(ids, options = {}) {
        const { onSuccess, onError } = options;

        router.post('provinces/bulk-delete', { ids }, {
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
