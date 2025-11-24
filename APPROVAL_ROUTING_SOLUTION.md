# 🎯 Contract Approval Routing - Phân Tích & Giải Pháp

## 📊 Phân tích Cấu trúc Hiện tại

### Database Schema
```
users (Tài khoản)
├── id
├── name
├── email
└── roles (via Spatie) → roles table

employees (Nhân viên)
├── id
├── user_id (FK → users.id)
├── employee_code
└── full_name

departments (Đơn vị)
├── id
├── name
├── head_assignment_id (FK → employee_assignments.id) ← Trưởng đơn vị
└── deputy_assignment_id (FK → employee_assignments.id) ← Phó

employee_assignments (Phân công)
├── id
├── employee_id (FK → employees.id)
├── department_id (FK → departments.id)
├── position_id
├── role_type (HEAD, DEPUTY, MEMBER)
├── is_primary (boolean)
└── is_active (boolean)

role_scopes (Phạm vi quyền)
├── id
├── role_id (FK → roles.id) ← Spatie role
├── employee_id (FK → employees.id) ← nullable
└── department_id (FK → departments.id) ← nullable

contracts (Hợp đồng)
├── id
├── employee_id (FK → employees.id)
├── department_id (FK → departments.id)
├── status (DRAFT, PENDING_APPROVAL, ACTIVE, ...)
└── ...

contract_approvals (Lịch sử phê duyệt)
├── id
├── contract_id (FK → contracts.id)
├── level (DIRECTOR)
├── order (1)
├── approver_id (FK → users.id) ← nullable
├── status (PENDING, APPROVED, REJECTED)
├── comments
└── approved_at
```

### ⚠️ Vấn đề Hiện tại

**Khi submit contract cho phê duyệt:**
```php
// ContractApprovalService.php line 269
protected function findDirectorForContract(Contract $contract): ?User
{
    return User::role('Director')->first(); // ❌ LẤY DIRECTOR ĐẦU TIÊN
}
```

**Vấn đề:**
1. ❌ Lấy Director đầu tiên trong hệ thống (không liên quan gì đến Department)
2. ❌ Nếu có 10 Directors khác nhau cho 10 departments → chỉ 1 Director được gán
3. ❌ Không có logic routing dựa trên organization structure
4. ❌ Không scale khi công ty mở rộng

---

## 🎯 Các Giải Pháp Chuyên Nghiệp

### **Giải pháp 1: Department-based Director (Recommended ⭐⭐⭐⭐⭐)**

**Ý tưởng:** Mỗi Department có 1 Director phụ trách

#### Cách thực hiện:

**Option 1A: Sử dụng `departments.head_assignment_id` có sẵn**
```sql
-- Bổ sung cột director_assignment_id
ALTER TABLE departments
ADD COLUMN director_assignment_id UUID NULLABLE;
ADD FOREIGN KEY (director_assignment_id)
    REFERENCES employee_assignments(id);
```

```php
// Department Model
public function directorAssignment()
{
    return $this->belongsTo(EmployeeAssignment::class, 'director_assignment_id');
}

public function director()
{
    return $this->hasOneThrough(
        Employee::class,
        EmployeeAssignment::class,
        'id', // key on employee_assignments
        'id', // key on employees
        'director_assignment_id', // local key on departments
        'employee_id' // foreign key on employee_assignments
    );
}

public function directorUser()
{
    // Director's User account
    return $this->director->user ?? null;
}
```

```php
// ContractApprovalService.php
protected function findDirectorForContract(Contract $contract): ?User
{
    // Lấy Director của department (qua employee_assignments)
    $department = $contract->department;

    if (!$department || !$department->director_assignment_id) {
        // Fallback: Tìm HEAD của department hiện tại
        $headAssignment = EmployeeAssignment::where('department_id', $department->id)
            ->where('role_type', 'HEAD')
            ->where('is_active', true)
            ->first();

        $employee = $headAssignment?->employee;
    } else {
        $employee = $department->directorAssignment?->employee;
    }

    return $employee?->user;
}
```

**Ưu điểm:**
- ✅ Tận dụng cấu trúc `employee_assignments` có sẵn
- ✅ Mỗi department có Director riêng
- ✅ Dễ quản lý qua UI (assign Director cho department)
- ✅ Flexible: có thể assign Director ở cấp phòng, ban, công ty

