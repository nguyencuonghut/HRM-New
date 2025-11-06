import { router } from '@inertiajs/vue3';

export class EducationLevelService {
  static index(params = {}, options = {}) {
    const { onSuccess, onError } = options;
    router.get('education-levels', params, {
      preserveState: true, preserveScroll: true,
      onSuccess: (p)=> onSuccess && onSuccess(p),
      onError:   (e)=> onError && onError(e),
    });
  }

  static store(data, options = {}) {
    const { onSuccess, onError } = options;
    router.post('education-levels', data, {
      preserveState: true, preserveScroll: true,
      onSuccess: (p)=> onSuccess && onSuccess(p),
      onError:   (e)=> onError && onError(e),
    });
  }

  static update(id, data, options = {}) {
    const { onSuccess, onError } = options;
    router.put(`education-levels/${id}`, data, {
      preserveState: true, preserveScroll: true,
      onSuccess: (p)=> onSuccess && onSuccess(p),
      onError:   (e)=> onError && onError(e),
    });
  }

  static destroy(id, options = {}) {
    const { onSuccess, onError } = options;
    router.delete(`education-levels/${id}`, {
      preserveState: true, preserveScroll: true,
      onSuccess: (p)=> onSuccess && onSuccess(p),
      onError:   (e)=> onError && onError(e),
    });
  }

  static bulkDelete(ids, options = {}) {
    const { onSuccess, onError } = options;
    router.post('education-levels/bulk-delete', { ids }, {
      preserveState: true, preserveScroll: true,
      onSuccess: (p)=> onSuccess && onSuccess(p),
      onError:   (e)=> onError && onError(e),
    });
  }
}
