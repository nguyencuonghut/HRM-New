<template>
  <Head>
    <title>Quản lý Hợp đồng</title>
  </Head>

  <div>
    <div class="card">
      <Toolbar class="mb-6">
        <template #start>
          <Button label="Thêm mới" icon="pi pi-plus" class="mr-2" @click="openNew" />
          <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined" @click="confirmDeleteSelected" :disabled="!selected || !selected.length" />
        </template>
        <template #end>
          <Button label="Xuất dữ liệu" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
        </template>
      </Toolbar>

      <DataTable
        ref="dt"
        :value="rows"
        v-model:selection="selected"
        dataKey="id"
        :paginator="true"
        :rows="10"
        :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Hiển thị {first}–{last}/{totalRecords} hợp đồng"
      >
        <template #header>
          <div class="flex flex-wrap gap-2 items-center justify-between">
            <h4 class="m-0">Danh sách Hợp đồng</h4>
            <IconField>
              <InputIcon><i class="pi pi-search" /></InputIcon>
              <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
            </IconField>
          </div>
        </template>

        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
        <Column field="contract_number" header="Số HĐ" sortable headerStyle="min-width:10rem;">
            <template #body="sp">
                <a
                href="#"
                class="text-primary underline"
                @click.prevent="goToGeneral(sp.data)"
                >
                {{ sp.data.contract_number }}
                </a>
            </template>
        </Column>
        <Column field="employee_name" header="Nhân viên" headerStyle="min-width:14rem;">
          <template #body="sp">
            {{ sp.data.employee?.full_name }} ({{ sp.data.employee?.employee_code }})
          </template>
        </Column>
        <Column field="department_name" header="Đơn vị" headerStyle="min-width:12rem;">
          <template #body="sp">{{ sp.data.department?.name || '-' }}</template>
        </Column>
        <Column field="position_name" header="Chức danh" headerStyle="min-width:12rem;">
          <template #body="sp">{{ sp.data.position?.title || '-' }}</template>
        </Column>
        <Column field="contract_type_label" header="Loại HĐ" sortable headerStyle="min-width:10rem;" />
        <Column field="start_date" header="Bắt đầu" sortable headerStyle="min-width:10rem;">
          <template #body="sp">{{ formatDate(sp.data.start_date) }}</template>
        </Column>
        <Column field="end_date" header="Kết thúc" sortable headerStyle="min-width:10rem;">
          <template #body="sp">{{ formatDate(sp.data.end_date) || '—' }}</template>
        </Column>
        <Column field="status_label" header="Trạng thái" headerStyle="min-width:14rem;">
          <template #body="sp">
            <div class="flex items-center gap-2 flex-wrap">
              <Tag :value="sp.data.status_label" :severity="statusSeverity(sp.data.status)" />
              <Tag v-if="sp.data.approval_progress && sp.data.status === 'PENDING_APPROVAL'"
                   :value="`${sp.data.approval_progress.approved}/${sp.data.approval_progress.total}`"
                   severity="info"
                   v-tooltip="'Tiến trình phê duyệt'" />
              <Tag v-if="isExpiringSoon(sp.data)"
                   :value="`Còn ${getDaysUntilExpiry(sp.data)} ngày`"
                   :severity="expiryBadgeSeverity(getDaysUntilExpiry(sp.data))"
                   v-tooltip="'Hợp đồng sắp hết hạn!'"
                   icon="pi pi-clock" />
            </div>
          </template>
        </Column>
        <Column header="Tệp sinh ra" headerStyle="min-width:12rem;">
          <template #body="sp">
            <a v-if="sp.data.generated_pdf_path" :href="sp.data.generated_pdf_path" target="_blank" class="text-primary underline">Xem PDF</a>
            <span v-else>—</span>
          </template>
        </Column>
        <Column header="Thao tác" headerStyle="min-width:24rem;">
          <template #body="sp">
            <div class="flex gap-2">
              <!-- Actions for DRAFT status -->
              <template v-if="sp.data.status === 'DRAFT'">
                <Button icon="pi pi-pencil" outlined severity="success" rounded @click="edit(sp.data)" v-tooltip="'Chỉnh sửa'" />
                <Button icon="pi pi-trash" outlined severity="danger" rounded @click="confirmDelete(sp.data)" v-tooltip="'Xóa'" />
                <Button icon="pi pi-send" outlined severity="info" rounded @click="confirmSubmitForApproval(sp.data)" v-tooltip="'Gửi phê duyệt'" />
                <Button icon="pi pi-file" outlined rounded @click="openGenerate(sp.data)" v-tooltip="'Sinh PDF'" />
              </template>

              <!-- Actions for PENDING_APPROVAL status -->
              <template v-else-if="sp.data.status === 'PENDING_APPROVAL'">
                <Button icon="pi pi-check" outlined severity="success" rounded @click="openApproveDialog(sp.data)" v-tooltip="'Phê duyệt'" />
                <Button icon="pi pi-times" outlined severity="danger" rounded @click="openRejectDialog(sp.data)" v-tooltip="'Từ chối'" />
                <Button icon="pi pi-replay" outlined severity="warning" rounded @click="confirmRecall(sp.data)" v-tooltip="'Thu hồi'" />
              </template>

              <!-- Actions for ACTIVE status -->
              <template v-else-if="sp.data.status === 'ACTIVE'">
                <Button icon="pi pi-file" outlined rounded @click="openGenerate(sp.data)" v-tooltip="'Sinh PDF'" />
                <Button icon="pi pi-refresh" outlined severity="info" rounded @click="openRenewalDialog(sp.data)" v-tooltip="'Gia hạn HĐ'" />
                <Button icon="pi pi-ban" outlined severity="danger" rounded @click="openTerminateDialog(sp.data)" v-tooltip="'Chấm dứt HĐ'" />
              </template>

              <!-- Actions for TERMINATED status -->
              <template v-else-if="sp.data.status === 'TERMINATED'">
                <Button icon="pi pi-eye" outlined severity="contrast" rounded @click="goToGeneral(sp.data)" v-tooltip="'Xem chi tiết chấm dứt'" />
              </template>

              <!-- Common action -->
              <Button icon="pi pi-list" outlined rounded @click="goToAppendixes(sp.data)" v-tooltip="'Phụ lục'" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog tạo/sửa hợp đồng -->
    <Dialog v-model:visible="dialog" :style="{ width: '900px' }" header="Thông tin Hợp đồng" :modal="true">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-bold mb-2 required-field">Nhân viên</label>
          <Select v-model="form.employee_id" :options="employees" optionLabel="full_name" optionValue="id" filter showClear fluid
                  :invalid="submitted && !form.employee_id"
                  optionGroupLabel=""
                  :itemTemplate="empItemTmpl" />
          <small class="text-red-500" v-if="submitted && !form.employee_id">Nhân viên là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('employee_id')">{{ errors.employee_id }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Số HĐ</label>
          <InputText v-model.trim="form.contract_number" class="w-full" placeholder="VD: HĐ-2025-001" :invalid="submitted && !form.contract_number" />
          <small class="text-red-500" v-if="submitted && !form.contract_number">Số hợp đồng là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('contract_number')">{{ errors.contract_number }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Đơn vị</label>
          <Select v-model="form.department_id" :options="departments" optionLabel="name" optionValue="id" filter showClear fluid :invalid="submitted && !form.department_id" @change="onDepartmentChange" />
          <small class="text-red-500" v-if="submitted && !form.department_id">Đơn vị là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('department_id')">{{ errors.department_id }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Chức danh</label>
          <Select v-model="form.position_id" :options="positions" optionLabel="title" optionValue="id" filter showClear fluid :invalid="submitted && !form.position_id" />
          <small class="text-red-500" v-if="submitted && !form.position_id">Chức danh là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('position_id')">{{ errors.position_id }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Loại HĐ</label>
          <Select v-model="form.contract_type" :options="contractTypeOptions" optionLabel="label" optionValue="value" showClear fluid :invalid="submitted && !form.contract_type" />
          <small class="text-red-500" v-if="submitted && !form.contract_type">Loại hợp đồng là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('contract_type')">{{ errors.contract_type }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2">Trạng thái</label>
          <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" showClear fluid />
          <small class="text-red-500" v-if="hasError('status')">{{ errors.status }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Ngày bắt đầu</label>
          <DatePicker v-model="form.start_date" dateFormat="yy-mm-dd" showIcon fluid :invalid="hasError('start_date') || (submitted && !form.start_date)" />
          <small class="text-red-500" v-if="hasError('start_date')">{{ errors.start_date }}</small>
          <small class="text-red-500" v-else-if="submitted && !form.start_date">Ngày bắt đầu là bắt buộc.</small>
        </div>
        <div>
          <label class="block font-bold mb-2">Ngày kết thúc</label>
          <DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" showIcon fluid :disabled="form.contract_type==='INDEFINITE'" />
          <small class="text-red-500" v-if="hasError('end_date')">{{ errors.end_date }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Lương cơ bản</label>
          <InputText v-model.number="form.base_salary" type="number" class="w-full" placeholder="VND/tháng" :invalid="submitted && !form.base_salary" />
          <small class="text-red-500" v-if="submitted && !form.base_salary">Lương cơ bản là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('base_salary')">{{ errors.base_salary }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Lương đóng BH</label>
          <InputText v-model.number="form.insurance_salary" type="number" class="w-full" placeholder="VND/tháng" :invalid="submitted && !form.insurance_salary" />
          <small class="text-red-500" v-if="submitted && !form.insurance_salary">Lương đóng BH là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('insurance_salary')">{{ errors.insurance_salary }}</small>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Phụ cấp vị trí</label>
          <InputText v-model.number="form.position_allowance" type="number" class="w-full" placeholder="VND/tháng" :invalid="submitted && !form.position_allowance" />
          <small class="text-red-500" v-if="submitted && !form.position_allowance">Phụ cấp vị trí là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('position_allowance')">{{ errors.position_allowance }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2">Thời gian làm việc</label>
          <InputText v-model.trim="form.working_time" class="w-full" placeholder="VD: T2–T6 08:00–17:00" />
        </div>

        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Địa điểm làm việc</label>
          <InputText v-model.trim="form.work_location" class="w-full" placeholder="VD: Văn phòng Ninh Bình" />
        </div>

        <!-- Other allowances (repeater) -->
        <div class="md:col-span-2">
          <div class="flex items-center justify-between mb-2">
            <label class="block font-bold">Phụ cấp khác</label>
            <Button size="small" icon="pi pi-plus" label="Thêm phụ cấp" @click="addAllowance" />
          </div>
          <div v-if="!form.other_allowances || form.other_allowances.length===0" class="text-gray-500 text-sm">Chưa có phụ cấp khác.</div>
          <div v-for="(al, idx) in form.other_allowances" :key="idx" class="grid grid-cols-12 gap-2 mb-2">
            <div class="col-span-6">
              <InputText v-model.trim="al.name" class="w-full" placeholder="Tên phụ cấp" />
            </div>
            <div class="col-span-5">
              <InputText v-model.number="al.amount" type="number" class="w-full" placeholder="Số tiền VND/tháng" />
            </div>
            <div class="col-span-1 flex items-center justify-end">
              <Button icon="pi pi-trash" severity="danger" text @click="removeAllowance(idx)" />
            </div>
          </div>
        </div>

        <!-- Bổ sung: nguồn sinh hợp đồng + template -->
        <div>
          <label class="block font-bold mb-2 required-field">Nguồn tạo</label>
          <Select v-model="form.source" :options="sourceOptions" optionLabel="label" optionValue="value" showClear fluid :invalid="submitted && !form.source" />
          <small class="text-red-500" v-if="submitted && !form.source">Nguồn tạo là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('source')">{{ errors.source }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2">Source ID (nếu có)</label>
          <InputText v-model.trim="form.source_id" class="w-full" placeholder="offers.id hoặc để trống" />
          <small class="text-red-500" v-if="hasError('source_id')">{{ errors.source_id }}</small>
        </div>

        <div class="md:col-span-2">
          <!-- Template sẽ được chọn tự động khi sinh PDF -->
        </div>

        <div class="md:col-span-2">
          <label class="block font-bold mb-2">Ghi chú</label>
          <Textarea v-model.trim="form.note" autoResize rows="3" class="w-full" />
        </div>
      </div>

      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="hideDialog" />
        <Button label="Lưu" icon="pi pi-check" @click="save" :loading="saving" />
      </template>
    </Dialog>

    <!-- Dialog sinh hợp đồng (chọn template nhanh) -->
    <Dialog v-model:visible="generateDialog" :style="{ width: '600px' }" header="Xác nhận sinh PDF" :modal="true">
      <div class="mb-4">
        <div class="flex items-center gap-3 mb-4 p-3 bg-blue-50 rounded">
          <i class="pi pi-info-circle text-blue-600 text-xl"></i>
          <div>
            <div class="font-semibold text-gray-800">Hợp đồng: {{ current?.contract_number }}</div>
            <div class="text-sm text-gray-600">Loại: {{ current?.contract_type_label }}</div>
          </div>
        </div>

        <div v-if="defaultContractTemplate" class="mb-3">
          <p class="text-sm text-gray-700 mb-2">Mẫu được chọn:</p>
          <div class="p-3 border rounded bg-gray-50">
            <div class="font-semibold">{{ defaultContractTemplate.name }}</div>
            <div v-if="defaultContractTemplate.is_default" class="text-xs text-green-600 mt-1">
              <i class="pi pi-check-circle"></i> Mẫu mặc định
            </div>
          </div>
        </div>

        <details class="mt-4">
          <summary class="cursor-pointer text-sm text-primary hover:underline">
            Hoặc chọn mẫu khác...
          </summary>
          <Select
            v-model="generateTemplateId"
            :options="availableContractTemplates"
            optionLabel="name"
            optionValue="id"
            placeholder="-- Chọn mẫu khác --"
            showClear
            fluid
            :loading="loadingContractTemplates"
            class="mt-3"
          >
            <template #option="slotProps">
              <div>
                <div class="font-semibold">{{ slotProps.option.name }}</div>
                <div class="text-sm text-gray-600">{{ slotProps.option.type_label }}</div>
              </div>
            </template>
          </Select>
        </details>

        <div class="text-sm text-gray-600 mt-4">
          <i class="pi pi-info-circle mr-2"></i>
          Sau khi sinh, tệp PDF sẽ lưu vào hệ thống và hiển thị link tải ở danh sách.
        </div>
      </div>
      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="generateDialog=false" />
        <Button
          label="Sinh PDF"
          icon="pi pi-file-pdf"
          severity="success"
          @click="doGenerate"
          :loading="generating"
        />
      </template>
    </Dialog>

    <!-- Dialog xác nhận xóa -->
    <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span v-if="current">Bạn có chắc muốn xóa <b>{{ current.contract_number || current.employee?.full_name }}</b>?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="remove" :loading="deleting" />
      </template>
    </Dialog>

    <!-- Dialog xác nhận xóa nhiều -->
    <Dialog v-model:visible="deleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span>Bạn có chắc muốn xóa các hợp đồng đã chọn?</span>
      </div>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="deleteManyDialog=false" />
        <Button label="Có" icon="pi pi-check" severity="danger" @click="removeMany" :loading="deleting" />
      </template>
    </Dialog>

    <!-- Dialog gửi phê duyệt -->
    <Dialog v-model:visible="submitApprovalDialog" :style="{ width: '500px' }" header="Xác nhận gửi phê duyệt" :modal="true">
      <div class="mb-4">
        <div class="flex items-center gap-3 mb-3 p-3 bg-blue-50 rounded">
          <i class="pi pi-info-circle text-blue-600 text-xl"></i>
          <div>
            <div class="font-semibold text-gray-800">{{ current?.contract_number }}</div>
            <div class="text-sm text-gray-600">{{ current?.employee?.full_name }}</div>
          </div>
        </div>
        <p class="text-sm text-gray-700">
          Hợp đồng sẽ được gửi phê duyệt cho:<br/>
          <span class="font-semibold">Giám đốc (Director)</span>
        </p>
      </div>
      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="submitApprovalDialog=false" />
        <Button label="Gửi phê duyệt" icon="pi pi-send" severity="info" @click="doSubmitForApproval" :loading="submitting" />
      </template>
    </Dialog>

    <!-- Dialog phê duyệt -->
    <Dialog v-model:visible="approveDialog" :style="{ width: '600px' }" header="Phê duyệt hợp đồng" :modal="true">
      <div class="mb-4">
        <div class="flex items-center gap-3 mb-4 p-3 bg-green-50 rounded">
          <i class="pi pi-check-circle text-green-600 text-xl"></i>
          <div>
            <div class="font-semibold text-gray-800">{{ current?.contract_number }}</div>
            <div class="text-sm text-gray-600">{{ current?.employee?.full_name }}</div>
          </div>
        </div>

        <div v-if="current?.current_approval_step" class="mb-4 p-3 bg-gray-50 rounded">
          <div class="text-sm font-semibold text-gray-700 mb-1">Bước phê duyệt hiện tại:</div>
          <div class="text-sm text-gray-600">{{ current.current_approval_step.level_label }}</div>
        </div>

        <div>
          <label class="block font-bold mb-2">Ý kiến phê duyệt</label>
          <Textarea v-model="approvalComments" autoResize rows="3" class="w-full" placeholder="Nhập ý kiến (không bắt buộc)..." />
        </div>
      </div>
      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="approveDialog=false" />
        <Button label="Phê duyệt" icon="pi pi-check" severity="success" @click="doApprove" :loading="approving" />
      </template>
    </Dialog>

    <!-- Dialog từ chối -->
    <Dialog v-model:visible="rejectDialog" :style="{ width: '600px' }" header="Từ chối hợp đồng" :modal="true">
      <div class="mb-4">
        <div class="flex items-center gap-3 mb-4 p-3 bg-red-50 rounded">
          <i class="pi pi-times-circle text-red-600 text-xl"></i>
          <div>
            <div class="font-semibold text-gray-800">{{ current?.contract_number }}</div>
            <div class="text-sm text-gray-600">{{ current?.employee?.full_name }}</div>
          </div>
        </div>

        <div v-if="current?.current_approval_step" class="mb-4 p-3 bg-gray-50 rounded">
          <div class="text-sm font-semibold text-gray-700 mb-1">Bước phê duyệt hiện tại:</div>
          <div class="text-sm text-gray-600">{{ current.current_approval_step.level_label }}</div>
        </div>

        <div>
          <label class="block font-bold mb-2 required-field">Lý do từ chối</label>
          <Textarea v-model="rejectComments" autoResize rows="4" class="w-full" placeholder="Nhập lý do từ chối (bắt buộc)..." :invalid="rejectSubmitted && !rejectComments" />
          <small class="text-red-500" v-if="rejectSubmitted && !rejectComments">Vui lòng nhập lý do từ chối.</small>
        </div>
      </div>
      <template #footer>
        <Button label="Hủy" icon="pi pi-times" text @click="rejectDialog=false" />
        <Button label="Từ chối" icon="pi pi-times-circle" severity="danger" @click="doReject" :loading="rejecting" />
      </template>
    </Dialog>

    <!-- Dialog thu hồi -->
    <Dialog v-model:visible="recallDialog" :style="{ width: '500px' }" header="Xác nhận thu hồi" :modal="true">
      <div class="flex items-center gap-4 mb-3">
        <i class="pi pi-exclamation-triangle text-orange-500 !text-3xl" />
        <div>
          <div class="font-semibold">{{ current?.contract_number }}</div>
          <div class="text-sm text-gray-600">Bạn có chắc muốn thu hồi yêu cầu phê duyệt?</div>
        </div>
      </div>
      <p class="text-sm text-gray-600">Hợp đồng sẽ quay về trạng thái Nháp và cần gửi phê duyệt lại.</p>
      <template #footer>
        <Button label="Không" icon="pi pi-times" text @click="recallDialog=false" />
        <Button label="Thu hồi" icon="pi pi-replay" severity="warning" @click="doRecall" :loading="recalling" />
      </template>
    </Dialog>

    <!-- Terminate Contract Modal -->
    <TerminateContractModal
      v-model="terminateDialog"
      :contract="contractToTerminate"
      @terminated="terminateDialog = false"
    />

    <!-- Renewal Contract Modal -->
    <ContractRenewalModal
      v-model="renewalDialog"
      :contract="current"
      :departments="departments"
      :positions="allPositions"
      @renewed="onContractRenewed"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import { ContractService } from '@/services/ContractService'
import { useFormValidation } from '@/composables/useFormValidation'
import { formatDate, toYMD } from '@/utils/dateHelper'
import TerminateContractModal from '@/Components/TerminateContractModal.vue'
import ContractRenewalModal from '@/Components/ContractRenewalModal.vue'

const { errors, hasError, getError } = useFormValidation()

const definePropsData = defineProps({
  contracts: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  positions: { type: Array, default: () => [] },
  contractTypeOptions: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] },
  sourceOptions: { type: Array, default: () => [] },
})

