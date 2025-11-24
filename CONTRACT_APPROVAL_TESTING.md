# Contract Approval Workflow - Testing Guide

## 🎯 Mục đích

Hướng dẫn test đầy đủ tính năng Contract Approval Workflow đã implement.

## 📋 Chuẩn bị

### 1. Demo Accounts

| Role | Email | Password | Quyền |
|------|-------|----------|-------|
| Super Admin | nguyenvancuong@honghafeed.com.vn | Hongha@123 | Full access |
| Admin | admin@example.com | password | Quản lý users, xem contracts |
| **Director** | director@example.com | password | **Phê duyệt level 2** |
| **Manager** | manager@example.com | password | **Phê duyệt level 1** |

### 2. Reset Database
```bash
php artisan migrate:fresh --seed
npm run build
```

## 🧪 Test Scenarios

### Scenario 1: Full Approval Flow (Happy Path)

#### Step 1: Tạo Contract (HR/Admin)
1. Login: `admin@example.com` / `password`
2. Vào menu **Hợp đồng**
3. Click **Thêm mới**
4. Điền thông tin:
   - Nhân viên: Chọn bất kỳ
   - Số HĐ: `TEST-2025-001`
   - Đơn vị: Chọn bất kỳ
   - Chức danh: Chọn theo đơn vị
   - Loại HĐ: `Xác định thời hạn`
   - Ngày bắt đầu: Hôm nay
   - Ngày kết thúc: +1 năm
   - Lương cơ bản: 10000000
   - Lương đóng BH: 8000000
   - Phụ cấp vị trí: 1000000
5. Click **Lưu**

**Expected Result:**
- ✅ Contract được tạo với status: `Nháp` (DRAFT)
- ✅ Trong DataTable thấy contract mới
- ✅ Có button **"Gửi phê duyệt"** (icon pi-send màu xanh)

#### Step 2: Gửi phê duyệt (HR/Admin)
1. Click button **Gửi phê duyệt** ở contract vừa tạo
2. Dialog hiện ra với thông tin:
   - Số HĐ: TEST-2025-001
   - Workflow: Trưởng phòng → Giám đốc
3. Click **Gửi phê duyệt**

**Expected Result:**
- ✅ Success message: "Đã gửi hợp đồng để phê duyệt"
- ✅ Contract status đổi thành: `Chờ duyệt` (PENDING_APPROVAL)
- ✅ Có badge hiển thị tiến trình: `0/2` hoặc `0/2` (0 đã duyệt / 2 tổng bước)
- ✅ Buttons đổi thành:
  - ✓ Phê duyệt (xanh)
  - ✗ Từ chối (đỏ)
  - ⟲ Thu hồi (vàng)

#### Step 3: Manager phê duyệt
1. Logout và login: `manager@example.com` / `password`
2. Vào menu **Hợp đồng**
3. Tìm contract `TEST-2025-001` với status `Chờ duyệt`
4. Click button **Phê duyệt** (icon pi-check màu xanh)
5. Dialog hiện ra:
   - Bước hiện tại: `Trưởng phòng`
   - Textarea: Nhập ý kiến (optional)
6. Nhập ý kiến: "Đồng ý phê duyệt" (hoặc để trống)
7. Click **Phê duyệt**

**Expected Result:**
- ✅ Success message: "Đã phê duyệt hợp đồng"
- ✅ Contract vẫn ở status: `Chờ duyệt` (vì còn Director phải duyệt)
- ✅ Badge tiến trình: `1/2` (1 đã duyệt / 2 tổng bước)
- ✅ Manager không còn thấy button phê duyệt nữa (vì đã duyệt rồi)

#### Step 4: Director phê duyệt (hoàn tất)
1. Logout và login: `director@example.com` / `password`
2. Vào menu **Hợp đồng**
3. Tìm contract `TEST-2025-001` với status `Chờ duyệt` và badge `1/2`
4. Click button **Phê duyệt**
5. Dialog hiện ra:
   - Bước hiện tại: `Giám đốc`
   - Textarea: Nhập ý kiến
6. Nhập ý kiến: "Phê duyệt cuối cùng"
7. Click **Phê duyệt**

