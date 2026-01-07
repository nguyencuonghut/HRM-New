import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Composable for checking user permissions
 *
 * Usage:
 * ```js
 * import { usePermissions } from '@/Composables/usePermissions';
 *
 * const { can, canAny, canAll } = usePermissions();
 *
 * // Check single permission
 * if (can('create employees')) {
 *   // show button
 * }
 *
 * // Check if user has ANY of the permissions
 * if (canAny('edit employees', 'delete employees')) {
 *   // show actions column
 * }
 *
 * // Check if user has ALL permissions
 * if (canAll('view employees', 'edit employees')) {
 *   // show advanced features
 * }
 * ```
 */
export function usePermissions() {
    const page = usePage();

    /**
     * Get user's permissions from Inertia shared data
     */
    const userPermissions = computed(() => {
        return page.props.auth?.user?.permissions || [];
    });

    /**
     * Check if user has a specific permission
     * @param {string} permissionName - The permission name to check
     * @returns {boolean}
     */
    const can = (permissionName) => {
        return userPermissions.value.some(p => p.name === permissionName);
    };

    /**
     * Check if user has ANY of the specified permissions
     * @param {...string} permissionNames - Permission names to check
     * @returns {boolean}
     */
    const canAny = (...permissionNames) => {
        return permissionNames.some(name => can(name));
    };

    /**
     * Check if user has ALL of the specified permissions
     * @param {...string} permissionNames - Permission names to check
     * @returns {boolean}
     */
    const canAll = (...permissionNames) => {
        return permissionNames.every(name => can(name));
    };

    /**
     * Check if user has a specific role
     * @param {string} roleName - The role name to check
     * @returns {boolean}
     */
    const hasRole = (roleName) => {
        const userRoles = page.props.auth?.user?.roles || [];
        return userRoles.includes(roleName);
    };

    /**
     * Check if user has ANY of the specified roles
     * @param {...string} roleNames - Role names to check
     * @returns {boolean}
     */
    const hasAnyRole = (...roleNames) => {
        return roleNames.some(name => hasRole(name));
    };

    /**
     * Check if user has ALL of the specified roles
     * @param {...string} roleNames - Role names to check
     * @returns {boolean}
     */
    const hasAllRoles = (...roleNames) => {
        return roleNames.every(name => hasRole(name));
    };

    /**
     * Check if user is Super Admin
     * @returns {boolean}
     */
    const isSuperAdmin = () => {
        return hasRole('Super Admin');
    };

    return {
        can,
        canAny,
        canAll,
        hasRole,
        hasAnyRole,
        hasAllRoles,
        isSuperAdmin,
        userPermissions,
    };
}
