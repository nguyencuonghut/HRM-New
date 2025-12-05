# Hướng dẫn sử dụng Module Nghỉ phép & Lương

## 📅 Module Nghỉ phép (Leave Management)

### 1. Truy cập Menu
Sau khi login, bạn sẽ thấy menu **"Nghỉ phép"** trong sidebar bên trái với 2 mục:
- **Đơn nghỉ phép**: Quản lý đơn nghỉ phép của bạn
- **Phê duyệt**: Xem đơn chờ duyệt (dành cho Manager/HR)

---

### 2. Tạo đơn nghỉ phép mới

#### Bước 1: Vào trang Đơn nghỉ phép
- Click **"Nghỉ phép" → "Đơn nghỉ phép"** trong menu
- Click nút **"Tạo đơn mới"** (màu xanh, góc trên bên trái)

#### Bước 2: Điền thông tin
1. **Loại phép**: Chọn từ dropdown
   - Phép năm (12 ngày/năm, có lương)
   - Phép ốm (30 ngày/năm, có lương)
   - Phép riêng có lương (3 ngày/năm)
   - Phép không lương
   - Phép thai sản (180 ngày)
   - Phép học tập, Phép công tác

2. **Số ngày phép còn lại**: Hiển thị tự động sau khi chọn loại phép

3. **Từ ngày**: Chọn ngày bắt đầu nghỉ

4. **Đến ngày**: Chọn ngày kết thúc nghỉ (phải >= ngày bắt đầu)

5. **Số ngày nghỉ**: Tự động tính (không tính thứ 7, CN)
   - ⚠️ Cảnh báo màu đỏ nếu vượt số ngày phép còn lại

6. **Lý do nghỉ**: Nhập lý do (không bắt buộc)

#### Bước 3: Lưu hoặc Nộp đơn
- **Lưu nháp**: Lưu đơn để chỉnh sửa sau
- **Nộp đơn**: Gửi ngay vào quy trình phê duyệt
  - ✅ Hệ thống tự động kiểm tra số ngày phép
  - ✅ Tạo chuỗi phê duyệt 3 bước:
    1. Trưởng phòng (LINE_MANAGER)
    2. Giám đốc (DIRECTOR) - nếu có phòng cấp trên
    3. Phòng Nhân sự (HR)

---

### 3. Quản lý đơn nghỉ phép

#### Xem danh sách đơn
Trang **"Đơn nghỉ phép"** hiển thị tất cả đơn của bạn với các cột:
- Mã NV
- Loại phép (badge màu)
- Từ ngày → Đến ngày
- Số ngày
- Trạng thái (badge màu):
  - 🔵 **Nháp**: Chưa nộp, có thể sửa
  - 🟡 **Chờ duyệt**: Đang trong quy trình
  - 🟢 **Đã duyệt**: Hoàn tất
  - 🔴 **Từ chối**: Bị từ chối
  - 🟠 **Đã hủy**: Đã hủy bởi bạn
- Ngày nộp

#### Bộ lọc
- **Loại phép**: Lọc theo loại
- **Trạng thái**: Lọc theo trạng thái
- **Từ ngày/Đến ngày**: Lọc theo khoảng thời gian
- **Tìm kiếm**: Tìm theo tên NV hoặc lý do

#### Thao tác trên đơn
1. **👁️ Xem chi tiết**: Click icon mắt
   - Xem timeline phê duyệt
   - Xem comment của người duyệt
   - Xem thời gian từng bước

2. **✏️ Chỉnh sửa**: Chỉ với đơn NHÁP
   - Sửa thông tin và lưu lại
   - Hoặc sửa xong và nộp luôn

3. **🗑️ Xóa**: Chỉ với đơn NHÁP hoặc ĐÃ HỦY

---

### 4. Xem chi tiết đơn nghỉ phép

Khi click vào icon 👁️, bạn sẽ thấy:

