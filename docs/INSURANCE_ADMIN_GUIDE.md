# Hướng Dẫn Quản Trị Module Bảo Hiểm

## Mục Lục

1. [Tổng Quan Hệ Thống](#1-tổng-quan-hệ-thống)
2. [Quản Lý Tỷ Lệ Đóng BHXH](#2-quản-lý-tỷ-lệ-đóng-bhxh)
3. [Quản Lý Quyền Hạn](#3-quản-lý-quyền-hạn)
4. [Kiểm Tra Toàn Vẹn Dữ Liệu](#4-kiểm-tra-toàn-vẹn-dữ-liệu)
5. [Giám Sát Hiệu Năng](#5-giám-sát-hiệu-năng)
6. [Backup & Recovery](#6-backup--recovery)
7. [Troubleshooting](#7-troubleshooting)
8. [Database Schema](#8-database-schema)
9. [API Reference](#9-api-reference)
10. [Best Practices](#10-best-practices)

---

## 1. Tổng Quan Hệ Thống

### 1.1 Kiến Trúc Module

```
┌─────────────────────────────────────────────────────────┐
│                    Presentation Layer                    │
│  (Vue 3 Components + Inertia.js)                        │
├─────────────────────────────────────────────────────────┤
│                    Application Layer                     │
│  Controllers | Policies | Resources | Requests          │
├─────────────────────────────────────────────────────────┤
│                      Business Layer                      │
│  Services | Events | Observers | Calculator             │
├─────────────────────────────────────────────────────────┤
│                       Data Layer                         │
│  Models | Repositories | Migrations                     │
├─────────────────────────────────────────────────────────┤
│                     Infrastructure                       │
│  Database | Cache | Queue | Storage                     │
└─────────────────────────────────────────────────────────┘
```

### 1.2 Các Thành Phần Chính

**Models** (app/Models):
- `InsuranceComponent`: Cấu hình thành phần BHXH (5 loại)
- `InsuranceParticipation`: Tham gia BHXH theo HĐ
- `InsuranceParticipationComponent`: Chi tiết thành phần của mỗi participation
- `InsuranceChangeRecord`: Bản ghi thay đổi (tăng/giảm/điều chỉnh)
- `InsuranceMonthlyContribution`: Báo cáo BHXH hàng tháng
- `InsuranceMonthlyContributionItem`: Chi tiết mức đóng từng NV

**Services** (app/Services):
- `InsuranceChangeDetectionService`: Phát hiện thay đổi BHXH
- `InsuranceContributionCalculatorService`: Tính toán mức đóng
- `InsuranceSnapshotService`: Tạo snapshot khi hoàn tất
- `InsuranceExcelExportService`: Xuất file Excel

**Commands** (app/Console/Commands):
- `CheckInsuranceIntegrity`: Kiểm tra toàn vẹn dữ liệu
- `BenchmarkInsuranceOperations`: Benchmark hiệu năng

**Policies** (app/Policies):
- `InsuranceComponentPolicy`: Phân quyền CRUD thành phần
- `InsuranceMonthlyReportPolicy`: Phân quyền báo cáo

### 1.3 Database Tables

| Table | Rows (Est.) | Storage | Purpose |
|-------|-------------|---------|---------|
| insurance_components | 5 | < 10 KB | Cấu hình 5 thành phần BHXH |
| insurance_participations | ~1,000 | ~500 KB | Tham gia BHXH theo HĐ |
| insurance_participation_components | ~4,000 | ~2 MB | Chi tiết thành phần (avg 4/participation) |
| insurance_change_records | ~5,000 | ~3 MB | Lịch sử thay đổi |
| insurance_monthly_contributions | ~120 | ~100 KB | Báo cáo hàng tháng (10 năm) |
| insurance_monthly_contribution_items | ~120,000 | ~60 MB | Chi tiết mức đóng |

**Tổng ước tính**: ~66 MB cho 1,000 employees × 10 years

### 1.4 Performance Metrics

**Target Response Time**:
- Generate report: < 5 seconds (< 1000 employees)
- Finalize report: < 10 seconds (< 1000 employees)
- Excel export: < 15 seconds (< 1000 employees)
- Query single participation: < 100ms

**Current Performance** (after optimization):
- Active participations query: 0.25ms ✅
- Participations with components: 0.30ms ✅
- Monthly reports query: 0.15ms ✅
- Recent change records: 0.21ms ✅

---

## 2. Quản Lý Tỷ Lệ Đóng BHXH

### 2.1 Cấu Hình Thành Phần BHXH

#### Truy Cập Trang Cấu Hình

1. Vào menu **Quản lý BHXH** → **Cấu hình thành phần**
2. Danh sách 5 thành phần hiển thị

#### Các Thành Phần Mặc Định

```
1. BHXH - Hưu Trí (retirement)
   - Tỷ lệ: 22% (NLĐ: 8%, NSDLĐ: 14%)
   - Trạng thái: Kích hoạt
   - Có thể tắt: Không (bắt buộc)

2. BHXH - Ốm Đau (sickness)
   - Tỷ lệ: 3% (NLĐ: 0%, NSDLĐ: 3%)
   - Trạng thái: Kích hoạt
   - Có thể tắt: Không (bắt buộc)

3. BHXH - TNLĐ-BNN (labor_accident)
   - Tỷ lệ: 1% (NLĐ: 0%, NSDLĐ: 1%)
   - Trạng thái: Kích hoạt
   - Có thể tắt: Có

4. BHTN (unemployment)
   - Tỷ lệ: 2% (NLĐ: 1%, NSDLĐ: 1%)
   - Trạng thái: Kích hoạt
   - Có thể tắt: Có
   - Đặc biệt: Hỗ trợ mức cố định

5. BHYT (health)
   - Tỷ lệ: 4.5% (NLĐ: 1.5%, NSDLĐ: 3%)
   - Trạng thái: Kích hoạt
   - Có thể tắt: Không (bắt buộc)
```

### 2.2 Chỉnh Sửa Tỷ Lệ

#### Khi Nào Cần Chỉnh Sửa?

- Nhà nước thay đổi tỷ lệ BHXH theo nghị định mới
- Điều chỉnh theo quy định công ty (nếu được phép)

#### Các Bước Chỉnh Sửa

1. Click vào thành phần cần sửa
2. Dialog hiển thị với form:

```
Tên thành phần: [BHXH - Hưu Trí] (readonly)
Mã code: [retirement] (readonly)

Tỷ lệ Người Lao Động: [8.00] %
Tỷ lệ Người Sử Dụng: [14.00] %
Tổng tỷ lệ: 22.00% (tự động tính)

Trạng thái: 
  ☑ Kích hoạt
  ☐ Tắt (chỉ hiển thị nếu không bắt buộc)

[Hủy] [Lưu]
```

3. Nhập tỷ lệ mới (kiểm tra tổng có đúng không)
4. Click **Lưu**

#### Cảnh Báo Quan Trọng

⚠️ **Thay đổi tỷ lệ chỉ ảnh hưởng đến**:
- Hợp đồng MỚI tạo sau khi thay đổi
- Báo cáo CHƯA hoàn tất

⚠️ **KHÔNG ảnh hưởng đến**:
- Hợp đồng ĐÃ tồn tại (giữ tỷ lệ cũ)
- Báo cáo ĐÃ hoàn tất (snapshot không đổi)

#### Ví Dụ Thay Đổi Tỷ Lệ

**Tình huống**: Nhà nước tăng BHXH Hưu Trí từ 22% lên 23% (NLĐ: 8% → 9%)

**Các bước**:
```bash
1. Cập nhật trong hệ thống:
   - Tỷ lệ NLĐ: 8% → 9%
   - Tỷ lệ NSDLĐ: 14% (giữ nguyên)
   - Tổng: 22% → 23%

2. Kết quả:
   - HĐ mới từ ngày 15/01: Dùng 23%
   - HĐ cũ trước 15/01: Vẫn 22%
   - Báo cáo tháng 12/2025 (đã finalized): Vẫn 22%
   - Báo cáo tháng 01/2026 (draft): Dùng 23%
```

### 2.3 Tắt/Bật Thành Phần

#### Khi Nào Tắt?

- Thành phần tạm ngưng theo quy định
- Testing/staging environment

#### Lưu Ý

❌ **Không thể tắt**: BHXH Hưu Trí, BHXH Ốm Đau, BHYT (bắt buộc)

✅ **Có thể tắt**: BHXH TNLĐ, BHTN

**Tắt thành phần**:
- Không hiển thị trong form tạo HĐ
- Không tính trong báo cáo mới
- HĐ cũ vẫn giữ nguyên

### 2.4 Lịch Sử Thay Đổi Tỷ Lệ

Hệ thống KHÔNG lưu lịch sử thay đổi tỷ lệ.

**Khuyến nghị**:
- Ghi chép thủ công khi thay đổi
- Lưu trữ nghị định/quyết định thay đổi
- Thông báo cho HR team trước khi thay đổi

**Ví dụ log thủ công**:
```
15/01/2026 - Admin: Nguyễn Văn X
- Tăng BHXH Hưu Trí: 22% → 23%
- Căn cứ: Nghị định XX/2026/NĐ-CP
- Áp dụng: Từ hợp đồng ngày 15/01/2026
```

---

## 3. Quản Lý Quyền Hạn

### 3.1 Roles & Permissions

#### Role: HR Employee

**Permissions**:
- `view_insurance_reports`: Xem báo cáo BHXH
- `view_insurance_details`: Xem chi tiết mức đóng

**Use Cases**:
- Tra cứu thông tin BHXH của nhân viên
- Hỗ trợ trả lời câu hỏi từ nhân viên
- Xem báo cáo lịch sử

**Không được phép**:
- Tạo/sửa/xóa báo cáo
- Hoàn tất báo cáo
- Xuất Excel
- Cấu hình thành phần

#### Role: Payroll Admin

**Permissions**:
- Tất cả quyền của HR Employee
- `create_insurance_reports`: Tạo báo cáo BHXH
- `update_insurance_reports`: Sửa báo cáo (draft)
- `finalize_insurance_reports`: Hoàn tất báo cáo
- `export_insurance_reports`: Xuất Excel
- `manage_insurance_components`: Quản lý thành phần

**Use Cases**:
- Tạo báo cáo BHXH hàng tháng
- Duyệt và điều chỉnh thay đổi
- Hoàn tất và xuất file nộp BHXH
- Cấu hình tỷ lệ khi có thay đổi

**Full Control**: Toàn quyền với module BHXH

### 3.2 Gán Quyền Cho User

#### Cách 1: Gán Qua Role

```bash
# Gán role Payroll Admin
php artisan tinker
>>> $user = User::find(123);
>>> $user->assignRole('payroll_admin');
```

#### Cách 2: Gán Permission Trực Tiếp

```bash
# Gán permission riêng lẻ
php artisan tinker
>>> $user = User::find(123);
>>> $user->givePermissionTo('view_insurance_reports');
>>> $user->givePermissionTo('create_insurance_reports');
```

#### Cách 3: Qua UI (Nếu Có)

1. Vào **Quản lý người dùng**
2. Chọn user cần gán
3. Tab **Roles & Permissions**
4. Chọn role hoặc permission
5. Lưu

### 3.3 Kiểm Tra Quyền

#### Kiểm Tra User Có Quyền

```bash
php artisan tinker
>>> $user = User::find(123);
>>> $user->hasPermissionTo('create_insurance_reports');
=> true

>>> $user->hasRole('payroll_admin');
=> true

>>> $user->getAllPermissions()->pluck('name');
=> [
     "view_insurance_reports",
     "create_insurance_reports",
     "update_insurance_reports",
     ...
   ]
```

#### Kiểm Tra Tất Cả Users Có Quyền

```bash
php artisan tinker
>>> Permission::findByName('create_insurance_reports')->users()->pluck('name');
=> [
     "Nguyễn Văn A",
     "Trần Thị B",
   ]
```

### 3.4 Custom Policies

#### InsuranceComponentPolicy

**File**: `app/Policies/InsuranceComponentPolicy.php`

```php
public function viewAny(User $user): bool
{
    return $user->can('view_insurance_reports');
}

public function create(User $user): bool
{
    return $user->can('manage_insurance_components');
}

public function update(User $user, InsuranceComponent $component): bool
{
    return $user->can('manage_insurance_components');
}

public function delete(User $user, InsuranceComponent $component): bool
{
    // Không cho phép xóa
    return false;
}
```

#### InsuranceMonthlyReportPolicy

**File**: `app/Policies/InsuranceMonthlyReportPolicy.php`

```php
public function finalize(User $user, InsuranceMonthlyContribution $report): bool
{
    return $user->can('finalize_insurance_reports') 
        && $report->status === 'draft';
}

public function export(User $user, InsuranceMonthlyContribution $report): bool
{
    return $user->can('export_insurance_reports') 
        && $report->status === 'finalized';
}
```

### 3.5 Testing Permissions

```bash
# Test permission setup
php artisan test --filter InsurancePermissionTest

# Test policy
php artisan test --filter InsuranceComponentPolicyTest
php artisan test --filter InsuranceMonthlyReportPolicyTest
```

---

## 4. Kiểm Tra Toàn Vẹn Dữ Liệu

### 4.1 Artisan Command: insurance:check-integrity

#### Mục Đích

Kiểm tra và sửa các lỗi dữ liệu:
- Orphaned participations (participation không còn HĐ)
- Missing components (participation thiếu thành phần)
- Invalid rate totals (tổng tỷ lệ không đúng)
- Inactive component references (tham chiếu đến component đã tắt)
- Duplicate active participations (trùng participation active)
- Missing insurance salary (thiếu lương BHXH)

#### Cú Pháp

```bash
php artisan insurance:check-integrity [options]

Options:
  --fix           Tự động sửa lỗi có thể sửa
  --detailed      Hiển thị chi tiết từng bản ghi lỗi
  --help          Hiển thị help
```

#### Ví Dụ Sử Dụng

**1. Check cơ bản (không sửa)**:
```bash
php artisan insurance:check-integrity

Output:
╔════════════════════════════════════════════════════╗
║     INSURANCE DATA INTEGRITY CHECK RESULTS         ║
╚════════════════════════════════════════════════════╝

1. Orphaned Participations: 7 issues found
2. Missing Components: 0 issues found
3. Invalid Rate Totals: 3 issues found
4. Inactive Component References: 0 issues found
5. Duplicate Active Participations: 0 issues found
6. Missing Insurance Salary: 0 issues found

Total Issues: 10
Fixable: 10
Manual Review: 0
```

**2. Check với auto-fix**:
```bash
php artisan insurance:check-integrity --fix

Output:
╔════════════════════════════════════════════════════╗
║     INSURANCE DATA INTEGRITY CHECK RESULTS         ║
╚════════════════════════════════════════════════════╝

1. Orphaned Participations: 7 issues found
   ✅ Auto-fixed: Set 7 participations to TERMINATED

2. Missing Components: 0 issues found

3. Invalid Rate Totals: 3 issues found
   ✅ Auto-fixed: Recalculated rates for 3 participations

...

Total Issues: 10
Fixed: 10
Remaining: 0
```

**3. Check với detailed output**:
```bash
php artisan insurance:check-integrity --detailed

Output:
1. Orphaned Participations: 7 issues found

   Issue #1:
   - Participation ID: abc123...
   - Employee: Nguyễn Văn A (NV001)
   - Status: ACTIVE
   - Contract: (deleted)
   - Recommendation: Set to TERMINATED

   Issue #2:
   ...
```

### 4.2 Lịch Chạy Định Kỳ

#### Cấu Hình Scheduler

**File**: `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule): void
{
    // Chạy mỗi ngày 2h sáng
    $schedule->command('insurance:check-integrity --fix')
             ->dailyAt('02:00')
             ->emailOutputOnFailure('admin@company.com');
    
    // Hoặc chạy hàng tuần
    $schedule->command('insurance:check-integrity --fix')
             ->weekly()
             ->mondays()
             ->at('03:00');
}
```

#### Kích Hoạt Scheduler

```bash
# Thêm vào crontab
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### 4.3 Giải Thích Các Lỗi

#### 1. Orphaned Participations

**Nguyên nhân**:
- Hợp đồng bị xóa nhầm
- Bug trong code xóa HĐ
- Database inconsistency

**Ảnh hưởng**:
- Participation không có HĐ gốc
- Không thể xác định nhân viên
- Báo cáo sai

**Auto-fix**:
- Set status = TERMINATED
- Ghi log lại

**Manual action**: Không cần

#### 2. Missing Components

**Nguyên nhân**:
- Lỗi khi tạo HĐ (transaction không hoàn tất)
- Xóa component nhầm

**Ảnh hưởng**:
- Participation thiếu thành phần
- Tính toán mức đóng sai
- Báo cáo không đầy đủ

**Auto-fix**: Không
**Manual action**: Cần thêm component bị thiếu vào participation

#### 3. Invalid Rate Totals

**Nguyên nhân**:
- Nhập tỷ lệ sai
- Bug tính toán
- Database corruption

**Ảnh hưởng**:
- Tổng tỷ lệ không khớp với từng thành phần
- Mức đóng sai

**Auto-fix**:
- Tính lại tổng từ các thành phần
- Cập nhật participation

**Manual action**: Không cần

#### 4. Inactive Component References

**Nguyên nhân**:
- Tắt component nhưng vẫn còn participation tham chiếu
- Quên cập nhật participation khi tắt component

**Ảnh hưởng**:
- Tham chiếu đến component đã tắt
- UI có thể lỗi

**Auto-fix**: Không
**Manual action**: Review và decide (giữ hay xóa tham chiếu)

#### 5. Duplicate Active Participations

**Nguyên nhân**:
- Tạo nhiều participation cho 1 HĐ
- Bug trong code tạo HĐ

**Ảnh hưởng**:
- 1 nhân viên có nhiều participation active
- Tính toán mức đóng trùng
- Báo cáo sai

**Auto-fix**: Không (cần quyết định giữ cái nào)
**Manual action**: Giữ 1, terminate các cái còn lại

#### 6. Missing Insurance Salary

**Nguyên nhân**:
- Không nhập lương BHXH khi tạo HĐ
- Database null

**Ảnh hưởng**:
- Không tính được mức đóng
- Báo cáo lỗi

**Auto-fix**: Không (cần biết lương đúng)
**Manual action**: Cập nhật lương BHXH cho HĐ

### 4.4 Best Practices

✅ **Chạy định kỳ**: Mỗi ngày hoặc hàng tuần

✅ **Auto-fix**: Dùng `--fix` cho các lỗi đơn giản

✅ **Review manual**: Kiểm tra log để xử lý lỗi phức tạp

✅ **Backup trước**: Luôn backup trước khi chạy `--fix`

✅ **Monitor**: Theo dõi số lỗi, nếu tăng đột ngột → có vấn đề

---

## 5. Giám Sát Hiệu Năng

### 5.1 Artisan Command: insurance:benchmark

#### Mục Đích

Đo hiệu năng các thao tác:
- Change detection (phát hiện thay đổi)
- Snapshot creation (tạo snapshot)
- Excel export (xuất Excel)
- Database queries (các truy vấn thường dùng)

#### Cú Pháp

```bash
php artisan insurance:benchmark [options]

Options:
  --employees=N    Số lượng nhân viên test (default: 100)
  --iterations=N   Số lần chạy mỗi test (default: 3)
  --export         Xuất kết quả ra file JSON
  --help           Hiển thị help
```

#### Ví Dụ Sử Dụng

**1. Benchmark cơ bản**:
```bash
php artisan insurance:benchmark

Output:
╔════════════════════════════════════════════════════════════╗
║           INSURANCE SYSTEM BENCHMARK RESULTS               ║
╚════════════════════════════════════════════════════════════╝

1. Change Detection (100 employees)
   Average: 2.83 ms [EXCELLENT]
   Min: 2.75 ms | Max: 2.91 ms | Std Dev: 0.08 ms

2. Snapshot Creation (100 employees)
   Average: 145.23 ms [EXCELLENT]
   Min: 143.12 ms | Max: 147.89 ms | Std Dev: 2.35 ms

3. Excel Export (100 employees)
   Average: 312.45 ms [GOOD]
   Min: 308.21 ms | Max: 318.76 ms | Std Dev: 5.23 ms

4. Database Query Performance:
   - Active Participations: 0.25 ms [EXCELLENT]
   - With Components: 0.30 ms [EXCELLENT]
   - Monthly Reports: 0.15 ms [EXCELLENT]
   - Recent Records: 0.21 ms [EXCELLENT]

Overall Performance: EXCELLENT ✅
System ready for production.
```

**2. Benchmark với 1000 nhân viên**:
```bash
php artisan insurance:benchmark --employees=1000

Output:
1. Change Detection (1000 employees)
   Average: 28.45 ms [EXCELLENT]

2. Snapshot Creation (1000 employees)
   Average: 1452.34 ms [GOOD]

3. Excel Export (1000 employees)
   Average: 3.12 seconds [ACCEPTABLE]
```

**3. Benchmark với nhiều lần chạy**:
```bash
php artisan insurance:benchmark --iterations=10

Output:
(Chạy mỗi test 10 lần để có kết quả chính xác hơn)
```

**4. Xuất kết quả ra file**:
```bash
php artisan insurance:benchmark --export

Output:
Benchmark results saved to: storage/benchmark/insurance_benchmark_20260112_141523.json
```

### 5.2 Ngưỡng Hiệu Năng

| Operation | Excellent | Good | Acceptable | Poor |
|-----------|-----------|------|------------|------|
| Change Detection (100 employees) | < 10ms | < 50ms | < 100ms | > 100ms |
| Snapshot Creation (100 employees) | < 500ms | < 2s | < 5s | > 5s |
| Excel Export (100 employees) | < 1s | < 3s | < 10s | > 10s |
| Database Queries | < 1ms | < 10ms | < 50ms | > 50ms |

### 5.3 Tối Ưu Hóa

#### Nếu Change Detection Chậm

**Nguyên nhân**:
- Quá nhiều hợp đồng cần quét
- Query không tối ưu

**Giải pháp**:
```bash
# Thêm index cho contracts
php artisan migrate --path=database/migrations/2026_01_12_100000_add_insurance_performance_indexes.php

# Cache contract status
Cache::remember("active_contracts_{$month}", 3600, function() {
    return Contract::active($month)->get();
});
```

#### Nếu Snapshot Creation Chậm

**Nguyên nhân**:
- Tính toán nhiều nhân viên
- Nhiều thành phần BHXH

**Giải pháp**:
```bash
# Sử dụng chunk để xử lý từng batch
$participations->chunk(100, function ($batch) {
    $this->processSnapshotBatch($batch);
});

# Hoặc sử dụng queue
dispatch(new CreateInsuranceSnapshotJob($reportId));
```

#### Nếu Excel Export Chậm

**Nguyên nhân**:
- File quá lớn
- Nhiều formatting

**Giải pháp**:
```bash
# Export to CSV thay vì Excel (nhanh hơn)
# Hoặc giảm formatting

# Sử dụng queue cho export lớn
dispatch(new ExportInsuranceReportJob($reportId));
```

#### Nếu Database Queries Chậm

**Nguyên nhân**:
- Thiếu index
- N+1 query problem

**Giải pháp**:
```bash
# Kiểm tra missing indexes
php artisan db:show
php artisan model:show InsuranceParticipation

# Thêm eager loading
InsuranceParticipation::with(['contract', 'components'])->get();

# Thêm index mới (nếu cần)
```

### 5.4 Monitoring trong Production

#### Thiết Lập New Relic / Datadog

```php
// config/monitoring.php
return [
    'insurance' => [
        'change_detection_threshold' => 100, // ms
        'snapshot_creation_threshold' => 5000, // ms
        'excel_export_threshold' => 10000, // ms
    ],
];

// App\Services\InsuranceMonitoringService.php
public function recordMetric(string $operation, float $duration): void
{
    // Send to monitoring service
    Monitoring::timing("insurance.{$operation}", $duration);
    
    // Alert if threshold exceeded
    $threshold = config("monitoring.insurance.{$operation}_threshold");
    if ($duration > $threshold) {
        Monitoring::alert("Slow insurance operation: {$operation}");
    }
}
```

#### Database Query Monitoring

```php
// App\Providers\AppServiceProvider.php
public function boot(): void
{
    DB::listen(function ($query) {
        if ($query->time > 100) { // > 100ms
            Log::warning('Slow query detected', [
                'sql' => $query->sql,
                'time' => $query->time,
                'bindings' => $query->bindings,
            ]);
        }
    });
}
```

#### Custom Logging

```php
// Trong các service methods
Log::info('Insurance report generated', [
    'report_id' => $report->id,
    'month' => $report->month,
    'employee_count' => $report->items_count,
    'duration' => $duration,
]);
```

---

## 6. Backup & Recovery

### 6.1 Backup Strategy

#### Backup Database

```bash
# Backup toàn bộ database
php artisan backup:run --only-db

# Hoặc dùng mysqldump
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Backup chỉ bảng insurance
mysqldump -u username -p database_name \
  insurance_components \
  insurance_participations \
  insurance_participation_components \
  insurance_change_records \
  insurance_monthly_contributions \
  insurance_monthly_contribution_items \
  > insurance_backup_$(date +%Y%m%d).sql
```

#### Backup Schedule

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Backup hàng ngày
    $schedule->command('backup:run --only-db')
             ->daily()
             ->at('01:00');
    
    // Backup hàng tuần (full)
    $schedule->command('backup:run')
             ->weekly()
             ->sundays()
             ->at('02:00');
}
```

#### Backup trước khi Finalize Report

```php
// App\Services\InsuranceSnapshotService.php
public function createSnapshot(InsuranceMonthlyContribution $report): void
{
    // Backup trước khi finalize
    $this->backupReport($report);
    
    // Create snapshot
    ...
}

private function backupReport(InsuranceMonthlyContribution $report): void
{
    $backup = [
        'report_id' => $report->id,
        'status' => $report->status,
        'items' => $report->items()->with('participation')->get(),
        'timestamp' => now(),
    ];
    
    Storage::put(
        "insurance/backups/report_{$report->id}_" . time() . ".json",
        json_encode($backup, JSON_PRETTY_PRINT)
    );
}
```

### 6.2 Recovery Procedures

#### Khôi Phục Database

```bash
# Khôi phục từ backup
mysql -u username -p database_name < backup_20260112.sql

# Khôi phục chỉ bảng insurance
mysql -u username -p database_name < insurance_backup_20260112.sql
```

#### Khôi Phục Participation Đã Xóa

```bash
php artisan tinker
>>> $participation = InsuranceParticipation::withTrashed()->find('abc123...');
>>> $participation->restore();
```

#### Revert Finalized Report

**Lưu ý**: CỰC KỲ NGUY HIỂM, chỉ dùng khi cần thiết

```bash
php artisan tinker
>>> $report = InsuranceMonthlyContribution::find(123);
>>> $report->status = 'draft';
>>> $report->finalized_at = null;
>>> $report->finalized_by = null;
>>> $report->save();

>>> // Xóa snapshot items
>>> $report->items()->delete();

>>> echo "Report reverted to draft. Re-create snapshot.";
```

#### Rollback Migration

```bash
# Rollback migration mới nhất
php artisan migrate:rollback

# Rollback migration cụ thể
php artisan migrate:rollback --path=database/migrations/2026_01_12_000000_create_insurance_tables.php
```

### 6.3 Data Export for Archive

```bash
# Export báo cáo đã finalized (để archive)
php artisan tinker
>>> $reports = InsuranceMonthlyContribution::where('status', 'finalized')
...     ->where('year', '<', 2020) // Báo cáo cũ hơn 5 năm
...     ->get();
>>> 
>>> foreach ($reports as $report) {
...     $data = [
...         'id' => $report->id,
...         'month' => $report->month,
...         'year' => $report->year,
...         'items' => $report->items()->with('participation.contract')->get(),
...     ];
...     Storage::put("insurance/archive/report_{$report->id}.json", json_encode($data));
... }
```

---

## 7. Troubleshooting

### 7.1 Lỗi Thường Gặp

#### Error: "Cannot create report for this month"

**Nguyên nhân**: Tháng đã có báo cáo

**Giải pháp**:
```bash
# Kiểm tra báo cáo đã tồn tại
php artisan tinker
>>> InsuranceMonthlyContribution::where('month', 1)->where('year', 2026)->first();

# Xóa nếu cần (cẩn thận!)
>>> $report->delete();
```

#### Error: "Participation not found"

**Nguyên nhân**: Participation đã bị xóa hoặc terminated

**Giải pháp**:
```bash
# Tìm participation (bao gồm soft deleted)
php artisan tinker
>>> $participation = InsuranceParticipation::withTrashed()->find('abc123...');
>>> $participation->status; // Check status
>>> $participation->deleted_at; // Check if soft deleted
```

#### Error: "Invalid total rate"

**Nguyên nhân**: Tổng tỷ lệ không khớp

**Giải pháp**:
```bash
# Chạy integrity check
php artisan insurance:check-integrity --fix

# Hoặc fix thủ công
php artisan tinker
>>> $participation = InsuranceParticipation::find('abc123...');
>>> $totalRate = $participation->components()->sum(DB::raw('employee_rate + employer_rate'));
>>> $participation->update(['total_rate' => $totalRate]);
```

#### Error: "Export failed"

**Nguyên nhân**: 
- Báo cáo chưa finalized
- File permission error
- Excel package lỗi

**Giải pháp**:
```bash
# Check report status
php artisan tinker
>>> $report = InsuranceMonthlyContribution::find(123);
>>> $report->status; // Must be 'finalized'

# Check file permissions
ls -la storage/app/insurance/exports/

# Reinstall Excel package
composer require maatwebsite/excel --update-with-dependencies
```

### 7.2 Performance Issues

#### Slow Report Generation

**Chẩn đoán**:
```bash
# Chạy benchmark
php artisan insurance:benchmark

# Check slow queries
php artisan tinker
>>> DB::enableQueryLog();
>>> // Chạy thao tác chậm
>>> DB::getQueryLog();
```

**Giải pháp**:
- Thêm indexes (xem phần 5.3)
- Sử dụng queue cho thao tác nặng
- Optimize queries (eager loading)

#### Database Lock

**Triệu chứng**: Các thao tác bị treo khi finalize report

**Giải pháp**:
```bash
# Check active transactions
mysql> SHOW PROCESSLIST;
mysql> SHOW ENGINE INNODB STATUS\G

# Kill long-running query (cẩn thận!)
mysql> KILL <process_id>;

# Trong code: Sử dụng database transactions đúng cách
DB::transaction(function () {
    // ...
}, 3); // Retry 3 lần nếu deadlock
```

### 7.3 Data Inconsistency

#### Snapshot không khớp với Change Records

**Nguyên nhân**: Thay đổi dữ liệu sau khi finalize

**Chẩn đoán**:
```bash
php artisan tinker
>>> $report = InsuranceMonthlyContribution::find(123);
>>> $snapshotTotal = $report->items()->sum('total_contribution');
>>> 
>>> // Tính lại từ change records
>>> $records = InsuranceChangeRecord::where('report_id', $report->id)->get();
>>> $calculatedTotal = $records->sum(function($r) { ... });
>>> 
>>> if ($snapshotTotal != $calculatedTotal) {
>>>     echo "Mismatch detected!";
>>> }
```

**Giải pháp**:
- Revert report về draft
- Re-create snapshot
- Hoặc chấp nhận và ghi log (nếu sai số nhỏ)

#### Participation Component không đúng

**Nguyên nhân**: Thành phần bị xóa hoặc sửa sau khi tạo

**Chẩn đoán**:
```bash
php artisan insurance:check-integrity --detailed
```

**Giải pháp**:
- Khôi phục component bị thiếu
- Hoặc xóa component reference nếu không cần

### 7.4 UI Issues

#### Không hiển thị tab "Tổng hợp đóng BHXH"

**Nguyên nhân**: Báo cáo chưa finalized

**Giải pháp**: Hoàn tất báo cáo trước

#### Dropdown tháng kê khai không hoạt động

**Nguyên nhân**: 
- JavaScript error
- Permission không đủ

**Giải pháp**:
```bash
# Kiểm tra console log (F12)
# Kiểm tra permission
>>> $user->can('update_insurance_reports');

# Rebuild frontend
npm run build
```

---

## 8. Database Schema

### 8.1 ER Diagram

```
┌────────────────────────┐
│ insurance_components   │
│────────────────────────│
│ id (UUID, PK)          │
│ name                   │
│ code (unique)          │
│ employee_rate (%)      │
│ employer_rate (%)      │
│ is_enabled (bool)      │
│ is_required (bool)     │
│ can_be_fixed_amount    │
└────────────────────────┘
             │
             │ 1:N
             ▼
┌──────────────────────────────┐         ┌─────────────────────────┐
│insurance_participation_      │   N:1   │ insurance_participations│
│components                    │────────▶│─────────────────────────│
│──────────────────────────────│         │ id (UUID, PK)           │
│ id (UUID, PK)                │         │ contract_id (FK)        │
│ participation_id (FK)        │         │ insurance_salary (dec)  │
│ component_id (FK)            │         │ status (enum)           │
│ employee_rate (snapshot)     │         │ started_at              │
│ employer_rate (snapshot)     │         │ terminated_at           │
│ is_fixed_amount (bool)       │         │ total_rate              │
│ fixed_amount (decimal)       │         └─────────────────────────┘
└──────────────────────────────┘                   │
                                                   │ 1:N
                                                   ▼
                                  ┌──────────────────────────────┐
                                  │ insurance_change_records     │
                                  │──────────────────────────────│
                                  │ id (UUID, PK)                │
                                  │ report_id (FK)               │
                                  │ participation_id (FK)        │
                                  │ change_type (enum)           │
                                  │ declaration_month (YYYY-MM)  │
                                  │ suggested_month (YYYY-MM)    │
                                  │ override_reason (text)       │
                                  └──────────────────────────────┘
                                                   │
                                                   │ N:1
                                                   ▼
┌──────────────────────────────┐         ┌─────────────────────────────┐
│insurance_monthly_            │   1:N   │ insurance_monthly_          │
│contribution_items            │◀────────│ contributions               │
│──────────────────────────────│         │─────────────────────────────│
│ id (UUID, PK)                │         │ id (bigint, PK)             │
│ report_id (FK)               │         │ month (int)                 │
│ participation_id (FK)        │         │ year (int)                  │
│ insurance_salary (snapshot)  │         │ status (enum)               │
│ components (JSON)            │         │ finalized_at                │
│ total_contribution (decimal) │         │ finalized_by (FK)           │
└──────────────────────────────┘         └─────────────────────────────┘
```

### 8.2 Table Details

#### insurance_components

```sql
CREATE TABLE insurance_components (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    employee_rate DECIMAL(5,2) NOT NULL,
    employer_rate DECIMAL(5,2) NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    is_required BOOLEAN DEFAULT FALSE,
    can_be_fixed_amount BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Indexes
CREATE INDEX idx_components_enabled ON insurance_components(is_enabled);
CREATE INDEX idx_components_code ON insurance_components(code);
```

**Dữ liệu mẫu**:
```sql
INSERT INTO insurance_components VALUES
('uuid1', 'BHXH - Hưu trí', 'retirement', 8.00, 14.00, 1, 1, 0, 1, NOW(), NOW()),
('uuid2', 'BHXH - Ốm đau', 'sickness', 0.00, 3.00, 1, 1, 0, 2, NOW(), NOW()),
('uuid3', 'BHXH - TNLĐ-BNN', 'labor_accident', 0.00, 1.00, 1, 0, 0, 3, NOW(), NOW()),
('uuid4', 'BHTN', 'unemployment', 1.00, 1.00, 1, 0, 1, 4, NOW(), NOW()),
('uuid5', 'BHYT', 'health', 1.50, 3.00, 1, 1, 0, 5, NOW(), NOW());
```

#### insurance_participations

```sql
CREATE TABLE insurance_participations (
    id CHAR(36) PRIMARY KEY,
    contract_id CHAR(36) NOT NULL,
    insurance_salary DECIMAL(15,2) NOT NULL,
    status ENUM('ACTIVE', 'TERMINATED') DEFAULT 'ACTIVE',
    started_at DATE NOT NULL,
    terminated_at DATE,
    total_rate DECIMAL(5,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
);

-- Indexes (20 total, see migration file)
CREATE INDEX idx_participations_contract ON insurance_participations(contract_id);
CREATE INDEX idx_participations_status ON insurance_participations(status);
CREATE INDEX idx_participations_employee_status ON insurance_participations(status, started_at);
...
```

### 8.3 Important Constraints

**Foreign Keys**:
- `insurance_participations.contract_id` → `contracts.id` (CASCADE)
- `insurance_participation_components.participation_id` → `insurance_participations.id` (CASCADE)
- `insurance_change_records.participation_id` → `insurance_participations.id` (CASCADE)
- `insurance_monthly_contribution_items.report_id` → `insurance_monthly_contributions.id` (CASCADE)

**Unique Constraints**:
- `insurance_components.code` (UNIQUE)
- `insurance_monthly_contributions(month, year)` (UNIQUE)

**Check Constraints** (Laravel validation):
- `employee_rate >= 0`
- `employer_rate >= 0`
- `insurance_salary > 0`
- `total_rate >= 0`

---

## 9. API Reference

Xem file riêng: [docs/INSURANCE_API_DOCUMENTATION.md](./INSURANCE_API_DOCUMENTATION.md)

**Highlights**:
- 9 endpoints chính
- RESTful design
- JSON request/response
- Policy-based authorization
- Rate limiting: 60 requests/minute

---

## 10. Best Practices

### 10.1 Development

✅ **Luôn sử dụng transactions** cho thao tác multi-step:
```php
DB::transaction(function () {
    $participation = InsuranceParticipation::create(...);
    $participation->components()->createMany(...);
});
```

✅ **Eager loading** để tránh N+1:
```php
InsuranceParticipation::with(['contract', 'components', 'changeRecords'])->get();
```

✅ **Validate input** ở cả frontend và backend

✅ **Use queues** cho thao tác nặng (export Excel, finalize report lớn)

✅ **Log tất cả critical actions**:
```php
Log::info('Report finalized', [
    'report_id' => $report->id,
    'user_id' => auth()->id(),
    'items_count' => $report->items()->count(),
]);
```

### 10.2 Deployment

✅ **Chạy migrations** trước khi deploy code:
```bash
php artisan migrate --force
```

✅ **Clear cache** sau mỗi deployment:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

✅ **Rebuild frontend assets**:
```bash
npm run build
```

✅ **Chạy integrity check** sau deploy:
```bash
php artisan insurance:check-integrity --fix
```

✅ **Monitor logs** trong 24h đầu sau deploy

### 10.3 Maintenance

✅ **Backup trước mỗi thao tác nguy hiểm**

✅ **Chạy integrity check định kỳ** (daily/weekly)

✅ **Review performance metrics** hàng tháng

✅ **Archive dữ liệu cũ** (> 5 năm) để giảm database size

✅ **Update documentation** khi có thay đổi

### 10.4 Security

✅ **Kiểm tra quyền hạn** trong mọi controller method:
```php
$this->authorize('update', $report);
```

✅ **Validate tất cả input** để tránh injection:
```php
$request->validate([
    'month' => 'required|integer|between:1,12',
    'year' => 'required|integer|min:2020',
]);
```

✅ **Không expose sensitive data** trong API response

✅ **Use rate limiting** cho API endpoints:
```php
Route::middleware(['throttle:insurance'])->group(function () {
    // ...
});
```

✅ **Log security events** (unauthorized access, failed validation...)

---

## Phụ Lục

### A. Command Cheat Sheet

```bash
# Integrity check
php artisan insurance:check-integrity [--fix] [--detailed]

# Benchmark
php artisan insurance:benchmark [--employees=N] [--iterations=N] [--export]

# Migrations
php artisan migrate --path=database/migrations/2026_01_12_000000_create_insurance_tables.php
php artisan migrate:rollback --step=1

# Cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# Testing
php artisan test --filter Insurance
```

### B. Useful Queries

```sql
-- Tổng số participation active
SELECT COUNT(*) FROM insurance_participations WHERE status = 'ACTIVE' AND deleted_at IS NULL;

-- Báo cáo chưa finalized
SELECT * FROM insurance_monthly_contributions WHERE status = 'draft';

-- Top 10 NV có lương BHXH cao nhất
SELECT c.employee_id, ip.insurance_salary 
FROM insurance_participations ip
JOIN contracts c ON ip.contract_id = c.id
WHERE ip.status = 'ACTIVE'
ORDER BY ip.insurance_salary DESC
LIMIT 10;

-- Số lượng thay đổi theo tháng
SELECT 
    month, 
    year,
    SUM(CASE WHEN change_type = 'INCREASE' THEN 1 ELSE 0 END) as increases,
    SUM(CASE WHEN change_type = 'DECREASE' THEN 1 ELSE 0 END) as decreases,
    SUM(CASE WHEN change_type = 'ADJUSTMENT' THEN 1 ELSE 0 END) as adjustments
FROM insurance_change_records icr
JOIN insurance_monthly_contributions imc ON icr.report_id = imc.id
GROUP BY month, year
ORDER BY year DESC, month DESC;
```

### C. Support Contacts

- **Technical Issues**: devteam@company.com
- **Business Logic**: hr@company.com  
- **Emergency**: +84 xxx xxx xxx (On-call)

---

**Document Version**: 1.0  
**Last Updated**: January 12, 2026  
**Author**: Development Team
