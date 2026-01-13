<?php

namespace App\Http\Controllers;

use App\Models\InsuranceComponent;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class InsuranceComponentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get all active insurance components for form selection
     */
    public function getActiveComponents(Request $request)
    {
        $components = InsuranceComponent::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name_vi', 'default_rate_total']);

        return response()->json($components);
    }

    /**
     * Display management page for all insurance components (admin only)
     */
    public function index(Request $request)
    {
        $this->authorize('manage', InsuranceComponent::class);

        return Inertia::render('Insurance/ComponentIndex');
    }

    /**
     * Get all insurance components (for management page)
     */
    public function list(Request $request)
    {
        $this->authorize('manage', InsuranceComponent::class);

        $components = InsuranceComponent::orderBy('code')->get();
        return response()->json($components);
    }

    /**
     * Update insurance component rates
     */
    public function update(Request $request, InsuranceComponent $component)
    {
        $this->authorize('update', $component);

        $validated = $request->validate([
            'default_rate_employee' => 'required|numeric|min:0|max:1',
            'default_rate_employer' => 'required|numeric|min:0|max:1',
            'is_active' => 'required|boolean',
        ]);

        // Calculate total rate
        $validated['default_rate_total'] = $validated['default_rate_employee'] + $validated['default_rate_employer'];

        $component->update($validated);

        return response()->json([
            'message' => 'Đã cập nhật tỷ lệ đóng BHXH',
            'component' => $component->fresh()
        ]);
    }
}
