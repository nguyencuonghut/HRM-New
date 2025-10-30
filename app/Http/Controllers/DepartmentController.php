<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Str;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DepartmentController extends Controller
{
    use AuthorizesRequests;

    // GET /departments
    // Trả về MẢNG (không paginate) giống pattern UserIndex.vue để DataTable lọc client-side
    public function index(Request $request)
    {
        $this->authorize('viewAny', Department::class);

        $search    = trim((string) $request->get('search', ''));
        $type      = (string) $request->get('type', '');
        $isActiveQ = $request->has('is_active') ? $request->get('is_active') : null;

        // Chuẩn hoá is_active từ query (?is_active=true/false/null)
        $isActive = null;
        if ($isActiveQ === '1' || $isActiveQ === 'true' || $isActiveQ === 1 || $isActiveQ === true) {
            $isActive = true;
        } elseif ($isActiveQ === '0' || $isActiveQ === 'false' || $isActiveQ === 0 || $isActiveQ === false) {
            $isActive = false;
        }

        $query = Department::query()
            ->with(['parent:id,name'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', fn($q) => $q->where('type', $type))
            ->when(!is_null($isActive), fn($q) => $q->where('is_active', $isActive))
            ->orderByRaw('CASE WHEN order_index IS NULL THEN 1 ELSE 0 END, order_index ASC')
            ->orderBy('name');

        $departments = $query->get([
            'id','parent_id','type','name','code',
            'order_index','is_active','created_at','updated_at'
        ]);

        $parents = Department::query()
            ->orderBy('name')
            ->get(['id','name']);

        return Inertia::render('DepartmentIndex', [
            // giống UserIndex.vue: props là mảng
            'departments' => $departments,
            'parents'     => $parents,
            'enums'       => [
                'types' => [
                    ['value' => 'DEPARTMENT', 'label' => 'Phòng/Ban'],
                    ['value' => 'UNIT',       'label' => 'Bộ phận'],
                    ['value' => 'TEAM',       'label' => 'Nhóm'],
                ],
            ],
        ]);
    }

    // POST /departments
    public function store(StoreDepartmentRequest $request)
    {
        $this->authorize('create', Department::class);

        $data = $request->validated();
        if (empty($data['code'])) {
            $data['code'] = Str::slug($data['name'], '_');
        }

        // Tự động tính order_index nếu không được cung cấp
        if (!isset($data['order_index']) || $data['order_index'] === null) {
            $data['order_index'] = $this->getNextOrderIndex($data['parent_id'] ?? null);
        }

        Department::create($data);

        // Trả về Index để UserIndex-style cập nhật list
        return redirect()->route('departments.index')
            ->with([
                'message' => 'Tạo phòng/ban thành công.',
                'type' => 'success'
            ]);
    }

    // PUT /departments/{department}
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $this->authorize('update', $department);

        $data = $request->validated();
        if (empty($data['code'])) {
            $data['code'] = Str::slug($data['name'], '_');
        }

        $department->update($data);

        return redirect()->route('departments.index')
            ->with([
                'message' => 'Cập nhật phòng/ban thành công.',
                'type' => 'success'
            ]);
    }

    // DELETE /departments/{department}
    public function destroy(Department $department)
    {
        $this->authorize('delete', $department);

        // Kiểm tra các ràng buộc trước khi xóa
        $constraints = $this->checkDepartmentConstraints($department->id);

        if (!empty($constraints)) {
            return redirect()->route('departments.index')
                ->with([
                    'message' => 'Không thể xóa phòng/ban "' . $department->name . '". ' . implode(' ', $constraints),
                    'type' => 'error'
                ]);
        }

        $department->delete();

        return redirect()->route('departments.index')
            ->with([
                'message' => 'Đã xóa phòng/ban "' . $department->name . '" thành công.',
                'type' => 'success'
            ]);
    }    // DELETE /departments/bulk-delete
    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', Department::class);

        $ids = (array) $request->get('ids', []);
        if (empty($ids)) {
            return redirect()->route('departments.index')
                ->with([
                    'message' => 'Không có mục nào được chọn để xóa.',
                    'type' => 'warning'
                ]);
        }

        // Kiểm tra ràng buộc cho từng department
        $allConstraints = [];
        $validIds = [];

        foreach ($ids as $id) {
            $department = Department::find($id);
            if (!$department) continue;

            $constraints = $this->checkDepartmentConstraints($id);
            if (!empty($constraints)) {
                $allConstraints[] = $department->name . ': ' . implode(', ', $constraints);
            } else {
                $validIds[] = $id;
            }
        }

        // Xóa các department hợp lệ
        $deletedCount = 0;
        if (!empty($validIds)) {
            $deletedCount = Department::whereIn('id', $validIds)->count();
            Department::whereIn('id', $validIds)->delete();
        }

        // Tạo thông báo phù hợp
        if (!empty($allConstraints) && $deletedCount > 0) {
            return redirect()->route('departments.index')
                ->with([
                    'message' => "Đã xóa $deletedCount phòng/ban. Không thể xóa một số phòng/ban khác: " . implode('; ', $allConstraints),
                    'type' => 'warning'
                ]);
        } elseif (!empty($allConstraints)) {
            return redirect()->route('departments.index')
                ->with([
                    'message' => 'Không thể xóa các phòng/ban đã chọn: ' . implode('; ', $allConstraints),
                    'type' => 'error'
                ]);
        } else {
            return redirect()->route('departments.index')
                ->with([
                    'message' => "Đã xóa $deletedCount phòng/ban thành công.",
                    'type' => 'success'
                ]);
        }
    }

    /**
     * Get next order_index for a department at given parent level
     * @param string|null $parentId
     * @return int
     */
    private function getNextOrderIndex($parentId = null)
    {
        $maxOrder = Department::query()
            ->where('parent_id', $parentId)
            ->max('order_index');

        // If no departments exist at this level, start from 10
        // Otherwise, increment by 10 to allow for future insertions
        return $maxOrder ? $maxOrder + 10 : 10;
    }

    /**
     * API: Get next order_index for preview
     */
    public function getNextOrderIndexApi(Request $request, $parentId = null)
    {
        if ($parentId === 'null') $parentId = null;

        return response()->json([
            'next_order_index' => $this->getNextOrderIndex($parentId)
        ]);
    }

    /**
     * API: Update order_index for departments at the same level
     */
    public function updateOrderIndexes(Request $request)
    {
        $this->authorize('reorder', Department::class);

        $orders = $request->input('orders', []);

        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|uuid|exists:departments,id',
            'orders.*.order_index' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($orders as $item) {
                Department::where('id', $item['id'])
                    ->update(['order_index' => $item['order_index']]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Cập nhật thứ tự thành công']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Kiểm tra các ràng buộc trước khi xóa department
     * @param string $departmentId
     * @return array Danh sách các lỗi ràng buộc
     */
    private function checkDepartmentConstraints($departmentId)
    {
        $constraints = [];

        // 1. Kiểm tra có departments con không
        $hasChildren = Department::where('parent_id', $departmentId)->exists();
        if ($hasChildren) {
            $childrenCount = Department::where('parent_id', $departmentId)->count();
            $constraints[] = "Có $childrenCount phòng/ban con.";
        }

        // 2. Kiểm tra có positions không
        $hasPositions = DB::table('positions')->where('department_id', $departmentId)->exists();
        if ($hasPositions) {
            $positionsCount = DB::table('positions')->where('department_id', $departmentId)->count();
            $constraints[] = "Có $positionsCount vị trí công việc.";
        }

        // 3. Kiểm tra có employee assignments không
        $hasAssignments = DB::table('employee_assignments')->where('department_id', $departmentId)->exists();
        if ($hasAssignments) {
            $assignmentsCount = DB::table('employee_assignments')->where('department_id', $departmentId)->count();
            $constraints[] = "Có $assignmentsCount nhân viên.";
        }

        // 4. Kiểm tra có role scopes không
        $hasRoleScopes = DB::table('role_scopes')->where('department_id', $departmentId)->exists();
        if ($hasRoleScopes) {
            $roleScopesCount = DB::table('role_scopes')->where('department_id', $departmentId)->count();
            $constraints[] = "Có $roleScopesCount phạm vi quyền.";
        }

        return $constraints;
    }
}