// Table
const dt = ref()
const selected = ref([])
const filters = ref({ global: { value: null, matchMode: 'contains' } })
const rows = computed(() => definePropsData.contracts || [])

// Dialog states
const dialog = ref(false)
const deleteDialog = ref(false)
const deleteManyDialog = ref(false)
const generateDialog = ref(false)
const submitApprovalDialog = ref(false)
const approveDialog = ref(false)
const rejectDialog = ref(false)
const recallDialog = ref(false)
const terminateDialog = ref(false)
const renewalDialog = ref(false)

const saving = ref(false)
const deleting = ref(false)
const generating = ref(false)
const submitting = ref(false)
const approving = ref(false)
const rejecting = ref(false)
const recalling = ref(false)

const submitted = ref(false)
const rejectSubmitted = ref(false)

const current = ref(null)
const contractToTerminate = ref(null)
const generateTemplateId = ref(null)
const availableContractTemplates = ref([])
const loadingContractTemplates = ref(false)
const defaultContractTemplate = ref(null)

// Approval workflow state
const approvalComments = ref('')
const rejectComments = ref('')

// Form model
const form = ref({
  id: null,
  employee_id: null,
  department_id: null,
  position_id: null,
  contract_number: '',
  contract_type: null,
  start_date: null,
  end_date: null,
  base_salary: null,
  insurance_salary: null,
  position_allowance: null,
  other_allowances: [],
  working_time: '',
  work_location: '',
  status: 'DRAFT',
  source: 'LEGACY',
  source_id: '',
  note: ''
})

