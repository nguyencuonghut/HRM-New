import { ref, computed } from 'vue';

// Ngôn ngữ hiện tại
const currentLocale = ref(localStorage.getItem('locale') || 'vi');

// Từ điển ngôn ngữ
const messages = {
  vi: {
    // Auth
    auth: {
      welcome: 'Chào mừng đến với PrimeLand!',
      signInToContinue: 'Đăng nhập để tiếp tục',
      email: 'Email',
      emailPlaceholder: 'Địa chỉ email',
      password: 'Mật khẩu',
      passwordPlaceholder: 'Mật khẩu',
      rememberMe: 'Ghi nhớ đăng nhập',
      forgotPassword: 'Tôi quên mật khẩu',
      signIn: 'Đăng nhập',
      logout: 'Đăng xuất',
      loginSuccess: 'Đăng nhập thành công!',
      loginFailed: 'Đăng nhập thất bại! Vui lòng kiểm tra lại thông tin.',
      logoutSuccess: 'Đăng xuất thành công!',
      loginError: 'Đăng nhập thất bại. Vui lòng thử lại.',
      passwordResetSent: 'Liên kết đặt lại mật khẩu đã được gửi đến email của bạn!',

      // Forgot Password
      forgotPasswordTitle: 'Quên mật khẩu',
      forgotPasswordSubtitle: 'Nhập email của bạn để nhận liên kết đặt lại mật khẩu',
      sendResetLink: 'Gửi liên kết đặt lại',
      sending: 'Đang gửi...',
      backToLogin: 'Quay lại đăng nhập',
      resetTokenCooldown: 'Vui lòng đợi 5 phút trước khi yêu cầu đặt lại mật khẩu mới.',

      // Reset Password
      resetPassword: 'Đặt lại mật khẩu',
      resetPasswordTitle: 'Đặt lại mật khẩu',
      resetPasswordSubtitle: 'Nhập mật khẩu mới cho tài khoản của bạn',
      newPassword: 'Mật khẩu mới',
      newPasswordPlaceholder: 'Nhập mật khẩu mới',
      confirmPassword: 'Xác nhận mật khẩu',
      confirmPasswordPlaceholder: 'Nhập lại mật khẩu mới',
      resetting: 'Đang đặt lại...',
      passwordResetSuccess: 'Mật khẩu đã được đặt lại thành công! Bạn có thể đăng nhập ngay.',
      resetPasswordError: 'Có lỗi xảy ra khi đặt lại mật khẩu. Vui lòng thử lại.',
      invalidResetToken: 'Liên kết đặt lại mật khẩu không hợp lệ.',
      expiredResetToken: 'Liên kết đặt lại mật khẩu đã hết hạn.',

      // Password Validation
      passwordWeak: 'Mật khẩu yếu',
      passwordMedium: 'Mật khẩu trung bình',
      passwordStrong: 'Mật khẩu mạnh',
      passwordTooWeak: 'Mật khẩu quá yếu. Vui lòng chọn mật khẩu mạnh hơn.',
      passwordsDoNotMatch: 'Mật khẩu xác nhận không khớp.',
    },
    // Common
    common: {
      language: 'Ngôn ngữ',
      vietnamese: 'Tiếng Việt',
      english: 'Tiếng Anh',
      success: 'Thành công',
      error: 'Lỗi',
      warning: 'Cảnh báo',
      info: 'Thông tin',
      yes: 'Có',
      no: 'Không',
      ok: 'Đồng ý',
      cancel: 'Hủy',
      confirm: 'Xác nhận',
      loading: 'Đang tải...',
      save: 'Lưu',
      edit: 'Sửa',
      delete: 'Xóa',
      add: 'Thêm',
      search: 'Tìm kiếm',
      action: 'Thao tác',
      // Tooltips
      restore: 'Khôi phục',
      forceDelete: 'Xóa vĩnh viễn',
      confirmBulkDelete: 'Xác nhận xóa nhiều',
      confirmBulkDeleteMessage: 'Bạn có chắc chắn muốn xóa những mục đã chọn không?',
      confirmRestore: 'Xác nhận khôi phục',
      confirmRestoreMessage: 'Bạn có chắc chắn muốn khôi phục {name} không?',
      confirmForceDelete: 'Xác nhận xóa vĩnh viễn',
      forceDeleteMessage: 'Bạn có chắc chắn muốn xóa vĩnh viễn {name} không?',
      forceDeleteWarning: 'Hành động này không thể hoàn tác!',
    },
    // Navigation & Menu
    nav: {
      home: 'Trang Chủ',
      dashboard: 'Bảng điều khiển',
      calendar: 'Lịch',
      messages: 'Tin nhắn',
      profile: 'Hồ sơ',
      system: 'Hệ thống',
      users: 'Người dùng',
      roles: 'Vai trò',
      departments: 'Phòng/Ban',
    },
    // Users
    users: {
      title: 'Quản lý người dùng',
      user: 'Người dùng',
      name: 'Tên',
      email: 'Email',
      password: 'Mật khẩu',
      confirmPassword: 'Xác nhận mật khẩu',
      roles: 'Vai trò',
      selectRoles: 'Chọn vai trò',
      createdAt: 'Ngày tạo',
      updatedAt: 'Ngày cập nhật',
      actions: 'Thao tác',
      status: 'Trạng thái',

      // Buttons & Actions
      add: 'Thêm',
      edit: 'Sửa',
      delete: 'Xóa',
      save: 'Lưu',
      cancel: 'Hủy',
      search: 'Tìm kiếm',
      export: 'Xuất dữ liệu',
      import: 'Nhập dữ liệu',

      // Dialogs
      addUser: 'Thêm người dùng',
      editUser: 'Sửa người dùng',
      userDetails: 'Chi tiết người dùng',
      confirmDelete: 'Xác nhận xóa',
      confirmDeleteMessage: 'Bạn có chắc chắn muốn xóa người dùng {name}?',
      confirmBulkDelete: 'Xác nhận xóa nhiều',
      confirmBulkDeleteMessage: 'Bạn có chắc chắn muốn xóa những người dùng đã chọn?',

      // Messages
      createSuccess: 'Tạo người dùng thành công!',
      createError: 'Có lỗi xảy ra khi tạo người dùng!',
      updateSuccess: 'Cập nhật người dùng thành công!',
      updateError: 'Có lỗi xảy ra khi cập nhật người dùng!',
      deleteSuccess: 'Xóa người dùng thành công!',
      deleteError: 'Có lỗi xảy ra khi xóa người dùng!',
      bulkDeleteSuccess: 'Xóa nhiều người dùng thành công!',
      bulkDeleteError: 'Có lỗi xảy ra khi xóa nhiều người dùng!',
      loadError: 'Có lỗi xảy ra khi tải danh sách người dùng!',
      restoreSuccess: 'Khôi phục người dùng thành công!',

      // Validation
      nameRequired: 'Tên là bắt buộc',
      emailRequired: 'Email là bắt buộc',
      emailInvalid: 'Email không đúng định dạng',
      passwordRequired: 'Mật khẩu là bắt buộc',
      passwordMin: 'Mật khẩu phải có ít nhất 8 ký tự',
      passwordConfirmRequired: 'Xác nhận mật khẩu là bắt buộc',
      passwordConfirmMismatch: 'Xác nhận mật khẩu không khớp',

      // Table
      showing: 'Hiển thị từ {first} đến {last} trong tổng số {total} người dùng',
      noData: 'Không có dữ liệu',
      loading: 'Đang tải...',
    },
    // Roles
    roles: {
      createSuccess: 'Tạo vai trò thành công!',
      createError: 'Có lỗi xảy ra khi tạo vai trò!',
      updateSuccess: 'Cập nhật vai trò thành công!',
      updateError: 'Có lỗi xảy ra khi cập nhật vai trò!',
      deleteSuccess: 'Xóa vai trò thành công!',
      deleteError: 'Có lỗi xảy ra khi xóa vai trò!',
      bulkDeleteSuccess: 'Xóa các vai trò thành công!',
      bulkDeleteError: 'Có lỗi xảy ra khi xóa các vai trò!',
      cannotDeleteSystemRoles: 'Không thể xóa vai trò hệ thống!',
      loadError: 'Có lỗi xảy ra khi tải danh sách vai trò!',
    },
    // Activity Logs
    activityLog: {
      title: 'Nhật ký hoạt động',
      activityLog: 'Nhật ký hoạt động',
      id: 'ID',
      time: 'Thời gian',
      causer: 'Người thực hiện',
      action: 'Hành động',
      subject: 'Đối tượng',
      detail: 'Chi tiết',
      deleteSuccess: 'Xóa nhật ký hoạt động thành công!',
      clearSuccess: 'Xóa tất cả nhật ký hoạt động thành công!',
    },
    // Home Page
    home: {
      title: 'Trang Chủ',
      subtitle: 'Chào mừng bạn đến với hệ thống quản lý trung tâm ngoại ngữ!',
      welcomeMessage: 'Bạn đã đăng nhập thành công vào hệ thống.',
      features: {
        studentManagement: 'Quản lý học viên',
        courseTracking: 'Theo dõi khóa học',
        progressMonitoring: 'Theo dõi tiến độ học tập',
      },
      startUsing: 'Bắt đầu sử dụng',
    },
    // Validation Messages
    validation: {
      required: 'Trường này là bắt buộc.',
      email: 'Email không đúng định dạng.',
      emailRequired: 'Email là bắt buộc.',
      passwordRequired: 'Mật khẩu là bắt buộc.',
      invalidCredentials: 'Thông tin đăng nhập không chính xác.',

      // Password Reset Validation
      emailNotExists: 'Email này không tồn tại trong hệ thống.',
      resetTokenCooldown: 'Vui lòng đợi 5 phút trước khi yêu cầu đặt lại mật khẩu mới.',
      invalidResetLink: 'Liên kết đặt lại mật khẩu không hợp lệ.',
      tokenRequired: 'Token đặt lại mật khẩu là bắt buộc.',
      invalidResetToken: 'Token đặt lại mật khẩu không hợp lệ.',
      expiredResetToken: 'Token đặt lại mật khẩu đã hết hạn.',
      passwordConfirmed: 'Xác nhận mật khẩu không khớp.',
      passwordMin: 'Mật khẩu phải có ít nhất 8 ký tự và bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.',
      emailSendFailed: 'Không thể gửi email. Vui lòng thử lại sau.',
    }
  },
  en: {
    // Auth
    auth: {
      welcome: 'Welcome to PrimeLand!',
      signInToContinue: 'Sign in to continue',
      email: 'Email',
      emailPlaceholder: 'Email address',
      password: 'Password',
      passwordPlaceholder: 'Password',
      rememberMe: 'Remember me',
      forgotPassword: 'Forgot Password',
      signIn: 'Sign In',
      logout: 'Logout',
      loginSuccess: 'Login successful!',
      loginFailed: 'Login failed! Please check your credentials.',
      logoutSuccess: 'Logout successful!',
      loginError: 'Login failed. Please try again.',
      passwordResetSent: 'Password reset link has been sent to your email!',

      // Forgot Password
      forgotPasswordTitle: 'Forgot Password',
      forgotPasswordSubtitle: 'Enter your email to receive a password reset link',
      sendResetLink: 'Send Reset Link',
      sending: 'Sending...',
      backToLogin: 'Back to Login',
      resetTokenCooldown: 'Please wait 5 minutes before requesting a new password reset.',

      // Reset Password
      resetPassword: 'Reset Password',
      resetPasswordTitle: 'Reset Password',
      resetPasswordSubtitle: 'Enter a new password for your account',
      newPassword: 'New Password',
      newPasswordPlaceholder: 'Enter new password',
      confirmPassword: 'Confirm Password',
      confirmPasswordPlaceholder: 'Re-enter new password',
      resetting: 'Resetting...',
      passwordResetSuccess: 'Password has been reset successfully! You can login now.',
      resetPasswordError: 'An error occurred while resetting password. Please try again.',
      invalidResetToken: 'Invalid password reset link.',
      expiredResetToken: 'Password reset link has expired.',

      // Password Validation
      passwordWeak: 'Weak password',
      passwordMedium: 'Medium password',
      passwordStrong: 'Strong password',
      passwordTooWeak: 'Password is too weak. Please choose a stronger password.',
      passwordsDoNotMatch: 'Password confirmation does not match.',
    },
    // Common
    common: {
      language: 'Language',
      vietnamese: 'Vietnamese',
      english: 'English',
      success: 'Success',
      error: 'Error',
      warning: 'Warning',
      info: 'Information',
      yes: 'Yes',
      no: 'No',
      ok: 'OK',
      cancel: 'Cancel',
      confirm: 'Confirm',
      loading: 'Loading...',
      save: 'Save',
      edit: 'Edit',
      delete: 'Delete',
      add: 'Add',
      search: 'Search',
      action: 'Action',
      // Tooltips
      restore: 'Restore',
      forceDelete: 'Force Delete',
      confirmBulkDelete: 'Confirm Bulk Delete',
      confirmBulkDeleteMessage: 'Are you sure you want to delete the selected items?',
      confirmRestore: 'Confirm Restore',
      confirmRestoreMessage: 'Are you sure you want to restore {name}?',
      confirmForceDelete: 'Confirm Force Delete',
      forceDeleteMessage: 'Are you sure you want to permanently delete {name}?',
      forceDeleteWarning: 'This action cannot be undone!',
    },
    // Navigation & Menu
    nav: {
      home: 'Home',
      dashboard: 'Dashboard',
      calendar: 'Calendar',
      messages: 'Messages',
      profile: 'Profile',
      system: 'System',
      users: 'Users',
      roles: 'Roles',
      departments: 'Departments',
    },
    // Users
    users: {
      title: 'User Management',
      user: 'User',
      name: 'Name',
      email: 'Email',
      password: 'Password',
      confirmPassword: 'Confirm Password',
      roles: 'Roles',
      selectRoles: 'Select Roles',
      createdAt: 'Created At',
      updatedAt: 'Updated At',
      actions: 'Actions',
      status: 'Status',

      // Buttons & Actions
      add: 'Add',
      edit: 'Edit',
      delete: 'Delete',
      save: 'Save',
      cancel: 'Cancel',
      search: 'Search',
      export: 'Export',
      import: 'Import',

      // Dialogs
      addUser: 'Add User',
      editUser: 'Edit User',
      userDetails: 'User Details',
      confirmDelete: 'Confirm Delete',
      confirmDeleteMessage: 'Are you sure you want to delete user {name}?',
      confirmBulkDelete: 'Confirm Bulk Delete',
      confirmBulkDeleteMessage: 'Are you sure you want to delete selected users?',

      // Messages
      createSuccess: 'User created successfully!',
      createError: 'An error occurred while creating user!',
      updateSuccess: 'User updated successfully!',
      updateError: 'An error occurred while updating user!',
      deleteSuccess: 'User deleted successfully!',
      deleteError: 'An error occurred while deleting user!',
      bulkDeleteSuccess: 'Users deleted successfully!',
      bulkDeleteError: 'An error occurred while deleting users!',
      loadError: 'An error occurred while loading users!',
      restoreSuccess: 'User restored successfully!',

      // Validation
      nameRequired: 'Name is required',
      emailRequired: 'Email is required',
      emailInvalid: 'Invalid email format',
      passwordRequired: 'Password is required',
      passwordMin: 'Password must be at least 8 characters',
      passwordConfirmRequired: 'Password confirmation is required',
      passwordConfirmMismatch: 'Password confirmation does not match',

      // Table
      showing: 'Showing {first} to {last} of {total} users',
      noData: 'No data available',
      loading: 'Loading...',
    },
    // Roles
    roles: {
      createSuccess: 'Role created successfully!',
      createError: 'An error occurred while creating role!',
      updateSuccess: 'Role updated successfully!',
      updateError: 'An error occurred while updating role!',
      deleteSuccess: 'Role deleted successfully!',
      deleteError: 'An error occurred while deleting role!',
      bulkDeleteSuccess: 'Roles deleted successfully!',
      bulkDeleteError: 'An error occurred while deleting roles!',
      cannotDeleteSystemRoles: 'Cannot delete system roles!',
      loadError: 'An error occurred while loading roles!',
    },

    // Activity Logs
    activityLog: {
      title: 'Activity Logs',
      activityLog: 'Activity Log',
      id: 'ID',
      time: 'Time',
      causer: 'Causer',
      action: 'Action',
      subject: 'Subject',
      detail: 'Detail',
      deleteSuccess: 'Activity log deleted successfully!',
      clearSuccess: 'All activity logs cleared successfully!',
    },

    // Home Page
    home: {
      title: 'Home',
      subtitle: 'Welcome to the Language Center Management System!',
      welcomeMessage: 'You have successfully logged into the system.',
      features: {
        studentManagement: 'Student Management',
        courseTracking: 'Course Tracking',
        progressMonitoring: 'Progress Monitoring',
      },
      startUsing: 'Get Started',
    },
    // Validation Messages
    validation: {
      required: 'This field is required.',
      email: 'Invalid email format.',
      emailRequired: 'Email is required.',
      passwordRequired: 'Password is required.',
      invalidCredentials: 'Invalid login credentials.',

      // Password Reset Validation
      emailNotExists: 'This email does not exist in our system.',
      resetTokenCooldown: 'Please wait 5 minutes before requesting a new password reset.',
      invalidResetLink: 'Invalid password reset link.',
      tokenRequired: 'Password reset token is required.',
      invalidResetToken: 'Invalid password reset token.',
      expiredResetToken: 'Password reset token has expired.',
      passwordConfirmed: 'Password confirmation does not match.',
      passwordMin: 'Password must be at least 8 characters and include uppercase, lowercase, numbers and special characters.',
      emailSendFailed: 'Failed to send email. Please try again later.',
    }
  }
};

