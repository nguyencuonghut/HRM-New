<template>
  <Head>
    <title>Sơ đồ tổ chức</title>
  </Head>

  <div>
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <div class="flex gap-2 items-center">
            <Button icon="pi pi-plus" label="Mở rộng tất cả" text @click="expandAll" :loading="expandingAll" />
            <Button icon="pi pi-minus" label="Thu gọn tất cả" text @click="collapseAll" />
            <Button icon="pi pi-search" label="Load toàn bộ" text @click="loadAllForSearch" :loading="loadingAll"
                    v-tooltip="'Load tất cả nodes để có thể tìm kiếm trong toàn bộ cây'" />
          </div>
        </template>
      </Toolbar>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Trái: Cây -->
        <div class="lg:col-span-1">
          <div class="mb-2 text-sm text-gray-600">
            <i class="pi pi-info-circle mr-1"></i>
            Tìm kiếm chỉ trong nodes đã được load. Click "Load toàn bộ" để tìm kiếm sâu hơn.
          </div>
          <Tree
            :value="nodes"
            :loading="loading"
            selectionMode="single"
            v-model:selectionKeys="selectedKey"
            :filter="true"
            filterMode="custom"
            :filterFunction="customFilter"
            filterPlaceholder="Tìm kiếm trong cây tổ chức..."
            :expandedKeys="expandedKeys"
            @nodeExpand="onExpand"
            @nodeSelect="onSelect"
            class="border rounded"
          >
            <template #node="{ node }">
              <div class="flex items-center gap-2">
                <Tag :value="typeLabel(node.data.type)"
                     :style="getTagStyle(node.data.type)" />
                <span class="font-medium">{{ node.label }}</span>
                <Badge :value="node.data.headcount" severity="info" />

                <!-- Status indicator -->
                <i v-if="node.data.is_active"
                   class="pi pi-circle-fill !text-green-500 text-xs"
                   title="Đang hoạt động"></i>
                <i v-else
                   class="pi pi-circle-fill !text-red-400 text-xs"
                   title="Ngừng hoạt động"></i>
              </div>
              <div class="text-xs text-gray-500 ml-8" v-if="node.data.head || node.data.deputy">
                <span v-if="node.data.head"><b>Trưởng:</b> {{ node.data.head }}</span>
                <span v-if="node.data.deputy" class="ml-2"><b>Phó:</b> {{ node.data.deputy }}</span>
              </div>
            </template>
          </Tree>
        </div>

        <!-- Phải: Chi tiết -->
        <div class="lg:col-span-2">
          <Card v-if="current">
            <template #title>
              <div class="flex items-center gap-2">
                <Tag :value="typeLabel(current.data.type)"
                     :style="getTagStyle(current.data.type)" />
                <span class="font-semibold">{{ current.label }}</span>
              </div>
            </template>
            <template #content>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <span class="text-sm text-gray-500">Mã đơn vị</span>
                  <div class="font-medium">{{ current.data.code || '-' }}</div>
                </div>
                <div>
                  <span class="text-sm text-gray-500">Trạng thái</span>
                  <div>
                    <Badge :value="current.data.is_active ? 'Đang hoạt động' : 'Ngừng hoạt động'"
                           :severity="current.data.is_active ? 'success' : 'danger'" />
                  </div>
                </div>
                <div>
                  <span class="text-sm text-gray-500">Trưởng đơn vị</span>
                  <div class="font-medium">{{ current.data.head || '-' }}</div>
                </div>
                <div>
                  <span class="text-sm text-gray-500">Phó đơn vị</span>
                  <div class="font-medium">{{ current.data.deputy || '-' }}</div>
                </div>
                <div>
                  <span class="text-sm text-gray-500">Nhân sự (ACTIVE)</span>
                  <div class="font-medium">{{ current.data.headcount }}</div>
                </div>
              </div>

              <div class="mt-4">
                <Button icon="pi pi-pencil" label="Quản lý đơn vị" class="mr-2"
                        @click="$inertia.visit(route('departments.index'), { preserveState:true })" />
                <!-- có thể thêm: Tạo đơn vị con, Gán Trưởng/Phó... tuỳ quyền -->
              </div>
            </template>
          </Card>
          <div v-else class="text-gray-500">Chọn một đơn vị ở cây bên trái để xem chi tiết.</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import Select from 'primevue/select'
import { OrgService } from '@/services'

