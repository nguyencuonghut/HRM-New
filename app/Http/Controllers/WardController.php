<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Ward;
use App\Models\Province;
use App\Http\Resources\WardResource;
use App\Http\Requests\StoreWardRequest;
use App\Http\Requests\UpdateWardRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WardController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Ward::class);

        $wards = WardResource::collection(
            Ward::with('province')
                ->join('provinces', 'wards.province_id', '=', 'provinces.id')
                ->orderBy('provinces.name')
                ->orderBy('wards.name')
                ->select('wards.*')
                ->get()
        )->resolve();

        $provinces = Province::orderBy('name')->get(['id', 'name']);

        return inertia('WardIndex', compact('wards', 'provinces'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWardRequest $request)
    {
        $this->authorize('create', Ward::class);

        $validated = $request->validated();

        $ward = Ward::create($validated);

        activity()
            ->performedOn($ward)
            ->causedBy(Auth::user())
            ->withProperties(['ward_name' => $ward->name, 'province_name' => $ward->province->name])
            ->log('Tạo phường/xã mới: ' . $ward->name . ' (' . $ward->province->name . ')');

        return redirect()->route('wards.index')->with([
            'message' => 'Tạo phường/xã thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWardRequest $request, Ward $ward)
    {
        $this->authorize('update', $ward);

        $validated = $request->validated();

        $ward->update($validated);
        $ward->refresh(); // Reload relationships

        activity()
            ->performedOn($ward)
            ->causedBy(Auth::user())
            ->withProperties(['ward_name' => $ward->name, 'province_name' => $ward->province->name])
            ->log('Sửa phường/xã: ' . $ward->name . ' (' . $ward->province->name . ')');

        return redirect()->route('wards.index')->with([
            'message' => 'Cập nhật phường/xã thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ward $ward)
    {
        $this->authorize('delete', $ward);

        // Check if ward has employees
        if ($ward->employees()->count() > 0) {
            return redirect()->route('wards.index')->with([
                'message' => 'Không thể xóa phường/xã này vì đang có nhân viên!',
                'type' => 'error'
            ]);
        }

        $wardName = $ward->name;
        $provinceName = $ward->province->name;
        $ward->delete();

        activity()
            ->causedBy(Auth::user())
            ->withProperties(['ward_name' => $wardName, 'province_name' => $provinceName])
            ->log('Xóa phường/xã: ' . $wardName . ' (' . $provinceName . ')');

        return redirect()->route('wards.index')->with([
            'message' => 'Xóa phường/xã thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', Ward::class);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:wards,id'
        ]);

        $wardsToDelete = Ward::with('province')->whereIn('id', $request->ids)->get();

        // Check if any wards have employees
        $wardsInUse = [];
        foreach ($wardsToDelete as $ward) {
            if ($ward->employees()->count() > 0) {
                $wardsInUse[] = $ward->name;
            }
        }

        if (!empty($wardsInUse)) {
            return redirect()->route('wards.index')->with([
                'message' => 'Không thể xóa các phường/xã sau vì đang có nhân viên: ' . implode(', ', $wardsInUse),
                'type' => 'error'
            ]);
        }

        foreach ($wardsToDelete as $ward) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties(['ward_name' => $ward->name, 'province_name' => $ward->province->name])
                ->log('Xóa phường/xã: ' . $ward->name . ' (' . $ward->province->name . ')');
        }

        $wardsToDelete->each->delete();

        return redirect()->route('wards.index')->with([
            'message' => 'Xóa các phường/xã đã chọn thành công!',
            'type' => 'success'
        ]);
    }
}