// Options - Backend sẽ cung cấp qua props
const contractTypeOptions = computed(() => definePropsData.contractTypeOptions || [])
const statusOptions = computed(() => definePropsData.statusOptions || [])
const sourceOptions = computed(() => definePropsData.sourceOptions || [])
const employees = computed(() => definePropsData.employees || [])
const departments = computed(() => definePropsData.departments || [])
const allPositions = computed(() => definePropsData.positions || [])

// Filter positions theo department đã chọn
const positions = computed(() => {
  if (!form.value.department_id) {
    return allPositions.value
  }
  return allPositions.value.filter(p => p.department_id === form.value.department_id)
})

// Helpers
const statusSeverity = (s) => ({
  DRAFT: 'secondary',
  PENDING_APPROVAL: 'warn',
  ACTIVE: 'success',
  REJECTED: 'danger',
  SUSPENDED: 'contrast',
  TERMINATED: 'contrast',
  EXPIRED: 'contrast',
  CANCELLED: 'contrast'
}[s] || 'info')

const empItemTmpl = (opt) => `${opt.full_name} (${opt.employee_code || '—'})`

// CRUD
function openNew() {
  form.value = {
    id: null,
    employee_id: null,
    department_id: null,
    position_id: null,
    contract_number: '',
    contract_type: null,
    start_date: null,
    end_date: null,
    base_salary: null,
    insurance_salary: null,
    position_allowance: null,
    other_allowances: [],
    working_time: '',
    work_location: '',
    status: 'DRAFT',
    source: 'LEGACY',
    source_id: '',
    note: ''
  }
  submitted.value = false
  dialog.value = true
}
function edit(row) {
  form.value = {
    id: row.id,
    employee_id: row.employee_id,
    department_id: row.department_id,
    position_id: row.position_id,
    contract_number: row.contract_number || '',
    contract_type: row.contract_type || null,
    start_date: row.start_date,
    end_date: row.end_date,
    base_salary: row.base_salary || null,
    insurance_salary: row.insurance_salary || null,
    position_allowance: row.position_allowance || null,
    other_allowances: Array.isArray(row.other_allowances) ? JSON.parse(JSON.stringify(row.other_allowances)) : [],
    working_time: row.working_time || '',
    work_location: row.work_location || '',
    status: row.status || 'DRAFT',
    source: row.source || 'LEGACY',
    source_id: row.source_id || '',
    note: row.note || ''
  }
  submitted.value = false
  dialog.value = true
}
function hideDialog() {
  dialog.value = false
  submitted.value = false
}
function save() {
  submitted.value = true

  // Validation
  if (!form.value.employee_id || !form.value.contract_number || !form.value.department_id ||
      !form.value.position_id || !form.value.contract_type || !form.value.start_date ||
      !form.value.base_salary || !form.value.insurance_salary || !form.value.position_allowance ||
      !form.value.source) {
    return
  }

  saving.value = true
  const payload = {
    ...form.value,
    start_date: toYMD(form.value.start_date),
    end_date: toYMD(form.value.end_date)
  }
  const opts = {
    onFinish: () => (saving.value = false),
    onSuccess: () => {
      dialog.value = false
      form.value = {}
    }
  }
  if (!form.value.id) {
    ContractService.store(payload, opts)
  } else {
    ContractService.update(form.value.id, payload, opts)
  }
}
function confirmDelete(row) {
  current.value = row
  deleteDialog.value = true
}
function remove() {
  deleting.value = true
  ContractService.destroy(current.value.id, {
    onFinish: () => {
      deleting.value = false
      deleteDialog.value = false
      current.value = null
    }
  })
}
function confirmDeleteSelected() {
  deleteManyDialog.value = true
}
function removeMany() {
  const ids = selected.value.map((x) => x.id)
  deleting.value = true
  ContractService.bulkDelete(ids, {
    onFinish: () => {
      deleting.value = false
      deleteManyDialog.value = false
      selected.value = []
    }
  })
}
function exportCSV() {
  dt.value?.exportCSV()
}

