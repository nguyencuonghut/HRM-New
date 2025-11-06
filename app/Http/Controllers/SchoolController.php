<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\UpdateSchoolRequest;
use App\Http\Resources\SchoolResource;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class SchoolController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', School::class);

        $search = trim((string) $request->get('search',''));
        $schools = School::query()
            ->when($search !== '', fn($q) => $q->where('name','like',"%{$search}%")
                                              ->orWhere('code','like',"%{$search}%"))
            ->orderBy('name')
            ->get();

        return Inertia::render('SchoolIndex', [
            'schools' => SchoolResource::collection($schools)->resolve(),
        ]);
    }

    public function store(StoreSchoolRequest $request)
    {
        $this->authorize('create', School::class);
        $data = $request->validated();
        $school = School::create($data);

        activity()
            ->performedOn($school)
            ->causedBy($request->user())
            ->withProperties([
                'attributes' => [
                    'code' => $school->code,
                    'name' => $school->name,
                ]
            ])
            ->log('Tạo trường');

        return redirect()->route('schools.index')->with([
            'message' => 'Đã tạo trường.',
            'type' => 'success'
        ]);
    }

    public function update(UpdateSchoolRequest $request, School $school)
    {
        $this->authorize('update', $school);

        $oldData = [
            'code' => $school->code,
            'name' => $school->name,
        ];

        $data = $request->validated();
        $school->update($data);

        activity()
            ->performedOn($school)
            ->causedBy($request->user())
            ->withProperties([
                'old' => $oldData,
                'attributes' => [
                    'code' => $school->code,
                    'name' => $school->name,
                ]
            ])
            ->log('Cập nhật trường');

        return redirect()->route('schools.index')->with([
            'message' => 'Đã cập nhật trường.',
            'type' => 'success'
        ]);
    }

    public function destroy(Request $request, School $school)
    {
        $this->authorize('delete', $school);

        $oldData = [
            'code' => $school->code,
            'name' => $school->name,
        ];

        $school->delete();

        activity()
            ->performedOn($school)
            ->causedBy($request->user())
            ->withProperties(['old' => $oldData])
            ->log('Xóa trường');

        return redirect()->route('schools.index')->with([
            'message' => 'Đã xoá trường.',
            'type' => 'success'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', School::class);
        $ids = (array) $request->input('ids', []);
        $schools = School::whereIn('id', $ids)->get();
        
        $deletedRecords = $schools->map(function ($school) {
            return [
                'code' => $school->code,
                'name' => $school->name,
            ];
        })->toArray();
        
        School::whereIn('id', $ids)->delete();

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'count' => count($ids),
                'deleted_records' => $deletedRecords
            ])
            ->log('Xóa hàng loạt trường');

        return redirect()->route('schools.index')->with([
            'message' => 'Đã xoá các trường đã chọn.',
            'type' => 'success'
        ]);
    }
}