#### Panel bên trái: Thông tin chính
- Thông tin nhân viên
- Loại phép (badge màu + nhãn "Có lương")
- Từ ngày → Đến ngày
- Số ngày nghỉ (số lớn màu xanh)
- Số ngày phép còn lại
- Lý do nghỉ

#### Timeline phê duyệt
Hiển thị các bước phê duyệt theo thứ tự:
- ⏳ **Chờ duyệt**: Icon xám
- ✅ **Đã duyệt**: Icon xanh + thời gian + comment
- ❌ **Từ chối**: Icon đỏ + thời gian + lý do

Ví dụ:
```
✅ Bước 1 - Trưởng phòng
   Nguyễn Văn A
   Đã duyệt
   💬 "Đồng ý cho nghỉ"
   ✅ 04/12/2025 10:30

⏳ Bước 2 - Giám đốc
   Trần Văn B
   Chờ duyệt

⏳ Bước 3 - Phòng Nhân sự
   HR Head
   Chờ duyệt
```

#### Panel bên phải: Thời gian & Thao tác
**Thời gian:**
- Tạo lúc
- Nộp đơn
- Duyệt cuối / Từ chối / Hủy bỏ

**Thao tác:**
- ✏️ Chỉnh sửa (nếu NHÁP)
- 📤 Nộp đơn (nếu NHÁP)
- ✅ Phê duyệt (nếu bạn là người duyệt tiếp theo)
- ❌ Từ chối (nếu bạn là người duyệt)
- 🚫 Hủy đơn (nếu NHÁP hoặc CHỜ DUYỆT)
- ⬅️ Quay lại

---

### 5. Phê duyệt đơn nghỉ phép (Dành cho Manager/HR)

#### Truy cập trang Phê duyệt
- Click **"Nghỉ phép" → "Phê duyệt"**
- Badge số đếm hiển thị số đơn chờ bạn duyệt

#### Danh sách đơn chờ duyệt
Hiển thị các đơn mà **bạn là người duyệt tiếp theo**:
- Thông tin nhân viên
- Loại phép
- Từ ngày → Đến ngày
- Số ngày
- Ngày nộp
- **Bước duyệt**: Hiển thị bước hiện tại + vai trò của bạn
  - "Bước 1 - Trưởng phòng"
  - "Bước 2 - Giám đốc"
  - "Bước 3 - Phòng Nhân sự"

#### Phê duyệt/Từ chối nhanh
Từ danh sách, click:
1. **👁️ Xem chi tiết**: Xem đầy đủ thông tin
2. **✅ Phê duyệt**: Mở dialog phê duyệt
3. **❌ Từ chối**: Mở dialog từ chối

#### Dialog Phê duyệt
1. Xem lại thông tin đơn
2. Nhập nhận xét (không bắt buộc)
3. Click **"Phê duyệt"**
   - ✅ Chuyển sang bước tiếp theo
   - ✅ Nếu là bước cuối → đơn APPROVED + trừ số ngày phép

#### Dialog Từ chối
1. Xem lại thông tin đơn
2. **Nhập lý do từ chối** (BẮT BUỘC)
3. Click **"Từ chối"**
   - ❌ Đơn chuyển sang REJECTED
   - ❌ Không trừ số ngày phép

---

### 6. Quy trình phê duyệt tự động

Khi bạn nộp đơn, hệ thống tự động:

1. **Tìm Trưởng phòng** (Bước 1)
   - Lấy từ phân công PRIMARY của bạn
   - Tìm người có role_type = HEAD trong cùng phòng
   - Không tự phê duyệt cho chính mình

2. **Tìm Giám đốc** (Bước 2) - nếu có
   - Nếu phòng của bạn có phòng cấp trên (parent_department)
   - Tìm HEAD của phòng cấp trên

3. **Phòng Nhân sự** (Bước 3)
   - Tự động tìm user có role "HR Head"

4. **Phê duyệt tuần tự**
   - Phải duyệt theo thứ tự: Bước 1 → 2 → 3
   - Không thể skip bước

