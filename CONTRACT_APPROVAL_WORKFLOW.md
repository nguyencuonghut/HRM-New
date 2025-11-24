# Contract Approval Workflow - Implementation Summary

## 📋 Tổng quan

Hệ thống phê duyệt hợp đồng đa cấp (Multi-level Approval Workflow) cho phép quản lý quy trình phê duyệt hợp đồng theo thứ bậc: **Manager → Director**.

## 🗂️ Database Schema

### Bảng `contract_approvals`
```sql
- id (UUID, PK)
- contract_id (UUID, FK → contracts)
- level (ENUM: MANAGER, DIRECTOR)
- order (INT: 1=Manager, 2=Director)
- approver_id (UUID nullable, FK → users)
- status (ENUM: PENDING, APPROVED, REJECTED)
- comments (TEXT nullable)
- approved_at (TIMESTAMP nullable)
- timestamps
```

**Indexes:**
- `(contract_id, level, status)` - Query nhanh approval steps
- `(approver_id, status)` - Query "contracts chờ tôi duyệt"

## 📊 Enums

### ApprovalLevel
- `MANAGER` → "Trưởng phòng" (order: 1)
- `DIRECTOR` → "Giám đốc" (order: 2)

### ApprovalStatus  
- `PENDING` → "Chờ duyệt" (warning)
- `APPROVED` → "Đã duyệt" (success)
- `REJECTED` → "Từ chối" (danger)

## 🔐 Permissions

| Permission | Mô tả |
|------------|-------|
| `view contracts` | Xem danh sách hợp đồng |
| `create contracts` | Tạo hợp đồng mới |
| `edit contracts` | Chỉnh sửa hợp đồng |
| `delete contracts` | Xóa hợp đồng |
| `submit contracts` | Gửi hợp đồng để phê duyệt |
| `approve contracts` | Phê duyệt hợp đồng (Manager/Director) |
| `recall contracts` | Thu hồi yêu cầu phê duyệt |

## 👥 Roles & Quyền hạn

### Super Admin
- Full access tất cả chức năng

### Admin  
- Quản lý users, departments
- Xem contracts nhưng không approve

### Director
- Phê duyệt level 2 (sau Manager)
- Quyền `approve contracts`
- Demo user: `director@example.com` / `password`

### Manager
- Phê duyệt level 1 (đầu tiên)
- Quyền `approve contracts`
- Demo user: `manager@example.com` / `password`

## 🔄 Workflow

### 1. Tạo hợp đồng (DRAFT)
```
HR tạo contract → status = DRAFT
```

### 2. Gửi phê duyệt (PENDING_APPROVAL)
```
POST /contracts/{id}/submit-for-approval
↓
- Tạo 2 approval steps:
  * Step 1: MANAGER (order=1, status=PENDING)
  * Step 2: DIRECTOR (order=2, status=PENDING)
- Contract.status = PENDING_APPROVAL
```

### 3. Manager phê duyệt
```
POST /contracts/{id}/approve
Body: { comments: "Đồng ý phê duyệt" }
↓
- Step 1: status = APPROVED, approved_at = now()
- Contract vẫn ở PENDING_APPROVAL (chờ Director)
```

### 4. Director phê duyệt (hoàn tất)
```
POST /contracts/{id}/approve  
Body: { comments: "Phê duyệt cuối cùng" }
↓
- Step 2: status = APPROVED
- Contract.status = ACTIVE
- Contract.approved_at = now()
```

### 5. Từ chối (bất kỳ level nào)
```
POST /contracts/{id}/reject
Body: { comments: "Lý do từ chối (required)" }
↓
- Current step: status = REJECTED
- Tất cả steps còn lại: status = REJECTED  
- Contract.status = DRAFT
- Contract.rejected_at = now()
```

### 6. Thu hồi
```
POST /contracts/{id}/recall
↓
- Xóa tất cả approval steps
- Contract.status = DRAFT
- Chỉ được phép nếu chưa có bước nào APPROVED
```

## 🎯 Business Rules

### Policy Authorization
```php
// ContractPolicy.php

submit(User $user, Contract $contract)
- Chỉ HR (create contracts) và contract phải DRAFT

approve(User $user, Contract $contract)
- Admin: luôn được approve
- Manager/Director: kiểm tra workflow (canUserApprove)

recall(User $user, Contract $contract)
- Chỉ Admin hoặc người có quyền create contracts
```

### Service Logic
```php
// ContractApprovalService.php

canUserApprove(Contract $contract, User $user): bool
- Contract phải PENDING_APPROVAL
- User phải match với approver_id (nếu đã assign)
- Hoặc User có role tương ứng với level hiện tại
```

