// resources/js/services/LeaveRequestService.js
import { router } from '@inertiajs/vue3';
import axios from 'axios';

export class LeaveRequestService {
  /**
   * Get list of leave requests with filters
   */
  static index(filters = {}, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.get('/leave-requests', filters, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => onError && onError(errors),
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  /**
   * Navigate to create page
   */
  static create() {
    router.visit('/leave-requests/create');
  }

  /**
   * Store new leave request
   */
  static store(data, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.post('/leave-requests', data, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => onError && onError(errors),
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true,
      forceFormData: true, // Required for FormData with Inertia
    });
  }

  /**
   * Navigate to show page
   */
  static show(id) {
    router.visit(`/leave-requests/${id}`);
  }

  /**
   * Navigate to edit page
   */
  static edit(id) {
    router.visit(`/leave-requests/${id}/edit`);
  }

  /**
   * Update leave request
   */
  static update(id, data, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.put(`/leave-requests/${id}`, data, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => onError && onError(errors),
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true,
      forceFormData: true, // Required for FormData with Inertia
    });
  }

  /**
   * Delete leave request
   */
  static destroy(id, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.delete(`/leave-requests/${id}`, {}, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => onError && onError(errors),
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  /**
   * Submit leave request for approval
   */
  static submit(id, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.post(`/leave-requests/${id}/submit`, {}, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => onError && onError(errors),
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  /**
   * Cancel leave request
   */
  static cancel(id, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.post(`/leave-requests/${id}/cancel`, {}, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => onError && onError(errors),
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  /**
   * Approve leave request
   */
  static approve(id, data, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.post(`/leave-requests/${id}/approve`, data, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => onError && onError(errors),
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  /**
   * Reject leave request
   */
  static reject(id, data, options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.post(`/leave-requests/${id}/reject`, data, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => onError && onError(errors),
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  /**
   * Get leave balance via axios
   */
  static async getBalance(params) {
    try {
      const response = await axios.get('/leave-requests/balance', { params });
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  /**
   * Navigate to back
   */
  static back() {
    router.visit('/leave-requests');
  }
}
