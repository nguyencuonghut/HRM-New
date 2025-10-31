import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Composable for checking user roles and permissions in Vue components
 *
 * Usage:
 * const { hasRole, hasPermission, hasAnyRole, hasAllRoles, hasAnyPermission, can } = usePermission();
 *
 * if (hasRole('Super Admin')) { ... }
 * if (can('manage users')) { ... }
 *
 * In template:
 * <Button v-if="hasRole('Super Admin')" />
 * <div v-if="can('edit users')"> ... </div>
 */
export function usePermission() {
    const page = usePage();

    /**
     * Get current authenticated user
     */
    const user = computed(() => page.props.auth?.user || null);

    /**
     * Get user's roles array
     */
    const userRoles = computed(() => {
        if (!user.value) return [];
        return user.value.roles || [];
    });

    /**
     * Get user's permissions array
     */
    const userPermissions = computed(() => {
        if (!user.value) return [];
        return user.value.permissions || [];
    });

    /**
     * Check if user has a specific role
     * @param {string} role - Role name to check
     * @returns {boolean}
     */
    const hasRole = (role) => {
        if (!user.value || !role) return false;
        return userRoles.value.some(r => r.name === role);
    };

    /**
     * Check if user has any of the given roles
     * @param {string|Array<string>} roles - Role name(s) to check
     * @returns {boolean}
     */
    const hasAnyRole = (roles) => {
        if (!user.value) return false;

        const roleArray = Array.isArray(roles) ? roles : [roles];
        return roleArray.some(role => hasRole(role));
    };

    /**
     * Check if user has all of the given roles
     * @param {Array<string>} roles - Array of role names to check
     * @returns {boolean}
     */
    const hasAllRoles = (roles) => {
        if (!user.value || !Array.isArray(roles)) return false;

        return roles.every(role => hasRole(role));
    };

    /**
     * Check if user has a specific permission
     * @param {string} permission - Permission name to check
     * @returns {boolean}
     */
    const hasPermission = (permission) => {
        if (!user.value || !permission) return false;
        return userPermissions.value.some(p => p.name === permission);
    };

    /**
     * Check if user has any of the given permissions
     * @param {string|Array<string>} permissions - Permission name(s) to check
     * @returns {boolean}
     */
    const hasAnyPermission = (permissions) => {
        if (!user.value) return false;

        const permArray = Array.isArray(permissions) ? permissions : [permissions];
        return permArray.some(permission => hasPermission(permission));
    };

    /**
     * Check if user has all of the given permissions
     * @param {Array<string>} permissions - Array of permission names to check
     * @returns {boolean}
     */
    const hasAllPermissions = (permissions) => {
        if (!user.value || !Array.isArray(permissions)) return false;

        return permissions.every(permission => hasPermission(permission));
    };

    /**
     * Alias for hasPermission (Laravel-style)
     * @param {string} permission - Permission name to check
     * @returns {boolean}
     */
    const can = (permission) => {
        return hasPermission(permission);
    };

    /**
     * Check if user is Super Admin
     * @returns {boolean}
     */
    const isSuperAdmin = () => {
        return hasRole('Super Admin');
    };

    /**
     * Check if user is Admin (Super Admin or Admin)
     * @returns {boolean}
     */
    const isAdmin = () => {
        return hasAnyRole(['Super Admin', 'Admin']);
    };

    /**
     * Check if user can manage users (CRUD operations)
     * @returns {boolean}
     */
    const canManageUsers = () => {
        return can('view users') && can('create users') && can('edit users') && can('delete users');
    };

    /**
     * Check if user can view users
     * @returns {boolean}
     */
    const canViewUsers = () => {
        return can('view users');
    };

    /**
     * Check if user can create users
     * @returns {boolean}
     */
    const canCreateUsers = () => {
        return can('create users');
    };

    /**
     * Check if user can edit users
     * @returns {boolean}
     */
    const canEditUsers = () => {
        return can('edit users');
    };

    /**
     * Check if user can delete users
     * @returns {boolean}
     */
    const canDeleteUsers = () => {
        return can('delete users');
    };

    /**
     * Check if user can manage roles (CRUD operations)
     * @returns {boolean}
     */
    const canManageRoles = () => {
        return can('view roles') && can('create roles') && can('edit roles') && can('delete roles');
    };

    /**
     * Check if user can view roles
     * @returns {boolean}
     */
    const canViewRoles = () => {
        return can('view roles');
    };

    /**
     * Check if user can create roles
     * @returns {boolean}
     */
    const canCreateRoles = () => {
        return can('create roles');
    };

    /**
     * Check if user can edit roles
     * @returns {boolean}
     */
    const canEditRoles = () => {
        return can('edit roles');
    };

    /**
     * Check if user can delete roles
     * @returns {boolean}
     */
    const canDeleteRoles = () => {
        return can('delete roles');
    };

    /**
     * Check if user can manage backups
     * @returns {boolean}
     */
    const canManageBackups = () => {
        return can('view backups') && can('create backups') && can('configure backups');
    };

    /**
     * Check if user can view backups
     * @returns {boolean}
     */
    const canViewBackups = () => {
        return can('view backups');
    };

    /**
     * Check if user can create backups
     * @returns {boolean}
     */
    const canCreateBackups = () => {
        return can('create backups');
    };

    /**
     * Check if user can configure backups
     * @returns {boolean}
     */
    const canConfigureBackups = () => {
        return can('configure backups');
    };

    /**
     * Check if user can restore backups
     * @returns {boolean}
     */
    const canRestoreBackups = () => {
        return can('restore backups');
    };

    /**
     * Check if user can delete backups
     * @returns {boolean}
     */
    const canDeleteBackups = () => {
        return can('delete backups');
    };

    /**
     * Check if user can view departments
     * @returns {boolean}
     */
    const canViewDepartments = () => {
        return can('view departments');
    };

    /**
     * Check if user can create departments
     * @returns {boolean}
     */
    const canCreateDepartments = () => {
        return can('create departments');
    };

    /**
     * Check if user can edit departments
     * @returns {boolean}
     */
    const canEditDepartments = () => {
        return can('edit departments');
    };

    /**
     * Check if user can delete departments
     * @returns {boolean}
     */
    const canDeleteDepartments = () => {
        return can('delete departments');
    };

    return {
        // User data
        user,
        userRoles,
        userPermissions,

        // Role checks
        hasRole,
        hasAnyRole,
        hasAllRoles,

        // Permission checks
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        can,

        // Convenience methods
        isSuperAdmin,
        isAdmin,
        canManageUsers,
        canViewUsers,
        canCreateUsers,
        canEditUsers,
        canDeleteUsers,
        canManageRoles,
        canViewRoles,
        canCreateRoles,
        canEditRoles,
        canDeleteRoles,
        canManageBackups,
        canViewBackups,
        canCreateBackups,
        canConfigureBackups,
        canRestoreBackups,
        canDeleteBackups,

        // Department permissions
        canViewDepartments,
        canCreateDepartments,
        canEditDepartments,
        canDeleteDepartments,
    };
}
