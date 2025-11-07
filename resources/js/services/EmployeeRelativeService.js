import { router } from '@inertiajs/vue3';

export class EmployeeRelativeService {
  static store(employeeId, data, { onStart, onFinish, onSuccess, onError } = {}) {
    router.post(`/employees/${employeeId}/relatives`, data, {
      preserveState: true, preserveScroll: true,
      onStart, onFinish, onSuccess, onError
    });
  }
  static update(employeeId, id, data, { onStart, onFinish, onSuccess, onError } = {}) {
    router.put(`/employees/${employeeId}/relatives/${id}`, data, {
      preserveState: true, preserveScroll: true,
      onStart, onFinish, onSuccess, onError
    });
  }
  static destroy(employeeId, id, { onStart, onFinish, onSuccess, onError } = {}) {
    router.delete(`/employees/${employeeId}/relatives/${id}`, {
      preserveState: true, preserveScroll: true,
      onStart, onFinish, onSuccess, onError
    });
  }
  static bulkDelete(employeeId, ids, { onStart, onFinish, onSuccess, onError } = {}) {
    router.post(`/employees/${employeeId}/relatives/bulk-delete`, { ids }, {
      preserveState: true, preserveScroll: true,
      onStart, onFinish, onSuccess, onError
    });
  }
}
