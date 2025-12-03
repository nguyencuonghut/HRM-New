 Phân tích hiện trạng Employee
✅ Đã có sẵn (Foundation tốt)
1. Employee Core Model

Table employees: đầy đủ thông tin cá nhân (CCCD, địa chỉ, BHXH, ngày vào...)
Status: ACTIVE/INACTIVE/ON_LEAVE/TERMINATED
Relationships: user, ward, tempWard, educations, relatives, experiences, skills
2. EmployeeAssignment

✅ Unique constraint rất chuẩn: uq_emp_one_active_primary đảm bảo 1 nhân viên chỉ có 1 assignment PRIMARY + ACTIVE
Fields: employee_id, department_id, position_id, is_primary, role_type (HEAD/DEPUTY/MEMBER), start_date, end_date, status
CRUD đầy đủ: Controller, Policy, Request, Resource, Service
UI: EmployeeAssignmentIndex.vue (trang độc lập)
3. Profile Module - 4 tabs đã hoàn thiện

✅ Education: education_levels + schools (chuẩn hóa), CRUD đầy đủ
✅ Relatives: CRUD đầy đủ với nested routes employees/{employee}/relatives
✅ Experiences: CRUD đầy đủ, có is_current flag
✅ Skills:
Table skills: master list (code, name, unique name)
Table employee_skills: mapping với level (0-5), years, note
CRUD đầy đủ, unique constraint (1 skill/1 employee)
4. Activity Log

Đã implement đầy đủ cho tất cả module: employee, assignment, education, relative, experience, skill
Format chuẩn: log theo module + employee_id
⚠️ Cần hoàn thiện
1. EmployeeAssignment chưa có tab trong Profile

Hiện tại chỉ có trang độc lập EmployeeAssignmentIndex
Cần thêm tab "Phân công" trong EmployeeProfile để HR xem/cập nhật nhanh
Cần highlight rõ PRIMARY assignment
2. Skills chưa có Categories

Hiện tại skills chỉ có code và name
Nên thêm skill_categories để phân nhóm (VD: Tin học, Ngoại ngữ, Chuyên môn, Quản lý...)
3. Profile Completion Tracking

Chưa có checklist "Thiếu gì" trong Profile
Chưa có % hoàn thiện hồ sơ
🎯 Kế hoạch triển khai (5 bước ưu tiên)
Tôi đã tạo todo list với 5 bước sau:

Bước 1: Hoàn thiện EmployeeAssignment CRUD + Logic ⭐⭐⭐⭐⭐
Mục tiêu: Chốt "xương sống nhân sự" - mọi thứ liên quan đơn vị/chức danh/line manager phải đúng

Chi tiết thực hiện:

Tạo EmployeeAssignmentTab.vue component trong EmployeeProfile (tab thứ 5)
UI hiển thị tất cả assignments của nhân viên, highlight PRIMARY bằng Badge
Form CRUD inline (giống RelativeTab, ExperienceTab)
Validation đặc biệt:
Frontend: Khi check "Phân công CHÍNH", disable các assignment khác nếu đã có PRIMARY ACTIVE
Backend đã có unique constraint uq_emp_one_active_primary → catch QueryException
Validate start_date <= end_date
Status: ACTIVE/INACTIVE
Business rules:
Một nhân viên CÓ THỂ có nhiều assignments (kiêm nhiệm), nhưng CHỈ 1 PRIMARY
PRIMARY assignment quyết định department/position mặc định cho Contract/Leave/Payroll
Output: Hệ thống "biết nhân viên thuộc đâu" → nền tảng cho Contract/Leave/Payroll

Bước 2: Hoàn thiện các tab Profile còn lại
Trạng thái: ✅ ĐÃ XONG 95% - chỉ cần verify

Đã có đầy đủ:

Routes nested chuẩn: employees/{employee}/educations|relatives|experiences|skills
Controller/Request/Policy/Resource/Service đầy đủ cho cả 4 module
EmployeeProfile.vue với 4 tabs hoạt động tốt
Activity log đầy đủ
Cần check thêm:

Link từ EmployeeIndex → EmployeeProfile?tab=education (set active tab)
Đảm bảo bulk-delete hoạt động cho cả 4 module
Bước 3: Chuẩn hóa danh mục Skills
Vấn đề: Skills hiện tại không có phân nhóm, khó quản lý khi số lượng tăng

Giải pháp:

Categories gợi ý:

Tin học văn phòng (MS Office, Google Suite)
Lập trình (Java, Python, PHP, JavaScript...)
Ngoại ngữ (English, Japanese, Korean...)
Chuyên môn nghề nghiệp (Kế toán, Marketing, HR...)
Kỹ năng mềm (Lãnh đạo, Quản lý dự án, Giao tiếp...)
UI cải tiến:

SkillIndex.vue: thêm filter theo category
EmployeeProfile Skills tab: group skills theo category khi hiển thị
Bước 4: Tạo Profile Completion & Audit View
Mục đích: Giúp HR vận hành, kiểm soát chất lượng hồ sơ

Tính năng:

4.1. Profile Completion Score

4.2. Checklist "Thiếu gì"

Component ProfileChecklist.vue trong EmployeeProfile
Hiển thị progress bar + danh sách "Chưa hoàn thành"
Badge đỏ cạnh tab nếu module đó thiếu dữ liệu
4.3. Activity Timeline

Tab "Lịch sử" trong EmployeeProfile
Hiển thị tất cả activity theo employee_id, group theo module
Filter theo module: All / Assignment / Education / Relative / Experience / Skill
4.4. EmployeeIndex enhancement

Thêm cột "% Hoàn thiện" với progress bar
Filter "Hồ sơ chưa đầy đủ" (score < 80%)
Bước 5: Mở sang nghiệp vụ lớn - Contract/Leave/Payroll
Điều kiện tiên quyết: Bước 1 (EmployeeAssignment) phải xong

5.1. Contract Module (đã có sẵn)

Khi tạo Contract, lấy snapshot từ PRIMARY assignment:
Trong ContractDetail, hiển thị "Phòng ban tại thời điểm ký"
5.2. Leave Module (chưa có)

Approval routing dựa trên assignment:
Line manager = HEAD của department (từ assignment)
Director = Director assignment (đã có trong APPROVAL_ROUTING_SOLUTION.md)
Tạo bảng leave_requests, leave_types, leave_approvals
5.3. Payroll Module (chưa có)

Lương cơ bản từ Contract
Phụ cấp từ Assignment (phụ cấp chức vụ HEAD/DEPUTY)
Tính lương theo department salary scale
📋 Thứ tự thực hiện ngay hôm nay
Ưu tiên cao nhất: Bước 1 - EmployeeAssignment Tab + Logic

Lý do:

Ảnh hưởng trực tiếp đến Contract/Approval/Payroll
Backend đã chuẩn (unique constraint), chỉ cần UI
Tạo nền tảng cho tất cả nghiệp vụ sau
Bước kế tiếp: Bước 3 (Skill Categories) → Bước 4 (Profile Completion) → Bước 5 (Contract/Leave/Payroll)

Bước 2 coi như đã xong 95%, chỉ cần verify link tab từ EmployeeIndex.
