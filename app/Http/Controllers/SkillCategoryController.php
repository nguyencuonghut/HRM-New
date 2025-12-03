<?php

namespace App\Http\Controllers;

use App\Enums\ActivityLogDescription;
use App\Http\Requests\StoreSkillCategoryRequest;
use App\Http\Requests\UpdateSkillCategoryRequest;
use App\Http\Resources\SkillCategoryResource;
use App\Models\SkillCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SkillCategoryController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', SkillCategory::class);

        $query = SkillCategory::query()->withCount('skills');

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query->orderBy('order_index')->orderBy('name')->get();

        return Inertia::render('SkillCategoryIndex', [
            'categories' => SkillCategoryResource::collection($categories)->resolve(),
        ]);
    }

    public function store(StoreSkillCategoryRequest $request)
    {
        $this->authorize('create', SkillCategory::class);

        $data = $request->validated();
        $category = SkillCategory::create($data);

        activity()
            ->performedOn($category)
            ->causedBy($request->user())
            ->withProperties([
                'attributes' => [
                    'name' => $category->name,
                    'description' => $category->description,
                    'order_index' => $category->order_index,
                    'is_active' => $category->is_active ? 'Hoạt động' : 'Không hoạt động',
                ]
            ])
            ->log('Tạo nhóm kỹ năng');

        return redirect()->route('skill-categories.index')
            ->with('success', 'Đã tạo nhóm kỹ năng.');
    }

    public function update(UpdateSkillCategoryRequest $request, SkillCategory $skillCategory)
    {
        $this->authorize('update', $skillCategory);

        $oldData = [
            'name' => $skillCategory->name,
            'description' => $skillCategory->description,
            'order_index' => $skillCategory->order_index,
            'is_active' => $skillCategory->is_active ? 'Hoạt động' : 'Không hoạt động',
        ];

        $skillCategory->update($request->validated());

        $newData = [
            'name' => $skillCategory->name,
            'description' => $skillCategory->description,
            'order_index' => $skillCategory->order_index,
            'is_active' => $skillCategory->is_active ? 'Hoạt động' : 'Không hoạt động',
        ];

        activity()
            ->performedOn($skillCategory)
            ->causedBy($request->user())
            ->withProperties([
                'old' => $oldData,
                'attributes' => $newData
            ])
            ->log('Cập nhật nhóm kỹ năng');

        return redirect()->route('skill-categories.index')
            ->with('success', 'Đã cập nhật nhóm kỹ năng.');
    }

    public function destroy(SkillCategory $skillCategory)
    {
        $this->authorize('delete', $skillCategory);

        // Check if category has skills
        if ($skillCategory->skills()->count() > 0) {
            return back()->withErrors([
                'category' => 'Không thể xóa nhóm đang có kỹ năng. Vui lòng chuyển các kỹ năng sang nhóm khác trước.'
            ]);
        }

        $oldData = [
            'name' => $skillCategory->name,
            'description' => $skillCategory->description,
            'order_index' => $skillCategory->order_index,
            'is_active' => $skillCategory->is_active ? 'Hoạt động' : 'Không hoạt động',
        ];

        $skillCategory->delete();

        activity()
            ->performedOn($skillCategory)
            ->causedBy(request()->user())
            ->withProperties(['old' => $oldData])
            ->log('Xóa nhóm kỹ năng');

        return redirect()->route('skill-categories.index')
            ->with('success', 'Đã xóa nhóm kỹ năng.');
    }

    public function bulkDestroy(Request $request)
    {
        $this->authorize('delete', SkillCategory::class);

        $ids = (array) $request->get('ids', []);
        if (!empty($ids)) {
            $categories = SkillCategory::withCount('skills')->whereIn('id', $ids)->get();

            // Check if any category has skills
            $hasSkills = $categories->where('skills_count', '>', 0);
            if ($hasSkills->count() > 0) {
                return back()->withErrors([
                    'category' => 'Không thể xóa nhóm đang có kỹ năng. Vui lòng chuyển các kỹ năng sang nhóm khác trước.'
                ]);
            }

            $deletedRecords = $categories->map(function ($cat) {
                return [
                    'name' => $cat->name,
                    'description' => $cat->description,
                    'is_active' => $cat->is_active ? 'Hoạt động' : 'Không hoạt động',
                ];
            })->toArray();

            SkillCategory::whereIn('id', $ids)->delete();

            activity()
                ->causedBy($request->user())
                ->withProperties([
                    'count' => count($ids),
                    'deleted_records' => $deletedRecords
                ])
                ->log('Xóa hàng loạt nhóm kỹ năng');
        }

        return redirect()->route('skill-categories.index')
            ->with('success', 'Đã xóa các nhóm kỹ năng đã chọn.');
    }
}
