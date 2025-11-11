import { router } from '@inertiajs/vue3';

export class ContractService {
  static index(opts={}) { router.get('/contracts', {}, { preserveState:true, preserveScroll:true, ...opts }); }
  static store(data, opts={}) { router.post('/contracts', data, { preserveState:true, preserveScroll:true, ...opts }); }
  static update(id, data, opts={}) { router.put(`/contracts/${id}`, data, { preserveState:true, preserveScroll:true, ...opts }); }
  static destroy(id, opts={}) { router.delete(`/contracts/${id}`, { preserveState:true, preserveScroll:true, ...opts }); }
  static bulkDelete(ids, opts={}) { router.post('/contracts/bulk-delete', { ids }, { preserveState:true, preserveScroll:true, ...opts }); }
  static approve(id, data={}, opts={}) { router.post(`/contracts/${id}/approve`, data, { preserveState:true, preserveScroll:true, ...opts }); }
  static reject(id, data={}, opts={}) { router.post(`/contracts/${id}/reject`, data, { preserveState:true, preserveScroll:true, ...opts }); }
  static generate(id, data={}, opts={}) { router.post(`/contracts/${id}/generate`, data, { preserveState:true, preserveScroll:true, ...opts }); }
}
