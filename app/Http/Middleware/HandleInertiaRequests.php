<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;
use App\Helpers\PermissionHelper;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'flash' => [
                // Legacy format (for backward compatibility)
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),

                // Modern message + type format
                'message' => $request->session()->get('message'),
                'type' => $request->session()->get('type'),
            ],
            'auth' => Auth::check() ? [
                'user' => [
                    'id' => Auth::user()->id,
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'roles' => Auth::user()->roles->pluck('name')->toArray(),
                    'permissions' => Auth::user()->getAllPermissions()->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'label' => PermissionHelper::getLabel($permission->name),
                            'description' => PermissionHelper::getDescription($permission->name),
                        ];
                    })->toArray(),
                ],
            ] : null
        ]);
    }
}
