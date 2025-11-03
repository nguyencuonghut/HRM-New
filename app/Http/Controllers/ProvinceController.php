<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Province;
use App\Models\Ward;
use App\Http\Resources\ProvinceResource;
use App\Http\Requests\StoreProvinceRequest;
use App\Http\Requests\UpdateProvinceRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProvinceController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Check if this is an API call (for dropdowns)
        if (request()->wantsJson() || request()->is('api/*')) {
            $provinces = Province::orderBy('name')->get(['id', 'code', 'name']);
            return response()->json($provinces);
        }

        // CRUD page
        $this->authorize('viewAny', Province::class);

        $provinces = ProvinceResource::collection(
            Province::withCount('wards')->orderBy('name')->get()
        )->resolve();

        return inertia('ProvinceIndex', compact('provinces'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProvinceRequest $request)
    {
        $this->authorize('create', Province::class);

        $validated = $request->validated();

        $province = Province::create($validated);

        activity()
            ->performedOn($province)
            ->causedBy(Auth::user())
            ->withProperties(['province_name' => $province->name])
            ->log('Tạo tỉnh/thành phố mới: ' . $province->name);

        return redirect()->route('provinces.index')->with([
            'message' => 'Tạo tỉnh/thành phố thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProvinceRequest $request, Province $province)
    {
        $this->authorize('update', $province);

        $validated = $request->validated();

        $province->update($validated);

        activity()
            ->performedOn($province)
            ->causedBy(Auth::user())
            ->withProperties(['province_name' => $province->name])
            ->log('Sửa tỉnh/thành phố: ' . $province->name);

        return redirect()->route('provinces.index')->with([
            'message' => 'Cập nhật tỉnh/thành phố thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Province $province)
    {
        $this->authorize('delete', $province);

        // Check if province has wards
        if ($province->wards()->count() > 0) {
            return redirect()->route('provinces.index')->with([
                'message' => 'Không thể xóa tỉnh/thành phố này vì đang có phường/xã!',
                'type' => 'error'
            ]);
        }

        $provinceName = $province->name;
        $province->delete();

        activity()
            ->causedBy(Auth::user())
            ->withProperties(['province_name' => $provinceName])
            ->log('Xóa tỉnh/thành phố: ' . $provinceName);

        return redirect()->route('provinces.index')->with([
            'message' => 'Xóa tỉnh/thành phố thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', Province::class);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:provinces,id'
        ]);

        $provincesToDelete = Province::whereIn('id', $request->ids)->get();

        // Check if any provinces have wards
        $provincesInUse = [];
        foreach ($provincesToDelete as $province) {
            if ($province->wards()->count() > 0) {
                $provincesInUse[] = $province->name;
            }
        }

        if (!empty($provincesInUse)) {
            return redirect()->route('provinces.index')->with([
                'message' => 'Không thể xóa các tỉnh/thành phố sau vì đang có phường/xã: ' . implode(', ', $provincesInUse),
                'type' => 'error'
            ]);
        }

        foreach ($provincesToDelete as $province) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties(['province_name' => $province->name])
                ->log('Xóa tỉnh/thành phố: ' . $province->name);
        }

        $provincesToDelete->each->delete();

        return redirect()->route('provinces.index')->with([
            'message' => 'Xóa các tỉnh/thành phố đã chọn thành công!',
            'type' => 'success'
        ]);
    }

    /**
     * Get wards by province_id for dropdown (API endpoint)
     */
    public function getWards($provinceId)
    {
        $wards = Ward::where('province_id', $provinceId)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'province_id']);

        return response()->json($wards);
    }
}
