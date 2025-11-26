<template>
  <div class="notification-bell">
    <Button
      icon="pi pi-bell"
      :badge="unreadCount > 0 ? String(unreadCount) : undefined"
      badgeSeverity="danger"
      text
      rounded
      @click="togglePanel"
    />

    <Popover ref="notificationPanel" :style="{ width: '400px', maxHeight: '500px' }">
      <div class="notification-panel">
        <div class="flex items-center justify-between mb-3 pb-2 border-b">
          <h3 class="text-lg font-semibold">Thông báo</h3>
          <Button
            v-if="unreadCount > 0"
            label="Đánh dấu tất cả đã đọc"
            text
            size="small"
            @click="markAllAsRead"
          />
        </div>

        <div v-if="loading" class="text-center py-8">
          <i class="pi pi-spin pi-spinner text-2xl"></i>
        </div>

        <div v-else-if="notifications.length === 0" class="text-center py-8 text-gray-500">
          <i class="pi pi-inbox text-3xl mb-2"></i>
          <p>Không có thông báo</p>
        </div>

        <div v-else class="notification-list" style="max-height: 400px; overflow-y: auto;">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="notification-item p-3 border-b hover:bg-gray-50 cursor-pointer transition-colors"
            :class="{ 'bg-blue-50': !notification.read_at }"
            @click="handleNotificationClick(notification)"
          >
            <div class="flex items-start gap-3">
              <div class="notification-icon mt-1">
                <i :class="getNotificationIcon(notification.data.type)" class="text-xl"></i>
              </div>
              <div class="flex-1">
                <div class="font-semibold text-sm mb-1">
                  {{ notification.data.message }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ formatDate(notification.created_at) }}
                </div>
              </div>
              <Button
                v-if="!notification.read_at"
                icon="pi pi-check"
                text
                rounded
                size="small"
                severity="success"
                @click.stop="markAsRead(notification.id)"
              />
            </div>
          </div>
        </div>
      </div>
    </Popover>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Popover from 'primevue/popover'
import axios from 'axios'

const notificationPanel = ref(null)
const notifications = ref([])
const unreadCount = ref(0)
const loading = ref(false)
let pollingInterval = null

onMounted(() => {
  loadNotifications()
  // Polling mỗi 30s để cập nhật notifications
  pollingInterval = setInterval(loadNotifications, 30000)
})

onUnmounted(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
  }
})

function togglePanel(event) {
  notificationPanel.value.toggle(event)
  if (!notifications.value.length) {
    loadNotifications()
  }
}

async function loadNotifications() {
  try {
    loading.value = true
    const response = await axios.get('/notifications')
    notifications.value = response.data.notifications
    unreadCount.value = response.data.unread_count
  } catch (error) {
    console.error('Failed to load notifications:', error)
  } finally {
    loading.value = false
  }
}

async function markAsRead(notificationId) {
  try {
    await axios.post(`/notifications/${notificationId}/mark-as-read`)
    await loadNotifications()
  } catch (error) {
    console.error('Failed to mark notification as read:', error)
  }
}

async function markAllAsRead() {
  try {
    await axios.post('/notifications/mark-all-as-read')
    await loadNotifications()
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error)
  }
}

function handleNotificationClick(notification) {
  // Đánh dấu đã đọc
  if (!notification.read_at) {
    markAsRead(notification.id)
  }

  // Navigate đến trang liên quan
  if (notification.data.action_url) {
    router.visit(notification.data.action_url)
    notificationPanel.value.hide()
  }
}

function getNotificationIcon(type) {
  const icons = {
    contract_approval_requested: 'pi pi-send text-yellow-600',
    contract_approved: 'pi pi-check-circle text-green-600',
    contract_rejected: 'pi pi-times-circle text-red-600',
  }
  return icons[type] || 'pi pi-bell text-blue-600'
}

function formatDate(dateString) {
  const date = new Date(dateString)
  const now = new Date()
  const diff = now - date

  // Dưới 1 phút
  if (diff < 60000) {
    return 'Vừa xong'
  }

  // Dưới 1 giờ
  if (diff < 3600000) {
    const minutes = Math.floor(diff / 60000)
    return `${minutes} phút trước`
  }

  // Dưới 1 ngày
  if (diff < 86400000) {
    const hours = Math.floor(diff / 3600000)
    return `${hours} giờ trước`
  }

  // Dưới 7 ngày
  if (diff < 604800000) {
    const days = Math.floor(diff / 86400000)
    return `${days} ngày trước`
  }

  // Format đầy đủ
  return date.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
.notification-bell {
  position: relative;
}

.notification-panel {
  min-width: 400px;
}

.notification-list {
  scrollbar-width: thin;
}

.notification-list::-webkit-scrollbar {
  width: 6px;
}

.notification-list::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.notification-list::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

.notification-list::-webkit-scrollbar-thumb:hover {
  background: #555;
}

.notification-item:last-child {
  border-bottom: none;
}
</style>
