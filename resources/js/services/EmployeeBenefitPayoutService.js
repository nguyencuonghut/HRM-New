import { router } from '@inertiajs/vue3'
import { ToastService } from './ToastService'

export const EmployeeBenefitPayoutService = {
  index(data = {}, options = {}) {
    router.get('/employee-benefit-payouts', data, {
      preserveState: true,
      preserveScroll: true,
      only: ['payouts'],
      ...options
    })
  },

  store(data, options = {}) {
    router.post('/employee-benefit-payouts', data, {
      preserveState: true,
      preserveScroll: true,
      only: ['payouts'],
      onSuccess: () => {
        if (options.onSuccess) options.onSuccess()
        ToastService.success('Tạo khoản chi phúc lợi thành công!')
      },
      onError: (errors) => {
        if (options.onError) options.onError(errors)
        const firstError = Object.values(errors)[0]
        ToastService.error(firstError || 'Có lỗi xảy ra')
      }
    })
  },

  update(id, data, options = {}) {
    router.put(`/employee-benefit-payouts/${id}`, data, {
      preserveState: true,
      preserveScroll: true,
      only: ['payouts'],
      onSuccess: () => {
        if (options.onSuccess) options.onSuccess()
        ToastService.success('Cập nhật khoản chi phúc lợi thành công!')
      },
      onError: (errors) => {
        if (options.onError) options.onError(errors)
        const firstError = Object.values(errors)[0]
        ToastService.error(firstError || 'Có lỗi xảy ra')
      }
    })
  },

  destroy(id, options = {}) {
    router.delete(`/employee-benefit-payouts/${id}`, {
      preserveState: true,
      preserveScroll: true,
      only: ['payouts'],
      onSuccess: () => {
        if (options.onSuccess) options.onSuccess()
        ToastService.success('Xóa khoản chi phúc lợi thành công!')
      },
      onError: (errors) => {
        if (options.onError) options.onError(errors)
        const firstError = Object.values(errors)[0]
        ToastService.error(firstError || 'Có lỗi xảy ra')
      }
    })
  },

  bulkDelete(ids, options = {}) {
    router.delete('/employee-benefit-payouts/bulk-delete', {
      data: { ids },
      preserveState: true,
      preserveScroll: true,
      only: ['payouts'],
      onSuccess: () => {
        if (options.onSuccess) options.onSuccess()
        ToastService.success('Xóa khoản chi phúc lợi thành công!')
      },
      onError: (errors) => {
        if (options.onError) options.onError(errors)
        const firstError = Object.values(errors)[0]
        ToastService.error(firstError || 'Có lỗi xảy ra')
      }
    })
  }
}