5. **Auto-approve**
   - Nếu loại phép không cần phê duyệt (requires_approval = false)
   - Tự động APPROVED và trừ số ngày phép

---

## 💰 Module Lương (Payroll)

### 1. Cấu trúc Module

#### 3 bảng chính:
1. **Kỳ lương (Payroll Periods)**
   - Quản lý theo tháng/năm
   - Status: DRAFT → PROCESSING → APPROVED → PAID

2. **Bảng lương (Payroll Items)**
   - Chi tiết lương từng nhân viên
   - Lưu snapshot: phòng ban, chức vụ, role

3. **Điều chỉnh (Adjustments)**
   - Thưởng, phạt, tạm ứng, làm thêm giờ

---

### 2. Tính lương tự động

#### PayrollCalculationService thực hiện:

**Bước 1: Lấy thông tin cơ bản**
- Contract hiện tại → **Base Salary**
- Assignment PRIMARY → **Position Allowance**
  - HEAD: 2,000,000 VND
  - DEPUTY: 1,000,000 VND
  - MEMBER: 0 VND

**Bước 2: Tính Gross Salary**
```
Total Allowances = Position + Responsibility + Other
Gross Salary = (Base Salary + Total Allowances) × (Working Days / 22)
```

Ví dụ:
- Base: 15,000,000 VND
- Position (HEAD): 2,000,000 VND
- Working days: 20/22
- Gross = (15M + 2M) × (20/22) = 15,454,545 VND

**Bước 3: Khấu trừ Bảo hiểm**
- BHXH: 8% × Gross = 1,236,364 VND
- BHYT: 1.5% × Gross = 231,818 VND
- BHTN: 1% × Gross = 154,545 VND
- **Tổng Bảo hiểm**: 1,622,727 VND

**Bước 4: Tính Thu nhập chịu thuế**
```
Taxable Income = Gross - Insurance - Personal Deduction - Dependent Deduction
```
- Personal Deduction: 11,000,000 VND (bản thân)
- Dependent Deduction: 4,400,000 VND × số người phụ thuộc

Ví dụ (0 người phụ thuộc):
```
Taxable = 15,454,545 - 1,622,727 - 11,000,000 - 0 = 2,831,818 VND
```

**Bước 5: Thuế TNCN (7 bậc lũy tiến)**
| Thu nhập chịu thuế | Thuế suất |
|-------------------|-----------|
| 0 - 5M            | 5%        |
| 5M - 10M          | 10%       |
| 10M - 18M         | 15%       |
| 18M - 32M         | 20%       |
| 32M - 52M         | 25%       |
| 52M - 80M         | 30%       |
| Trên 80M          | 35%       |

Ví dụ với 2,831,818 VND:
```
Tax = 2,831,818 × 5% = 141,591 VND
```

**Bước 6: Tính Lương thực lĩnh**
```
Net Salary = Gross Salary - Total Deductions
Total Deductions = Insurance + Income Tax + Other Deductions
```

Ví dụ:
```
Net = 15,454,545 - (1,622,727 + 141,591) = 13,690,227 VND
```

---

### 3. Snapshot Pattern

Hệ thống lưu **snapshot** của thông tin tại thời điểm tính lương:
- department_name: "Phòng IT"
- position_title: "Trưởng phòng"
- role_type: "HEAD"

✅ Đảm bảo dữ liệu lương không thay đổi khi:
- Nhân viên chuyển phòng
- Đổi chức vụ
- Cập nhật tên phòng/chức vụ

---

### 4. Calculation Details (JSON)

Mỗi payroll item lưu chi tiết tính toán:
```json
{
  "contract_number": "HD-2025-001",
  "calculation_date": "2025-12-04 10:30:00",
  "base_salary_breakdown": {
    "monthly_base": 15000000,
    "position_allowance": 2000000,
    "role_type": "HEAD"
  },
  "attendance": {
    "working_days": 20,
    "standard_days": 22,
    "rate": 90.91
  },
  "deductions_breakdown": {
    "social_insurance": {"rate": "8%", "amount": 1236364},
    "health_insurance": {"rate": "1.5%", "amount": 231818},
    "unemployment_insurance": {"rate": "1%", "amount": 154545}
  },
  "tax_calculation": {
    "gross_salary": 15454545,
    "insurance_deduction": 1622727,
    "personal_deduction": 11000000,
    "dependent_deduction": 0,
    "dependents_count": 0,
    "taxable_income": 2831818,
    "income_tax": 141591
  }
}
```