export function useI18n() {
  // Cập nhật ngôn ngữ
  const setLocale = (locale) => {
    currentLocale.value = locale;
    localStorage.setItem('locale', locale);

    // Clear validation errors khi chuyển ngôn ngữ
    // Để tránh hiển thị cả validation messages tiếng cũ và mới
    if (typeof window !== 'undefined' && window.history && window.history.state) {
      // Clear Inertia errors by updating the page state
      const currentState = window.history.state;
      if (currentState && currentState.props && currentState.props.errors) {
        currentState.props.errors = {};
        window.history.replaceState(currentState, '', window.location.href);
      }
    }
  };

  // Lấy text theo key
  const t = (key) => {
    const keys = key.split('.');
    let result = messages[currentLocale.value];

    for (const k of keys) {
      if (result && typeof result === 'object') {
        result = result[k];
      } else {
        return key; // Trả về key nếu không tìm thấy
      }
    }

    return result || key;
  };

  // Computed cho locale hiện tại
  const locale = computed(() => currentLocale.value);

  // Danh sách ngôn ngữ có sẵn
  const availableLocales = [
    { code: 'vi', name: 'Tiếng Việt', flag: '🇻🇳', icon: 'pi-flag' },
    { code: 'en', name: 'English', flag: '🇺🇸', icon: 'pi-flag' }
  ];

  return {
    locale,
    setLocale,
    t,
    availableLocales
  };
}
