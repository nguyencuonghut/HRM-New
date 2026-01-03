import { router } from '@inertiajs/vue3'
import { ToastService } from './ToastService'

export const BenefitTypeService = {
  index(data = {}, options = {}) {
    router.get('/benefit-types', data, {
      preserveState: true,
      preserveScroll: true,
      ...options
    })
  },

  store(data, options = {}) {
    router.post('/benefit-types', data, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        if (options.onSuccess) options.onSuccess()
        ToastService.success('Tạo loại phúc lợi thành công!')
      },
      onError: (errors) => {
        if (options.onError) options.onError(errors)
        const firstError = Object.values(errors)[0]
        ToastService.error(firstError || 'Có lỗi xảy ra')
      }
    })
  },

  update(id, data, options = {}) {
    router.put(`/benefit-types/${id}`, data, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        if (options.onSuccess) options.onSuccess()
        ToastService.success('Cập nhật loại phúc lợi thành công!')
      },
      onError: (errors) => {
        if (options.onError) options.onError(errors)
        const firstError = Object.values(errors)[0]
        ToastService.error(firstError || 'Có lỗi xảy ra')
      }
    })
  },

  destroy(id, options = {}) {
    router.delete(`/benefit-types/${id}`, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        if (options.onSuccess) options.onSuccess()
        ToastService.success('Xóa loại phúc lợi thành công!')
      },
      onError: (errors) => {
        if (options.onError) options.onError(errors)
        const firstError = Object.values(errors)[0]
        ToastService.error(firstError || 'Có lỗi xảy ra')
      }
    })
  },

  bulkDelete(ids, options = {}) {
    router.delete('/benefit-types/bulk-delete', {
      data: { ids },
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        if (options.onSuccess) options.onSuccess()
        ToastService.success('Xóa loại phúc lợi thành công!')
      },
      onError: (errors) => {
        if (options.onError) options.onError(errors)
        const firstError = Object.values(errors)[0]
        ToastService.error(firstError || 'Có lỗi xảy ra')
      }
    })
  }
}
