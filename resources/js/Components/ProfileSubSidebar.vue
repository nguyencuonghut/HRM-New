<template>
  <!-- Mobile Overlay -->
  <div
    v-if="isMobileMenuOpen"
    class="mobile-overlay"
    @click="closeMobileMenu"
  ></div>

  <div class="profile-sub-sidebar" :class="{ collapsed: isCollapsed, 'mobile-open': isMobileMenuOpen }">
    <!-- Toggle Button -->
    <button
      class="collapse-toggle"
      @click="toggleCollapse"
      :title="isCollapsed ? 'Mở rộng' : 'Thu gọn'"
    >
      <i :class="isCollapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'"></i>
    </button>

    <!-- Mobile Close Button -->
    <button class="mobile-close" @click="closeMobileMenu">
      <i class="pi pi-times"></i>
    </button>

    <!-- Navigation Groups -->
    <nav class="nav-groups">
      <div
        v-for="group in navigationGroups"
        :key="group.label"
        class="nav-group"
      >
        <div class="group-label" v-if="!isCollapsed">
          {{ group.label }}
        </div>
        <div class="group-items">
          <button
            v-for="item in group.items"
            :key="item.value"
            class="nav-item"
            :class="{ active: modelValue === item.value }"
            @click="handleNavClick(item.value)"
            :title="item.label"
          >
            <i :class="['nav-icon', item.icon]"></i>
            <span v-if="!isCollapsed" class="nav-label">{{ item.label }}</span>
          </button>
        </div>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: 'education'
  }
})

const emit = defineEmits(['update:modelValue', 'update:collapsed'])

const isCollapsed = ref(false)
const isMobileMenuOpen = ref(false)

const navigationGroups = [
  {
    label: 'THÔNG TIN CÁ NHÂN',
    items: [
      { value: 'education', label: 'Học vấn', icon: 'pi pi-book' },
      { value: 'relatives', label: 'Người thân', icon: 'pi pi-users' },
      { value: 'experiences', label: 'Kinh nghiệm', icon: 'pi pi-briefcase' },
      { value: 'skills', label: 'Kỹ năng', icon: 'pi pi-star' }
    ]
  },
  {
    label: 'CÔNG VIỆC',
    items: [
      { value: 'assignments', label: 'Phân công', icon: 'pi pi-sitemap' },
      { value: 'contracts', label: 'Hợp đồng', icon: 'pi pi-file-edit' },
      { value: 'payroll', label: 'Lương hiện tại', icon: 'pi pi-wallet' },
      { value: 'leave-balances', label: 'Số dư phép', icon: 'pi pi-calendar-times' }
    ]
  },
  {
    label: 'BÁO CÁO & LỊCH SỬ',
    items: [
      { value: 'employment-history', label: 'Lịch sử làm việc', icon: 'pi pi-history' },
      { value: 'timeline', label: 'Nhật ký hoạt động', icon: 'pi pi-list' }
    ]
  }
]

function toggleCollapse() {
  isCollapsed.value = !isCollapsed.value
  emit('update:collapsed', isCollapsed.value)
}

function handleNavClick(value) {
  emit('update:modelValue', value)
  // Close mobile menu after selection
  if (window.innerWidth < 1024) {
    closeMobileMenu()
  }
}

function openMobileMenu() {
  isMobileMenuOpen.value = true
}

function closeMobileMenu() {
  isMobileMenuOpen.value = false
}

// Expose for parent component
defineExpose({
  openMobileMenu,
  closeMobileMenu
})
</script>

<style scoped>
.mobile-overlay {
  display: none;
}

.profile-sub-sidebar {
  background: white;
  border-right: 1px solid #e5e7eb;
  height: 100%;
  transition: width 0.3s ease;
  width: 220px;
  position: relative;
  display: flex;
  flex-direction: column;
}

.profile-sub-sidebar.collapsed {
  width: 60px;
}

.mobile-close {
  display: none;
}

.collapse-toggle {
  position: absolute;
  top: 12px;
  right: -12px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: white;
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: all 0.2s;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.collapse-toggle:hover {
  background: #f3f4f6;
  transform: scale(1.1);
}

.collapse-toggle i {
  font-size: 12px;
  color: #6b7280;
}

.nav-groups {
  flex: 1;
  overflow-y: auto;
  padding: 16px 0;
}

.nav-group {
  margin-bottom: 24px;
}

.nav-group:last-child {
  margin-bottom: 0;
}

.group-label {
  font-size: 11px;
  font-weight: 700;
  color: #9ca3af;
  letter-spacing: 0.5px;
  padding: 8px 16px;
  text-transform: uppercase;
  transition: opacity 0.3s;
}

.collapsed .group-label {
  opacity: 0;
  height: 0;
  padding: 0;
  overflow: hidden;
}

.group-items {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  border: none;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s;
  color: #4b5563;
  font-size: 14px;
  text-align: left;
  border-left: 3px solid transparent;
  position: relative;
}

.collapsed .nav-item {
  justify-content: center;
  padding: 10px 0;
}

.nav-item:hover {
  background: #f9fafb;
  color: #1f2937;
}

.nav-item.active {
  background: rgba(59, 130, 246, 0.08);
  border-left-color: #3b82f6;
  color: #3b82f6;
  font-weight: 600;
}

.nav-icon {
  font-size: 16px;
  flex-shrink: 0;
  width: 20px;
  text-align: center;
}

.nav-label {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: opacity 0.3s;
}

.collapsed .nav-label {
  opacity: 0;
  width: 0;
  overflow: hidden;
}

/* Scrollbar styling */
.nav-groups::-webkit-scrollbar {
  width: 4px;
}

.nav-groups::-webkit-scrollbar-track {
  background: transparent;
}

.nav-groups::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 2px;
}

.nav-groups::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Responsive */
@media (max-width: 1024px) {
  .mobile-overlay {
    display: block;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 39;
    animation: fadeIn 0.2s ease;
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  .profile-sub-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    z-index: 40;
    width: 280px;
    box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
    transform: translateX(-100%);
    transition: transform 0.3s ease;
  }

  .profile-sub-sidebar.mobile-open {
    transform: translateX(0);
  }

  .collapse-toggle {
    display: none;
  }

  .mobile-close {
    display: flex;
    position: absolute;
    top: 12px;
    right: 12px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f3f4f6;
    border: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
  }

  .mobile-close:hover {
    background: #e5e7eb;
  }

  .mobile-close i {
    font-size: 16px;
    color: #6b7280;
  }
}
</style>
