// resources/js/Pages/EmployeeAssignments/Service.js
import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';

export class EmployeeAssignmentService {
    /**
     * Điều hướng tới trang Index (có thể kèm query)
     */
    static index(params = {}, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.get('/employee-assignments', params, {
            preserveState: true,
            replace: true,
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi khi tải danh sách phân công!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => { if (onSuccess) onSuccess(page); },
        });
    }

    /**
     * Tạo mới phân công
     */
    static store(payload, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post('/employee-assignments', payload, {
            preserveScroll: true,
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                // Field errors sẽ hiển thị ở form; chỉ toast nếu general error
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors || {}).length === 0) {
                    ToastService.error('Có lỗi khi tạo phân công!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => { if (onSuccess) onSuccess(page); },
        });
    }

    /**
     * Cập nhật phân công
     */
    static update(id, payload, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.put(`/employee-assignments/${id}`, payload, {
            preserveScroll: true,
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors || {}).length === 0) {
                    ToastService.error('Có lỗi khi cập nhật phân công!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => { if (onSuccess) onSuccess(page); },
        });
    }

    /**
     * Xoá một phân công
     */
    static destroy(id, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete(`/employee-assignments/${id}`, {
            preserveScroll: true,
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi khi xoá phân công!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => { if (onSuccess) onSuccess(page); },
        });
    }

    /**
     * Xoá nhiều phân công
     */
    static bulkDelete(ids, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post('/employee-assignments/bulk-delete', { ids }, {
            preserveScroll: true,
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi khi xoá phân công!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => { if (onSuccess) onSuccess(page); },
        });
    }

    // ============= NESTED ROUTES FOR EMPLOYEE PROFILE =============

    /**
     * Tạo mới phân công cho nhân viên (nested trong profile)
     */
    static storeForEmployee(employeeId, payload, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post(`/employees/${employeeId}/assignments`, payload, {
            preserveScroll: true,
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors || {}).length === 0) {
                    ToastService.error('Có lỗi khi tạo phân công!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => { if (onSuccess) onSuccess(page); },
        });
    }

    /**
     * Cập nhật phân công cho nhân viên (nested trong profile)
     */
    static updateForEmployee(employeeId, assignmentId, payload, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.put(`/employees/${employeeId}/assignments/${assignmentId}`, payload, {
            preserveScroll: true,
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else if (Object.keys(errors || {}).length === 0) {
                    ToastService.error('Có lỗi khi cập nhật phân công!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => { if (onSuccess) onSuccess(page); },
        });
    }

    /**
     * Xoá phân công cho nhân viên (nested trong profile)
     */
    static destroyForEmployee(employeeId, assignmentId, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.delete(`/employees/${employeeId}/assignments/${assignmentId}`, {
            preserveScroll: true,
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi khi xoá phân công!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => { if (onSuccess) onSuccess(page); },
        });
    }

    /**
     * Xoá nhiều phân công cho nhân viên (nested trong profile)
     */
    static bulkDeleteForEmployee(employeeId, ids, options = {}) {
        const { onStart, onFinish, onError, onSuccess } = options;

        router.post(`/employees/${employeeId}/assignments/bulk-delete`, { ids }, {
            preserveScroll: true,
            onStart: () => { if (onStart) onStart(); },
            onFinish: () => { if (onFinish) onFinish(); },
            onError: (errors) => {
                if (errors?.message) {
                    ToastService.error(errors.message);
                } else {
                    ToastService.error('Có lỗi khi xoá phân công!');
                }
                if (onError) onError(errors);
            },
            onSuccess: (page) => { if (onSuccess) onSuccess(page); },
        });
    }
}
