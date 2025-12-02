<?php

namespace App\Enums;

enum ActivityLogDescription: string
{
    // Contract operations
    case CONTRACT_CREATED = 'CONTRACT_CREATED';
    case CONTRACT_UPDATED = 'CONTRACT_UPDATED';
    case CONTRACT_DELETED = 'CONTRACT_DELETED';
    case CONTRACT_BULK_DELETED = 'CONTRACT_BULK_DELETED';
    case CONTRACT_SUBMITTED = 'CONTRACT_SUBMITTED';
    case CONTRACT_APPROVED_STEP = 'CONTRACT_APPROVED_STEP';
    case CONTRACT_APPROVED_FINAL = 'CONTRACT_APPROVED_FINAL';
    case CONTRACT_REJECTED = 'CONTRACT_REJECTED';
    case CONTRACT_RECALLED = 'CONTRACT_RECALLED';
    case CONTRACT_GENERATED_PDF = 'CONTRACT_GENERATED_PDF';
    case CONTRACT_TERMINATED = 'CONTRACT_TERMINATED';

    // Contract renewal
    case CONTRACT_RENEWAL_REQUESTED = 'CONTRACT_RENEWAL_REQUESTED';
    case CONTRACT_RENEWAL_APPROVED = 'CONTRACT_RENEWAL_APPROVED';
    case CONTRACT_RENEWAL_REJECTED = 'CONTRACT_RENEWAL_REJECTED';

    // Contract appendix operations
    case APPENDIX_CREATED = 'APPENDIX_CREATED';
    case APPENDIX_UPDATED = 'APPENDIX_UPDATED';
    case APPENDIX_DELETED = 'APPENDIX_DELETED';
    case APPENDIX_BULK_DELETED = 'APPENDIX_BULK_DELETED';
    case APPENDIX_SUBMITTED = 'APPENDIX_SUBMITTED';
    case APPENDIX_RECALLED = 'APPENDIX_RECALLED';
    case APPENDIX_APPROVED = 'APPENDIX_APPROVED';
    case APPENDIX_REJECTED = 'APPENDIX_REJECTED';
    case APPENDIX_CANCELLED = 'APPENDIX_CANCELLED';

    // Backup operations
    case BACKUP_CREATED = 'BACKUP_CREATED';
    case BACKUP_EXECUTED = 'BACKUP_EXECUTED';
    case BACKUP_DELETED = 'BACKUP_DELETED';
    case BACKUP_TOGGLED = 'BACKUP_TOGGLED';

    public function label(): string
    {
        return match($this) {
            // Contract operations
            self::CONTRACT_CREATED => 'Tạo hợp đồng',
            self::CONTRACT_UPDATED => 'Chỉnh sửa hợp đồng',
            self::CONTRACT_DELETED => 'Xóa hợp đồng',
            self::CONTRACT_BULK_DELETED => 'Xóa nhiều hợp đồng',
            self::CONTRACT_SUBMITTED => 'Gửi phê duyệt',
            self::CONTRACT_APPROVED_STEP => 'Phê duyệt bước',
            self::CONTRACT_APPROVED_FINAL => 'Phê duyệt hoàn tất - Hợp đồng hiệu lực',
            self::CONTRACT_REJECTED => 'Từ chối phê duyệt',
            self::CONTRACT_RECALLED => 'Thu hồi yêu cầu phê duyệt',
            self::CONTRACT_GENERATED_PDF => 'Sinh file PDF',
            self::CONTRACT_TERMINATED => 'Chấm dứt hợp đồng',

            // Contract renewal
            self::CONTRACT_RENEWAL_REQUESTED => 'Yêu cầu gia hạn hợp đồng',
            self::CONTRACT_RENEWAL_APPROVED => 'Phê duyệt gia hạn hợp đồng',
            self::CONTRACT_RENEWAL_REJECTED => 'Từ chối gia hạn hợp đồng',

            // Contract appendix operations
            self::APPENDIX_CREATED => 'Tạo phụ lục',
            self::APPENDIX_UPDATED => 'Chỉnh sửa phụ lục',
            self::APPENDIX_DELETED => 'Xóa phụ lục',
            self::APPENDIX_BULK_DELETED => 'Xóa nhiều phụ lục',
            self::APPENDIX_SUBMITTED => 'Gửi phê duyệt phụ lục',
            self::APPENDIX_RECALLED => 'Thu hồi yêu cầu phê duyệt phụ lục',
            self::APPENDIX_APPROVED => 'Phê duyệt phụ lục',
            self::APPENDIX_REJECTED => 'Từ chối phụ lục',
            self::APPENDIX_CANCELLED => 'Hủy phụ lục',

            // Backup operations
            self::BACKUP_CREATED => 'Tạo cấu hình backup mới',
            self::BACKUP_EXECUTED => 'Thực thi backup',
            self::BACKUP_DELETED => 'Xóa cấu hình backup',
            self::BACKUP_TOGGLED => 'Thay đổi trạng thái backup',
        };
    }

    public function type(): string
    {
        return match($this) {
            // Contract operations
            self::CONTRACT_CREATED => 'CREATED',
            self::CONTRACT_UPDATED => 'UPDATED',
            self::CONTRACT_DELETED => 'DELETED',
            self::CONTRACT_BULK_DELETED => 'DELETED',
            self::CONTRACT_SUBMITTED => 'SUBMITTED',
            self::CONTRACT_APPROVED_STEP => 'APPROVED_STEP',
            self::CONTRACT_APPROVED_FINAL => 'APPROVED_FINAL',
            self::CONTRACT_REJECTED => 'REJECTED',
            self::CONTRACT_RECALLED => 'RECALLED',
            self::CONTRACT_GENERATED_PDF => 'GENERATED_PDF',
            self::CONTRACT_TERMINATED => 'TERMINATED',

            // Contract renewal
            self::CONTRACT_RENEWAL_REQUESTED => 'CONTRACT_RENEWAL_REQUESTED',
            self::CONTRACT_RENEWAL_APPROVED => 'CONTRACT_RENEWAL_APPROVED',
            self::CONTRACT_RENEWAL_REJECTED => 'CONTRACT_RENEWAL_REJECTED',

            // Contract appendix operations
            self::APPENDIX_CREATED => 'CREATED',
            self::APPENDIX_UPDATED => 'UPDATED',
            self::APPENDIX_DELETED => 'DELETED',
            self::APPENDIX_BULK_DELETED => 'DELETED',
            self::APPENDIX_SUBMITTED => 'SUBMITTED',
            self::APPENDIX_RECALLED => 'RECALLED',
            self::APPENDIX_APPROVED => 'APPROVED_STEP',
            self::APPENDIX_REJECTED => 'REJECTED',
            self::APPENDIX_CANCELLED => 'CANCELLED',
            self::BACKUP_CREATED => 'BACKUP_CREATED',
            self::BACKUP_EXECUTED => 'BACKUP_EXECUTED',
            self::BACKUP_DELETED => 'BACKUP_DELETED',
            self::BACKUP_TOGGLED => 'BACKUP_TOGGLED',
        };
    }
}