**Nhược điểm:**
- ⚠️ Cần thêm 1 cột `director_assignment_id` vào `departments`
- ⚠️ Cần UI để assign Director cho department

---

**Option 1B: Sử dụng `role_scopes` có sẵn**
```php
// ContractApprovalService.php
protected function findDirectorForContract(Contract $contract): ?User
{
    $department = $contract->department;

    // Tìm Director được assign cho department này (via role_scopes)
    $directorRole = Role::where('name', 'Director')->first();

    $roleScope = RoleScope::where('role_id', $directorRole->id)
        ->where('department_id', $department->id)
        ->first();

    if ($roleScope && $roleScope->employee_id) {
        $employee = Employee::find($roleScope->employee_id);
        return $employee?->user;
    }

    // Fallback: Tìm HEAD của department
    $headAssignment = EmployeeAssignment::where('department_id', $department->id)
        ->where('role_type', 'HEAD')
        ->where('is_active', true)
        ->first();

    return $headAssignment?->employee?->user;
}
```

**Ưu điểm:**
- ✅ Tận dụng `role_scopes` có sẵn (không cần migration)
- ✅ Flexible: 1 department có thể có nhiều Directors
- ✅ Đã có seeder ví dụ cho logic này

**Nhược điểm:**
- ⚠️ Cần seed data cho `role_scopes`
- ⚠️ Phức tạp hơn Option 1A

---

### **Giải pháp 2: Hierarchical Department Routing (Enterprise ⭐⭐⭐⭐)**

**Ý tưởng:** Approval flow theo cây phòng ban (Department Tree)

```
Công ty (CEO)
├── VP Kinh doanh
│   ├── Phòng Sales
│   └── Phòng Marketing
└── VP Vận hành
    ├── Phòng IT
    └── Phòng Hành chính
```

**Logic:**
1. Contract của "Phòng IT" → Duyệt bởi "VP Vận hành"
2. Contract của "VP Vận hành" → Duyệt bởi "CEO"

```php
protected function findDirectorForContract(Contract $contract): ?User
{
    $department = $contract->department;

    // Tìm parent department có HEAD với role Director
    $parentDept = $department->parent; // Department::parent() relationship

    while ($parentDept) {
        $headAssignment = EmployeeAssignment::where('department_id', $parentDept->id)
            ->where('role_type', 'HEAD')
            ->where('is_active', true)
            ->first();

        if ($headAssignment) {
            $user = $headAssignment->employee?->user;

            // Check nếu user có role Director
            if ($user && $user->hasRole('Director')) {
                return $user;
            }
        }

        // Lên cấp cao hơn
        $parentDept = $parentDept->parent;
    }

    // Fallback: Tìm bất kỳ Director nào
    return User::role('Director')->first();
}
```

**Ưu điểm:**
- ✅ Scale tốt cho tổ chức lớn
- ✅ Tự động routing theo hierarchy
- ✅ Phù hợp mô hình tập đoàn

**Nhược điểm:**
- ⚠️ Phức tạp
- ⚠️ Yêu cầu cây phòng ban được thiết kế tốt
- ⚠️ Performance issue nếu tree sâu

---

### **Giải pháp 3: Rule-based Routing (Flexible ⭐⭐⭐)**

**Ý tưởng:** Tạo bảng config routing rules

