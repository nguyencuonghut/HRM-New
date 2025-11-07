import { router } from '@inertiajs/vue3';

export class EmployeeSkillService {
  static store(employeeId, data, { onStart, onFinish, onSuccess, onError } = {}) {
    router.post(`/employees/${employeeId}/skills`, data, {
      preserveState: true, preserveScroll: true,
      onStart, onFinish, onSuccess, onError
    });
  }
  static update(employeeId, id, data, { onStart, onFinish, onSuccess, onError } = {}) {
    router.put(`/employees/${employeeId}/skills/${id}`, data, {
      preserveState: true, preserveScroll: true,
      onStart, onFinish, onSuccess, onError
    });
  }
  static destroy(employeeId, id, { onStart, onFinish, onSuccess, onError } = {}) {
    router.delete(`/employees/${employeeId}/skills/${id}`, {
      preserveState: true, preserveScroll: true,
      onStart, onFinish, onSuccess, onError
    });
  }
  static bulkDelete(employeeId, ids, { onStart, onFinish, onSuccess, onError } = {}) {
    router.post(`/employees/${employeeId}/skills/bulk-delete`, { ids }, {
      preserveState: true, preserveScroll: true,
      onStart, onFinish, onSuccess, onError
    });
  }
}
