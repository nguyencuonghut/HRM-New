# Module Quản Lý Bảo Hiểm - Hướng Dẫn Nhanh

## 🎯 Tổng Quan 3 Phút

Module giúp tự động hóa quy trình quản lý BHXH/BHYT/BHTN theo đúng quy định:
- ✅ Tự động phát hiện tăng/giảm/điều chỉnh hàng tháng
- ✅ Tính toán chính xác mức đóng 5 thành phần BHXH
- ✅ Xuất file Excel nộp cơ quan BHXH
- ✅ Lưu trữ snapshot không thay đổi

---

## 🚀 Quick Start - 5 Bước Cơ Bản

### Bước 1: Tạo Hợp Đồng Có BHXH (HR)

```
1. Menu: Hợp đồng → Tạo hợp đồng mới
2. Nhập thông tin nhân viên + lương
3. Phần "Thông tin BHXH":
   ├─ Lương đóng BHXH: [15,000,000]
   ├─ ☑ BHXH Hưu Trí (22%)
   ├─ ☑ BHXH Ốm Đau (3%)
   ├─ ☑ BHXH TNLĐ-BNN (1%)
   ├─ ☑ BHTN (2%) - Chọn theo lương hoặc cố định
   └─ ☑ BHYT (4.5%)
4. Lưu → Hệ thống tự động tạo participation
```

### Bước 2: Tạo Báo Cáo Tháng (Payroll Admin)

```
1. Menu: Quản lý BHXH → Báo cáo BHXH
2. Click "Tạo báo cáo mới"
3. Chọn tháng: [01] Năm: [2026]
4. Click "Tạo"
5. Chờ 5-30 giây → Hệ thống quét thay đổi
```

### Bước 3: Review & Điều Chỉnh (Payroll Admin)

```
1. Xem 3 tab: TĂNG | GIẢM | ĐIỀU CHỈNH
2. Nếu cần đổi tháng kê khai:
   ├─ Click dropdown "Tháng KK chính thức"
   ├─ Chọn tháng khác
   └─ Nhập lý do (bắt buộc)
3. Lưu tự động
```

### Bước 4: Hoàn Tất Báo Cáo (Payroll Admin)

```
1. Kiểm tra tất cả thông tin đúng
2. Click "Hoàn tất báo cáo"
3. Xác nhận
4. Chờ 5-15 giây → Snapshot được tạo
5. Tab "TỔNG HỢP ĐÓNG BHXH" xuất hiện
```

### Bước 5: Xuất Excel (Payroll Admin)

```
1. Vào tab "TỔNG HỢP ĐÓNG BHXH"
2. Click "Xuất Excel"
3. File tải về: BaoCao_BHXH_2026_01.xlsx
4. Nộp cho cơ quan BHXH
```

---

## 📊 Tỷ Lệ BHXH Hiện Hành

| Thành Phần | NLĐ | NSDLĐ | Tổng |
|------------|-----|-------|------|
| **BHXH Hưu Trí** | 8% | 14% | **22%** |
| **BHXH Ốm Đau** | 0% | 3% | **3%** |
| **BHXH TNLĐ-BNN** | 0% | 1% | **1%** |
| **BHTN** | 1% | 1% | **2%** |
| **BHYT** | 1.5% | 3% | **4.5%** |
| **TỔNG** | **10.5%** | **22%** | **32.5%** |

**Ví dụ tính**: Lương BHXH 15,000,000 VND
```
Tổng mức đóng = 15,000,000 × 32.5% = 4,875,000 VND
```

---

## 🔐 Phân Quyền

### HR Employee (Xem)
- ✅ Xem báo cáo BHXH
- ✅ Xem chi tiết mức đóng
- ❌ Không tạo/sửa/hoàn tất

### Payroll Admin (Toàn Quyền)
- ✅ Tất cả quyền của HR Employee
- ✅ Tạo báo cáo BHXH
- ✅ Điều chỉnh tháng kê khai
- ✅ Hoàn tất báo cáo
- ✅ Xuất Excel
- ✅ Cấu hình tỷ lệ BHXH

---

## 📝 Các Tình Huống Thường Gặp

### 1. Nhân viên ký HĐ cuối tháng

**Vấn đề**: Ký ngày 28/01, hệ thống gợi ý kê khai tháng 02

**Giải pháp**:
```
Nếu muốn kê khai từ tháng 01:
1. Mở báo cáo tháng 01
2. Tìm bản ghi NV
3. Dropdown "Tháng KK" → Chọn 2026-01
4. Nhập lý do: "Thỏa thuận với NV kê khai từ tháng 01"
```

### 2. BHTN tính trên mức cố định

**Vấn đề**: Lương 20M nhưng BHTN chỉ đóng trên 5M

**Giải pháp**:
```
Khi tạo HĐ:
1. Chọn ☑ BHTN
2. Chọn ⚫ Theo mức cố định
3. Nhập: 5,000,000
4. Lưu
→ BHTN = 5,000,000 × 2% = 100,000 (thay vì 400,000)
```

### 3. Nhân viên không tham gia BHTN

