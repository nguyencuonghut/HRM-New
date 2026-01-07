<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permission Definitions
    |--------------------------------------------------------------------------
    |
    | Define all permissions with Vietnamese labels grouped by module.
    | Format: 'permission.name' => ['label' => '...', 'description' => '...']
    |
    */

    'modules' => [
        'system_administration' => [
            'label' => 'Quản trị hệ thống',
            'icon' => 'pi-cog',
            'permissions' => [
                'manage system settings' => [
                    'label' => 'Quản lý cấu hình hệ thống',
                    'description' => 'Thay đổi cài đặt hệ thống'
                ],
                'view system logs' => [
                    'label' => 'Xem logs hệ thống',
                    'description' => 'Truy cập system logs'
                ],
                'manage integrations' => [
                    'label' => 'Quản lý tích hợp',
                    'description' => 'Cấu hình tích hợp bên thứ 3'
                ],
            ]
        ],

        'user_management' => [
            'label' => 'Quản lý người dùng',
            'icon' => 'pi-users',
            'permissions' => [
                'view users' => [
                    'label' => 'Xem danh sách người dùng',
                    'description' => 'Truy cập danh sách users'
                ],
                'create users' => [
                    'label' => 'Tạo người dùng',
                    'description' => 'Thêm user mới'
                ],
                'edit users' => [
                    'label' => 'Sửa người dùng',
                    'description' => 'Chỉnh sửa thông tin user'
                ],
                'delete users' => [
                    'label' => 'Xóa người dùng',
                    'description' => 'Xóa user khỏi hệ thống'
                ],
                'import users' => [
                    'label' => 'Import người dùng',
                    'description' => 'Import users từ file'
                ],
                'export users' => [
                    'label' => 'Export người dùng',
                    'description' => 'Xuất danh sách users'
                ],
                'reset user passwords' => [
                    'label' => 'Reset mật khẩu',
                    'description' => 'Reset password cho user'
                ],
            ]
        ],

        'role_management' => [
            'label' => 'Quản lý vai trò',
            'icon' => 'pi-shield',
            'permissions' => [
                'view roles' => [
                    'label' => 'Xem danh sách vai trò',
                    'description' => 'Truy cập danh sách roles'
                ],
                'create roles' => [
                    'label' => 'Tạo vai trò',
                    'description' => 'Thêm role mới'
                ],
                'edit roles' => [
                    'label' => 'Sửa vai trò',
                    'description' => 'Chỉnh sửa role'
                ],
                'delete roles' => [
                    'label' => 'Xóa vai trò',
                    'description' => 'Xóa role khỏi hệ thống'
                ],
            ]
        ],

        'permission_management' => [
            'label' => 'Quản lý quyền',
            'icon' => 'pi-lock',
            'permissions' => [
                'view permissions' => [
                    'label' => 'Xem danh sách quyền',
                    'description' => 'Truy cập danh sách permissions'
                ],
                'create permissions' => [
                    'label' => 'Tạo quyền',
                    'description' => 'Thêm permission mới'
                ],
                'edit permissions' => [
                    'label' => 'Sửa quyền',
                    'description' => 'Chỉnh sửa permission'
                ],
                'delete permissions' => [
                    'label' => 'Xóa quyền',
                    'description' => 'Xóa permission'
                ],
                'assign permissions' => [
                    'label' => 'Phân quyền',
                    'description' => 'Gán permissions cho roles/users'
                ],
            ]
        ],

        'backup_management' => [
            'label' => 'Quản lý sao lưu',
            'icon' => 'pi-database',
            'permissions' => [
                'view backups' => [
                    'label' => 'Xem sao lưu',
                    'description' => 'Truy cập danh sách backups'
                ],
                'create backups' => [
                    'label' => 'Tạo sao lưu',
                    'description' => 'Backup hệ thống'
                ],
                'restore backups' => [
                    'label' => 'Khôi phục sao lưu',
                    'description' => 'Restore từ backup'
                ],
                'delete backups' => [
                    'label' => 'Xóa sao lưu',
                    'description' => 'Xóa file backup'
                ],
                'configure backups' => [
                    'label' => 'Cấu hình sao lưu',
                    'description' => 'Cấu hình auto backup'
                ],
            ]
        ],

        'activity_logs' => [
            'label' => 'Nhật ký hoạt động',
            'icon' => 'pi-history',
            'permissions' => [
                'view activity logs' => [
                    'label' => 'Xem nhật ký',
                    'description' => 'Truy cập activity logs'
                ],
                'view own activity logs' => [
                    'label' => 'Xem nhật ký cá nhân',
                    'description' => 'Xem logs của mình'
                ],
                'delete activity logs' => [
                    'label' => 'Xóa nhật ký',
                    'description' => 'Xóa activity logs'
                ],
                'export activity logs' => [
                    'label' => 'Xuất nhật ký',
                    'description' => 'Export activity logs'
                ],
            ]
        ],

        'organization_structure' => [
            'label' => 'Cơ cấu tổ chức',
            'icon' => 'pi-sitemap',
            'permissions' => [
                'view departments' => [
                    'label' => 'Xem phòng/ban',
                    'description' => 'Truy cập danh sách departments'
                ],
                'create departments' => [
                    'label' => 'Tạo phòng/ban',
                    'description' => 'Thêm department mới'
                ],
                'edit departments' => [
                    'label' => 'Sửa phòng/ban',
                    'description' => 'Chỉnh sửa department'
                ],
                'delete departments' => [
                    'label' => 'Xóa phòng/ban',
                    'description' => 'Xóa department'
                ],
            ]
        ],

        'employee_management' => [
            'label' => 'Quản lý nhân viên',
            'icon' => 'pi-id-card',
            'permissions' => [
                'view employees' => [
                    'label' => 'Xem nhân viên',
                    'description' => 'Truy cập danh sách nhân viên'
                ],
                'create employees' => [
                    'label' => 'Tạo nhân viên',
                    'description' => 'Thêm nhân viên mới'
                ],
                'edit employees' => [
                    'label' => 'Sửa nhân viên',
                    'description' => 'Chỉnh sửa thông tin nhân viên'
                ],
                'delete employees' => [
                    'label' => 'Xóa nhân viên',
                    'description' => 'Xóa nhân viên'
                ],
                'import employees' => [
                    'label' => 'Import nhân viên',
                    'description' => 'Import từ file Excel'
                ],
                'export employees' => [
                    'label' => 'Xuất danh sách nhân viên',
                    'description' => 'Export ra Excel'
                ],
                'view employee profiles' => [
                    'label' => 'Xem hồ sơ nhân viên',
                    'description' => 'Truy cập hồ sơ chi tiết'
                ],
                'edit employee profiles' => [
                    'label' => 'Sửa hồ sơ nhân viên',
                    'description' => 'Chỉnh sửa hồ sơ'
                ],
                'terminate employees' => [
                    'label' => 'Nghỉ việc nhân viên',
                    'description' => 'Xử lý nghỉ việc'
                ],
                'transfer employees' => [
                    'label' => 'Điều chuyển nhân viên',
                    'description' => 'Chuyển phòng ban'
                ],
            ]
        ],

        'employee_assignment' => [
            'label' => 'Phân công nhân sự',
            'icon' => 'pi-user-edit',
            'permissions' => [
                'view employee assignments' => [
                    'label' => 'Xem phân công',
                    'description' => 'Xem danh sách phân công'
                ],
                'create employee assignments' => [
                    'label' => 'Tạo phân công',
                    'description' => 'Phân công nhân sự'
                ],
                'edit employee assignments' => [
                    'label' => 'Sửa phân công',
                    'description' => 'Chỉnh sửa phân công'
                ],
                'delete employee assignments' => [
                    'label' => 'Xóa phân công',
                    'description' => 'Xóa phân công'
                ],
            ]
        ],

        'position_management' => [
            'label' => 'Quản lý chức vụ',
            'icon' => 'pi-briefcase',
            'permissions' => [
                'view positions' => [
                    'label' => 'Xem chức vụ',
                    'description' => 'Xem danh sách chức vụ'
                ],
                'create positions' => [
                    'label' => 'Tạo chức vụ',
                    'description' => 'Thêm chức vụ mới'
                ],
                'edit positions' => [
                    'label' => 'Sửa chức vụ',
                    'description' => 'Chỉnh sửa chức vụ'
                ],
                'delete positions' => [
                    'label' => 'Xóa chức vụ',
                    'description' => 'Xóa chức vụ'
                ],
            ]
        ],

        'master_data' => [
            'label' => 'Dữ liệu danh mục',
            'icon' => 'pi-table',
            'permissions' => [
                'view provinces' => ['label' => 'Xem tỉnh/thành', 'description' => 'Xem danh sách tỉnh thành'],
                'create provinces' => ['label' => 'Tạo tỉnh/thành', 'description' => 'Thêm tỉnh thành'],
                'edit provinces' => ['label' => 'Sửa tỉnh/thành', 'description' => 'Sửa tỉnh thành'],
                'delete provinces' => ['label' => 'Xóa tỉnh/thành', 'description' => 'Xóa tỉnh thành'],
                'view wards' => ['label' => 'Xem phường/xã', 'description' => 'Xem danh sách phường xã'],
                'create wards' => ['label' => 'Tạo phường/xã', 'description' => 'Thêm phường xã'],
                'edit wards' => ['label' => 'Sửa phường/xã', 'description' => 'Sửa phường xã'],
                'delete wards' => ['label' => 'Xóa phường/xã', 'description' => 'Xóa phường xã'],
                'view education levels' => ['label' => 'Xem trình độ học vấn', 'description' => 'Xem danh mục học vấn'],
                'create education levels' => ['label' => 'Tạo trình độ học vấn', 'description' => 'Thêm trình độ'],
                'edit education levels' => ['label' => 'Sửa trình độ học vấn', 'description' => 'Sửa trình độ'],
                'delete education levels' => ['label' => 'Xóa trình độ học vấn', 'description' => 'Xóa trình độ'],
                'view schools' => ['label' => 'Xem trường học', 'description' => 'Xem danh sách trường'],
                'create schools' => ['label' => 'Tạo trường học', 'description' => 'Thêm trường học'],
                'edit schools' => ['label' => 'Sửa trường học', 'description' => 'Sửa trường học'],
                'delete schools' => ['label' => 'Xóa trường học', 'description' => 'Xóa trường học'],
            ]
        ],

        'skill_management' => [
            'label' => 'Quản lý kỹ năng',
            'icon' => 'pi-star',
            'permissions' => [
                'view skills' => ['label' => 'Xem kỹ năng', 'description' => 'Xem danh sách kỹ năng'],
                'create skills' => ['label' => 'Tạo kỹ năng', 'description' => 'Thêm kỹ năng mới'],
                'edit skills' => ['label' => 'Sửa kỹ năng', 'description' => 'Sửa kỹ năng'],
                'delete skills' => ['label' => 'Xóa kỹ năng', 'description' => 'Xóa kỹ năng'],
                'view skill categories' => ['label' => 'Xem danh mục kỹ năng', 'description' => 'Xem danh mục'],
                'create skill categories' => ['label' => 'Tạo danh mục kỹ năng', 'description' => 'Thêm danh mục'],
                'edit skill categories' => ['label' => 'Sửa danh mục kỹ năng', 'description' => 'Sửa danh mục'],
                'delete skill categories' => ['label' => 'Xóa danh mục kỹ năng', 'description' => 'Xóa danh mục'],
            ]
        ],

        'contract_management' => [
            'label' => 'Quản lý hợp đồng',
            'icon' => 'pi-file',
            'permissions' => [
                'view contracts' => ['label' => 'Xem hợp đồng', 'description' => 'Truy cập danh sách hợp đồng'],
                'create contracts' => ['label' => 'Tạo hợp đồng', 'description' => 'Tạo hợp đồng mới'],
                'edit contracts' => ['label' => 'Sửa hợp đồng', 'description' => 'Chỉnh sửa hợp đồng'],
                'delete contracts' => ['label' => 'Xóa hợp đồng', 'description' => 'Xóa hợp đồng'],
                'submit contracts' => ['label' => 'Gửi hợp đồng', 'description' => 'Gửi phê duyệt hợp đồng'],
                'approve contracts' => ['label' => 'Phê duyệt hợp đồng', 'description' => 'Duyệt hợp đồng'],
                'reject contracts' => ['label' => 'Từ chối hợp đồng', 'description' => 'Từ chối phê duyệt'],
                'recall contracts' => ['label' => 'Thu hồi hợp đồng', 'description' => 'Thu hồi hợp đồng đã gửi'],
                'renew contracts' => ['label' => 'Gia hạn hợp đồng', 'description' => 'Gia hạn hợp đồng'],
                'terminate contracts' => ['label' => 'Kết thúc hợp đồng', 'description' => 'Chấm dứt hợp đồng'],
                'view contract templates' => ['label' => 'Xem mẫu hợp đồng', 'description' => 'Xem templates'],
                'create contract templates' => ['label' => 'Tạo mẫu hợp đồng', 'description' => 'Tạo template'],
                'edit contract templates' => ['label' => 'Sửa mẫu hợp đồng', 'description' => 'Sửa template'],
                'delete contract templates' => ['label' => 'Xóa mẫu hợp đồng', 'description' => 'Xóa template'],
                'view appendix templates' => ['label' => 'Xem mẫu phụ lục', 'description' => 'Xem mẫu phụ lục'],
                'create appendix templates' => ['label' => 'Tạo mẫu phụ lục', 'description' => 'Tạo mẫu'],
                'edit appendix templates' => ['label' => 'Sửa mẫu phụ lục', 'description' => 'Sửa mẫu'],
                'delete appendix templates' => ['label' => 'Xóa mẫu phụ lục', 'description' => 'Xóa mẫu'],
                'approve appendixes' => ['label' => 'Phê duyệt phụ lục', 'description' => 'Duyệt phụ lục'],
                'reject appendixes' => ['label' => 'Từ chối phụ lục', 'description' => 'Từ chối phụ lục'],
            ]
        ],

        'leave_management' => [
            'label' => 'Quản lý nghỉ phép',
            'icon' => 'pi-calendar',
            'permissions' => [
                'view leave requests' => ['label' => 'Xem đơn nghỉ phép', 'description' => 'Xem danh sách đơn'],
                'create leave requests' => ['label' => 'Tạo đơn nghỉ phép', 'description' => 'Tạo đơn mới'],
                'edit leave requests' => ['label' => 'Sửa đơn nghỉ phép', 'description' => 'Chỉnh sửa đơn'],
                'delete leave requests' => ['label' => 'Xóa đơn nghỉ phép', 'description' => 'Xóa đơn'],
                'submit leave requests' => ['label' => 'Gửi đơn nghỉ phép', 'description' => 'Gửi phê duyệt'],
                'approve leave requests' => ['label' => 'Phê duyệt đơn nghỉ', 'description' => 'Duyệt đơn nghỉ'],
                'reject leave requests' => ['label' => 'Từ chối đơn nghỉ', 'description' => 'Từ chối đơn'],
                'cancel leave requests' => ['label' => 'Hủy đơn nghỉ phép', 'description' => 'Hủy đơn đã duyệt'],
                'view leave balances' => ['label' => 'Xem số dư phép', 'description' => 'Xem số ngày phép'],
                'adjust leave balances' => ['label' => 'Điều chỉnh số dư phép', 'description' => 'Cộng/trừ phép'],
                'view leave types' => ['label' => 'Xem loại phép', 'description' => 'Xem danh mục loại phép'],
                'manage leave types' => ['label' => 'Quản lý loại phép', 'description' => 'CRUD loại phép'],
                'export leave reports' => ['label' => 'Xuất báo cáo nghỉ phép', 'description' => 'Export report'],
            ]
        ],

        'payroll_benefits' => [
            'label' => 'Lương & Phúc lợi',
            'icon' => 'pi-money-bill',
            'permissions' => [
                'view payroll' => ['label' => 'Xem bảng lương', 'description' => 'Xem thông tin lương'],
                'create payroll' => ['label' => 'Tạo bảng lương', 'description' => 'Tạo payroll mới'],
                'edit payroll' => ['label' => 'Sửa bảng lương', 'description' => 'Chỉnh sửa lương'],
                'delete payroll' => ['label' => 'Xóa bảng lương', 'description' => 'Xóa payroll'],
                'process payroll' => ['label' => 'Xử lý bảng lương', 'description' => 'Tính lương tháng'],
                'approve payroll' => ['label' => 'Phê duyệt bảng lương', 'description' => 'Duyệt payroll'],
                'export payroll' => ['label' => 'Xuất bảng lương', 'description' => 'Export payroll'],
                'view benefits' => ['label' => 'Xem phúc lợi', 'description' => 'Xem benefits'],
                'manage benefits' => ['label' => 'Quản lý phúc lợi', 'description' => 'CRUD benefits'],
                'approve benefit payouts' => ['label' => 'Duyệt chi phúc lợi', 'description' => 'Duyệt chi trả'],
                'export payroll reports' => ['label' => 'Xuất báo cáo lương', 'description' => 'Export reports'],
            ]
        ],

        'insurance_management' => [
            'label' => 'Quản lý BHXH',
            'icon' => 'pi-shield',
            'permissions' => [
                'view insurance reports' => ['label' => 'Xem báo cáo BHXH', 'description' => 'Xem reports'],
                'create insurance reports' => ['label' => 'Tạo báo cáo BHXH', 'description' => 'Tạo report mới'],
                'approve insurance records' => ['label' => 'Duyệt BHXH', 'description' => 'Phê duyệt records'],
                'reject insurance records' => ['label' => 'Từ chối BHXH', 'description' => 'Từ chối records'],
                'adjust insurance records' => ['label' => 'Điều chỉnh BHXH', 'description' => 'Sửa records'],
                'finalize insurance reports' => ['label' => 'Hoàn tất báo cáo BHXH', 'description' => 'Finalize report'],
                'export insurance reports' => ['label' => 'Xuất báo cáo BHXH', 'description' => 'Export'],
                'delete insurance reports' => ['label' => 'Xóa báo cáo BHXH', 'description' => 'Xóa report'],
            ]
        ],

        'performance_management' => [
            'label' => 'Quản lý hiệu suất',
            'icon' => 'pi-chart-line',
            'permissions' => [
                'view performance reviews' => ['label' => 'Xem đánh giá', 'description' => 'Xem reviews'],
                'create performance reviews' => ['label' => 'Tạo đánh giá', 'description' => 'Tạo review'],
                'edit performance reviews' => ['label' => 'Sửa đánh giá', 'description' => 'Sửa review'],
                'delete performance reviews' => ['label' => 'Xóa đánh giá', 'description' => 'Xóa review'],
                'approve performance reviews' => ['label' => 'Duyệt đánh giá', 'description' => 'Phê duyệt'],
                'view KPI data' => ['label' => 'Xem KPI', 'description' => 'Xem dữ liệu KPI'],
                'manage KPI templates' => ['label' => 'Quản lý mẫu KPI', 'description' => 'CRUD templates'],
                'export performance reports' => ['label' => 'Xuất báo cáo hiệu suất', 'description' => 'Export'],
            ]
        ],

        'rewards_discipline' => [
            'label' => 'Khen thưởng & Kỷ luật',
            'icon' => 'pi-trophy',
            'permissions' => [
                'view rewards' => ['label' => 'Xem khen thưởng', 'description' => 'Xem rewards'],
                'create rewards' => ['label' => 'Tạo khen thưởng', 'description' => 'Tạo reward'],
                'edit rewards' => ['label' => 'Sửa khen thưởng', 'description' => 'Sửa reward'],
                'delete rewards' => ['label' => 'Xóa khen thưởng', 'description' => 'Xóa reward'],
                'approve rewards' => ['label' => 'Duyệt khen thưởng', 'description' => 'Phê duyệt'],
                'view disciplines' => ['label' => 'Xem kỷ luật', 'description' => 'Xem disciplines'],
                'create disciplines' => ['label' => 'Tạo kỷ luật', 'description' => 'Tạo discipline'],
                'edit disciplines' => ['label' => 'Sửa kỷ luật', 'description' => 'Sửa discipline'],
                'delete disciplines' => ['label' => 'Xóa kỷ luật', 'description' => 'Xóa discipline'],
                'approve disciplines' => ['label' => 'Duyệt kỷ luật', 'description' => 'Phê duyệt'],
            ]
        ],

        'reports_analytics' => [
            'label' => 'Báo cáo & Phân tích',
            'icon' => 'pi-chart-bar',
            'permissions' => [
                'view all reports' => ['label' => 'Xem tất cả báo cáo', 'description' => 'Access all reports'],
                'view department reports' => ['label' => 'Xem báo cáo phòng ban', 'description' => 'Department reports'],
                'view employee reports' => ['label' => 'Xem báo cáo nhân sự', 'description' => 'Employee reports'],
                'view contract reports' => ['label' => 'Xem báo cáo hợp đồng', 'description' => 'Contract reports'],
                'view leave reports' => ['label' => 'Xem báo cáo nghỉ phép', 'description' => 'Leave reports'],
                'view payroll reports' => ['label' => 'Xem báo cáo lương', 'description' => 'Payroll reports'],
                'view performance reports' => ['label' => 'Xem báo cáo hiệu suất', 'description' => 'Performance reports'],
                'export all reports' => ['label' => 'Xuất tất cả báo cáo', 'description' => 'Export all'],
                'export department reports' => ['label' => 'Xuất báo cáo phòng ban', 'description' => 'Export dept'],
                'schedule reports' => ['label' => 'Lên lịch báo cáo', 'description' => 'Schedule automated reports'],
                'create custom reports' => ['label' => 'Tạo báo cáo tùy chỉnh', 'description' => 'Custom reports'],
            ]
        ],

        'settings_configuration' => [
            'label' => 'Cài đặt',
            'icon' => 'pi-sliders-h',
            'permissions' => [
                'view settings' => ['label' => 'Xem cài đặt', 'description' => 'View settings'],
                'edit settings' => ['label' => 'Sửa cài đặt', 'description' => 'Modify settings'],
                'manage notification templates' => ['label' => 'Quản lý mẫu thông báo', 'description' => 'CRUD notification templates'],
                'manage email templates' => ['label' => 'Quản lý mẫu email', 'description' => 'CRUD email templates'],
            ]
        ],

        'legacy_data_import' => [
            'label' => 'Import dữ liệu lịch sử',
            'icon' => 'pi-upload',
            'permissions' => [
                'import legacy data' => ['label' => 'Import dữ liệu cũ', 'description' => 'General legacy import'],
                'backfill employees' => ['label' => 'Backfill nhân viên', 'description' => 'Import nhân viên cũ'],
                'backfill contracts' => ['label' => 'Backfill hợp đồng', 'description' => 'Import hợp đồng cũ'],
                'backfill leave requests' => ['label' => 'Backfill đơn nghỉ', 'description' => 'Import đơn nghỉ đã duyệt'],
                'backfill insurance records' => ['label' => 'Backfill BHXH', 'description' => 'Import BHXH lịch sử'],
                'backfill payroll records' => ['label' => 'Backfill lương', 'description' => 'Import lịch sử lương'],
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    // Get permission label (Vietnamese)
    'get_label' => function ($permissionName) {
        $modules = config('permissions.modules');
        foreach ($modules as $module) {
            if (isset($module['permissions'][$permissionName])) {
                return $module['permissions'][$permissionName]['label'];
            }
        }
        return $permissionName; // Fallback to English name
    },

    // Get permission description
    'get_description' => function ($permissionName) {
        $modules = config('permissions.modules');
        foreach ($modules as $module) {
            if (isset($module['permissions'][$permissionName])) {
                return $module['permissions'][$permissionName]['description'];
            }
        }
        return '';
    },

    // Get all permissions as flat array
    'get_all' => function () {
        $modules = config('permissions.modules');
        $permissions = [];
        foreach ($modules as $module) {
            foreach ($module['permissions'] as $name => $data) {
                $permissions[$name] = array_merge($data, [
                    'module' => $module['label'],
                    'module_icon' => $module['icon']
                ]);
            }
        }
        return $permissions;
    },
];
