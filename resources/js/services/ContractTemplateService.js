// resources/js/services/ContractTemplateService.js
import { router } from '@inertiajs/vue3';
import { ToastService } from './ToastService';

export class ContractTemplateService {
  static index(options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.get('/contract-templates', {}, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => {
        ToastService.error(errors?.message || 'Không tải được danh sách mẫu hợp đồng!');
        onError && onError(errors);
      },
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  static store(data, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.post('/contract-templates', data, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => {
        // chỉ toast lỗi tổng quát, field error để form hiển thị
        if (!errors || Object.keys(errors).length === 0) {
          ToastService.error('Có lỗi khi tạo mẫu hợp đồng!');
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
    router.put(`/contract-templates/${id}`, data, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => {
        if (!errors || Object.keys(errors).length === 0) {
          ToastService.error('Có lỗi khi cập nhật mẫu hợp đồng!');
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
    router.delete(`/contract-templates/${id}`, {}, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => {
        ToastService.error(errors?.message || 'Không thể xóa mẫu hợp đồng!');
        onError && onError(errors);
      },
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  static bulkDelete(ids, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.post('/contract-templates/bulk-delete', { ids }, {
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