```sql
CREATE TABLE approval_routing_rules (
    id UUID PRIMARY KEY,
    contract_type VARCHAR, -- FIXED_TERM, INDEFINITE, ...
    department_id UUID, -- nullable (áp dụng cho department cụ thể)
    approval_level VARCHAR, -- DIRECTOR
    approver_user_id UUID, -- FK → users.id
    priority INT, -- Ưu tiên rule nào
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

```php
protected function findDirectorForContract(Contract $contract): ?User
{
    // Tìm rule phù hợp nhất
    $rule = ApprovalRoutingRule::where('is_active', true)
        ->where(function($q) use ($contract) {
            $q->where('contract_type', $contract->contract_type)
              ->orWhereNull('contract_type');
        })
        ->where(function($q) use ($contract) {
            $q->where('department_id', $contract->department_id)
              ->orWhereNull('department_id');
        })
        ->where('approval_level', 'DIRECTOR')
        ->orderBy('priority', 'desc')
        ->first();

    return $rule?->approverUser;
}
```

**Ưu điểm:**
- ✅ Cực kỳ flexible
- ✅ Có thể config qua UI admin
- ✅ Hỗ trợ nhiều tiêu chí (contract_type, department, position, salary range, ...)

**Nhược điểm:**
- ⚠️ Cần thêm bảng mới
- ⚠️ Cần UI để quản lý rules
- ⚠️ Phức tạp cho small company

---

### **Giải pháp 4: Simple Fixed Director (Quick ⭐⭐)**

**Ý tưởng:** Chỉ có 1 Director cho toàn công ty

```php
// config/approval.php
return [
    'contract_director_user_id' => env('CONTRACT_DIRECTOR_USER_ID', null),
    'contract_director_email' => env('CONTRACT_DIRECTOR_EMAIL', 'director@company.com'),
];
```

```php
protected function findDirectorForContract(Contract $contract): ?User
{
    $userId = config('approval.contract_director_user_id');

    if ($userId) {
        return User::find($userId);
    }

    $email = config('approval.contract_director_email');
    return User::where('email', $email)->first();
}
```

**Ưu điểm:**
- ✅ Cực kỳ đơn giản
- ✅ Không cần migration
- ✅ Phù hợp startup/small company

**Nhược điểm:**
- ❌ Không scale
- ❌ Hardcode
- ❌ Không flexible

---

## 🏆 Đề xuất của tôi

### **Recommended: Option 1B + Fallback**

**Lý do:**
1. ✅ **Tận dụng infrastructure có sẵn** (`role_scopes`, `employee_assignments`)
2. ✅ **Không cần migration** (role_scopes đã tồn tại)
3. ✅ **Professional** (department-based routing)
4. ✅ **Có fallback** (tìm HEAD nếu không có Director)
5. ✅ **Scale tốt** (có thể mở rộng thành hierarchical sau)

### Implementation Plan

#### Step 1: Update RoleScope Model
```php
// app/Models/RoleScope.php
class RoleScope extends Model
{
    use HasUuids;

    protected $fillable = ['role_id','employee_id','department_id'];

    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function employee() {
        return $this->belongsTo(Employee::class);
    }

    public function department() {
        return $this->belongsTo(Department::class);
    }

    // Helper: Tìm user có role trong department
    public static function findUserWithRoleInDepartment(string $roleName, string $departmentId): ?User
    {
        $role = Role::where('name', $roleName)->first();
        if (!$role) return null;

        $roleScope = self::where('role_id', $role->id)
            ->where('department_id', $departmentId)
            ->first();

        return $roleScope?->employee?->user;
    }
}
```

#### Step 2: Update ContractApprovalService
```php
protected function findDirectorForContract(Contract $contract): ?User
{
    $department = $contract->department;

    if (!$department) {
        // Fallback 1: Tìm Director bất kỳ
        return User::role('Director')->first();
    }

    // Tìm Director được assign cho department này (via role_scopes)
    $director = RoleScope::findUserWithRoleInDepartment('Director', $department->id);

    if ($director) {
        return $director;
    }

    // Fallback 2: Tìm HEAD của department
    $headAssignment = EmployeeAssignment::where('department_id', $department->id)
        ->where('role_type', 'HEAD')
        ->where('is_active', true)
        ->first();

    if ($headAssignment) {
        return $headAssignment->employee?->user;
    }

    // Fallback 3: Tìm Director ở parent department (nếu có)
    if ($department->parent_id) {
        $parentDept = Department::find($department->parent_id);
        if ($parentDept) {
            $parentDirector = RoleScope::findUserWithRoleInDepartment('Director', $parentDept->id);
            if ($parentDirector) {
                return $parentDirector;
            }
        }
    }

    // Fallback 4: Tìm Director bất kỳ
    return User::role('Director')->first();
}
```

#### Step 3: Seed Directors cho các Departments
```php
// database/seeders/ApprovalDirectorSeeder.php
class ApprovalDirectorSeeder extends Seeder
{
    public function run(): void
    {
        $directorRole = Role::where('name', 'Director')->first();

        // Assign Directors cho các departments
        $assignments = [
            'Phòng Hành chính Nhân sự' => 'director@example.com',
            'Phòng Kiểm Soát Nội Bộ' => 'director@example.com',
            'Phòng Kế toán' => 'director@example.com',
            // ... thêm các departments khác
        ];

        foreach ($assignments as $deptName => $directorEmail) {
            $dept = Department::where('name', $deptName)->first();
            $user = User::where('email', $directorEmail)->first();
            $employee = $user?->employee;

            if ($directorRole && $dept && $employee) {
                RoleScope::updateOrCreate(
                    [
                        'role_id' => $directorRole->id,
                        'department_id' => $dept->id,
                    ],
                    [
                        'employee_id' => $employee->id,
                    ]
                );
            }
        }
    }
}
```

#### Step 4: Activity Log Enhancement
```php
// Trong submitForApproval()
activity('contract')
    ->performedOn($contract)
    ->causedBy(auth()->user())
    ->withProperties([
        'contract_number' => $contract->contract_number,
        'action' => 'submitted_for_approval',
        'director_email' => $director?->email,
        'director_name' => $director?->name,
        'routing_method' => $routingMethod, // 'role_scope', 'head_assignment', 'fallback'
    ])
    ->log('Gửi phê duyệt');