// other_allowances
function addAllowance() {
  if (!Array.isArray(form.value.other_allowances)) form.value.other_allowances = []
  form.value.other_allowances.push({ name: '', amount: null })
}
function removeAllowance(idx) {
  form.value.other_allowances.splice(idx, 1)
}

// Auto-clear position khi đổi department
function onDepartmentChange() {
  // Nếu position hiện tại không thuộc department mới, clear nó
  if (form.value.position_id) {
    const currentPosition = allPositions.value.find(p => p.id === form.value.position_id)
    if (currentPosition && currentPosition.department_id !== form.value.department_id) {
      form.value.position_id = null
    }
  }
}

// Generate PDF - auto-select default template based on contract_type
async function openGenerate(row) {
  current.value = row
  generateTemplateId.value = null
  defaultContractTemplate.value = null
  generateDialog.value = true

  // Load available templates for this contract type
  loadingContractTemplates.value = true
  try {
    const response = await fetch(`/contract-templates?type=${row.contract_type}`)
    const data = await response.json()
    availableContractTemplates.value = data.data || []

    // Auto-select default template
    defaultContractTemplate.value = availableContractTemplates.value.find(t => t.is_default) || availableContractTemplates.value[0]
    if (defaultContractTemplate.value) {
      generateTemplateId.value = defaultContractTemplate.value.id
    }
  } catch (error) {
    console.error('Failed to load templates:', error)
    availableContractTemplates.value = []
  } finally {
    loadingContractTemplates.value = false
  }
}

