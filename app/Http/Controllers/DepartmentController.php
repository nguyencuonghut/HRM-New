<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
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
            ->orderBy('order_index')
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
}