```

---

## 📝 Testing Scenarios

### Scenario 1: Director được assign qua role_scopes
```
Contract của Phòng IT
→ role_scopes có: role_id=Director, department_id=IT, employee_id=123
→ Lấy User của Employee 123
→ ✅ Thành công
```

### Scenario 2: Director không được assign, fallback HEAD
```
Contract của Phòng Marketing
→ role_scopes không có Director cho Marketing
→ Tìm HEAD của Marketing (via employee_assignments)
→ ✅ Lấy User của HEAD
```

### Scenario 3: Parent department routing
```
Contract của "Phòng IT" (child)
→ Không có Director cho "Phòng IT"
→ Tìm Director của "Ban Vận hành" (parent)
→ ✅ Lấy Director của parent
```

### Scenario 4: Global fallback
```
Contract không có department
→ Lấy Director bất kỳ trong hệ thống
→ ✅ Fallback thành công
```

---

## 🚀 Roadmap

### Phase 1: Quick Fix (Today) ⚡
- [ ] Implement Option 1B (role_scopes)
- [ ] Add fallback logic (HEAD → Parent → Global)
- [ ] Seed Directors cho main departments

### Phase 2: UI Admin (Next Week) 🎨
- [ ] CRUD cho role_scopes
- [ ] Assign Director cho Department (UI)
- [ ] Validation: 1 department chỉ 1 Director

### Phase 3: Advanced Routing (Future) 🔮
- [ ] Multi-level approval (optional Manager step)
- [ ] Conditional approval (by salary range)
- [ ] Delegation (ủy quyền approve)
- [ ] Auto-escalation (nếu không duyệt sau X ngày)

---

## 📊 Comparison Matrix

| Giải pháp | Complexity | Scalability | Flexibility | Migration | Recommended |
|-----------|-----------|-------------|-------------|-----------|-------------|
| **1A. Department Director** | ⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | Yes | ⭐⭐⭐⭐ |
| **1B. RoleScope (Choice)** | ⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | No | ⭐⭐⭐⭐⭐ |
| **2. Hierarchical** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | No | ⭐⭐⭐⭐ |
| **3. Rule-based** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | Yes | ⭐⭐⭐ |
| **4. Fixed Director** | ⭐ | ⭐ | ⭐ | No | ⭐⭐ |

---

## ✅ Checklist Implementation

- [ ] Quyết định giải pháp (recommend: 1B)
- [ ] Update `RoleScope` model với helper methods
- [ ] Update `ContractApprovalService.findDirectorForContract()`
- [ ] Tạo `ApprovalDirectorSeeder` để seed data
- [ ] Run seeder: `php artisan db:seed --class=ApprovalDirectorSeeder`
- [ ] Test với 3 departments khác nhau
- [ ] Verify activity log có đủ thông tin routing
- [ ] Update documentation

---

**Tôi recommend bắt đầu với Option 1B vì:**
1. ✅ Professional nhất trong các giải pháp đơn giản
2. ✅ Không cần migration (dùng lại role_scopes)
3. ✅ Có fallback chain an toàn
4. ✅ Dễ test và verify
5. ✅ Có thể mở rộng sau (thêm rules, hierarchical)

Bạn muốn tôi implement Option 1B ngay không? 🚀
