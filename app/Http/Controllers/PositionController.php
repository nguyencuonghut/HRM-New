<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Position;
use App\Models\Department;
use App\Http\Resources\PositionResource;
use App\Http\Resources\DepartmentResource;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PositionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Position::class);

        $positions = PositionResource::collection(
            Position::with('department')->orderBy('department_id')->latest()->get()
        )->resolve();

        $departments = DepartmentResource::collection(
            Department::where('is_active', true)->orderBy('name')->get()
        )->resolve();

        return inertia('PositionIndex', compact('positions', 'departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePositionRequest $request)
    {
        $this->authorize('create', Position::class);

        $validated = $request->validated();

        $position = Position::create($validated);

        // Log activity
        activity()
            ->performedOn($position)
            ->causedBy(Auth::user())
            ->withProperties([
                'position_title' => $position->title,
                'department' => $position->department?->name
            ])
            ->log('Tạo chức vụ mới: ' . $position->title);

        return redirect()->route('positions.index')->with([
            'message' => 'Tạo chức vụ thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePositionRequest $request, Position $position)
    {
        $this->authorize('update', $position);

        $validated = $request->validated();

        $position->update($validated);

        activity()
            ->performedOn($position)
            ->causedBy(Auth::user())
            ->withProperties([
                'position_title' => $position->title,
                'department' => $position->department?->name
            ])
            ->log('Sửa chức vụ: ' . $position->title);

        return redirect()->route('positions.index')->with([
            'message' => 'Cập nhật chức vụ thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position)
    {
        $this->authorize('delete', $position);

        // Check if position is being used in employee assignments
        if ($position->employeeAssignments()->count() > 0) {
            return redirect()->route('positions.index')->with([
                'message' => 'Không thể xóa chức vụ này vì đang có nhân viên được gán!',
                'type' => 'error'
            ]);
        }

        $positionTitle = $position->title;
        $position->delete();

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'position_title' => $positionTitle
            ])
            ->log('Xóa chức vụ: ' . $positionTitle);

        return redirect()->route('positions.index')->with([
            'message' => 'Xóa chức vụ thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', Position::class);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:positions,id'
        ]);

        $positionsToDelete = Position::whereIn('id', $request->ids)->get();

        // Check if any positions are being used in employee assignments
        $positionsInUse = [];
        foreach ($positionsToDelete as $position) {
            if ($position->employeeAssignments()->count() > 0) {
                $positionsInUse[] = $position->title;
            }
        }

        if (!empty($positionsInUse)) {
            return redirect()->route('positions.index')->with([
                'message' => 'Không thể xóa các chức vụ sau vì đang có nhân viên được gán: ' . implode(', ', $positionsInUse),
                'type' => 'error'
            ]);
        }

        foreach ($positionsToDelete as $position) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'position_title' => $position->title
                ])
                ->log('Xóa chức vụ: ' . $position->title);
        }

        $positionsToDelete->each->delete();

        return redirect()->route('positions.index')->with([
            'message' => 'Xóa các chức vụ đã chọn thành công!',
            'type' => 'success'
        ]);
    }
}
