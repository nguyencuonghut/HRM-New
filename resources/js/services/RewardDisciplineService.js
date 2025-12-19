import { router } from '@inertiajs/vue3';

export class RewardDisciplineService {
  static store(employeeId, data, { onStart, onFinish, onSuccess, onError } = {}) {
    router.post(`/employees/${employeeId}/rewards-disciplines`, data, {
      preserveState: true, preserveScroll: true,
      onStart, onFinish, onSuccess, onError
    });
  }

  static update(employeeId, id, data, { onStart, onFinish, onSuccess, onError } = {}) {
    router.put(`/employees/${employeeId}/rewards-disciplines/${id}`, data, {
      preserveState: true, preserveScroll: true,
      onStart, onFinish, onSuccess, onError
    });
  }

  static destroy(employeeId, id, { onStart, onFinish, onSuccess, onError } = {}) {
    router.delete(`/employees/${employeeId}/rewards-disciplines/${id}`, {
      preserveState: true, preserveScroll: true,
      onStart, onFinish, onSuccess, onError
    });
  }
}
