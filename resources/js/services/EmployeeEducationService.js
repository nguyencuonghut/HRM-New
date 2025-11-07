import { router } from '@inertiajs/vue3';

export class EmployeeEducationService {
  static index(employeeId, options = {}) {
    const { onSuccess, onError } = options;
    // Tải lại trang profile để đồng bộ props (giữ nguyên UX project)
    router.get(`/employees/${employeeId}/profile`, {}, {
      preserveState: true, preserveScroll: true, replace: true,
      onSuccess: (p)=> onSuccess && onSuccess(p),
      onError:   (e)=> onError && onError(e),
    });
  }

  static store(employeeId, data, options = {}) {
    const { onStart, onFinish, onSuccess, onError } = options;
    router.post(`/employees/${employeeId}/educations`, data, {
      preserveState: true, preserveScroll: true,
      onStart:  ()=> onStart && onStart(),
      onFinish: ()=> onFinish && onFinish(),
      onSuccess:(p)=> onSuccess && onSuccess(p),
      onError:  (e)=> onError && onError(e),
    });
  }

  static update(employeeId, educationId, data, options = {}) {
    const { onStart, onFinish, onSuccess, onError } = options;
    router.put(`/employees/${employeeId}/educations/${educationId}`, data, {
      preserveState: true, preserveScroll: true,
      onStart:  ()=> onStart && onStart(),
      onFinish: ()=> onFinish && onFinish(),
      onSuccess:(p)=> onSuccess && onSuccess(p),
      onError:  (e)=> onError && onError(e),
    });
  }

  static destroy(employeeId, educationId, options = {}) {
    const { onSuccess, onError, onStart, onFinish } = options;
    router.delete(`/employees/${employeeId}/educations/${educationId}`, {
      preserveState: true, preserveScroll: true,
      onStart:  ()=> onStart && onStart(),
      onFinish: ()=> onFinish && onFinish(),
      onSuccess:(p)=> onSuccess && onSuccess(p),
      onError:  (e)=> onError && onError(e),
    });
  }

  static bulkDelete(employeeId, ids = [], options = {}) {
    const { onSuccess, onError, onStart, onFinish } = options;
    router.post(`/employees/${employeeId}/educations/bulk-delete`, { ids }, {
      preserveState: true, preserveScroll: true,
      onStart:  ()=> onStart && onStart(),
      onFinish: ()=> onFinish && onFinish(),
      onSuccess:(p)=> onSuccess && onSuccess(p),
      onError:  (e)=> onError && onError(e),
    });
  }
}
