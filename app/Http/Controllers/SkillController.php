<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use App\Http\Requests\StoreSkillRequest;
use App\Http\Requests\UpdateSkillRequest;
use App\Http\Resources\SkillResource;
use App\Http\Resources\SkillCategoryResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SkillController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of skills
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Skill::class);

        $search = trim((string) $request->get('search', ''));
        $categoryId = $request->get('category_id', '');

        $query = Skill::query()
            ->with(['category:id,name'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($categoryId !== '', fn($q) => $q->where('category_id', $categoryId))
            ->orderBy('name');

        $skills = $query->get();

        // Get all active categories for filter
        $categories = SkillCategory::where('is_active', true)
            ->orderBy('order_index')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('SkillIndex', [
            'skills' => SkillResource::collection($skills)->resolve(),
            'categories' => SkillCategoryResource::collection($categories)->resolve(),
        ]);
    }

    /**
     * Store a newly created skill
     */
    public function store(StoreSkillRequest $request)
    {
        $this->authorize('create', Skill::class);

        $data = $request->validated();

        if (empty($data['code'])) {
            $data['code'] = Str::slug($data['name'], '_');
        }

        $skill = Skill::create($data);
        $skill->load('category');

        activity()
            ->performedOn($skill)
            ->causedBy($request->user())
            ->withProperties([
                'attributes' => [
                    'name' => $skill->name,
                    'code' => $skill->code,
                    'category' => $skill->category?->name,
                ]
            ])
            ->log('Tạo kỹ năng');

        return redirect()->route('skills.index')
            ->with([
                'message' => 'Tạo kỹ năng thành công.',
                'type' => 'success'
            ]);
    }

    /**
     * Update the specified skill
     */
    public function update(UpdateSkillRequest $request, Skill $skill)
    {
        $this->authorize('update', $skill);

        $skill->load('category');
        $oldData = [
            'name' => $skill->name,
            'code' => $skill->code,
            'category' => $skill->category?->name,
        ];

        $data = $request->validated();

        if (empty($data['code'])) {
            $data['code'] = Str::slug($data['name'], '_');
        }

        $skill->update($data);
        $skill->refresh()->load('category');

        $newData = [
            'name' => $skill->name,
            'code' => $skill->code,
            'category' => $skill->category?->name,
        ];

        activity()
            ->performedOn($skill)
            ->causedBy($request->user())
            ->withProperties([
                'old' => $oldData,
                'attributes' => $newData
            ])
            ->log('Cập nhật kỹ năng');

        return redirect()->route('skills.index')
            ->with([
                'message' => 'Cập nhật kỹ năng thành công.',
                'type' => 'success'
            ]);
    }

    /**
     * Remove the specified skill
     */
    public function destroy(Skill $skill)
    {
        $this->authorize('delete', $skill);

        // Check if skill is being used by employees
        if ($skill->employees()->count() > 0) {
            return redirect()->route('skills.index')
                ->with([
                    'message' => 'Không thể xóa kỹ năng đang được sử dụng.',
                    'type' => 'error'
                ]);
        }

        $name = $skill->name;
        $skill->delete();

        activity()
            ->performedOn($skill)
            ->causedBy(request()->user())
            ->withProperties([
                'attributes' => ['name' => $name]
            ])
            ->log('Xóa kỹ năng');

        return redirect()->route('skills.index')
            ->with([
                'message' => 'Xóa kỹ năng thành công.',
                'type' => 'success'
            ]);
    }

    /**
     * Bulk delete skills
     */
    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', Skill::class);

        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('skills.index')
                ->with([
                    'message' => 'Không có kỹ năng nào được chọn.',
                    'type' => 'error'
                ]);
        }

        // Check if any skill is being used
        $skillsInUse = Skill::whereIn('id', $ids)
            ->has('employees')
            ->count();

        if ($skillsInUse > 0) {
            return redirect()->route('skills.index')
                ->with([
                    'message' => 'Một số kỹ năng đang được sử dụng, không thể xóa.',
                    'type' => 'error'
                ]);
        }

        Skill::whereIn('id', $ids)->delete();

        activity()
            ->causedBy(request()->user())
            ->withProperties(['count' => count($ids)])
            ->log('Xóa nhiều kỹ năng');

        return redirect()->route('skills.index')
            ->with([
                'message' => 'Xóa ' . count($ids) . ' kỹ năng thành công.',
                'type' => 'success'
            ]);
    }
}