function doGenerate() {
  if (!current.value) return

  generating.value = true
  const payload = generateTemplateId.value ? { template_id: generateTemplateId.value } : {}

  ContractService.generate(current.value.id, payload, {
    onFinish: () => {
      generating.value = false
      generateDialog.value = false
    }
  })
}
function goToAppendixes(row) {
  router.get(`/contracts/${row.id}`, { tab: 'appendixes' })
}
function goToGeneral(row) {
  router.get(`/contracts/${row.id}`, { tab: 'general' })
}

// ==================== RENEWAL FUNCTIONS ====================

function openRenewalDialog(row) {
  current.value = row
  renewalDialog.value = true
}

function onContractRenewed() {
  router.reload({ only: ['contracts'] })
}

// Check if contract is expiring soon (within 30 days)
function isExpiringSoon(row) {
  if (!row.end_date || row.status !== 'ACTIVE') return false
  const endDate = new Date(row.end_date)
  const today = new Date()
  const daysUntilExpiry = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24))
  return daysUntilExpiry > 0 && daysUntilExpiry <= 30
}

function getDaysUntilExpiry(row) {
  if (!row.end_date) return null
  const endDate = new Date(row.end_date)
  const today = new Date()
  const daysUntilExpiry = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24))
  return daysUntilExpiry
}

