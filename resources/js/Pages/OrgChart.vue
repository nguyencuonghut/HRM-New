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
                <Badge :value="getTotalHeadcount(node)" severity="info" />

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
                  <div class="font-medium">{{ getTotalHeadcount(current) }}</div>
                </div>
              </div>

              <div class="mt-4">
                <Button icon="pi pi-pencil" label="Quản lý đơn vị" class="mr-2"
                        @click="$inertia.visit(route('departments.index'), { preserveState:true })" />
                <!-- có thể thêm: Tạo đơn vị con, Gán Trưởng/Phó... tuỳ quyền -->
              </div>
            </template>
          </Card>

          <!-- Organization Chart cho nhân viên -->
          <Card v-if="current" class="mt-4">
            <template #title>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <i class="pi pi-users text-blue-600"></i>
                  <span class="font-semibold">Nhân viên - {{ current.label }}</span>
                  <Badge :value="currentEmployees.length" severity="info" />
                </div>
                <Button icon="pi pi-refresh"
                        label="Tải lại"
                        text
                        size="small"
                        :loading="loadingEmployees"
                        @click="loadDepartmentEmployees" />
              </div>
            </template>
            <template #content>
              <div v-if="loadingEmployees" class="text-center py-8">
                <i class="pi pi-spinner pi-spin text-2xl text-blue-600"></i>
                <p class="text-gray-500 mt-2">Đang tải danh sách nhân viên...</p>
              </div>

              <div v-else-if="currentEmployees.length === 0" class="text-center py-8">
                <i class="pi pi-user-plus text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-700 mb-2">Chưa có nhân viên</h3>
                <p class="text-gray-500">Đơn vị này chưa có nhân viên nào được phân công</p>
              </div>

              <div v-else-if="employeeChartData" class="org-chart-container">
                <OrganizationChart :value="employeeChartData"
                                  class="org-chart-custom">
                  <template #person="slotProps">
                    <div class="employee-card bg-white border border-gray-200 rounded-lg p-3 min-w-[160px] max-w-[200px]">
                      <div class="text-center">
                        <div class="font-semibold text-gray-800 text-sm truncate">{{ slotProps.node.data.name }}</div>
                        <div class="text-xs text-gray-600 mt-1 truncate">{{ slotProps.node.data.position }}</div>
                        <div class="text-xs text-blue-600 mt-1">{{ slotProps.node.data.role }}</div>
                        <div v-if="slotProps.node.data.department" class="text-xs text-gray-500 mt-1 truncate">
                          {{ slotProps.node.data.department }}
                        </div>
                      </div>
                    </div>
                  </template>
                </OrganizationChart>
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
import { ref, onMounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import OrganizationChart from 'primevue/organizationchart'
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
const currentEmployees = ref([])
const loadingEmployees = ref(false)

// Computed để tạo data cho OrganizationChart với hierarchy theo department
const employeeChartData = computed(() => {
  if (currentEmployees.value.length === 0) {
    return null
  }

  // REAL DATA: Tạo cấu trúc thực từ dữ liệu nhân viên
  if (currentEmployees.value.length >= 2) {
    // Nhóm nhân viên theo department
    const employeesByDept = {}

    currentEmployees.value.forEach(emp => {
      const deptName = emp.department_name
      if (!employeesByDept[deptName]) {
        employeesByDept[deptName] = {
          department_name: deptName,
          employees: []
        }
      }
      employeesByDept[deptName].employees.push(emp)
    })

    // Nếu chỉ có 1 department, dùng buildSingleDepartmentChart
    const deptCount = Object.keys(employeesByDept).length
    if (deptCount === 1) {
      const singleDept = Object.values(employeesByDept)[0]
      const realData = buildSingleDepartmentChart(singleDept.employees)
      return realData
    } else {
      // Có nhiều departments, dùng buildMultiDepartmentChart
      const realData = buildMultiDepartmentChart(employeesByDept, current.value?.key)
      return realData
    }
  }  // Fallback: single employee
  if (currentEmployees.value.length === 1) {
    const fallbackData = {
      key: currentEmployees.value[0].id,
      type: 'person',
      data: {
        name: currentEmployees.value[0].full_name,
        position: currentEmployees.value[0].position_name || 'Nhân viên',
        role: getRoleLabel(currentEmployees.value[0].role_type)
      }
    }

    return fallbackData
  }

  // Multiple employees without hierarchy
  const fallbackData = {
    key: 'department_root',
    type: 'person',
    data: {
      name: current.value?.label || 'Đơn vị',
      position: 'Đơn vị',
      role: 'Department'
    },
    children: currentEmployees.value.map(emp => ({
      key: emp.id,
      type: 'person',
      data: {
        name: emp.full_name,
        position: emp.position_name || 'Nhân viên',
        role: getRoleLabel(emp.role_type)
      }
    }))
  }

  return fallbackData
})// Function xây dựng chart cho 1 department
function buildSingleDepartmentChart(employees) {
  const head = employees.find(emp => emp.role_type === 'HEAD')

  if (!head) {
    // Không có trưởng, tạo node department làm root
    return {
      key: 'dept_root',
      type: 'person',
      data: {
        name: current.value?.label || 'Đơn vị',
        position: 'Đơn vị',
        role: 'Department'
      },
      children: employees.map(emp => ({
        key: emp.id,
        type: 'person',
        data: {
          name: emp.full_name,
          position: emp.position_name || 'Nhân viên',
          role: getRoleLabel(emp.role_type),
          department: emp.department_name
        }
      }))
    }
  }

  // Có trưởng, tạo cấu trúc phân cấp
  const children = employees
    .filter(emp => emp.role_type !== 'HEAD')
    .map(emp => ({
      key: emp.id,
      type: 'person',
      data: {
        name: emp.full_name,
        position: emp.position_name || 'Nhân viên',
        role: getRoleLabel(emp.role_type),
        department: emp.department_name
      }
    }))

  return {
    key: head.id,
    type: 'person',
    data: {
      name: head.full_name,
      position: head.position_name || 'Trưởng đơn vị',
      role: getRoleLabel(head.role_type),
      department: head.department_name
    },
    children: children
  }
}

// Function xây dựng chart cho nhiều department với hierarchy
function buildMultiDepartmentChart(employeesByDept, mainDeptId) {
  const deptNodes = []

  Object.entries(employeesByDept).forEach(([deptId, deptData]) => {
    const employees = deptData.employees
    const deptName = deptData.department_name

    // Tìm trưởng đơn vị của department này
    const head = employees.find(emp => emp.role_type === 'HEAD')

    if (head) {
      // Có trưởng đơn vị
      const children = employees
        .filter(emp => emp.role_type !== 'HEAD')
        .map(emp => ({
          key: emp.id,
          type: 'person',
          data: {
            name: emp.full_name,
            position: emp.position_name || 'Nhân viên',
            role: getRoleLabel(emp.role_type),
            department: deptName
          }
        }))

      deptNodes.push({
        key: head.id,
        type: 'person',
        data: {
          name: head.full_name,
          position: head.position_name || 'Trưởng đơn vị',
          role: getRoleLabel(head.role_type),
          department: deptName,
          isDepartmentHead: true
        },
        children: children
      })
    } else {
      // Không có trưởng, tạo node department
      const empNodes = employees.map(emp => ({
        key: emp.id,
        type: 'person',
        data: {
          name: emp.full_name,
          position: emp.position_name || 'Nhân viên',
          role: getRoleLabel(emp.role_type),
          department: deptName
        }
      }))

      deptNodes.push({
        key: `dept_${deptId}`,
        type: 'department',
        data: {
          name: deptName,
          position: 'Đơn vị',
          role: 'Department',
          department: deptName
        },
        children: empNodes
      })
    }
  })

  // Tìm department chính (được click) để làm root
  const mainDeptName = current.value?.label || 'Tổ chức'
  const mainNode = deptNodes.find(node =>
    node.data.department === mainDeptName ||
    node.key === mainDeptId
  )

  if (mainNode && deptNodes.length > 1) {
    // Có department chính, đặt làm root với các department khác làm children
    const otherNodes = deptNodes.filter(node => node !== mainNode)
    return {
      ...mainNode,
      children: [...(mainNode.children || []), ...otherNodes]
    }
  } else if (deptNodes.length > 1) {
    // Không tìm thấy department chính, tạo root ảo
    return {
      key: 'org_root',
      type: 'department',
      data: {
        name: mainDeptName,
        position: 'Tổ chức',
        role: 'Organization',
        department: mainDeptName
      },
      children: deptNodes
    }
  }

  // Chỉ có 1 department, trả về node đầu tiên
  return deptNodes[0] || null
}// Function để lấy label cho role
function getRoleLabel(roleType) {
  switch (roleType) {
    case 'HEAD': return 'Trưởng đơn vị'
    case 'DEPUTY': return 'Phó đơn vị'
    case 'MEMBER': return 'Thành viên'
    default: return 'Nhân viên'
  }
}

// Function để tính tổng nhân viên đệ quy bao gồm cả node con
function getTotalHeadcount(node) {
  if (!node) return 0

  // Backend đã tính sẵn headcount bao gồm cả descendants
  return node.data.headcount || 0
}function typeLabel(v) {
  switch (v) {
    case 'DEPARTMENT': return 'Phòng/Ban'
    case 'UNIT': return 'Bộ phận'
    case 'TEAM': return 'Nhóm'
    default: return v
  }
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

  // Load employees for selected department
  loadDepartmentEmployees()
}// Function to load employees for current department
async function loadDepartmentEmployees() {
  if (!current.value) {
    return
  }

  loadingEmployees.value = true

  try {
    // Call API to get employees of the department
    const url = `/departments/${current.value.key}/employees`

    const response = await fetch(url)

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()

    // Handle both old format (array) and new format (object with employees property)
    const employees = Array.isArray(data) ? data : data.employees || []

    currentEmployees.value = employees

  } catch (error) {
    console.error('Error loading employees:', error)
    currentEmployees.value = []
  } finally {
    loadingEmployees.value = false
  }
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
/* Organization Chart Container */
.org-chart-container {
  width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  padding: 20px 0;
  min-height: 400px;
}

/* Organization Chart Styling */
:deep(.org-chart-custom) {
  width: 100%;
  min-width: fit-content;
}

:deep(.org-chart-custom .p-organizationchart-table) {
  margin: 0 auto;
  width: auto;
  min-width: fit-content;
}

:deep(.org-chart-custom .p-organizationchart-node) {
  padding: 0.5rem;
  white-space: nowrap;
}

:deep(.org-chart-custom .p-organizationchart-node-content) {
  border: none;
  background: transparent;
  padding: 0;
}

/* Connection Lines */
:deep(.org-chart-custom .p-organizationchart-line-down) {
  background: #3b82f6;
  width: 2px;
}

:deep(.org-chart-custom .p-organizationchart-line-left),
:deep(.org-chart-custom .p-organizationchart-line-right) {
  border-color: #3b82f6;
  border-width: 2px;
}

:deep(.org-chart-custom .p-organizationchart-line-top) {
  border-color: #3b82f6;
  border-width: 2px;
}

/* Employee Card Styling */
.employee-card {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.employee-card:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .org-chart-container {
    padding: 10px 0;
  }

  .employee-card {
    min-width: 140px;
    max-width: 160px;
  }

  :deep(.org-chart-custom .p-organizationchart-node) {
    padding: 0.25rem;
  }
}
</style>
