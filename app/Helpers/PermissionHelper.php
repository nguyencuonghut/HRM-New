<?php

namespace App\Helpers;

class PermissionHelper
{
    /**
     * Get Vietnamese label for a permission.
     *
     * @param string $permissionName
     * @return string
     */
    public static function getLabel(string $permissionName): string
    {
        $modules = config('permissions.modules');

        foreach ($modules as $module) {
            if (isset($module['permissions'][$permissionName])) {
                return $module['permissions'][$permissionName]['label'];
            }
        }

        return $permissionName; // Fallback to English name
    }

    /**
     * Get description for a permission.
     *
     * @param string $permissionName
     * @return string
     */
    public static function getDescription(string $permissionName): string
    {
        $modules = config('permissions.modules');

        foreach ($modules as $module) {
            if (isset($module['permissions'][$permissionName])) {
                return $module['permissions'][$permissionName]['description'];
            }
        }

        return '';
    }

    /**
     * Get module name for a permission.
     *
     * @param string $permissionName
     * @return string|null
     */
    public static function getModule(string $permissionName): ?string
    {
        $modules = config('permissions.modules');

        foreach ($modules as $module) {
            if (isset($module['permissions'][$permissionName])) {
                return $module['label'];
            }
        }

        return null;
    }

    /**
     * Get module icon for a permission.
     *
     * @param string $permissionName
     * @return string|null
     */
    public static function getModuleIcon(string $permissionName): ?string
    {
        $modules = config('permissions.modules');

        foreach ($modules as $module) {
            if (isset($module['permissions'][$permissionName])) {
                return $module['icon'];
            }
        }

        return null;
    }

    /**
     * Get all permissions as flat array with labels.
     *
     * @return array
     */
    public static function getAllWithLabels(): array
    {
        $modules = config('permissions.modules');
        $permissions = [];

        foreach ($modules as $moduleKey => $module) {
            foreach ($module['permissions'] as $name => $data) {
                $permissions[$name] = [
                    'name' => $name,
                    'label' => $data['label'],
                    'description' => $data['description'] ?? '',
                    'module' => $module['label'],
                    'module_key' => $moduleKey,
                    'module_icon' => $module['icon'],
                ];
            }
        }

        return $permissions;
    }

    /**
     * Get all permissions grouped by module with labels.
     *
     * @return array
     */
    public static function getAllGrouped(): array
    {
        $modules = config('permissions.modules');
        $grouped = [];

        foreach ($modules as $moduleKey => $module) {
            $grouped[$module['label']] = [
                'label' => $module['label'],
                'icon' => $module['icon'],
                'permissions' => []
            ];

            foreach ($module['permissions'] as $name => $data) {
                $grouped[$module['label']]['permissions'][] = [
                    'name' => $name,
                    'label' => $data['label'],
                    'description' => $data['description'] ?? '',
                ];
            }
        }

        return $grouped;
    }

    /**
     * Get all module names.
     *
     * @return array
     */
    public static function getAllModules(): array
    {
        $modules = config('permissions.modules');
        $result = [];

        foreach ($modules as $moduleKey => $module) {
            $result[$moduleKey] = [
                'label' => $module['label'],
                'icon' => $module['icon'],
            ];
        }

        return $result;
    }

    /**
     * Transform permission collection to include Vietnamese labels.
     *
     * @param \Illuminate\Support\Collection $permissions
     * @return array
     */
    public static function transformCollection($permissions): array
    {
        return $permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => self::getLabel($permission->name),
                'description' => self::getDescription($permission->name),
                'module' => self::getModule($permission->name),
                'module_icon' => self::getModuleIcon($permission->name),
                'guard_name' => $permission->guard_name ?? 'web',
                'created_at' => $permission->created_at,
                'updated_at' => $permission->updated_at,
                // Include relationships if loaded
                'roles' => $permission->relationLoaded('roles') ? $permission->roles : null,
            ];
        })->toArray();
    }
}
