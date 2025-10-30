<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DepartmentOrgController extends Controller
{
    // Trang Inertia
    public function index(Request $request)
    {
        // Có thể nạp sẵn root để load nhanh lần đầu
        $roots = $this->nodes(
            Department::query()
                ->whereNull('parent_id')
                ->orderBy('order_index')->orderBy('name')
                ->get(['id','parent_id','type','name','code','is_active'])
        );

        return Inertia::render('OrgChart', [
            'roots' => $roots,
        ]);
    }

    // JSON: danh sách root (nếu bạn muốn gọi lại)
    public function roots()
    {
        $rows = Department::query()
            ->whereNull('parent_id')
            ->orderBy('order_index')->orderBy('name')
            ->get(['id','parent_id','type','name','code','is_active']);

        return response()->json($this->nodes($rows));
    }

    // JSON: con của 1 node
    public function children(Request $request)
    {
        $parentId = $request->query('parent_id');
        $rows = Department::query()
            ->where('parent_id', $parentId)
            ->orderBy('order_index')->orderBy('name')
            ->get(['id','parent_id','type','name','code','is_active']);

        return response()->json($this->nodes($rows));
    }

    // JSON: employees của 1 department (bao gồm cả nhân viên của department con)
    public function employees(Request $request, $departmentId)
    {
        // Lấy tất cả descendant department IDs (bao gồm cả department hiện tại)
        $descendantIds = $this->getAllDescendantIds($departmentId);
        $allDepartmentIds = array_merge([$departmentId], $descendantIds);

        // Query nhân viên từ tất cả departments với thông tin department
        $employees = DB::table('employee_assignments as ea')
            ->join('employees as e', 'e.id', '=', 'ea.employee_id')
            ->leftJoin('positions as p', 'p.id', '=', 'ea.position_id')
            ->leftJoin('departments as d', 'd.id', '=', 'ea.department_id')
            ->whereIn('ea.department_id', $allDepartmentIds)
            ->where('ea.status', 'ACTIVE')
            ->select([
                'e.id',
                'e.full_name',
                'p.title as position_name',
                'ea.role_type',
                'ea.department_id',
                'd.name as department_name'
            ])
            ->get();

        return response()->json([
            'success' => true,
            'department_id' => $departmentId,
            'employees' => $employees,
            'count' => $employees->count(),
            'debug' => 'API with descendants'
        ]);
    }

    // Map về cấu trúc PrimeVue Tree + headcount + trưởng/phó
    private function nodes($rows)
    {
        $ids = $rows->pluck('id')->all();

        // Get all descendant department IDs for each department (recursive)
        $allDescendants = [];
        foreach ($ids as $departmentId) {
            $allDescendants[$departmentId] = $this->getAllDescendantIds($departmentId);
        }

        // Calculate headcount including descendants
        $headcounts = [];
        foreach ($ids as $departmentId) {
            $departmentIds = array_merge([$departmentId], $allDescendants[$departmentId]);

            $count = DB::table('employee_assignments')
                ->whereIn('department_id', $departmentIds)
                ->where('status', 'ACTIVE')
                ->distinct('employee_id')
                ->count('employee_id');

            $headcounts[$departmentId] = $count;
        }

        $leaders = DB::table('employee_assignments as ea')
            ->join('employees as e', 'e.id', '=', 'ea.employee_id')
            ->whereIn('ea.department_id', $ids)
            ->where('ea.status', 'ACTIVE')
            ->whereIn('ea.role_type', ['HEAD','DEPUTY'])
            ->select('ea.department_id','ea.role_type','e.full_name')
            ->get()
            ->groupBy('department_id');

        // Get children count for each department to determine leaf status
        $childrenCounts = DB::table('departments')
            ->select('parent_id', DB::raw('COUNT(*) as children_count'))
            ->whereIn('parent_id', $ids)
            ->groupBy('parent_id')
            ->pluck('children_count', 'parent_id');

        return $rows->map(function($d) use ($headcounts, $leaders, $childrenCounts) {
            $ls = $leaders->get($d->id, collect());
            $head   = optional($ls->firstWhere('role_type','HEAD'))->full_name;
            $deputy = optional($ls->firstWhere('role_type','DEPUTY'))->full_name;

            // Determine if this department has children
            $childrenCount = $childrenCounts->get($d->id, 0);
            $isLeaf = $childrenCount === 0;

            return [
                'key'   => $d->id,
                'label' => $d->name,
                'data'  => [
                    'id'             => $d->id,
                    'type'           => $d->type,
                    'code'           => $d->code,
                    'is_active'      => (bool)$d->is_active,
                    'head'           => $head,
                    'deputy'         => $deputy,
                    'headcount'      => (int) ($headcounts[$d->id] ?? 0),
                    'children_count' => $childrenCount,
                ],
                'leaf'  => $isLeaf, // true if no children, false if has children
            ];
        })->values();
    }

    /**
     * Get all descendant department IDs recursively
     * @param string $departmentId
     * @return array
     */
    private function getAllDescendantIds($departmentId)
    {
        static $cache = [];

        if (isset($cache[$departmentId])) {
            return $cache[$departmentId];
        }

        $descendants = [];

        // Get direct children
        $children = DB::table('departments')
            ->where('parent_id', $departmentId)
            ->pluck('id')
            ->toArray();

        foreach ($children as $childId) {
            $descendants[] = $childId;
            // Recursively get descendants of children
            $childDescendants = $this->getAllDescendantIds($childId);
            $descendants = array_merge($descendants, $childDescendants);
        }

        $cache[$departmentId] = array_unique($descendants);
        return $cache[$departmentId];
    }
}
