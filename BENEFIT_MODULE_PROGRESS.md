# Module Phúc Lợi - Implementation Progress

## ✅ Đã hoàn thành (Backend foundation)

### 1. Migrations
- ✅ `2026_01_02_100001_create_benefit_types_table.php`
- ✅ `2026_01_02_100002_create_employee_benefit_payouts_table.php`

### 2. Models
- ✅ `BenefitType.php` - với relationships, scopes, activity log
- ✅ `EmployeeBenefitPayout.php` - với relationships, scopes, polymorphic attachments

### 3. Seeder  
- ✅ `BenefitTypeSeeder.php` - 8 loại phúc lợi chuẩn

### 4. Form Requests
- ✅ `StoreBenefitTypeRequest.php`
- ✅ `UpdateBenefitTypeRequest.php`
- ✅ `StoreEmployeeBenefitPayoutRequest.php`
- ✅ `UpdateEmployeeBenefitPayoutRequest.php`

### 5. Resources
- ✅ `BenefitTypeResource.php`
- ✅ `EmployeeBenefitPayoutResource.php`

## 🔄 Cần tiếp tục

### 6. Controllers (Cần tạo)

**BenefitTypeController.php**:
```php
- index(): Render danh sách benefit types
- store(): Tạo mới
- update(): Cập nhật
- destroy(): Xóa
- bulkDelete(): Xóa nhiều
```

**EmployeeBenefitPayoutController.php**:
```php
- index(): List payouts với filters (year, month, employee_id, benefit_type_id)
- store(): Tạo mới (auto set paid_by = auth()->id())
- update(): Cập nhật
- destroy(): Xóa
- bulkDelete(): Xóa nhiều
```

### 7. Routes (Thêm vào web.php)
```php
// Benefit Types
Route::delete('benefit-types/bulk-delete', [BenefitTypeController::class, 'bulkDelete']);
Route::resource('benefit-types', BenefitTypeController::class)->except(['show']);

// Benefit Payouts
Route::delete('employee-benefit-payouts/bulk-delete', [EmployeeBenefitPayoutController::class, 'bulkDelete']);
Route::resource('employee-benefit-payouts', EmployeeBenefitPayoutController::class)->except(['show']);
```

### 8. Frontend Services
- `BenefitTypeService.js`
- `EmployeeBenefitPayoutService.js`
- Update `services/index.js`

### 9. Vue Components
- `resources/js/Pages/BenefitType/Index.vue`
- `resources/js/Pages/EmployeeBenefitPayout/Index.vue`
- `resources/js/Pages/Employees/Components/BenefitPayoutTab.vue`

### 10. Integration
- Update `AppMenu.vue`: Thêm menu "Phúc lợi" với 2 submenu
- Update `EmployeeProfile.vue`: Thêm tab "Phúc lợi"
- Update `ProfileSubSidebar.vue`: Thêm "Phúc lợi" vào sidebar
- Update `EmployeeController::profile()`: Load benefit payouts
- Update `Employee.php`: Thêm relationship `benefitPayouts()`

### 11. Database
- Chạy seeder: `php artisan db:seed --class=BenefitTypeSeeder`
- Hoặc: `php artisan migrate:fresh --seed`

## 📋 Next Steps
Hãy cho tôi biết bạn muốn:
1. Tiếp tục tạo Controllers (step 6)?
2. Hay tôi tạo luôn full stack còn lại (Controllers → Routes → FE)?