**Vấn đề**: NV trên 60 tuổi không đóng BHTN

**Giải pháp**:
```
Khi tạo HĐ:
1. BỎ CHỌN ☐ BHTN
2. Chỉ chọn 4 thành phần còn lại
→ Tổng = 30.5% (thay vì 32.5%)
```

### 4. Phát hiện sai sau khi Hoàn tất

**Vấn đề**: Đã finalize nhưng phát hiện sai

**Giải pháp**:
```
⚠️ KHÔNG THỂ SỬA sau khi finalize

Cách xử lý:
- Tạo báo cáo ĐIỀU CHỈNH ở tháng tiếp theo
- Hoặc liên hệ IT để revert (mất snapshot)
```

---

## 🛠️ Tools Cho Admin

### 1. Kiểm Tra Toàn Vẹn Dữ Liệu

```bash
# Check cơ bản
php artisan insurance:check-integrity

# Auto-fix
php artisan insurance:check-integrity --fix

# Chi tiết
php artisan insurance:check-integrity --detailed
```

**Chạy định kỳ**: Mỗi ngày 2h sáng (đã cấu hình scheduler)

### 2. Benchmark Hiệu Năng

```bash
# Test cơ bản
php artisan insurance:benchmark

# Test với 1000 nhân viên
php artisan insurance:benchmark --employees=1000

# Xuất kết quả ra file
php artisan insurance:benchmark --export
```

**Chạy khi**: Sau mỗi optimization hoặc khi nghi ngờ chậm

### 3. Backup Database

```bash
# Backup toàn bộ
php artisan backup:run --only-db

# Backup chỉ bảng insurance
mysqldump -u user -p database \
  insurance_* > insurance_backup_$(date +%Y%m%d).sql
```

**Lịch backup**: Hàng ngày 1h sáng (đã cấu hình scheduler)

---

## ⚡ Performance Targets

| Operation | Target | Current | Status |
|-----------|--------|---------|--------|
| Generate report (100 NV) | < 5s | ~3s | ✅ |
| Finalize report (100 NV) | < 10s | ~5s | ✅ |
| Excel export (100 NV) | < 15s | ~8s | ✅ |
| Database queries | < 1ms | ~0.25ms | ✅ |

**Scale**: Hệ thống test OK với 1,000+ nhân viên

---

## 🐛 Troubleshooting Nhanh

### Lỗi: "Cannot create report"
```
Nguyên nhân: Tháng đã có báo cáo
Giải pháp: Xóa báo cáo cũ (nếu chưa finalize)
```

### Lỗi: "Export failed"
```
Nguyên nhân: Báo cáo chưa finalized
Giải pháp: Hoàn tất báo cáo trước
```

### UI: Tab "Tổng hợp" không hiện
```
Nguyên nhân: Báo cáo chưa finalized
Giải pháp: Click "Hoàn tất báo cáo"
```

### Slow performance
```
Giải pháp:
1. Chạy: php artisan insurance:benchmark
2. Check indexes: database/migrations/*_add_insurance_performance_indexes.php
3. Clear cache: php artisan cache:clear
```

---

## 📚 Tài Liệu Chi Tiết

| Tài liệu | Mục đích | Đối tượng |
|----------|----------|-----------|
| [INSURANCE_USER_GUIDE.md](./INSURANCE_USER_GUIDE.md) | Hướng dẫn sử dụng đầy đủ | HR Team |
| [INSURANCE_ADMIN_GUIDE.md](./INSURANCE_ADMIN_GUIDE.md) | Hướng dẫn quản trị | Admin/IT |
| [INSURANCE_API_DOCUMENTATION.md](./INSURANCE_API_DOCUMENTATION.md) | API Reference | Developers |
| [INSURANCE_INTEGRITY_CHECK_GUIDE.md](./INSURANCE_INTEGRITY_CHECK_GUIDE.md) | Data integrity | Admin |
| [INSURANCE_PERFORMANCE_TESTING_GUIDE.md](./INSURANCE_PERFORMANCE_TESTING_GUIDE.md) | Performance testing | Admin/DevOps |
| [INSURANCE_UAT_TEST_PLAN.md](./INSURANCE_UAT_TEST_PLAN.md) | UAT scenarios | QA Team |

---

## 📞 Liên Hệ Hỗ Trợ

**Kỹ Thuật**:
- Email: support@company.com
- Hotline: 1900-xxxx (8h-17h, T2-T6)
- Slack: #hrm-support

**Nghiệp Vụ**:
- Phòng Nhân Sự: nhansu@company.com
- Phòng Tài Chính: taichinh@company.com

**Khẩn Cấp**:
- On-call: +84 xxx xxx xxx (24/7)

---

## 🔄 Update History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 12/01/2026 | Initial release |

---

**💡 Tips**:
- Backup trước mỗi thao tác quan trọng
- Chạy integrity check định kỳ
- Monitor performance metrics
- Đọc full guide khi cần chi tiết

**⚠️ Warnings**:
- KHÔNG thể sửa sau khi finalized
- Thay đổi tỷ lệ chỉ ảnh hưởng HĐ mới
- Luôn test trên staging trước khi production
