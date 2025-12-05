// resources/js/utils/leaveHelpers.js
import { formatDate, formatDateTime } from './dateHelper';

/**
 * Calculate number of working days between two dates (exclude weekends)
 * Uses local date components to avoid timezone issues
 */
export const calculateWorkingDays = (startDate, endDate) => {
  if (!startDate || !endDate) return 0;

  // Parse dates using local date components to avoid timezone shift
  let start, end;
  
  if (startDate instanceof Date) {
    start = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate());
  } else {
    const [y1, m1, d1] = startDate.split('-').map(Number);
    start = new Date(y1, m1 - 1, d1);
  }

  if (endDate instanceof Date) {
    end = new Date(endDate.getFullYear(), endDate.getMonth(), endDate.getDate());
  } else {
    const [y2, m2, d2] = endDate.split('-').map(Number);
    end = new Date(y2, m2 - 1, d2);
  }

  if (start > end) return 0;

  let workingDays = 0;
  const currentDate = new Date(start);

  while (currentDate <= end) {
    const dayOfWeek = currentDate.getDay();
    // 0 = Sunday, 6 = Saturday
    if (dayOfWeek !== 0 && dayOfWeek !== 6) {
      workingDays++;
    }
    currentDate.setDate(currentDate.getDate() + 1);
  }

  return workingDays;
};

// Re-export date formatting functions from dateHelper
export { formatDate, formatDateTime };

/**
 * Get status options for leave requests
 */
export const getStatusOptions = () => [
  { label: 'Nháp', value: 'DRAFT' },
  { label: 'Chờ duyệt', value: 'PENDING' },
  { label: 'Đã duyệt', value: 'APPROVED' },
  { label: 'Từ chối', value: 'REJECTED' },
  { label: 'Đã hủy', value: 'CANCELLED' },
];

/**
 * Get role label in Vietnamese
 */
export const getRoleLabel = (role) => {
  const labels = {
    'LINE_MANAGER': 'Trưởng phòng',
    'DIRECTOR': 'Giám đốc',
    'HR': 'Phòng nhân sự',
  };
  return labels[role] || role;
};

/**
 * Get marker color for timeline based on status
 */
export const getMarkerColor = (status) => {
  if (status === 'APPROVED') return 'green';
  if (status === 'REJECTED') return 'red';
  return 'gray';
};

/**
 * Get marker icon for timeline based on status
 */
export const getMarkerIcon = (status) => {
  if (status === 'APPROVED') return 'pi pi-check';
  if (status === 'REJECTED') return 'pi pi-times';
  return 'pi pi-clock';
};

/**
 * Get card class for timeline based on status
 */
export const getCardClass = (status) => {
  if (status === 'APPROVED') return 'border-green-200 bg-green-50';
  if (status === 'REJECTED') return 'border-red-200 bg-red-50';
  return 'border-gray-200 bg-white';
};

/**
 * Get status severity for Badge component
 */
export const getStatusSeverity = (status) => {
  if (status === 'APPROVED') return 'success';
  if (status === 'REJECTED') return 'danger';
  return 'info';
};

/**
 * Get current approval step from request
 */
export const getCurrentStep = (request) => {
  if (!request.approvals || request.approvals.length === 0) return null;
  return request.approvals.find(approval => approval.status === 'PENDING');
};
