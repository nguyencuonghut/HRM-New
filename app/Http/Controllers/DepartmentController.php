<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Str;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;

class DepartmentController extends Controller
{
    // GET /departments
    // Trả về MẢNG (không paginate) giống pattern UserIndex.vue để DataTable lọc client-side
    public function index(Request $request)
    {
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
            ->with('success', 'Tạo phòng/ban thành công.');
    }

    // PUT /departments/{department}
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $data = $request->validated();
        if (empty($data['code'])) {
            $data['code'] = Str::slug($data['name'], '_');
        }

        $department->update($data);

        return redirect()->route('departments.index')
            ->with('success', 'Cập nhật phòng/ban thành công.');
    }

    // DELETE /departments/{department}
    public function destroy(Department $department)
    {
        // Tuỳ nhu cầu, có thể kiểm tra ràng buộc children/assignments trước khi xoá
        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'Đã xoá phòng/ban.');
    }

    // DELETE /departments/bulk-delete
    public function bulkDelete(Request $request)
    {
        $ids = (array) $request->get('ids', []);
        if (!empty($ids)) {
            Department::whereIn('id', $ids)->delete();
        }

        return redirect()->route('departments.index')
            ->with('success', 'Đã xoá các mục đã chọn.');
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
}
