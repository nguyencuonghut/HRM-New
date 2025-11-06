<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEducationLevelRequest;
use App\Http\Requests\UpdateEducationLevelRequest;
use App\Http\Resources\EducationLevelResource;
use App\Models\EducationLevel;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class EducationLevelController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', EducationLevel::class);

        $search = trim((string) $request->get('search', ''));
        $levels = EducationLevel::query()
            ->when($search !== '', fn($q) => $q->where('name','like',"%{$search}%")
                                                ->orWhere('code','like',"%{$search}%"))
            ->orderBy('order_index')->orderBy('name')
            ->get();

        return Inertia::render('EducationLevelIndex', [
            'education_levels' => EducationLevelResource::collection($levels)->resolve(),
        ]);
    }

    public function store(StoreEducationLevelRequest $request)
    {
        $this->authorize('create', EducationLevel::class);
        $data = $request->validated();
        $level = EducationLevel::create($data);

        activity()
            ->performedOn($level)
            ->causedBy($request->user())
            ->withProperties([
                'attributes' => [
                    'code' => $level->code,
                    'name' => $level->name,
                    'order_index' => $level->order_index,
                ]
            ])
            ->log('Tạo trình độ học vấn');

        return redirect()->route('education-levels.index')->with([
            'message' => 'Đã tạo trình độ học vấn.',
            'type' => 'success'
        ]);
    }

    public function update(UpdateEducationLevelRequest $request, EducationLevel $education_level)
    {
        $this->authorize('update', $education_level);

        $oldData = [
            'code' => $education_level->code,
            'name' => $education_level->name,
            'order_index' => $education_level->order_index,
        ];

        $data = $request->validated();
        $education_level->update($data);

        activity()
            ->performedOn($education_level)
            ->causedBy($request->user())
            ->withProperties([
                'old' => $oldData,
                'attributes' => [
                    'code' => $education_level->code,
                    'name' => $education_level->name,
                    'order_index' => $education_level->order_index,
                ]
            ])
            ->log('Cập nhật trình độ học vấn');

        return redirect()->route('education-levels.index')->with([
            'message' => 'Đã cập nhật.',
            'type' => 'success'
        ]);
    }

    public function destroy(Request $request, EducationLevel $education_level)
    {
        $this->authorize('delete', $education_level);

        $oldData = [
            'code' => $education_level->code,
            'name' => $education_level->name,
            'order_index' => $education_level->order_index,
        ];

        $education_level->delete();

        activity()
            ->performedOn($education_level)
            ->causedBy($request->user())
            ->withProperties(['old' => $oldData])
            ->log('Xóa trình độ học vấn');

        return redirect()->route('education-levels.index')->with([
            'message' => 'Đã xoá.',
            'type' => 'success'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', EducationLevel::class);
        $ids = (array) $request->input('ids', []);
        $levels = EducationLevel::whereIn('id', $ids)->get();

        $deletedRecords = $levels->map(function ($level) {
            return [
                'code' => $level->code,
                'name' => $level->name,
                'order_index' => $level->order_index,
            ];
        })->toArray();

        EducationLevel::whereIn('id', $ids)->delete();

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'count' => count($ids),
                'deleted_records' => $deletedRecords
            ])
            ->log('Xóa hàng loạt trình độ học vấn');

        return redirect()->route('education-levels.index')->with([
            'message' => 'Đã xoá các mục đã chọn.',
            'type' => 'success'
        ]);
    }
}