---

### 5. Workflow (Dành cho HR/Admin)

#### Tạo kỳ lương mới
1. Tạo PayrollPeriod (tháng/năm)
2. Status: DRAFT

#### Tính lương cho toàn bộ nhân viên
```php
$service = new PayrollCalculationService();
$results = $service->calculatePayrollForPeriod($period);
```

Kết quả:
```php
[
  'success' => [employee_ids...],  // Thành công
  'failed' => [                     // Thất bại
    ['employee_id' => 'xxx', 'error' => 'No active contract']
  ],
  'total' => 50
]
```

#### Thêm điều chỉnh (Adjustment)
```php
PayrollAdjustment::create([
  'payroll_item_id' => $item->id,
  'type' => 'BONUS',           // BONUS/PENALTY/ADVANCE/OVERTIME/OTHER
  'amount' => 5000000,         // Số tiền
  'reason' => 'Thưởng dự án',
  'description' => 'Hoàn thành dự án X'
]);
```

#### Phê duyệt và Thanh toán
1. PROCESSING → APPROVED (sau khi review)
2. APPROVED → PAID (sau khi chuyển lương)

---

### 6. API Endpoints (Để implement UI sau)

Các route cần có:
```php
// Payroll Periods
GET    /payroll-periods           // List kỳ lương
POST   /payroll-periods           // Tạo kỳ mới
GET    /payroll-periods/{id}      // Chi tiết kỳ
POST   /payroll-periods/{id}/calculate  // Tính lương toàn bộ
POST   /payroll-periods/{id}/approve    // Phê duyệt
POST   /payroll-periods/{id}/mark-paid  // Đánh dấu đã trả

// Payroll Items
GET    /payroll-items             // List bảng lương
GET    /payroll-items/{id}        // Chi tiết 1 nhân viên
POST   /payroll-items/{id}/recalculate  // Tính lại

// Adjustments
POST   /payroll-items/{id}/adjustments  // Thêm điều chỉnh
DELETE /adjustments/{id}          // Xóa điều chỉnh
```

---

## 🎯 Tóm tắt cho User thông thường

### Nghỉ phép:
1. **Tạo đơn**: Menu → Nghỉ phép → Đơn nghỉ phép → Tạo đơn mới
2. **Điền form**: Chọn loại phép, từ ngày, đến ngày, lý do
3. **Nộp đơn**: Click "Nộp đơn" → Vào quy trình phê duyệt 3 bước
4. **Theo dõi**: Xem trạng thái và timeline phê duyệt

### Phê duyệt (Manager/HR):
1. **Kiểm tra**: Menu → Nghỉ phép → Phê duyệt
2. **Badge**: Số đơn chờ bạn duyệt
3. **Xử lý**: Click ✅ Phê duyệt hoặc ❌ Từ chối
4. **Comment**: Nhập nhận xét/lý do

### Lương (HR/Admin):
- Module đã sẵn sàng với full business logic
- UI để implement sau
- Tính toán tự động: Contract + Assignment + Attendance → Net Salary

---

## 📞 Hỗ trợ

Nếu có vấn đề:
1. Kiểm tra phân công PRIMARY (cần có để tính phụ cấp và approval routing)
2. Kiểm tra Contract hiện tại (cần có để tính lương cơ bản)
3. Kiểm tra role "HR Head" (cần có để phê duyệt cuối)
4. Xem Activity Logs để trace lỗi

✅ Leave Module: **Ready to use**
⏳ Payroll Module: **Backend ready, UI pending**