function expiryBadgeSeverity(days) {
  if (days <= 7) return 'danger'
  if (days <= 15) return 'warn'
  return 'info'
}

// ==================== APPROVAL WORKFLOW FUNCTIONS ====================

function confirmSubmitForApproval(row) {
  current.value = row
  submitApprovalDialog.value = true
}

function doSubmitForApproval() {
  if (!current.value) return

  submitting.value = true
  ContractService.submitForApproval(current.value.id, {
    onFinish: () => {
      submitting.value = false
      submitApprovalDialog.value = false
      current.value = null
    }
  })
}

function openApproveDialog(row) {
  current.value = row
  approvalComments.value = ''
  approveDialog.value = true
}

function doApprove() {
  if (!current.value) return

  approving.value = true
  const payload = { comments: approvalComments.value || null }

  ContractService.approve(current.value.id, payload, {
    onFinish: () => {
      approving.value = false
      approveDialog.value = false
      current.value = null
      approvalComments.value = ''
    }
  })
}

function openRejectDialog(row) {
  current.value = row
  rejectComments.value = ''
  rejectSubmitted.value = false
  rejectDialog.value = true
}

function doReject() {
  if (!current.value) return

  rejectSubmitted.value = true

  if (!rejectComments.value) {
    return
  }

  rejecting.value = true
  const payload = { comments: rejectComments.value }

  ContractService.reject(current.value.id, payload, {
    onFinish: () => {
      rejecting.value = false
      rejectDialog.value = false
      current.value = null
      rejectComments.value = ''
      rejectSubmitted.value = false
    }
  })
}

function confirmRecall(row) {
  current.value = row
  recallDialog.value = true
}

function doRecall() {
  if (!current.value) return

  recalling.value = true
  ContractService.recall(current.value.id, {
    onFinish: () => {
      recalling.value = false
      recallDialog.value = false
      current.value = null
    }
  })
}

function openTerminateDialog(row) {
  contractToTerminate.value = row
  terminateDialog.value = true
}
</script>

<style scoped>
.required-field::after { content: ' *'; color: red; }
</style>
