// resources/js/services/LeaveApprovalService.js
import { router } from '@inertiajs/vue3';

export class LeaveApprovalService {
  /**
   * Get pending approvals list
   */
  static index(options = {}) {
    const { onStart, onFinish, onError, onSuccess } = options;
    router.get('/leave-approvals', {}, {
      onStart: () => onStart && onStart(),
      onFinish: () => onFinish && onFinish(),
      onError: (errors) => onError && onError(errors),
      onSuccess: (page) => onSuccess && onSuccess(page),
      preserveState: true,
      preserveScroll: true
    });
  }

  /**
   * Navigate to approvals page
   */
  static navigate() {
    router.visit('/leave-approvals');
  }

  /**
   * Navigate to leave request detail
   */
  static viewRequest(id) {
    router.visit(`/leave-requests/${id}`);
  }
}