// Props from Controller
const props = defineProps({
  roots: { type: Array, default: () => [] }
})

const nodes = ref([])
const loading = ref(false)
const expandingAll = ref(false)
const loadingAll = ref(false)
const expandedKeys = ref({})
const selectedKey = ref(null)
const current = ref(null)

const typeFilter = ref(null)
const typeOptions = [
  { value: 'DEPARTMENT', label: 'Phòng/Ban' },
  { value: 'UNIT', label: 'Bộ phận' },
  { value: 'TEAM', label: 'Nhóm' },
]

function typeLabel(v) {
  const found = typeOptions.find(x => x.value === v)
  return found ? found.label : v
}

// Function to get tag inline style based on type
function getTagStyle(type) {
  switch (type) {
    case 'DEPARTMENT':
      return {
        backgroundColor: '#3b82f6',
        color: 'white',
        border: 'none'
      }
    case 'UNIT':
      return {
        backgroundColor: '#8b5cf6',
        color: 'white',
        border: 'none'
      }
    case 'TEAM':
      return {
        backgroundColor: '#10b981',
        color: 'white',
        border: 'none'
      }
    default:
      return {
        backgroundColor: '#6b7280',
        color: 'white',
        border: 'none'
      }
  }
}

// Function to check if a node has children
function hasChildren(node) {
  // Nếu node đã được expand và có children
  if (node.children && Array.isArray(node.children) && node.children.length > 0) {
    return true
  }

  // Nếu node đã được expand nhưng không có children (leaf = true)
  if (node.leaf === true) {
    return false
  }

  // Nếu chưa expand nhưng backend set leaf = false (có khả năng có con)
  if (node.leaf === false) {
    return true
  }

  // Fallback: dự đoán theo type nếu chưa có thông tin leaf
  if (node.data && node.data.type) {
    // DEPARTMENT thường có con, TEAM thường không có con
    return node.data.type === 'DEPARTMENT' || node.data.type === 'UNIT'
  }

  return false
}

// Custom filter function to search across multiple fields
function customFilter(node, filterValue) {
  if (!filterValue || filterValue.trim() === '') {
    return true // Show all nodes when no filter
  }

  const searchTerm = filterValue.toLowerCase()

  // Search in current node fields
  const fieldsToSearch = [
    node.label,
    node.data?.code,
    node.data?.head,
    node.data?.deputy
  ]

  const currentNodeMatches = fieldsToSearch.some(field =>
    field && field.toString().toLowerCase().includes(searchTerm)
  )

  // If current node matches, show it
  if (currentNodeMatches) {
    return true
  }

  // If node has children loaded, recursively search them
  if (node.children && Array.isArray(node.children) && node.children.length > 0) {
    const hasMatchingChild = node.children.some(child =>
      customFilter(child, filterValue)
    )

    if (hasMatchingChild) {
      // Auto-expand parent if child matches
      expandedKeys.value = { ...expandedKeys.value, [node.key]: true }
      return true
    }
  }

  return false
}

async function loadRoots() {
  loading.value = true
  try {
    // Use props first if available, otherwise fetch from API
    if (props.roots && props.roots.length > 0) {
      nodes.value = processNodesForLeafStatus(props.roots)
    } else {
      const rootNodes = await OrgService.roots()
      nodes.value = processNodesForLeafStatus(rootNodes)
    }
  } catch (error) {
    console.error('Error loading roots:', error)
    // Fallback to empty array on error
    nodes.value = []
  } finally {
    loading.value = false
  }
}

// Function to process nodes and set leaf status based on business logic
function processNodesForLeafStatus(nodeList) {
  return nodeList.map(node => {
    // Set leaf status based on whether department has children
    // A department is a leaf (no expand icon) if it has no children
    if (node.data && node.data.children_count !== undefined) {
      // If backend provides children_count, use it
      node.leaf = node.data.children_count === 0
    } else if (node.children && Array.isArray(node.children)) {
      // If children array is already loaded, check its length
      node.leaf = node.children.length === 0
    } else {
      // If no children info available, check if already has leaf property from backend
      // If not set, assume it may have children (leaf = false) - will be updated when expanded
      node.leaf = node.leaf !== undefined ? node.leaf : false
    }

    return node
  })
}

