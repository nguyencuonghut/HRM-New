<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * Check if the authenticated user has the specified permission(s).
     * Can check for single permission or multiple permissions (any or all).
     *
     * Usage in routes:
     * - Single permission: ->middleware('permission:view employees')
     * - Any permission (OR): ->middleware('permission:edit employees|delete employees')
     * - All permissions (AND): ->middleware('permission:view employees&edit employees')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permissions  Permission(s) to check (separated by | for OR, & for AND)
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            abort(403, 'Unauthorized action.');
        }

        // Check if user is Super Admin (bypass all permission checks)
        if ($request->user()->hasRole('Super Admin')) {
            return $next($request);
        }

        // Handle multiple permissions with OR logic (separated by |)
        if (str_contains($permissions, '|')) {
            $permissionArray = explode('|', $permissions);
            foreach ($permissionArray as $permission) {
                if ($request->user()->hasPermissionTo(trim($permission))) {
                    return $next($request);
                }
            }
            abort(403, 'You do not have permission to access this resource.');
        }

        // Handle multiple permissions with AND logic (separated by &)
        if (str_contains($permissions, '&')) {
            $permissionArray = explode('&', $permissions);
            foreach ($permissionArray as $permission) {
                if (!$request->user()->hasPermissionTo(trim($permission))) {
                    abort(403, 'You do not have permission to access this resource.');
                }
            }
            return $next($request);
        }

        // Handle single permission
        if (!$request->user()->hasPermissionTo($permissions)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
