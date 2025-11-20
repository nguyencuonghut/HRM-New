// resources/js/services/ContractAppendixTemplateService.js
import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';

export class ContractAppendixTemplateService {
  static index(options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.get('/contract-appendix-templates', {}, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => {
        ToastService.error(errors?.message || 'Không tải được danh sách mẫu phụ lục!');
        onError && onError(errors);
      },
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  static store(data, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.post('/contract-appendix-templates', data, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => {
        if (!errors || Object.keys(errors).length === 0) {
          ToastService.error('Có lỗi khi tạo mẫu phụ lục!');
        }
        onError && onError(errors);
      },
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  static update(id, data, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.put(`/contract-appendix-templates/${id}`, data, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => {
        if (!errors || Object.keys(errors).length === 0) {
          ToastService.error('Có lỗi khi cập nhật mẫu phụ lục!');
        }
        onError && onError(errors);
      },
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  static destroy(id, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.delete(`/contract-appendix-templates/${id}`, {}, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => {
        ToastService.error(errors?.message || 'Không thể xóa mẫu phụ lục!');
        onError && onError(errors);
      },
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  static bulkDelete(ids, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.post('/contract-appendix-templates/bulk-delete', { ids }, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => {
        ToastService.error(errors?.message || 'Không thể xóa nhiều mẫu!');
        onError && onError(errors);
      },
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }
}
