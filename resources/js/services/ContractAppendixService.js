import { router } from '@inertiajs/vue3';

export class ContractAppendixService {
  static index(contractId, opts={}) { router.get(`/contracts/${contractId}/appendixes`, {}, { preserveState:true, preserveScroll:true, ...opts }); }
  static store(contractId, data, opts={}) { router.post(`/contracts/${contractId}/appendixes`, data, { preserveState:true, preserveScroll:true, ...opts }); }
  static update(contractId, id, data, opts={}) { router.put(`/contracts/${contractId}/appendixes/${id}`, data, { preserveState:true, preserveScroll:true, ...opts }); }
  static destroy(contractId, id, opts={}) { router.delete(`/contracts/${contractId}/appendixes/${id}`, { preserveState:true, preserveScroll:true, ...opts }); }
  static bulkDelete(contractId, ids, opts={}) { router.post(`/contracts/${contractId}/appendixes/bulk-delete`, { ids }, { preserveState:true, preserveScroll:true, ...opts }); }
  static approve(contractId, id, data={}, opts={}) { router.post(`/contracts/${contractId}/appendixes/${id}/approve`, data, { preserveState:true, preserveScroll:true, ...opts }); }
  static reject(contractId, id, data={}, opts={}) { router.post(`/contracts/${contractId}/appendixes/${id}/reject`, data, { preserveState:true, preserveScroll:true, ...opts }); }
}