**Expected Result:**
- ✅ Success message: "Đã phê duyệt hợp đồng"
- ✅ Contract status đổi thành: `Hiệu lực` (ACTIVE)
- ✅ Badge tiến trình biến mất (vì đã hoàn tất)
- ✅ Không còn buttons phê duyệt/từ chối/thu hồi
- ✅ Chỉ còn button "Sinh PDF" và "Chi tiết"

#### Step 5: Xem lịch sử phê duyệt
1. Click button **Chi tiết** (icon pi-list)
2. Chuyển sang tab **Lịch sử phê duyệt**

**Expected Result:**
- ✅ Timeline hiển thị 2 bước:
  
  **Bước 1: Trưởng phòng**
  - ✓ Icon check màu xanh
  - Người duyệt: Manager User (manager@example.com)
  - Tag: `Đã duyệt` (xanh)
  - Ý kiến: "Đồng ý phê duyệt"
  - Thời gian: 23/11/2025 XX:XX
  
  **Bước 2: Giám đốc**
  - ✓ Icon check màu xanh
  - Người duyệt: Director User (director@example.com)
  - Tag: `Đã duyệt` (xanh)
  - Ý kiến: "Phê duyệt cuối cùng"
  - Thời gian: 23/11/2025 XX:XX

---

### Scenario 2: Rejection Flow

#### Step 1: Tạo và gửi contract
1. Login: `admin@example.com` / `password`
2. Tạo contract mới: `TEST-2025-002`
3. Click **Gửi phê duyệt**

**Expected:** Status = `Chờ duyệt`, badge `0/2`

#### Step 2: Manager từ chối
1. Login: `manager@example.com` / `password`
2. Tìm contract `TEST-2025-002`
3. Click button **Từ chối** (icon pi-times màu đỏ)
4. Dialog hiện ra:
   - Bước hiện tại: `Trưởng phòng`
   - Textarea **required**: Lý do từ chối
5. **Không nhập gì** và click **Từ chối**

**Expected Result:**
- ✅ Validation error: "Vui lòng nhập lý do từ chối"
- ✅ Textarea có border đỏ

6. Nhập lý do: "Thông tin lương chưa chính xác"
7. Click **Từ chối**

**Expected Result:**
- ✅ Success message: "Đã từ chối hợp đồng"
- ✅ Contract status đổi về: `Nháp` (DRAFT)
- ✅ Badge tiến trình biến mất
- ✅ Buttons về trạng thái ban đầu: Sửa, Xóa, Gửi phê duyệt

#### Step 3: Xem lịch sử rejection
1. Click **Chi tiết** → Tab **Lịch sử phê duyệt**

**Expected Result:**
- ✅ Timeline hiển thị 2 bước:
  
  **Bước 1: Trưởng phòng**
  - ✗ Icon X màu đỏ
  - Tag: `Từ chối` (đỏ)
  - Ý kiến: "Thông tin lương chưa chính xác"
  - Background: bg-red-50
  
  **Bước 2: Giám đốc**
  - ✗ Icon X màu đỏ
  - Tag: `Từ chối` (đỏ)
  - Chưa có người duyệt (auto-rejected)

---

### Scenario 3: Recall Flow

#### Step 1: Tạo và gửi contract
1. Login: `admin@example.com` / `password`
2. Tạo contract: `TEST-2025-003`
3. Click **Gửi phê duyệt**

**Expected:** Status = `Chờ duyệt`

#### Step 2: Thu hồi trước khi có ai duyệt
1. Vẫn với admin account
2. Click button **Thu hồi** (icon pi-replay màu vàng)
3. Dialog xác nhận: "Bạn có chắc muốn thu hồi?"
4. Click **Thu hồi**

**Expected Result:**
- ✅ Success message: "Đã thu hồi yêu cầu phê duyệt"
- ✅ Contract status về: `Nháp` (DRAFT)
- ✅ Các approval steps bị xóa (không còn trong DB)

#### Step 3: Test không được thu hồi sau khi đã có bước approve
1. Gửi phê duyệt lại contract `TEST-2025-003`
2. Login: `manager@example.com` và phê duyệt
3. Logout, login lại: `admin@example.com`
4. Click **Thu hồi**

**Expected Result:**
- ❌ Error message: "Không thể thu hồi hợp đồng đã được phê duyệt một phần"
- ✅ Contract vẫn ở status `Chờ duyệt`

---

