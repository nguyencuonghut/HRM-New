<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\Ward;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    /**
     * Get all provinces for dropdown
     */
    public function index()
    {
        $provinces = Province::orderBy('name')->get(['id', 'code', 'name']);
        return response()->json($provinces);
    }

    /**
     * Get wards by province_id for dropdown
     */
    public function getWards($provinceId)
    {
        $wards = Ward::where('province_id', $provinceId)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'province_id']);

        return response()->json($wards);
    }
}
