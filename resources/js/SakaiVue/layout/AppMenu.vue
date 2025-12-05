<script setup>
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { usePermission } from '@/composables/usePermission';
import AppMenuItem from './AppMenuItem.vue';

const { t } = useI18n();
const {
    canViewUsers,
    canViewRoles,
    canViewDepartments,
    canViewBackups,
    can
} = usePermission();

const model = computed(() => {
    const homeItems = [
        { label: t('nav.home'), icon: 'pi pi-fw pi-home', to: '/' },
    ];

    // Add Employee to home section if user has permission
    if (can('view employees')) {
        homeItems.push({
            label: 'Nhân sự',
            icon: 'pi pi-fw pi-id-card',
            to: '/employees'
        });
    }

    if (can('view employee assignments')) {
        homeItems.push({
            label: 'Phân công nhân sự',
            icon: 'pi pi-fw pi-users',
            to: '/employee-assignments'
        });
    }

    // Add Contract to home section if user has permission
    if (can('view contracts')) {
        homeItems.push({
            label: 'Hợp đồng',
            icon: 'pi pi-fw pi-file-edit',
            to: '/contracts'
        });
    }

    // Add Leave Management - accessible to all authenticated users
    homeItems.push({
        label: 'Nghỉ phép',
        icon: 'pi pi-fw pi-calendar',
        items: [
            {
                label: 'Đơn nghỉ phép',
                icon: 'pi pi-fw pi-calendar-plus',
                to: '/leave-requests'
            },
            {
                label: 'Phê duyệt',
                icon: 'pi pi-fw pi-check-circle',
                to: '/leave-approvals',
                badge: 'pending'  // Will be replaced with actual count
            }
        ]
    });

    const items = [
        {
            label: t('nav.home'),
            items: homeItems
        },
    ];

    // Build System menu based on permissions
    const systemMenuItems = [];

    // Users menu - check permission
    if (canViewUsers()) {
        systemMenuItems.push({
            label: t('nav.users'),
            icon: 'pi pi-fw pi-users',
            to: '/users'
        });
    }

    // Departments menu - check permission
    if (canViewDepartments()) {
        systemMenuItems.push({
            label: t('nav.departments'),
            icon: 'pi pi-fw pi-sitemap',
            items: [
                { label: 'Quản lý phòng/ban', icon: 'pi pi-fw pi-list-check', to: '/departments' },
                { label: 'Sơ đồ tổ chức', icon: 'pi pi-fw pi-share-alt', to: '/departments/org' }
            ]
        });
    }

    // Positions menu - check permission
    if (can('view positions')) {
        systemMenuItems.push({
            label: 'Chức vụ',
            icon: 'pi pi-fw pi-briefcase',
            to: '/positions'
        });
    }

    // Roles menu - check permission
    if (canViewRoles()) {
        systemMenuItems.push({
            label: t('nav.roles'),
            icon: 'pi pi-fw pi-lock',
            to: '/roles'
        });
    }

    // Province menu - check permission
    if (can('view provinces')) {
        systemMenuItems.push({
            label: 'Tỉnh/Thành phố',
            icon: 'pi pi-fw pi-map',
            to: '/provinces'
        });
    }

    // Ward menu - check permission
    if (can('view wards')) {
        systemMenuItems.push({
            label: 'Phường/Xã',
            icon: 'pi pi-fw pi-map-marker',
            to: '/wards'
        });
    }

    // Education Levels - check permission
    if (can('view education levels')) {
        systemMenuItems.push({
            label: 'Trình độ học vấn',
            icon: 'pi pi-fw pi-book',
            to: '/education-levels'
        });
    }

    // Schools - check permission
    if (can('view schools')) {
        systemMenuItems.push({
            label: 'Trường học',
            icon: 'pi pi-fw pi-building',
            to: '/schools'
        });
    }

    // Contract Templates - check permission
    if (can('view contract templates')) {
        systemMenuItems.push({
            label: 'Mẫu hợp đồng',
            icon: 'pi pi-fw pi-file',
            to: '/contract-templates'
        });
    }

    // Appendix Templates - check permission
    if (can('view appendix templates')) {
        systemMenuItems.push({
            label: 'Mẫu phụ lục',
            icon: 'pi pi-fw pi-file-edit',
            to: '/contract-appendix-templates'
        });
    }

    // Backup menu - check permission
    if (canViewBackups()) {
        systemMenuItems.push({
            label: 'Backup & Bảo trì',
            icon: 'pi pi-fw pi-shield',
            items: [
                { label: 'Backup thủ công', icon: 'pi pi-fw pi-download', to: '/backup' },
                { label: 'Auto Backup', icon: 'pi pi-fw pi-clock', to: '/backup/configurations' }
            ]
        });
    }

    // Activity logs - check permission
    if (can('view activity logs')) {
        systemMenuItems.push({
            label: 'Nhật ký hoạt động',
            icon: 'pi pi-fw pi-list',
            to: '/activity-logs'
        });
    }

    // Only add System menu if user has at least one permission
    if (systemMenuItems.length > 0) {
        items.push({
            label: t('nav.system'),
            items: systemMenuItems
        });
    }

    return items;
});
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