## 🔍 Checklist Testing

### UI Components
- [ ] DataTable hiển thị đúng status với Tag colors
- [ ] Badge tiến trình `X/Y` hiển thị khi PENDING_APPROVAL
- [ ] Buttons hiển thị đúng theo status:
  - [ ] DRAFT: Sửa, Xóa, Gửi phê duyệt, Sinh PDF
  - [ ] PENDING_APPROVAL: Phê duyệt, Từ chối, Thu hồi
  - [ ] ACTIVE: Sinh PDF, Chi tiết
- [ ] Dialog "Gửi phê duyệt" hiển thị đúng workflow
- [ ] Dialog "Phê duyệt" hiển thị bước hiện tại
- [ ] Dialog "Từ chối" validation required comments
- [ ] Tab "Lịch sử phê duyệt" timeline design đẹp

### Business Logic
- [ ] Submit tạo 2 approval steps (MANAGER, DIRECTOR)
- [ ] Manager chỉ approve được level MANAGER
- [ ] Director chỉ approve được level DIRECTOR
- [ ] Sau Manager approve, status vẫn PENDING (chờ Director)
- [ ] Sau Director approve, status thành ACTIVE
- [ ] Reject ở bất kỳ level nào → status về DRAFT
- [ ] Recall chỉ được khi chưa có bước nào approved
- [ ] Overlap check khi Director approve (bước cuối)

### Authorization
- [ ] Admin không thấy button Phê duyệt/Từ chối
- [ ] Manager thấy button khi ở level MANAGER
- [ ] Director thấy button khi ở level DIRECTOR
- [ ] Sau khi duyệt xong, người duyệt không còn thấy button

### Data Integrity
- [ ] Activity log ghi đúng các actions
- [ ] Approval history lưu đúng approver, comments, timestamp
- [ ] Contract.approved_at chỉ set khi hoàn tất workflow
- [ ] Contract.rejected_at set khi reject

---

## 🐛 Common Issues & Solutions

### Issue 1: "Không có quyền phê duyệt"
**Nguyên nhân:** User không có role Manager/Director hoặc không đúng level

**Fix:** Kiểm tra:
```sql
SELECT u.email, r.name 
FROM users u
JOIN model_has_roles mr ON mr.model_id = u.id
JOIN roles r ON r.id = mr.role_id
WHERE u.email IN ('manager@example.com', 'director@example.com');
```

### Issue 2: Badge tiến trình không hiển thị
**Nguyên nhân:** ContractResource không return approval_progress

**Fix:** Kiểm tra:
- Controller load relationship: `->with('approvals.approver')`
- ContractResource có: `'approval_progress' => $this->getApprovalProgress()`

### Issue 3: Timeline không hiển thị
**Nguyên nhân:** Contract không có approvals

**Fix:**
```sql
SELECT * FROM contract_approvals WHERE contract_id = 'xxx';
```

---

## 📊 Database Verification Queries

### Check approval steps
```sql
SELECT 
    c.contract_number,
    ca.level,
    ca.order,
    ca.status,
    u.name as approver,
    ca.comments,
    ca.approved_at
FROM contracts c
LEFT JOIN contract_approvals ca ON ca.contract_id = c.id
LEFT JOIN users u ON u.id = ca.approver_id
WHERE c.contract_number = 'TEST-2025-001'
ORDER BY ca.order;
```

### Check pending contracts for user
```sql
SELECT 
    c.contract_number,
    ca.level,
    ca.status
FROM contracts c
INNER JOIN contract_approvals ca ON ca.contract_id = c.id
WHERE ca.status = 'PENDING'
  AND ca.level = 'MANAGER' -- hoặc 'DIRECTOR'
  AND (ca.approver_id = 'user-uuid' OR ca.approver_id IS NULL);
```

---

## ✅ Sign-off Checklist

- [ ] All 3 scenarios tested successfully
- [ ] UI components render correctly
- [ ] No console errors
- [ ] Responsive design works on mobile
- [ ] Activity log records all actions
- [ ] Database integrity maintained
- [ ] Performance acceptable (< 1s per action)
- [ ] Error messages user-friendly

---

**Testing Date:** ___________  
**Tester:** ___________  
**Status:** ⬜ PASS | ⬜ FAIL  
**Notes:** ___________
