<template>
  <div class="card">
    <Toolbar class="mb-4">
      <template #start>
        <Button label="Thêm phụ lục" icon="pi pi-plus" class="mr-2" @click="openNew" />
        <Button
          label="Xóa"
          icon="pi pi-trash"
          severity="danger"
          variant="outlined"
          :disabled="!selected?.length"
          @click="confirmDeleteSelected"
        />
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
      :rowsPerPageOptions="[5,10,25]"
      currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} phụ lục"
    >
      <template #header>
        <div class="flex items-center justify-between gap-2">
          <h4 class="m-0">Danh sách Phụ lục</h4>
          <IconField>
            <InputIcon><i class="pi pi-search" /></InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Tìm kiếm..." />
          </IconField>
        </div>
      </template>

      <Column selectionMode="multiple" headerStyle="width:3rem" />
      <Column field="appendix_no" header="Số PL" sortable headerStyle="min-width:10rem;" />
      <Column field="appendix_type_label" header="Loại" sortable headerStyle="min-width:14rem;">
        <template #body="sp">
          {{ sp.data.appendix_type_label }}
        </template>
      </Column>
      <Column field="effective_date" header="Hiệu lực" headerStyle="min-width:10rem;">
        <template #body="sp">
          {{ formatDate(sp.data.effective_date) }}
        </template>
      </Column>
      <Column field="status_label" header="Trạng thái" headerStyle="min-width:10rem;">
        <template #body="sp">
          <Tag :value="sp.data.status_label" :severity="statusSeverity(sp.data.status)" />
        </template>
      </Column>
      <Column header="Tệp sinh ra" headerStyle="min-width:12rem;">
        <template #body="sp">
          <a
            v-if="sp.data.generated_pdf_url"
            :href="sp.data.generated_pdf_url"
            target="_blank"
            class="text-primary underline"
          >
            Xem PDF
          </a>
          <span v-else>—</span>
        </template>
      </Column>
      <Column header="Đính kèm" headerStyle="min-width:12rem;">
        <template #body="sp">
          <div v-if="sp.data.attachments && sp.data.attachments.length > 0">
            <div class="flex flex-col gap-1">
              <a
                v-for="att in sp.data.attachments.slice(0, 2)"
                :key="att.id"
                :href="att.download_url"
                target="_blank"
                rel="noopener noreferrer"
                class="text-xs text-primary hover:underline flex items-center gap-1"
              >
                <i class="pi pi-paperclip text-xs"></i>
                <span class="truncate max-w-[150px]">{{ att.file_name }}</span>
              </a>
              <span v-if="sp.data.attachments.length > 2" class="text-xs text-gray-500">
                +{{ sp.data.attachments.length - 2 }} file khác
              </span>
            </div>
          </div>
          <span v-else class="text-gray-400">—</span>
        </template>
      </Column>
      <Column header="Thao tác" headerStyle="min-width:16rem;">
        <template #body="sp">
          <div class="flex gap-2">
            <!-- View Detail -->
            <Button
              icon="pi pi-eye"
              outlined
              rounded
              @click="viewAppendix(sp.data)"
              v-tooltip="'Xem chi tiết'"
            />

            <!-- Edit: Only for DRAFT/REJECTED -->
            <Button
              v-if="['DRAFT', 'REJECTED'].includes(sp.data.status)"
              icon="pi pi-pencil"
              outlined
              severity="success"
              rounded
              @click="edit(sp.data)"
              v-tooltip="'Chỉnh sửa'"
            />

            <!-- Delete: Only for DRAFT/REJECTED -->
            <Button
              v-if="['DRAFT', 'REJECTED'].includes(sp.data.status)"
              icon="pi pi-trash"
              outlined
              severity="danger"
              rounded
              @click="confirmDelete(sp.data)"
              v-tooltip="'Xóa'"
            />

            <!-- Generate PDF: Always available -->
            <Button
              icon="pi pi-file"
              outlined
              rounded
              @click="generateAppendix(sp.data)"
              v-tooltip="'Sinh phụ lục (PDF)'"
            />

            <!-- Submit for approval: DRAFT -->
            <Button
              v-if="sp.data.status === 'DRAFT'"
              icon="pi pi-send"
              outlined
              severity="info"
              rounded
              @click="submitForApproval(sp.data)"
              v-tooltip="'Gửi phê duyệt'"
            />

            <!-- Resubmit: REJECTED -->
            <Button
              v-if="sp.data.status === 'REJECTED'"
              icon="pi pi-refresh"
              outlined
              severity="info"
              rounded
              @click="submitForApproval(sp.data)"
              v-tooltip="'Gửi lại phê duyệt'"
            />

            <!-- Recall: PENDING_APPROVAL -->
            <Button
              v-if="sp.data.status === 'PENDING_APPROVAL'"
              icon="pi pi-replay"
              outlined
              severity="warning"
              rounded
              @click="recall(sp.data)"
              v-tooltip="'Thu hồi'"
            />

            <!-- Approve: PENDING_APPROVAL -->
            <Button
              v-if="sp.data.status === 'PENDING_APPROVAL'"
              icon="pi pi-check"
              outlined
              severity="success"
              rounded
              @click="approve(sp.data)"
              v-tooltip="'Phê duyệt'"
            />

            <!-- Reject: PENDING_APPROVAL -->
            <Button
              v-if="sp.data.status === 'PENDING_APPROVAL'"
              icon="pi pi-times"
              outlined
              severity="danger"
              rounded
              @click="reject(sp.data)"
              v-tooltip="'Từ chối'"
            />
          </div>
        </template>
      </Column>
    </DataTable>
  </div>

  <!-- Dialog tạo/sửa phụ lục -->
  <Dialog v-model:visible="dialog" :style="{ width: '800px' }" header="Phụ lục hợp đồng" :modal="true">
    <!-- Thông tin chung -->
    <div class="mb-6">
      <h5 class="font-bold text-gray-700 mb-3 pb-2 border-b">Thông tin chung</h5>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-bold mb-2 required-field">Số PL</label>
          <InputText
            v-model="form.appendix_no"
            class="w-full"
            :invalid="(submitted && !form.appendix_no) || hasError('appendix_no')"
          />
          <small class="text-red-500" v-if="submitted && !form.appendix_no">Số phụ lục là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('appendix_no')">{{ getError('appendix_no') }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Loại</label>
          <Select
            v-model="form.appendix_type"
            :options="typeOptions"
            optionLabel="label"
            optionValue="value"
            showClear
            fluid
            :invalid="(submitted && !form.appendix_type) || hasError('appendix_type')"
          />
          <small class="text-red-500" v-if="submitted && !form.appendix_type">Loại phụ lục là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('appendix_type')">{{ getError('appendix_type') }}</small>
        </div>
        <div>
          <label class="block font-bold mb-2 required-field">Hiệu lực từ</label>
          <DatePicker
            v-model="form.effective_date"
            dateFormat="yy-mm-dd"
            showIcon
            fluid
            :invalid="(submitted && !form.effective_date) || hasError('effective_date')"
          />
          <small class="text-red-500" v-if="submitted && !form.effective_date">Ngày hiệu lực là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('effective_date')">{{ getError('effective_date') }}</small>
        </div>
        <div v-if="showField('end_date')">
          <label class="block font-bold mb-2" :class="{ 'required-field': currentSchema.required?.includes('end_date') }">Đến</label>
          <DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" showIcon fluid :invalid="hasError('end_date')" />
          <small class="text-red-500" v-if="hasError('end_date')">{{ getError('end_date') }}</small>
        </div>

        <div v-if="showStatusField" class="md:col-span-2">
          <label class="block font-bold mb-2 required-field">Trạng thái</label>
          <Select
            v-model="form.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            fluid
            :invalid="(submitted && !form.status) || hasError('status')"
          />
          <small class="text-gray-500 text-xs mt-1">Dùng khi backfill dữ liệu lịch sử</small>
          <small class="text-red-500" v-if="submitted && !form.status">Trạng thái là bắt buộc.</small>
          <small class="text-red-500" v-if="hasError('status')">{{ getError('status') }}</small>
        </div>
      </div>
    </div>

    <!-- Nội dung thay đổi (dynamic theo type) -->
    <div v-if="form.appendix_type" class="mb-6">
      <h5 class="font-bold text-gray-700 mb-3 pb-2 border-b">{{ currentSchema.sectionTitle || 'Nội dung thay đổi' }}</h5>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- DEPARTMENT: department_id -->
        <div v-if="showField('department_id')" class="md:col-span-2">
          <label class="block font-bold mb-2 required-field">Phòng/Ban mới</label>
          <Select
            v-model="form.department_id"
            :options="props.departments"
            optionLabel="name"
            optionValue="id"
            showClear
            filter
            fluid
            placeholder="-- Chọn phòng/ban --"
            :invalid="(submitted && currentSchema.required?.includes('department_id') && !form.department_id) || hasError('department_id')"
          />
          <small class="text-red-500" v-if="submitted && currentSchema.required?.includes('department_id') && !form.department_id">
            Phòng/Ban là bắt buộc.
          </small>
          <small class="text-red-500" v-if="hasError('department_id')">{{ getError('department_id') }}</small>
        </div>

        <!-- POSITION: position_id -->
        <div v-if="showField('position_id')" class="md:col-span-2">
          <label class="block font-bold mb-2 required-field">Chức danh mới</label>
          <Select
            v-model="form.position_id"
            :options="props.positions"
            optionLabel="title"
            optionValue="id"
            showClear
            filter
            fluid
            placeholder="-- Chọn chức danh --"
            :invalid="(submitted && currentSchema.required?.includes('position_id') && !form.position_id) || hasError('position_id')"
          />
          <small class="text-red-500" v-if="submitted && currentSchema.required?.includes('position_id') && !form.position_id">
            Chức danh là bắt buộc.
          </small>
          <small class="text-red-500" v-if="hasError('position_id')">{{ getError('position_id') }}</small>
        </div>

        <!-- SALARY: base_salary, insurance_salary, position_allowance -->
        <div v-if="showField('base_salary')">
          <label class="block font-bold mb-2" :class="{ 'required-field': currentSchema.required?.includes('base_salary') }">Lương cơ bản</label>
          <InputText
            type="number"
            v-model.number="form.base_salary"
            class="w-full"
            placeholder="VND/tháng"
            :invalid="(submitted && currentSchema.required?.includes('base_salary') && !form.base_salary) || hasError('base_salary')"
          />
          <small class="text-red-500" v-if="submitted && currentSchema.required?.includes('base_salary') && !form.base_salary">
            Lương cơ bản là bắt buộc.
          </small>
          <small class="text-red-500" v-if="hasError('base_salary')">{{ getError('base_salary') }}</small>
        </div>
        <div v-if="showField('insurance_salary')">
          <label class="block font-bold mb-2">Lương BH</label>
          <InputText
            type="number"
            v-model.number="form.insurance_salary"
            class="w-full"
            placeholder="VND/tháng"
            :invalid="hasError('insurance_salary')"
          />
          <small class="text-red-500" v-if="hasError('insurance_salary')">{{ getError('insurance_salary') }}</small>
        </div>
        <div v-if="showField('position_allowance')" :class="{ 'md:col-span-2': !showField('insurance_salary') }">
          <label class="block font-bold mb-2">PC vị trí</label>
          <InputText
            type="number"
            v-model.number="form.position_allowance"
            class="w-full"
            placeholder="VND/tháng"
            :invalid="hasError('position_allowance')"
          />
          <small class="text-red-500" v-if="hasError('position_allowance')">{{ getError('position_allowance') }}</small>
        </div>

        <!-- WORKING_TERMS: working_time, work_location -->
        <div v-if="showField('working_time')" class="md:col-span-2">
          <label class="block font-bold mb-2" :class="{ 'required-field': currentSchema.required?.includes('working_time') }">
            Thời gian làm việc
          </label>
          <InputText
            v-model="form.working_time"
            class="w-full"
            placeholder="VD: T2-T6, 08:00-17:00"
            :invalid="(submitted && currentSchema.required?.includes('working_time') && !form.working_time) || hasError('working_time')"
          />
          <small class="text-red-500" v-if="submitted && currentSchema.required?.includes('working_time') && !form.working_time">
            Thời gian làm việc là bắt buộc.
          </small>
          <small class="text-red-500" v-if="hasError('working_time')">{{ getError('working_time') }}</small>
        </div>
        <div v-if="showField('work_location')" class="md:col-span-2">
          <label class="block font-bold mb-2" :class="{ 'required-field': currentSchema.required?.includes('work_location') }">
            Địa điểm
          </label>
          <InputText
            v-model="form.work_location"
            class="w-full"
            placeholder="VD: Văn phòng Hà Nội"
            :invalid="(submitted && currentSchema.required?.includes('work_location') && !form.work_location) || hasError('work_location')"
          />
          <small class="text-red-500" v-if="submitted && currentSchema.required?.includes('work_location') && !form.work_location">
            Địa điểm là bắt buộc.
          </small>
          <small class="text-red-500" v-if="hasError('work_location')">{{ getError('work_location') }}</small>
        </div>

        <!-- Other allowances (repeater) - for SALARY, ALLOWANCE types -->
        <div v-if="showField('other_allowances')" class="md:col-span-2">
          <div class="flex items-center justify-between mb-2">
            <label class="block font-bold">Phụ cấp khác</label>
            <Button size="small" icon="pi pi-plus" label="Thêm phụ cấp" @click="addAllowance" />
          </div>
          <div v-if="!form.other_allowances || form.other_allowances.length === 0" class="text-gray-500 text-sm">
            Chưa có phụ cấp khác.
          </div>
          <div v-for="(al, idx) in form.other_allowances" :key="idx" class="grid grid-cols-12 gap-2 mb-2">
            <div class="col-span-6">
              <InputText v-model="al.name" class="w-full" placeholder="Tên phụ cấp" />
            </div>
            <div class="col-span-5">
              <InputText v-model.number="al.amount" type="number" class="w-full" placeholder="Số tiền VND/tháng" />
            </div>
            <div class="col-span-1 flex items-center justify-end">
              <Button icon="pi pi-trash" severity="danger" text @click="removeAllowance(idx)" />
            </div>
          </div>
        </div>

        <!-- Summary: for all types -->
        <div v-if="showField('summary')" class="md:col-span-2">
          <label class="block font-bold mb-2" :class="{ 'required-field': currentSchema.required?.includes('summary') }">
            Tóm tắt
          </label>
          <Textarea
            v-model="form.summary"
            autoResize
            rows="2"
            class="w-full"
            placeholder="Mô tả ngắn gọn nội dung thay đổi..."
            :invalid="(submitted && currentSchema.required?.includes('summary') && !form.summary) || hasError('summary')"
          />
          <small class="text-red-500" v-if="submitted && currentSchema.required?.includes('summary') && !form.summary">
            Tóm tắt là bắt buộc.
          </small>
          <small class="text-red-500" v-if="hasError('summary')">{{ getError('summary') }}</small>
        </div>
      </div>
    </div>

    <!-- Đính kèm -->
    <div class="mb-4">
      <h5 class="font-bold text-gray-700 mb-3 pb-2 border-b">Đính kèm</h5>
      <AttachmentUploader
        ref="attachmentUploader"
        :existingAttachments="form.attachments"
        @update:newFiles="form.newAttachments = $event"
        @update:deleteIds="form.deleteAttachments = $event"
      />
    </div>

    <!-- Ghi chú -->
    <div>
      <h5 class="font-bold text-gray-700 mb-3 pb-2 border-b">Ghi chú</h5>
      <Textarea v-model="form.note" autoResize rows="3" class="w-full" placeholder="Ghi chú thêm (không bắt buộc)..." :invalid="hasError('note')" />
      <small class="text-red-500" v-if="hasError('note')">{{ getError('note') }}</small>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="closeDialog" />
      <Button label="Lưu" icon="pi pi-check" @click="save" :loading="saving" />
    </template>
  </Dialog>

  <!-- Dialog xác nhận xóa -->
  <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
    <div class="flex items-center gap-4">
      <i class="pi pi-exclamation-triangle !text-3xl" />
      <span v-if="current">Bạn có chắc muốn xóa phụ lục <b>{{ current.appendix_no }}</b>?</span>
    </div>
    <template #footer>
      <Button label="Không" icon="pi pi-times" text @click="deleteDialog=false" />
      <Button label="Có" icon="pi pi-check" severity="danger" @click="remove" :loading="deleting" />
    </template>
  </Dialog>

  <!-- Dialog xóa nhiều -->
  <Dialog v-model:visible="deleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
    <div class="flex items-center gap-4">
      <i class="pi pi-exclamation-triangle !text-3xl" />
      <span>Bạn có chắc xóa {{ selected.length }} phụ lục đã chọn?</span>
    </div>
    <template #footer>
      <Button label="Không" icon="pi pi-times" text @click="deleteManyDialog=false" />
      <Button label="Có" icon="pi pi-check" severity="danger" @click="removeMany" :loading="deleting" />
    </template>
  </Dialog>

  <!-- Dialog gửi phê duyệt -->
  <Dialog v-model:visible="submitDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
    <div class="flex items-center gap-4">
      <i class="pi pi-send !text-3xl text-blue-600" />
      <span v-if="current">
        Bạn có chắc muốn {{ current.status === 'REJECTED' ? 'gửi lại' : 'gửi' }} phụ lục <b>{{ current.appendix_no }}</b> để phê duyệt?
      </span>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="submitDialog=false" />
      <Button
        :label="current?.status === 'REJECTED' ? 'Gửi lại' : 'Gửi phê duyệt'"
        :icon="current?.status === 'REJECTED' ? 'pi pi-refresh' : 'pi pi-send'"
        severity="info"
        @click="confirmSubmit"
        :loading="submitting"
      />
    </template>
  </Dialog>

  <!-- Dialog thu hồi -->
  <Dialog v-model:visible="recallDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
    <div class="flex items-center gap-4">
      <i class="pi pi-replay !text-3xl text-orange-600" />
      <span v-if="current">Bạn có chắc muốn thu hồi yêu cầu phê duyệt phụ lục <b>{{ current.appendix_no }}</b>?</span>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="recallDialog=false" />
      <Button label="Thu hồi" icon="pi pi-replay" severity="warning" @click="confirmRecall" :loading="recalling" />
    </template>
  </Dialog>

  <!-- Dialog phê duyệt -->
  <Dialog v-model:visible="approveDialog" :style="{ width: '600px' }" header="Phê duyệt phụ lục" :modal="true">
    <div class="mb-4">
      <div class="flex items-center gap-3 mb-4 p-3 bg-green-50 rounded">
        <i class="pi pi-check-circle text-green-600 text-xl"></i>
        <div>
          <div class="font-semibold text-gray-800">{{ current?.appendix_no }}</div>
          <div class="text-sm text-gray-600">{{ current?.appendix_type_label }}</div>
        </div>
      </div>

      <div>
        <label class="block font-bold mb-2">Ý kiến phê duyệt</label>
        <Textarea v-model="approvalNote" autoResize rows="3" class="w-full" placeholder="Nhập ý kiến (không bắt buộc)..." />
      </div>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="approveDialog=false" />
      <Button label="Phê duyệt" icon="pi pi-check" severity="success" @click="confirmApprove" :loading="approving" />
    </template>
  </Dialog>

  <!-- Dialog từ chối -->
  <Dialog v-model:visible="rejectDialog" :style="{ width: '600px' }" header="Từ chối phụ lục" :modal="true">
    <div class="mb-4">
      <div class="flex items-center gap-3 mb-4 p-3 bg-red-50 rounded">
        <i class="pi pi-times-circle text-red-600 text-xl"></i>
        <div>
          <div class="font-semibold text-gray-800">{{ current?.appendix_no }}</div>
          <div class="text-sm text-gray-600">{{ current?.appendix_type_label }}</div>
        </div>
      </div>

      <div>
        <label class="block font-bold mb-2 required-field">Lý do từ chối</label>
        <Textarea v-model="rejectNote" autoResize rows="4" class="w-full" placeholder="Nhập lý do từ chối (bắt buộc)..." :invalid="rejectSubmitted && !rejectNote" />
        <small class="text-red-500" v-if="rejectSubmitted && !rejectNote">Vui lòng nhập lý do từ chối.</small>
      </div>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="rejectDialog=false" />
      <Button label="Từ chối" icon="pi pi-times-circle" severity="danger" @click="confirmReject" :loading="rejecting" />
    </template>
  </Dialog>

  <!-- Dialog chọn template để sinh PDF -->
  <Dialog v-model:visible="generateDialog" :style="{ width: '600px' }" header="Xác nhận sinh PDF" :modal="true">
    <div class="mb-4">
      <div class="flex items-center gap-3 mb-4 p-3 bg-blue-50 rounded">
        <i class="pi pi-info-circle text-blue-600 text-xl"></i>
        <div>
          <div class="font-semibold text-gray-800">Phụ lục: {{ currentAppendix?.appendix_no }}</div>
          <div class="text-sm text-gray-600">Loại: {{ currentAppendix?.appendix_type_label }}</div>
        </div>
      </div>

      <div v-if="defaultTemplate" class="mb-3">
        <p class="text-sm text-gray-700 mb-2">Mẫu được chọn:</p>
        <div class="p-3 border rounded bg-gray-50">
          <div class="font-semibold">{{ defaultTemplate.name }}</div>
          <div class="text-sm text-gray-600">{{ defaultTemplate.code }}</div>
          <div v-if="defaultTemplate.is_default" class="text-xs text-green-600 mt-1">
            <i class="pi pi-check-circle"></i> Mẫu mặc định
          </div>
        </div>
      </div>

      <details class="mt-4">
        <summary class="cursor-pointer text-sm text-primary hover:underline">
          Hoặc chọn mẫu khác...
        </summary>
        <Select
          v-model="selectedTemplateId"
          :options="availableTemplates"
          optionLabel="name"
          optionValue="id"
          placeholder="-- Chọn mẫu khác --"
          showClear
          fluid
          :loading="loadingTemplates"
          class="mt-3"
        >
          <template #option="slotProps">
            <div>
              <div class="font-semibold">{{ slotProps.option.name }}</div>
              <div class="text-sm text-gray-600">{{ slotProps.option.code }}</div>
            </div>
          </template>
        </Select>
      </details>
    </div>
    <template #footer>
      <Button label="Hủy" icon="pi pi-times" text @click="generateDialog=false" />
      <Button
        label="Sinh PDF"
        icon="pi pi-file-pdf"
        severity="success"
        @click="confirmGenerate"
        :loading="generating"
      />
    </template>
  </Dialog>

  <!-- Drawer xem chi tiết phụ lục -->
  <Drawer v-model:visible="viewDialog" position="right" :style="{ width: '600px' }" header="Chi tiết phụ lục">
    <div v-if="viewing" class="space-y-6">
      <!-- Header Info -->
      <div class="pb-4 border-b">
        <div class="flex items-start justify-between mb-3">
          <div class="flex-1">
            <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ viewing.appendix_no }}</h3>
            <div class="flex items-center gap-2 mb-2">
              <Tag :value="viewing.appendix_type_label" severity="info" />
              <Tag :value="viewing.status_label" :severity="statusSeverity(viewing.status)" />
            </div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div>
            <span class="text-gray-600">Hiệu lực từ:</span>
            <span class="ml-2 font-medium">{{ formatDate(viewing.effective_date) }}</span>
          </div>
          <div v-if="viewing.end_date">
            <span class="text-gray-600">Đến:</span>
            <span class="ml-2 font-medium">{{ formatDate(viewing.end_date) }}</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="flex flex-wrap gap-2">
        <Button
          v-if="viewing.generated_pdf_url"
          label="Xem PDF"
          icon="pi pi-file-pdf"
          size="small"
          severity="secondary"
          outlined
          @click="openPdfInNewTab(viewing.generated_pdf_url)"
        />
        <Button
          label="Sinh PDF"
          icon="pi pi-file"
          size="small"
          outlined
          @click="generateAppendix(viewing)"
        />
        <Button
          v-if="viewing.status === 'DRAFT'"
          label="Gửi phê duyệt"
          icon="pi pi-send"
          size="small"
          severity="info"
          outlined
          @click="submitForApproval(viewing)"
        />
        <Button
          v-if="['DRAFT', 'REJECTED'].includes(viewing.status)"
          label="Chỉnh sửa"
          icon="pi pi-pencil"
          size="small"
          severity="success"
          outlined
          @click="edit(viewing); viewDialog = false"
        />
      </div>

      <!-- Content by Type -->
      <Accordion :value="['0', '1', '2']" multiple>
        <!-- Thông tin thay đổi -->
        <AccordionPanel value="0">
          <AccordionHeader>
            <div class="flex items-center gap-2">
              <i class="pi pi-list text-primary"></i>
              <span class="font-semibold">Nội dung thay đổi</span>
            </div>
          </AccordionHeader>
          <AccordionContent>
            <!-- DEPARTMENT Change -->
            <div v-if="viewing.appendix_type === 'DEPARTMENT'" class="space-y-3">
              <div class="bg-gray-50 p-3 rounded">
                <div class="text-sm text-gray-600 mb-1">Đơn vị mới:</div>
                <div class="font-medium text-lg">{{ viewing.department?.name || '—' }}</div>
              </div>
              <div v-if="viewing.summary" class="text-sm">
                <div class="text-gray-600 mb-1">Tóm tắt:</div>
                <div class="whitespace-pre-wrap">{{ viewing.summary }}</div>
              </div>
            </div>

            <!-- POSITION Change -->
            <div v-if="viewing.appendix_type === 'POSITION'" class="space-y-3">
              <div class="bg-gray-50 p-3 rounded">
                <div class="text-sm text-gray-600 mb-1">Chức danh mới:</div>
                <div class="font-medium text-lg">{{ viewing.position?.title || '—' }}</div>
              </div>
              <div v-if="viewing.base_salary" class="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <span class="text-gray-600">Lương cơ bản:</span>
                  <div class="font-medium">{{ formatCurrency(viewing.base_salary) }}</div>
                </div>
                <div v-if="viewing.position_allowance">
                  <span class="text-gray-600">PC vị trí:</span>
                  <div class="font-medium">{{ formatCurrency(viewing.position_allowance) }}</div>
                </div>
              </div>
              <div v-if="viewing.summary" class="text-sm">
                <div class="text-gray-600 mb-1">Tóm tắt:</div>
                <div class="whitespace-pre-wrap">{{ viewing.summary }}</div>
              </div>
            </div>

            <!-- SALARY Change -->
            <div v-if="viewing.appendix_type === 'SALARY'" class="space-y-3">
              <div class="bg-gray-50 p-3 rounded space-y-2">
                <div v-if="viewing.base_salary" class="flex justify-between">
                  <span class="text-gray-600">Lương cơ bản:</span>
                  <span class="font-semibold">{{ formatCurrency(viewing.base_salary) }}</span>
                </div>
                <div v-if="viewing.insurance_salary" class="flex justify-between">
                  <span class="text-gray-600">Lương BHXH:</span>
                  <span class="font-semibold">{{ formatCurrency(viewing.insurance_salary) }}</span>
                </div>
                <div v-if="viewing.position_allowance" class="flex justify-between">
                  <span class="text-gray-600">PC vị trí:</span>
                  <span class="font-semibold">{{ formatCurrency(viewing.position_allowance) }}</span>
                </div>
              </div>
              <div v-if="viewing.other_allowances && viewing.other_allowances.length > 0">
                <div class="text-sm text-gray-600 mb-2">Phụ cấp khác:</div>
                <div class="space-y-1">
                  <div v-for="(al, idx) in viewing.other_allowances" :key="idx" class="flex justify-between text-sm bg-gray-50 p-2 rounded">
                    <span>{{ al.name }}</span>
                    <span class="font-medium">{{ formatCurrency(al.amount) }}</span>
                  </div>
                </div>
              </div>
              <div v-if="viewing.summary" class="text-sm">
                <div class="text-gray-600 mb-1">Tóm tắt:</div>
                <div class="whitespace-pre-wrap">{{ viewing.summary }}</div>
              </div>
            </div>

            <!-- ALLOWANCE Change -->
            <div v-if="viewing.appendix_type === 'ALLOWANCE'" class="space-y-3">
              <div class="bg-gray-50 p-3 rounded space-y-2">
                <div v-if="viewing.position_allowance" class="flex justify-between">
                  <span class="text-gray-600">PC vị trí:</span>
                  <span class="font-semibold">{{ formatCurrency(viewing.position_allowance) }}</span>
                </div>
              </div>
              <div v-if="viewing.other_allowances && viewing.other_allowances.length > 0">
                <div class="text-sm text-gray-600 mb-2">Phụ cấp khác:</div>
                <div class="space-y-1">
                  <div v-for="(al, idx) in viewing.other_allowances" :key="idx" class="flex justify-between text-sm bg-gray-50 p-2 rounded">
                    <span>{{ al.name }}</span>
                    <span class="font-medium">{{ formatCurrency(al.amount) }}</span>
                  </div>
                </div>
              </div>
              <div v-if="viewing.summary" class="text-sm">
                <div class="text-gray-600 mb-1">Tóm tắt:</div>
                <div class="whitespace-pre-wrap">{{ viewing.summary }}</div>
              </div>
            </div>

            <!-- WORKING_TERMS Change -->
            <div v-if="viewing.appendix_type === 'WORKING_TERMS'" class="space-y-3">
              <div class="bg-gray-50 p-3 rounded space-y-2">
                <div v-if="viewing.working_time">
                  <span class="text-gray-600 text-sm">Thời gian làm việc:</span>
                  <div class="font-medium">{{ viewing.working_time }}</div>
                </div>
                <div v-if="viewing.work_location">
                  <span class="text-gray-600 text-sm">Địa điểm:</span>
                  <div class="font-medium">{{ viewing.work_location }}</div>
                </div>
              </div>
              <div v-if="viewing.summary" class="text-sm">
                <div class="text-gray-600 mb-1">Tóm tắt:</div>
                <div class="whitespace-pre-wrap">{{ viewing.summary }}</div>
              </div>
            </div>

            <!-- EXTENSION -->
            <div v-if="viewing.appendix_type === 'EXTENSION'" class="space-y-3">
              <div class="bg-gray-50 p-3 rounded">
                <div class="text-sm text-gray-600 mb-1">Gia hạn đến:</div>
                <div class="font-medium text-lg">{{ formatDate(viewing.end_date) }}</div>
              </div>
              <div v-if="viewing.summary" class="text-sm">
                <div class="text-gray-600 mb-1">Tóm tắt:</div>
                <div class="whitespace-pre-wrap">{{ viewing.summary }}</div>
              </div>
            </div>

            <!-- OTHER -->
            <div v-if="viewing.appendix_type === 'OTHER'" class="space-y-3">
              <div v-if="viewing.summary" class="text-sm">
                <div class="text-gray-600 mb-1">Nội dung:</div>
                <div class="whitespace-pre-wrap bg-gray-50 p-3 rounded">{{ viewing.summary }}</div>
              </div>
            </div>

            <!-- Note -->
            <div v-if="viewing.note" class="mt-4 pt-4 border-t">
              <div class="text-sm text-gray-600 mb-1">Ghi chú:</div>
              <div class="text-sm whitespace-pre-wrap bg-yellow-50 p-3 rounded border border-yellow-200">{{ viewing.note }}</div>
            </div>
          </AccordionContent>
        </AccordionPanel>

        <!-- Tệp đính kèm -->
        <AccordionPanel value="1">
          <AccordionHeader>
            <div class="flex items-center gap-2">
              <i class="pi pi-paperclip text-primary"></i>
              <span class="font-semibold">Tệp đính kèm</span>
              <Badge v-if="viewing.attachments?.length" :value="viewing.attachments.length" severity="info" />
            </div>
          </AccordionHeader>
          <AccordionContent>
            <div v-if="!viewing.attachments || viewing.attachments.length === 0" class="text-gray-500 text-sm text-center py-4">
              Không có tệp đính kèm
            </div>
            <div v-else class="space-y-2">
              <a
                v-for="att in viewing.attachments"
                :key="att.id"
                :href="att.download_url"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-3 p-3 bg-white border rounded hover:bg-blue-50 hover:border-blue-300 transition-all group"
              >
                <i class="pi pi-file text-xl text-gray-400"></i>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm truncate group-hover:text-primary">{{ att.file_name }}</div>
                  <div class="text-xs text-gray-500">{{ formatDate(att.created_at) }}</div>
                </div>
                <i class="pi pi-download text-gray-400 group-hover:text-primary"></i>
              </a>
            </div>
          </AccordionContent>
        </AccordionPanel>

        <!-- Audit/Workflow -->
        <AccordionPanel value="2">
          <AccordionHeader>
            <div class="flex items-center gap-2">
              <i class="pi pi-history text-primary"></i>
              <span class="font-semibold">Thông tin phê duyệt</span>
            </div>
          </AccordionHeader>
          <AccordionContent>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Nguồn tạo:</span>
                <span class="font-medium">{{ viewing.source === 'WORKFLOW' ? 'Workflow' : 'Backfill' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Ngày tạo:</span>
                <span class="font-medium">{{ formatDate(viewing.created_at) }}</span>
              </div>
              <div v-if="viewing.approved_at" class="flex justify-between">
                <span class="text-gray-600">Ngày phê duyệt:</span>
                <span class="font-medium">{{ formatDate(viewing.approved_at) }}</span>
              </div>
              <div v-if="viewing.rejected_at" class="flex justify-between">
                <span class="text-gray-600">Ngày từ chối:</span>
                <span class="font-medium">{{ formatDate(viewing.rejected_at) }}</span>
              </div>
              <div v-if="viewing.approval_note" class="pt-3 border-t">
                <div class="text-gray-600 mb-2">Ý kiến phê duyệt:</div>
                <div class="bg-gray-50 p-3 rounded whitespace-pre-wrap">{{ viewing.approval_note }}</div>
              </div>
            </div>
          </AccordionContent>
        </AccordionPanel>
      </Accordion>
    </div>
  </Drawer>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import Drawer from 'primevue/drawer'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import Badge from 'primevue/badge'
import { ContractAppendixService } from '@/services/ContractAppendixService'
import { useFormValidation } from '@/composables/useFormValidation'
import { toYMD, formatDate } from '@/utils/dateHelper'
import AttachmentUploader from '@/Components/AttachmentUploader.vue'

const { errors, hasError, getError } = useFormValidation()

const props = defineProps({
  contractId: { type: String, required: true },
  appendixes: { type: Array, default: () => [] },
  appendixTemplates: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  positions: { type: Array, default: () => [] },
  canBackfill: { type: Boolean, default: false }
})

const rows = computed(() => props.appendixes || [])
const dt = ref()
const selected = ref([])
const dialog = ref(false)
const saving = ref(false)
const submitted = ref(false)
const current = ref(null)
const deleting = ref(false)
const deleteDialog = ref(false)
const deleteManyDialog = ref(false)
const filters = ref({ global: { value: null, matchMode: 'contains' } })

const generating = ref(false)
const generateDialog = ref(false)
const currentAppendix = ref(null)
const selectedTemplateId = ref(null)
const availableTemplates = ref([])
const loadingTemplates = ref(false)
const defaultTemplate = ref(null)

const viewDialog = ref(false)
const viewing = ref(null)

const attachmentUploader = ref()

const form = ref({
  id: null,
  appendix_no: '',
  appendix_type: null,
  source: 'WORKFLOW',
  effective_date: null,
  end_date: null,
  status: 'DRAFT',
  base_salary: null,
  insurance_salary: null,
  position_allowance: null,
  other_allowances: [],
  department_id: null,
  position_id: null,
  working_time: '',
  work_location: '',
  summary: '',
  note: '',
  attachments: [],
  newAttachments: [],
  deleteAttachments: []
})

const typeOptions = [
  { value: 'SALARY', label: 'Điều chỉnh lương' },
  { value: 'ALLOWANCE', label: 'Điều chỉnh phụ cấp' },
  { value: 'POSITION', label: 'Điều chỉnh chức danh' },
  { value: 'DEPARTMENT', label: 'Điều chuyển đơn vị' },
  { value: 'WORKING_TERMS', label: 'Thời gian/địa điểm làm việc' },
  { value: 'EXTENSION', label: 'Gia hạn HĐ' },
  { value: 'OTHER', label: 'Khác' }
]

// Type-driven schema: xác định field nào hiển thị và required cho từng type
const appendixTypeSchema = {
  SALARY: {
    show: ['base_salary', 'insurance_salary', 'position_allowance', 'other_allowances', 'summary'],
    required: ['base_salary'],
    sectionTitle: 'Thông tin lương'
  },
  ALLOWANCE: {
    show: ['position_allowance', 'other_allowances', 'summary'],
    requiredAtLeastOne: ['position_allowance', 'other_allowances'],
    sectionTitle: 'Thông tin phụ cấp'
  },
  POSITION: {
    show: ['position_id', 'base_salary', 'position_allowance', 'summary'],
    required: ['position_id'],
    sectionTitle: 'Thông tin chức danh mới'
  },
  DEPARTMENT: {
    show: ['department_id', 'summary'],
    required: ['department_id'],
    sectionTitle: 'Thông tin đơn vị mới'
  },
  WORKING_TERMS: {
    show: ['working_time', 'work_location', 'summary'],
    required: ['working_time', 'work_location'],
    sectionTitle: 'Điều kiện làm việc mới'
  },
  EXTENSION: {
    show: ['end_date', 'summary'],
    required: ['end_date'],
    sectionTitle: 'Thông tin gia hạn'
  },
  OTHER: {
    show: ['summary'],
    required: ['summary'],
    sectionTitle: 'Nội dung thay đổi'
  }
}

// Computed: schema cho type hiện tại
const currentSchema = computed(() => {
  return appendixTypeSchema[form.value.appendix_type] || { show: [], required: [], sectionTitle: '' }
})

// Helper: check xem field có hiển thị không
const showField = (fieldName) => {
  if (!form.value.appendix_type) return true // Chưa chọn type thì hiện tất cả
  return currentSchema.value.show.includes(fieldName)
}

// Computed: show status field chỉ khi backfill hoặc edit legacy appendix
const showStatusField = computed(() => {
  return props.canBackfill || form.value.source === 'LEGACY'
})

const statusOptions = [
  { value: 'DRAFT', label: 'Nháp' },
  { value: 'PENDING_APPROVAL', label: 'Chờ phê duyệt' },
  { value: 'ACTIVE', label: 'Đang hiệu lực' },
  { value: 'REJECTED', label: 'Bị từ chối' },
  { value: 'CANCELLED', label: 'Đã hủy' }
]

const statusSeverity = (s) =>
  ({
    DRAFT: 'secondary',
    PENDING_APPROVAL: 'warn',
    ACTIVE: 'success',
    REJECTED: 'danger',
    CANCELLED: 'contrast'
  }[s] || 'info')

const formatCurrency = (amount) => {
  if (!amount) return '—'
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount)
}

const exportCSV = () => dt.value?.exportCSV()

function openNew() {
  reset()
  submitted.value = false
  dialog.value = true
}

function viewAppendix(appendix) {
  viewing.value = appendix
  viewDialog.value = true
}

function openPdfInNewTab(url) {
  if (url) {
    window.open(url, '_blank')
  }
}

function edit(row) {
  form.value = {
    ...row,
    other_allowances: Array.isArray(row.other_allowances)
      ? JSON.parse(JSON.stringify(row.other_allowances))
      : [],
    attachments: row.attachments || [],
    newAttachments: [],
    deleteAttachments: []
  }
  submitted.value = false
  dialog.value = true
}

function reset() {
  form.value = {
    id: null,
    appendix_no: '',
    appendix_type: null,
    source: 'WORKFLOW',
    effective_date: null,
    end_date: null,
    status: 'DRAFT',
    base_salary: null,
    insurance_salary: null,
    position_allowance: null,
    other_allowances: [],
    department_id: null,
    position_id: null,
    working_time: '',
    work_location: '',
    summary: '',
    note: '',
    attachments: [],
    newAttachments: [],
    deleteAttachments: []
  }
}

function closeDialog() {
  dialog.value = false
  submitted.value = false
  if (attachmentUploader.value) {
    attachmentUploader.value.reset()
  }
}

function save() {
  submitted.value = true

  // Basic validation
  if (!form.value.appendix_no || !form.value.appendix_type || !form.value.effective_date) {
    return
  }

  // Status required only if showing status field
  if (showStatusField.value && !form.value.status) {
    return
  }

  // Type-specific validation
  const schema = currentSchema.value

  // Check required fields
  if (schema.required) {
    for (const field of schema.required) {
      if (!form.value[field]) {
        return // Validation failed
      }
    }
  }

  // Check requiredAtLeastOne (for ALLOWANCE type)
  if (schema.requiredAtLeastOne) {
    const hasAtLeastOne = schema.requiredAtLeastOne.some(field => {
      const value = form.value[field]
      if (field === 'other_allowances') {
        return Array.isArray(value) && value.length > 0
      }
      return value && value > 0
    })
    if (!hasAtLeastOne) {
      return // Validation failed
    }
  }

  saving.value = true

  // Prepare FormData for file upload
  const formData = new FormData()

  // Add all form fields
  Object.keys(form.value).forEach(key => {
    if (key === 'newAttachments' || key === 'deleteAttachments' || key === 'attachments') {
      return // Skip these, handle separately
    }

    if (key === 'other_allowances') {
      // Append array items individually for FormData
      if (Array.isArray(form.value[key])) {
        form.value[key].forEach((item, index) => {
          formData.append(`other_allowances[${index}][name]`, item.name || '')
          formData.append(`other_allowances[${index}][amount]`, item.amount || 0)
        })
      }
    } else if (key === 'effective_date' || key === 'end_date') {
      const dateValue = toYMD(form.value[key])
      if (dateValue) formData.append(key, dateValue)
    } else if (form.value[key] !== null && form.value[key] !== undefined && form.value[key] !== '') {
      formData.append(key, form.value[key])
    }
  })  // Add new attachment files
  if (form.value.newAttachments && form.value.newAttachments.length > 0) {
    form.value.newAttachments.forEach(file => {
      formData.append('attachments[]', file)
    })
  }

  // Add IDs of attachments to delete
  if (form.value.deleteAttachments && form.value.deleteAttachments.length > 0) {
    form.value.deleteAttachments.forEach(id => {
      formData.append('delete_attachments[]', id)
    })
  }

  const opts = {
    onFinish: () => (saving.value = false),
    onSuccess: () => {
      dialog.value = false
      submitted.value = false
      if (attachmentUploader.value) {
        attachmentUploader.value.reset()
      }
    },
    onError: (errors) => {
      console.error('Appendix save error:', errors)
    }
  }

  console.log('Saving appendix with files:', {
    hasNewFiles: form.value.newAttachments?.length > 0,
    newFilesCount: form.value.newAttachments?.length,
    deleteIdsCount: form.value.deleteAttachments?.length
  })

  if (!form.value.id) {
    router.post(`/contracts/${props.contractId}/appendixes`, formData, {
      forceFormData: true,
      ...opts
    })
  } else {
    formData.append('_method', 'PUT')
    router.post(`/contracts/${props.contractId}/appendixes/${form.value.id}`, formData, {
      forceFormData: true,
      ...opts
    })
  }
}

function confirmDelete(row) {
  current.value = row
  deleteDialog.value = true
}

function remove() {
  deleting.value = true
  ContractAppendixService.destroy(props.contractId, current.value.id, {
    onSuccess: () => {
      deleting.value = false
      deleteDialog.value = false
      current.value = null
    },
    onError: () => {
      deleting.value = false
    },
    onFinish: () => {
      deleting.value = false
    }
  })
}

function confirmDeleteSelected() {
  deleteManyDialog.value = true
}

function removeMany() {
  const ids = selected.value.map((x) => x.id)
  deleting.value = true
  ContractAppendixService.bulkDelete(props.contractId, ids, {
    onSuccess: () => {
      deleting.value = false
      deleteManyDialog.value = false
      selected.value = []
    },
    onError: () => {
      deleting.value = false
    },
    onFinish: () => {
      deleting.value = false
    }
  })
}

// Approval dialogs
const approveDialog = ref(false)
const rejectDialog = ref(false)
const submitDialog = ref(false)
const recallDialog = ref(false)
const approving = ref(false)
const rejecting = ref(false)
const submitting = ref(false)
const recalling = ref(false)
const approvalNote = ref('')
const rejectNote = ref('')
const rejectSubmitted = ref(false)

function submitForApproval(row) {
  current.value = row
  submitDialog.value = true
}

function confirmSubmit() {
  submitting.value = true
  ContractAppendixService.submitForApproval(props.contractId, current.value.id, {
    onSuccess: () => {
      // Update local state instead of reloading
      const index = props.appendixes.findIndex(a => a.id === current.value.id)
      if (index !== -1) {
        props.appendixes[index].status = 'PENDING_APPROVAL'
        props.appendixes[index].status_label = 'Chờ duyệt'
      }
      submitDialog.value = false
      current.value = null
    },
    onError: () => {},
    onFinish: () => {
      submitting.value = false
    }
  })
}

function recall(row) {
  current.value = row
  recallDialog.value = true
}

function confirmRecall() {
  recalling.value = true
  ContractAppendixService.recall(props.contractId, current.value.id, {
    onSuccess: () => {
      // Update local state instead of reloading
      const index = props.appendixes.findIndex(a => a.id === current.value.id)
      if (index !== -1) {
        props.appendixes[index].status = 'DRAFT'
        props.appendixes[index].status_label = 'Nháp'
      }
      recallDialog.value = false
      current.value = null
    },
    onError: () => {},
    onFinish: () => {
      recalling.value = false
    }
  })
}

function approve(row) {
  current.value = row
  approvalNote.value = ''
  approveDialog.value = true
}

function confirmApprove() {
  approving.value = true
  ContractAppendixService.approve(props.contractId, current.value.id, { note: approvalNote.value }, {
    onSuccess: () => {
      // Update local state instead of reloading
      const index = props.appendixes.findIndex(a => a.id === current.value.id)
      if (index !== -1) {
        props.appendixes[index].status = 'ACTIVE'
        props.appendixes[index].status_label = 'Đã duyệt'
      }
      approveDialog.value = false
      current.value = null
      approvalNote.value = ''
    },
    onError: () => {},
    onFinish: () => {
      approving.value = false
    }
  })
}

function reject(row) {
  current.value = row
  rejectNote.value = ''
  rejectSubmitted.value = false
  rejectDialog.value = true
}

function confirmReject() {
  rejectSubmitted.value = true

  if (!rejectNote.value) {
    return
  }

  rejecting.value = true
  ContractAppendixService.reject(props.contractId, current.value.id, { note: rejectNote.value }, {
    onSuccess: () => {
      // Update local state instead of reloading
      const index = props.appendixes.findIndex(a => a.id === current.value.id)
      if (index !== -1) {
        props.appendixes[index].status = 'REJECTED'
        props.appendixes[index].status_label = 'Bị từ chối'
      }
      rejectDialog.value = false
      current.value = null
      rejectNote.value = ''
      rejectSubmitted.value = false
    },
    onError: () => {},
    onFinish: () => {
      rejecting.value = false
    }
  })
}

// other_allowances
function addAllowance() {
  if (!Array.isArray(form.value.other_allowances)) form.value.other_allowances = []
  form.value.other_allowances.push({ name: '', amount: null })
}
function removeAllowance(idx) {
  form.value.other_allowances.splice(idx, 1)
}

// Generate appendix PDF - auto-select default template based on appendix_type
async function generateAppendix(row) {
  currentAppendix.value = row
  selectedTemplateId.value = null
  defaultTemplate.value = null
  generateDialog.value = true

  // Load available templates for this appendix type
  loadingTemplates.value = true
  try {
    const response = await fetch(`/contract-appendix-templates?appendix_type=${row.appendix_type}`)
    const data = await response.json()
    availableTemplates.value = data.data || []

    // Auto-select default template
    defaultTemplate.value = availableTemplates.value.find(t => t.is_default) || availableTemplates.value[0]
    if (defaultTemplate.value) {
      selectedTemplateId.value = defaultTemplate.value.id
    }
  } catch (error) {
    console.error('Failed to load templates:', error)
    availableTemplates.value = []
  } finally {
    loadingTemplates.value = false
  }
}

// Confirm and generate PDF with selected template
function confirmGenerate() {
  if (!currentAppendix.value) return

  generating.value = true
  const payload = selectedTemplateId.value ? { template_id: selectedTemplateId.value } : {}

  ContractAppendixService.generate(props.contractId, currentAppendix.value.id, payload, {
    onFinish: () => {
      generating.value = false
      generateDialog.value = false
    }
  })
}
</script>

<style scoped>
.required-field::after {
  content: ' *';
  color: red;
}
</style>
