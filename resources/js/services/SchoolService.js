import { router } from '@inertiajs/vue3';

export class SchoolService {
  static index(params = {}, options = {}) {
    const { onSuccess, onError } = options;
    router.get('schools', params, {
      preserveState: true, preserveScroll: true,
      onSuccess: (p)=> onSuccess && onSuccess(p),
      onError:   (e)=> onError && onError(e),
    });
  }

  static store(data, options = {}) {
    const { onSuccess, onError } = options;
    router.post('schools', data, {
      preserveState: true, preserveScroll: true,
      onSuccess: (p)=> onSuccess && onSuccess(p),
      onError:   (e)=> onError && onError(e),
    });
  }

  static update(id, data, options = {}) {
    const { onSuccess, onError } = options;
    router.put(`schools/${id}`, data, {
      preserveState: true, preserveScroll: true,
      onSuccess: (p)=> onSuccess && onSuccess(p),
      onError:   (e)=> onError && onError(e),
    });
  }

  static destroy(id, options = {}) {
    const { onSuccess, onError } = options;
    router.delete(`schools/${id}`, {
      preserveState: true, preserveScroll: true,
      onSuccess: (p)=> onSuccess && onSuccess(p),
      onError:   (e)=> onError && onError(e),
    });
  }

  static bulkDelete(ids, options = {}) {
    const { onSuccess, onError } = options;
    router.post('schools/bulk-delete', { ids }, {
      preserveState: true, preserveScroll: true,
      onSuccess: (p)=> onSuccess && onSuccess(p),
      onError:   (e)=> onError && onError(e),
    });
  }
}