### Validation
- **Submit**: Contract phải DRAFT
- **Approve**: Contract phải PENDING_APPROVAL
- **Reject**: Contract phải PENDING_APPROVAL + comments required
- **Recall**: Contract phải PENDING_APPROVAL + chưa có bước nào APPROVED
- **Overlap Check**: Khi Director approve (bước cuối), kiểm tra trùng lặp thời gian với contracts khác

## 📡 API Endpoints

| Method | URL | Action | Auth |
|--------|-----|--------|------|
| POST | `/contracts/{id}/submit-for-approval` | Gửi phê duyệt | Policy: submit |
| POST | `/contracts/{id}/approve` | Phê duyệt | Policy: approve |
| POST | `/contracts/{id}/reject` | Từ chối | Policy: approve |
| POST | `/contracts/{id}/recall` | Thu hồi | Policy: recall |
| GET | `/contracts/pending-approvals` | Lấy contracts chờ duyệt của user | Auth |

## 🎨 Frontend Integration (Planned)

### ContractIndex.vue
**Action buttons** dựa trên status:
```javascript
if (contract.status === 'DRAFT') {
  // Hiện button: "Gửi phê duyệt"
}

if (contract.status === 'PENDING_APPROVAL') {
  // Nếu user có quyền approve:
  //   - Button "Phê duyệt"
  //   - Button "Từ chối"
  // Nếu là người tạo:
  //   - Button "Thu hồi"
}
```

**Approval Progress Badge:**
```html
<Tag v-if="contract.approval_progress">
  {{ contract.approval_progress.approved }}/{{ contract.approval_progress.total }}
</Tag>
```

### ContractDetail.vue - Tab Approval History
**Timeline display:**
```
┌─ Trưởng phòng (Manager)
│  ✓ Nguyễn Văn A
│  📅 23/11/2025 14:30
│  💬 "Đồng ý phê duyệt"
│
├─ Giám đốc (Director)  
│  ⏳ Chờ phê duyệt...
│  👤 Chưa xác định
└─
```

## 🔧 Testing Guide

### 1. Tạo contract (HR)
```bash
Login: admin@example.com / password
Tạo contract mới → Status: DRAFT
```

### 2. Gửi phê duyệt
```bash
Click "Gửi phê duyệt" → Status: PENDING_APPROVAL
```

### 3. Manager approve
```bash
Login: manager@example.com / password
Vào danh sách contracts → Click "Phê duyệt"
Nhập comments → Submit
```

### 4. Director approve
```bash
Login: director@example.com / password  
Vào danh sách contracts → Click "Phê duyệt"
Nhập comments → Submit
Status thành ACTIVE
```

### 5. Test reject
```bash
Login: manager@example.com
Click "Từ chối" → Nhập lý do (required)
Contract quay về DRAFT
```

## 📊 Database Queries Examples

### Lấy contracts chờ Manager duyệt
```sql
SELECT c.* FROM contracts c
INNER JOIN contract_approvals ca ON ca.contract_id = c.id
WHERE ca.level = 'MANAGER'
  AND ca.status = 'PENDING'
  AND (ca.approver_id = ? OR ca.approver_id IS NULL);
```

### Lấy approval history của contract
```sql
SELECT * FROM contract_approvals
WHERE contract_id = ?
ORDER BY `order` ASC;
```

### Thống kê contracts theo status
```sql
SELECT status, COUNT(*) as count
FROM contracts
GROUP BY status;
```

## 🚀 Future Enhancements

1. **Email Notifications** khi có contract chờ approve
2. **Slack/Teams integration** cho realtime alerts  
3. **Custom approval flow** - cấu hình theo department
4. **Parallel approval** - nhiều approver cùng level
5. **Conditional approval** - dựa trên giá trị hợp đồng
6. **Delegation** - ủy quyền approve cho người khác
7. **Auto-approve** - dựa trên rules (VD: contract < 10M VND)

## ✅ Checklist Implementation

- [x] Database migration
- [x] Models & Enums
- [x] Service layer (ContractApprovalService)
- [x] Policy authorization
- [x] Controller endpoints
- [x] Routes registration
- [x] Resources (ContractApprovalResource)
- [x] Permissions seeding
- [x] Demo users (Manager, Director)
- [ ] Frontend UI (ContractIndex approval buttons)
- [ ] Frontend UI (ContractDetail approval history)
- [ ] Testing với actual users
- [ ] Documentation update

## 📝 Notes

- **approver_id nullable**: Cho phép assign dynamic hoặc để null nếu dùng role-based approval
- **Activity logging**: Tất cả actions đều được log qua `activity('contract')`
- **Transaction safety**: Tất cả workflow operations dùng `DB::transaction()`
- **Validation exceptions**: Dùng `ValidationException::withMessages()` cho user-friendly errors

---

**Status**: Backend hoàn tất ✅ | Frontend đang pending ⏳  
**Last Updated**: 23/11/2025  
**Implementation Time**: ~2 hours