async function onExpand(node) {
  try {
    // The node is passed directly, not as event.node
    if (!node) {
      console.error('Invalid node in onExpand:', node)
      return
    }

    // Check if node has a key
    if (!node.key) {
      console.error('Node does not have a key:', node)
      return
    }


    // Use the correct method name from OrgService
    const kids = await OrgService.children(node.key)

    // Process children to set correct leaf status
    const processedChildren = processNodesForLeafStatus(kids)

    // Set children to the node
    node.children = processedChildren

    // Update leaf status based on whether it actually has children
    node.leaf = !kids || kids.length === 0

    // Update expanded keys
    expandedKeys.value = { ...expandedKeys.value, [node.key]: true }
  } catch (error) {
    console.error('Error in onExpand:', error)
    // Set as leaf if error occurs (assume no children)
    node.leaf = true
    // Handle the error gracefully, maybe show a toast message
  }
}

function onSelect(node) {
  // The node is passed directly, not as event.node
  if (!node) {
    console.error('Invalid node in onSelect:', node)
    return
  }

  current.value = node
}

async function expandAll() {
  expandingAll.value = true
  const map = {}

  // Recursive function to expand all nodes and load their children
  const expandNode = async (node) => {
    if (!node || !node.key) return

    // Add to expanded keys
    map[node.key] = true

    // If node doesn't have children loaded yet and is not a leaf, load them
    if (!node.children && !node.leaf) {
      try {
        const kids = await OrgService.children(node.key)
        const processedChildren = processNodesForLeafStatus(kids)
        node.children = processedChildren
        node.leaf = !kids || kids.length === 0

        // Recursively expand children
        if (processedChildren && processedChildren.length > 0) {
          for (const child of processedChildren) {
            await expandNode(child)
          }
        }
      } catch (error) {
        console.error('Error loading children for node:', node.key, error)
        node.leaf = true
      }
    } else if (node.children && Array.isArray(node.children)) {
      // If children already loaded, recursively expand them
      for (const child of node.children) {
        await expandNode(child)
      }
    }
  }

  // Start expansion from all root nodes
  try {
    for (const node of nodes.value) {
      await expandNode(node)
    }
    expandedKeys.value = map
  } catch (error) {
    console.error('Error in expandAll:', error)
    // Still update expanded keys for what we managed to expand
    expandedKeys.value = map
  } finally {
    expandingAll.value = false
  }
}

function collapseAll() {
  expandedKeys.value = {}
}

async function loadAllForSearch() {
  loadingAll.value = true

  // Recursive function to load all children without expanding
  const loadNode = async (node) => {
    if (!node || !node.key || node.leaf) return

    // If node doesn't have children loaded yet, load them
    if (!node.children) {
      try {
        const kids = await OrgService.children(node.key)
        const processedChildren = processNodesForLeafStatus(kids)
        node.children = processedChildren
        node.leaf = !kids || kids.length === 0

        // Recursively load children
        if (processedChildren && processedChildren.length > 0) {
          for (const child of processedChildren) {
            await loadNode(child)
          }
        }
      } catch (error) {
        console.error('Error loading children for node:', node.key, error)
        node.leaf = true
      }
    } else if (node.children && Array.isArray(node.children)) {
      // If children already loaded, recursively load their children
      for (const child of node.children) {
        await loadNode(child)
      }
    }
  }

  // Start loading from all root nodes
  try {
    for (const node of nodes.value) {
      await loadNode(node)
    }
  } catch (error) {
    console.error('Error in loadAllForSearch:', error)
  } finally {
    loadingAll.value = false
  }
}

onMounted(loadRoots)
</script>

<style scoped>
/* Cải thiện styling cho Tree component */
:deep(.p-tree) {
  border: none;
}

/* Styling cho tree nodes */
:deep(.p-tree-node-content) {
  border-radius: 6px;
  transition: all 0.2s;
}

:deep(.p-tree-node-content:hover) {
  background-color: rgba(59, 130, 246, 0.1);
}

/* Styling cho selected node */
:deep(.p-tree-node-content.p-tree-node-selectable.p-tree-node-selected) {
  background-color: rgba(59, 130, 246, 0.2) !important;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

/* Cải thiện icon toggle */
:deep(.p-tree-toggler) {
  color: #3b82f6 !important;
  border-radius: 50%;
  transition: all 0.2s;
}

:deep(.p-tree-toggler:hover) {
  background-color: rgba(59, 130, 246, 0.1) !important;
  color: #1d4ed8 !important;
}

/* Status indicators với màu rõ ràng hơn */
.pi-circle-fill {
  font-size: 8px !important;
}
</style>
