<template>
  <Head>
    <title>Hồ sơ nhân viên - {{ props.employee.full_name }}</title>
  </Head>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
    <!-- Sidebar: Profile Completion -->
    <div class="lg:col-span-1">
      <ProfileChecklist
        :completion-score="props.employee.completion_score || 0"
        :completion-details="props.employee.completion_details || []"
        :completion-missing="props.employee.completion_missing || []"
        :completion-level="props.employee.completion_level || 'Chưa xác định'"
        :completion-severity="props.employee.completion_severity || 'secondary'"
      />
    </div>

    <!-- Main Content -->
    <div class="lg:col-span-3">
      <div class="card">
        <div class="mb-6">
          <h2 class="text-xl font-semibold mb-4">Hồ sơ: {{ props.employee.full_name }} ({{ props.employee.employee_code }})</h2>

          <!-- Tenure Information -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Card class="bg-blue-50 border border-blue-200">
              <template #content>
                <div class="flex items-center gap-3">
                  <i class="pi pi-calendar text-blue-600 text-2xl flex-shrink-0"></i>
                  <div class="flex-1">
                    <p class="text-xs text-gray-600 mb-1">Thâm niên đợt hiện tại</p>
                    <p class="font-semibold text-blue-700">{{ props.employee.current_tenure_text || '0 ngày' }}</p>
                  </div>
                </div>
              </template>
            </Card>
            <Card class="bg-green-50 border border-green-200">
              <template #content>
                <div class="flex items-center gap-3">
                  <i class="pi pi-chart-line text-green-600 text-2xl flex-shrink-0"></i>
                  <div class="flex-1">
                    <p class="text-xs text-gray-600 mb-1">Thâm niên tích lũy</p>
                    <p class="font-semibold text-green-700">{{ props.employee.cumulative_tenure_text || '0 ngày' }}</p>
                  </div>
                </div>
              </template>
            </Card>
            <Card class="bg-purple-50 border border-purple-200">
              <template #content>
                <div class="flex items-center gap-3">
                  <i class="pi pi-sign-in text-purple-600 text-2xl flex-shrink-0"></i>
                  <div class="flex-1">
                    <p class="text-xs text-gray-600 mb-1">Ngày vào làm (đợt hiện tại)</p>
                    <p class="font-semibold text-purple-700">{{ props.employee.current_employment_start || '-' }}</p>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <Tabs value="education">
      <TabList>
        <Tab value="education">Học vấn</Tab>
        <Tab value="relatives">Người thân</Tab>
        <Tab value="experiences">Kinh nghiệm</Tab>
        <Tab value="skills">Kỹ năng</Tab>
        <Tab value="assignments">Phân công</Tab>
        <Tab value="contracts">Hợp đồng</Tab>
        <Tab value="leave-balances">Số dư phép</Tab>
        <Tab value="employment-history">Lịch sử làm việc</Tab>
        <Tab value="timeline">Lịch sử</Tab>
      </TabList>

      <!-- TAB HỌC VẤN -->
      <TabPanel value="education">
        <Toolbar class="mb-4">
          <template #start>
            <Button label="Thêm học vấn" icon="pi pi-plus" class="mr-2" @click="openEduNew" />
            <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined"
              @click="confirmEduDeleteSelected" :disabled="!selectedEdu || !selectedEdu.length" />
          </template>
          <template #end>
            <Button label="Xuất dữ liệu" icon="pi pi-upload" severity="secondary" @click="exportEduCSV" />
          </template>
        </Toolbar>

        <DataTable
          ref="eduDt"
          :value="eduRows"
          v-model:selection="selectedEdu"
          dataKey="id"
          :paginator="true" :rows="10"
          :filters="eduFilters"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          :rowsPerPageOptions="[5,10,25]"
          currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} học vấn"
        >
          <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
              <h4 class="m-0">Danh sách Học vấn</h4>
              <IconField>
                <InputIcon><i class="pi pi-search" /></InputIcon>
                <InputText v-model="eduFilters['global'].value" placeholder="Tìm kiếm..." />
              </IconField>
            </div>
          </template>

          <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
          <Column field="education_level.name" header="Trình độ" headerStyle="min-width:12rem;">
            <template #body="sp">{{ sp.data.education_level?.name || '-' }}</template>
          </Column>
          <Column field="school.name" header="Trường" headerStyle="min-width:12rem;">
            <template #body="sp">{{ sp.data.school?.name || '-' }}</template>
          </Column>
          <Column field="major" header="Chuyên ngành" headerStyle="min-width:12rem;">
            <template #body="sp">{{ sp.data.major || '-' }}</template>
          </Column>
          <Column field="start_year" header="Từ năm" sortable headerStyle="width:8rem;">
            <template #body="sp">{{ sp.data.start_year || '-' }}</template>
          </Column>
          <Column field="end_year" header="Đến năm" sortable headerStyle="width:8rem;">
            <template #body="sp">{{ sp.data.end_year || '-' }}</template>
          </Column>
          <Column field="grade" header="Xếp loại" headerStyle="min-width:10rem;">
            <template #body="sp">{{ sp.data.grade || '-' }}</template>
          </Column>
          <Column headerStyle="min-width:10rem;">
            <template #body="sp">
              <Button icon="pi pi-pencil" class="mr-2" outlined severity="success" rounded @click="openEduEdit(sp.data)" />
              <Button icon="pi pi-trash" class="mt-2" outlined severity="danger" rounded @click="confirmEduDelete(sp.data)" />
            </template>
          </Column>
        </DataTable>

        <!-- Dialog Học vấn -->
        <Dialog v-model:visible="eduDialog" :style="{ width: '600px' }" header="Thông tin Học vấn" :modal="true">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block font-bold mb-2">Trình độ</label>
              <Select v-model="eduForm.education_level_id" :options="props.education_levels" optionLabel="name" optionValue="id" filter showClear fluid />
            </div>
            <div>
              <label class="block font-bold mb-2">Trường</label>
              <Select v-model="eduForm.school_id" :options="props.schools" optionLabel="name" optionValue="id" filter showClear fluid />
            </div>
            <div>
              <label class="block font-bold mb-2">Chuyên ngành</label>
              <InputText v-model.trim="eduForm.major" class="w-full" />
            </div>
            <div>
              <label class="block font-bold mb-2">Hình thức học</label>
              <Select v-model="eduForm.study_form"
                      :options="studyFormOptions" optionLabel="label" optionValue="value" filter showClear fluid />
            </div>
            <div>
              <label class="block font-bold mb-2">Từ năm</label>
              <InputText v-model.number="eduForm.start_year" class="w-full" placeholder="VD: 2018" />
            </div>
            <div>
              <label class="block font-bold mb-2">Đến năm</label>
              <InputText v-model.number="eduForm.end_year" class="w-full" placeholder="VD: 2022" />
            </div>
            <div>
              <label class="block font-bold mb-2">Số hiệu văn bằng</label>
              <InputText v-model.trim="eduForm.certificate_no" class="w-full" />
            </div>
            <div>
              <label class="block font-bold mb-2">Ngày tốt nghiệp</label>
              <DatePicker v-model="eduForm.graduation_date" dateFormat="yy-mm-dd" showIcon fluid />
            </div>
            <div class="md:col-span-2">
              <label class="block font-bold mb-2">Xếp loại</label>
              <InputText v-model.trim="eduForm.grade" class="w-full" />
            </div>
            <div class="md:col-span-2">
              <label class="block font-bold mb-2">Ghi chú</label>
              <Textarea v-model.trim="eduForm.note" autoResize rows="3" class="w-full" />
            </div>
          </div>
          <template #footer>
            <Button label="Hủy" icon="pi pi-times" text @click="closeEduDialog" />
            <Button label="Lưu" icon="pi pi-check" @click="saveEducation" :loading="savingEdu" />
          </template>
        </Dialog>

        <!-- Dialog xóa -->
        <Dialog v-model:visible="eduDeleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
          <div class="flex items-center gap-4">
            <i class="pi pi-exclamation-triangle !text-3xl" />
            <span v-if="currentEdu">Bạn có chắc muốn xóa học vấn này?</span>
          </div>
          <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="eduDeleteDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeEducation" :loading="deletingEdu" />
          </template>
        </Dialog>

        <!-- Xóa nhiều -->
        <Dialog v-model:visible="eduDeleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
          <div class="flex items-center gap-4">
            <i class="pi pi-exclamation-triangle !text-3xl" />
            <span>Bạn có chắc muốn xóa các bản ghi đã chọn?</span>
          </div>
          <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="eduDeleteManyDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeManyEducation" :loading="deletingEdu" />
          </template>
        </Dialog>
      </TabPanel>

      <!-- 3 Tab còn lại: để bạn fill tương tự (Relatives / Experiences / Skills) -->
      <TabPanel value="relatives">
        <Toolbar class="mb-4">
            <template #start>
            <Button label="Thêm người thân" icon="pi pi-plus" class="mr-2" @click="openRelNew" />
            <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined"
                @click="confirmRelDeleteSelected" :disabled="!relSelected || !relSelected.length" />
            </template>
        </Toolbar>

        <DataTable
            ref="relDt"
            :value="relRows"
            v-model:selection="relSelected"
            dataKey="id"
            :paginator="true" :rows="10"
            :filters="relFilters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5,10,25]"
            currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} người thân"
        >
            <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Danh sách Người thân</h4>
                <IconField><InputIcon><i class="pi pi-search"/></InputIcon><InputText v-model="relFilters['global'].value" placeholder="Tìm kiếm..." /></IconField>
            </div>
            </template>

            <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
            <Column field="full_name" header="Họ tên" headerStyle="min-width:12rem;" />
            <Column field="relation" header="Quan hệ" headerStyle="min-width:10rem;">
            <template #body="sp">{{ relLabel(sp.data.relation) }}</template>
            </Column>
            <Column field="dob" header="Ngày sinh" headerStyle="min-width:10rem;">
            <template #body="sp">{{ formatDate(sp.data.dob) }}</template>
            </Column>
            <Column field="phone" header="SĐT" headerStyle="min-width:10rem;" />
            <Column field="is_emergency_contact" header="Liên hệ khẩn cấp" headerStyle="min-width:10rem;">
            <template #body="sp"><i :class="sp.data.is_emergency_contact ? 'pi pi-check text-green-500' : 'pi pi-minus text-gray-400'"/></template>
            </Column>
            <Column headerStyle="min-width:10rem;">
            <template #body="sp">
                <Button icon="pi pi-pencil" class="mr-2" outlined severity="success" rounded @click="openRelEdit(sp.data)" />
                <Button icon="pi pi-trash" class="mt-2" outlined severity="danger" rounded @click="confirmRelDelete(sp.data)" />
            </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="relDialog" :style="{ width: '600px' }" header="Thông tin Người thân" :modal="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block font-bold mb-2">Họ tên</label><InputText v-model.trim="relForm.full_name" class="w-full" /></div>
            <div>
                <label class="block font-bold mb-2">Quan hệ</label>
                <Select v-model="relForm.relation" :options="relationOptions" optionLabel="label" optionValue="value" showClear fluid />
            </div>
            <div><label class="block font-bold mb-2">Ngày sinh</label><DatePicker v-model="relForm.dob" dateFormat="yy-mm-dd" showIcon fluid /></div>
            <div><label class="block font-bold mb-2">SĐT</label><InputText v-model.trim="relForm.phone" class="w-full" /></div>
            <div class="md:col-span-2"><label class="block font-bold mb-2">Nghề nghiệp</label><InputText v-model.trim="relForm.occupation" class="w-full" /></div>
            <div class="md:col-span-2"><label class="block font-bold mb-2">Địa chỉ</label><InputText v-model.trim="relForm.address" class="w-full" /></div>
            <div class="md:col-span-2 flex items-center gap-2">
                <Checkbox v-model="relForm.is_emergency_contact" :binary="true" /> <span>Đặt làm liên hệ khẩn cấp</span>
            </div>
            <div class="md:col-span-2"><label class="block font-bold mb-2">Ghi chú</label><Textarea v-model.trim="relForm.note" autoResize rows="3" class="w-full" /></div>
            </div>
            <template #footer>
            <Button label="Hủy" icon="pi pi-times" text @click="relDialog=false" />
            <Button label="Lưu" icon="pi pi-check" @click="saveRelative" :loading="savingRel" />
            </template>
        </Dialog>

        <Dialog v-model:visible="relDeleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa người thân này?</span></div>
            <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="relDeleteDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeRelative" :loading="deletingRel" />
            </template>
        </Dialog>

        <Dialog v-model:visible="relDeleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa các bản ghi đã chọn?</span></div>
            <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="relDeleteManyDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeManyRelative" :loading="deletingRel" />
            </template>
        </Dialog>
      </TabPanel>

      <TabPanel value="experiences">
        <Toolbar class="mb-4">
            <template #start>
            <Button label="Thêm kinh nghiệm" icon="pi pi-plus" class="mr-2" @click="openExpNew" />
            <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined"
                @click="confirmExpDeleteSelected" :disabled="!expSelected || !expSelected.length" />
            </template>
        </Toolbar>

        <DataTable
            ref="expDt"
            :value="expRows"
            v-model:selection="expSelected"
            dataKey="id"
            :paginator="true" :rows="10"
            :filters="expFilters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5,10,25]"
            currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} kinh nghiệm"
        >
            <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Danh sách Kinh nghiệm</h4>
                <IconField><InputIcon><i class="pi pi-search"/></InputIcon><InputText v-model="expFilters['global'].value" placeholder="Tìm kiếm..." /></IconField>
            </div>
            </template>

            <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
            <Column field="company_name" header="Công ty" headerStyle="min-width:14rem;" />
            <Column field="position_title" header="Chức danh" headerStyle="min-width:12rem;" />
            <Column field="start_date" header="Bắt đầu" headerStyle="min-width:10rem;">
            <template #body="sp">{{ formatDate(sp.data.start_date) }}</template>
            </Column>
            <Column field="end_date" header="Kết thúc" headerStyle="min-width:10rem;">
            <template #body="sp">{{ sp.data.is_current ? 'Hiện tại' : formatDate(sp.data.end_date) }}</template>
            </Column>
            <Column field="is_current" header="Đang làm" headerStyle="min-width:8rem;">
            <template #body="sp"><i :class="sp.data.is_current ? 'pi pi-check text-green-500' : 'pi pi-minus text-gray-400'"/></template>
            </Column>
            <Column headerStyle="min-width:10rem;">
            <template #body="sp">
                <Button icon="pi pi-pencil" class="mr-2" outlined severity="success" rounded @click="openExpEdit(sp.data)" />
                <Button icon="pi pi-trash" class="mt-2" outlined severity="danger" rounded @click="confirmExpDelete(sp.data)" />
            </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="expDialog" :style="{ width: '700px' }" header="Thông tin Kinh nghiệm" :modal="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block font-bold mb-2">Công ty</label><InputText v-model.trim="expForm.company_name" class="w-full" /></div>
            <div><label class="block font-bold mb-2">Chức danh</label><InputText v-model.trim="expForm.position_title" class="w-full" /></div>
            <div><label class="block font-bold mb-2">Bắt đầu</label><DatePicker v-model="expForm.start_date" dateFormat="yy-mm-dd" showIcon fluid /></div>
            <div><label class="block font-bold mb-2">Kết thúc</label><DatePicker v-model="expForm.end_date" dateFormat="yy-mm-dd" showIcon fluid :disabled="expForm.is_current" /></div>
            <div class="md:col-span-2 flex items-center gap-2"><Checkbox v-model="expForm.is_current" :binary="true" /> <span>Hiện tại</span></div>
            <div class="md:col-span-2"><label class="block font-bold mb-2">Mô tả công việc</label><Textarea v-model.trim="expForm.responsibilities" autoResize rows="3" class="w-full" /></div>
            <div class="md:col-span-2"><label class="block font-bold mb-2">Thành tích</label><Textarea v-model.trim="expForm.achievements" autoResize rows="3" class="w-full" /></div>
            </div>
            <template #footer>
            <Button label="Hủy" icon="pi pi-times" text @click="expDialog=false" />
            <Button label="Lưu" icon="pi pi-check" @click="saveExperience" :loading="savingExp" />
            </template>
        </Dialog>

        <Dialog v-model:visible="expDeleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa kinh nghiệm này?</span></div>
            <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="expDeleteDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeExperience" :loading="deletingExp" />
            </template>
        </Dialog>

        <Dialog v-model:visible="expDeleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa các bản ghi đã chọn?</span></div>
            <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="expDeleteManyDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeManyExperience" :loading="deletingExp" />
            </template>
        </Dialog>
      </TabPanel>

      <TabPanel value="skills">
        <Toolbar class="mb-4">
            <template #start>
            <Button label="Thêm kỹ năng" icon="pi pi-plus" class="mr-2" @click="openSkillNew" />
            <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined"
                @click="confirmSkillDeleteSelected" :disabled="!skillSelected || !skillSelected.length" />
            </template>
            <template #end>
              <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600">Nhóm:</span>
                <Select v-model="selectedSkillCategory" :options="skillCategoryOptions" optionLabel="label" optionValue="value"
                        placeholder="Tất cả nhóm" showClear class="w-56" />
              </div>
            </template>
        </Toolbar>

        <DataTable
          :value="filteredSkillRows"
          v-model:selection="skillSelected"
          dataKey="id"
          :paginator="false"
          :rowGroupMode="'subheader'"
          :groupRowsBy="'category_name'"
          :sortField="'category_order'"
          :sortOrder="1"
        >
          <template #groupheader="slotProps">
            <div class="flex items-center gap-3 py-2">
              <span class="font-semibold text-lg">{{ slotProps.data.category_name }}</span>
              <Badge :value="getCategorySkillCount(slotProps.data.category_name)" severity="secondary" />
            </div>
          </template>

          <Column selectionMode="multiple" headerStyle="width:3rem"></Column>
          <Column field="skill_name" header="Kỹ năng" headerStyle="min-width:14rem;"></Column>
          <Column field="level" header="Mức (0-5)" headerStyle="min-width:12rem;">
            <template #body="sp">
              <div class="flex items-center gap-2">
                <span>{{ sp.data.level }}</span>
                <Rating :modelValue="sp.data.level" :cancel="false" readonly />
              </div>
            </template>
          </Column>
          <Column field="years" header="Số năm" headerStyle="min-width:8rem;"></Column>
          <Column field="note" header="Ghi chú" headerStyle="min-width:12rem;"></Column>
          <Column headerStyle="min-width:10rem;">
            <template #body="sp">
              <Button icon="pi pi-pencil" class="mr-2" outlined severity="success" rounded @click="openSkillEdit(sp.data)" />
              <Button icon="pi pi-trash" class="mt-2" outlined severity="danger" rounded @click="confirmSkillDelete(sp.data)" />
            </template>
          </Column>

          <template #empty>
            <div class="text-center p-8">
              <i class="pi pi-info-circle text-4xl text-gray-400 mb-3"></i>
              <p class="text-gray-600">Chưa có kỹ năng nào</p>
            </div>
          </template>
        </DataTable>

        <Dialog v-model:visible="skillDialog" :style="{ width: '600px' }" header="Thông tin Kỹ năng" :modal="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-bold mb-2">Nhóm kỹ năng</label>
                <Select v-model="skillFormCategoryFilter" :options="props.skill_categories" optionLabel="name" optionValue="id"
                        placeholder="Chọn nhóm để lọc..." showClear filter fluid />
            </div>
            <div>
                <label class="block font-bold mb-2">Kỹ năng <span class="text-red-500">*</span></label>
                <Select v-model="skillForm.skill_id" :options="filteredSkillsForForm" optionLabel="name" optionValue="id"
                        placeholder="Chọn kỹ năng..." showClear filter fluid
                        :invalid="!!page.props.errors?.skill_id" />
                <small v-if="page.props.errors?.skill_id" class="text-red-500">{{ page.props.errors.skill_id }}</small>
                <small v-else class="text-gray-500">{{ filteredSkillsForForm.length }} kỹ năng khả dụng</small>
            </div>
            <div><label class="block font-bold mb-2">Mức (0-5)</label><InputText v-model.number="skillForm.level" type="number" min="0" max="5" class="w-full" /></div>
            <div><label class="block font-bold mb-2">Số năm</label><InputText v-model.number="skillForm.years" type="number" min="0" class="w-full" /></div>
            <div class="md:col-span-2"><label class="block font-bold mb-2">Ghi chú</label><Textarea v-model.trim="skillForm.note" autoResize rows="3" class="w-full" /></div>
            </div>
            <template #footer>
            <Button label="Hủy" icon="pi pi-times" text @click="skillDialog=false" />
            <Button label="Lưu" icon="pi pi-check" @click="saveSkill" :loading="savingSkill" />
            </template>
        </Dialog>

        <Dialog v-model:visible="skillDeleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa kỹ năng này?</span></div>
            <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="skillDeleteDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeSkill" :loading="deletingSkill" />
            </template>
        </Dialog>

        <Dialog v-model:visible="skillDeleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
            <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa các kỹ năng đã chọn?</span></div>
            <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="skillDeleteManyDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeManySkill" :loading="deletingSkill" />
            </template>
        </Dialog>
      </TabPanel>

      <!-- TAB PHÂN CÔNG -->
      <TabPanel value="assignments">
        <Toolbar class="mb-4">
          <template #start>
            <Button label="Thêm phân công" icon="pi pi-plus" class="mr-2" @click="openAssignmentNew" />
            <Button label="Xóa" icon="pi pi-trash" severity="danger" variant="outlined"
              @click="confirmAssignmentDeleteSelected" :disabled="!selectedAssignments || !selectedAssignments.length" />
          </template>
          <template #end>
            <Button label="Xuất dữ liệu" icon="pi pi-upload" severity="secondary" @click="exportAssignmentCSV" />
          </template>
        </Toolbar>

        <DataTable
          ref="assignmentDt"
          :value="assignmentRows"
          v-model:selection="selectedAssignments"
          dataKey="id"
          :paginator="true" :rows="10"
          :filters="assignmentFilters"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          :rowsPerPageOptions="[5,10,25]"
          currentPageReportTemplate="Hiển thị {first}-{last}/{totalRecords} phân công"
          :rowClass="assignmentRowClass"
        >
          <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
              <h4 class="m-0">Danh sách Phân công</h4>
              <IconField>
                <InputIcon><i class="pi pi-search" /></InputIcon>
                <InputText v-model="assignmentFilters['global'].value" placeholder="Tìm kiếm..." />
              </IconField>
            </div>
          </template>

          <Column selectionMode="multiple" style="width:3rem" :exportable="false" />
          <Column field="department.name" header="Phòng/Ban" sortable style="min-width:14rem" />
          <Column field="position.title" header="Chức danh" sortable style="min-width:12rem">
            <template #body="sp">{{ sp.data.position?.title || '-' }}</template>
          </Column>
          <Column header="Vai trò" style="min-width:10rem">
            <template #body="sp">
              <Tag :value="getRoleLabel(sp.data.role_type)" />
            </template>
          </Column>
          <Column header="Loại" style="min-width:8rem">
            <template #body="sp">
              <Badge :value="sp.data.is_primary ? 'CHÍNH' : 'Phụ'" :severity="sp.data.is_primary ? 'success' : 'secondary'" />
            </template>
          </Column>
          <Column header="Hiệu lực" style="min-width:12rem">
            <template #body="sp">
              {{ formatDate(sp.data.start_date) }}<span v-if="sp.data.end_date"> → {{ formatDate(sp.data.end_date) }}</span>
            </template>
          </Column>
          <Column header="Trạng thái" style="min-width:10rem">
            <template #body="sp">
              <Badge :value="sp.data.status==='ACTIVE' ? 'Hoạt động' : 'Không hoạt động'" :severity="sp.data.status==='ACTIVE' ? 'success' : 'danger'" />
            </template>
          </Column>
          <Column header="Thao tác" :exportable="false" style="min-width:10rem">
            <template #body="sp">
              <div class="flex gap-2">
                <Button icon="pi pi-pencil" variant="outlined" rounded @click="openAssignmentEdit(sp.data)" />
                <Button icon="pi pi-trash" variant="outlined" rounded severity="danger" @click="confirmAssignmentDelete(sp.data)" />
              </div>
            </template>
          </Column>
        </DataTable>

        <!-- Dialog Add/Edit Assignment -->
        <Dialog v-model:visible="assignmentDialog" :style="{ width: '520px' }" :header="assignmentForm.id ? 'Cập nhật phân công' : 'Thêm phân công'" :modal="true">
          <div class="flex flex-col gap-6">
            <div>
              <label class="block font-bold mb-3 required-field">Phòng/Ban</label>
              <Select v-model="assignmentForm.department_id" :options="props.departments" optionLabel="name" filter optionValue="id" fluid
                      :invalid="assignmentSubmitted && !assignmentForm.department_id" />
              <small v-if="assignmentSubmitted && !assignmentForm.department_id" class="text-red-500">Bắt buộc</small>
            </div>

            <div>
              <label class="block font-bold mb-3">Chức danh</label>
              <Select v-model="assignmentForm.position_id" :options="filteredPositions" optionLabel="title" filter optionValue="id" fluid showClear
                      :placeholder="assignmentForm.department_id ? 'Chọn chức danh' : 'Vui lòng chọn phòng/ban trước'"
                      :disabled="!assignmentForm.department_id" />
              <small v-if="!assignmentForm.department_id" class="text-gray-500 block mt-1">Chọn phòng/ban để hiển thị chức danh</small>
              <small v-else-if="filteredPositions.length === 0" class="text-orange-500 block mt-1">Phòng/ban này chưa có chức danh nào</small>
              <small v-else class="text-gray-500 block mt-1">{{ filteredPositions.length }} chức danh khả dụng</small>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block font-bold mb-3 required-field">Vai trò</label>
                <Select v-model="assignmentForm.role_type" :options="roleTypeOptions" optionLabel="label" optionValue="value" fluid
                        :invalid="assignmentSubmitted && !assignmentForm.role_type" />
                <small v-if="assignmentSubmitted && !assignmentForm.role_type" class="text-red-500">Bắt buộc</small>
              </div>

              <div class="flex items-center gap-2 mt-7">
                <Checkbox v-model="assignmentForm.is_primary" :binary="true" inputId="is_primary" @change="onPrimaryChange" />
                <label for="is_primary" class="font-bold">Phân công CHÍNH</label>
              </div>
            </div>

            <!-- Warning về primary assignment -->
            <Message v-if="showPrimaryWarning" severity="warn" :closable="false">
              Nhân viên đã có phân công CHÍNH đang HOẠT ĐỘNG. Nếu bạn tạo phân công CHÍNH mới, phân công cũ sẽ tự động chuyển sang KHÔNG HOẠT ĐỘNG hoặc bỏ cờ CHÍNH.
            </Message>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block font-bold mb-3">Ngày bắt đầu</label>
                <DatePicker v-model="assignmentForm.start_date" dateFormat="yy-mm-dd" showIcon fluid />
              </div>
              <div>
                <label class="block font-bold mb-3">Ngày kết thúc</label>
                <DatePicker v-model="assignmentForm.end_date" dateFormat="yy-mm-dd" showIcon fluid />
              </div>
            </div>

            <div>
              <label class="block font-bold mb-3 required-field">Trạng thái</label>
              <Select v-model="assignmentForm.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid
                      :invalid="assignmentSubmitted && !assignmentForm.status" />
              <small v-if="assignmentSubmitted && !assignmentForm.status" class="text-red-500">Bắt buộc</small>
            </div>
          </div>

          <template #footer>
            <Button label="Hủy" icon="pi pi-times" text @click="assignmentDialog=false" />
            <Button label="Lưu" icon="pi pi-check" @click="saveAssignment" :loading="savingAssignment" />
          </template>
        </Dialog>

        <!-- Dialog Delete Assignment -->
        <Dialog v-model:visible="assignmentDeleteDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
          <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa phân công này?</span></div>
          <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="assignmentDeleteDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeAssignment" :loading="deletingAssignment" />
          </template>
        </Dialog>

        <!-- Dialog Delete Many Assignments -->
        <Dialog v-model:visible="assignmentDeleteManyDialog" :style="{ width: '450px' }" header="Xác nhận" :modal="true">
          <div class="flex items-center gap-4"><i class="pi pi-exclamation-triangle !text-3xl" /><span>Bạn có chắc muốn xóa các phân công đã chọn?</span></div>
          <template #footer>
            <Button label="Không" icon="pi pi-times" text @click="assignmentDeleteManyDialog=false" />
            <Button label="Có" icon="pi pi-check" severity="danger" @click="removeManyAssignment" :loading="deletingAssignment" />
          </template>
        </Dialog>
      </TabPanel>

      <!-- TAB HỢP ĐỒNG -->
      <TabPanel value="contracts">
        <ContractTab :contracts="props.contracts || []" />
      </TabPanel>

      <!-- TAB SỐ DƯ PHÉP -->
      <TabPanel value="leave-balances">
        <LeaveBalanceTab :employee="employee" />
      </TabPanel>

      <!-- TAB LỊCH SỬ LÀM VIỆC -->
      <TabPanel value="employment-history">
        <div class="space-y-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Lịch sử làm việc tại công ty</h3>
            <div class="text-sm text-gray-600">
              <span class="font-medium">Tổng thời gian:</span>
              <span class="text-blue-600 font-semibold">{{ props.employee.cumulative_tenure_text }}</span>
            </div>
          </div>

          <div v-if="!props.employee.employment_history || props.employee.employment_history.length === 0"
               class="text-center py-8 bg-gray-50 rounded-lg">
            <i class="pi pi-info-circle text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-600">Chưa có lịch sử làm việc</p>
          </div>

          <DataTable v-else :value="props.employee.employment_history" class="p-datatable-sm">
            <Column header="STT" headerStyle="width:4rem">
              <template #body="{ index }">{{ index + 1 }}</template>
            </Column>
            <Column field="start_date" header="Từ ngày" headerStyle="width:10rem">
              <template #body="{ data }">
                <span class="font-medium">{{ data.start_date }}</span>
              </template>
            </Column>
            <Column field="end_date" header="Đến ngày" headerStyle="width:10rem">
              <template #body="{ data }">
                <span :class="data.is_current ? 'text-green-600 font-semibold' : 'font-medium'">
                  {{ data.end_date }}
                </span>
              </template>
            </Column>
            <Column field="duration" header="Thời gian" headerStyle="width:12rem">
              <template #body="{ data }">
                <Badge :value="data.duration" severity="info" size="large" />
              </template>
            </Column>
            <Column field="is_current" header="Trạng thái" headerStyle="width:10rem">
              <template #body="{ data }">
                <Badge v-if="data.is_current" value="Đang làm việc" severity="success" />
                <Badge v-else-if="data.end_reason" :value="getEndReasonLabel(data.end_reason)" severity="secondary" />
                <Badge v-else value="Đã kết thúc" severity="secondary" />
              </template>
            </Column>
            <Column field="end_reason" header="Lý do kết thúc" headerStyle="min-width:12rem">
              <template #body="{ data }">
                <span v-if="data.end_reason" class="text-sm text-gray-600">
                  {{ getEndReasonLabel(data.end_reason) }}
                </span>
                <span v-else class="text-gray-400">-</span>
              </template>
            </Column>
          </DataTable>

          <!-- Summary Card -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <Card class="border border-blue-200 bg-blue-50">
              <template #content>
                <div class="flex items-center gap-3">
                  <i class="pi pi-calendar text-blue-600 text-3xl flex-shrink-0"></i>
                  <div class="flex-1">
                    <p class="text-sm text-gray-600 mb-1">Thâm niên đợt hiện tại</p>
                    <p class="text-xl font-bold text-blue-700">{{ props.employee.current_tenure_text }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                      Từ {{ props.employee.current_employment_start || '-' }}
                    </p>
                  </div>
                </div>
              </template>
            </Card>
            <Card class="border border-green-200 bg-green-50">
              <template #content>
                <div class="flex items-center gap-3">
                  <i class="pi pi-chart-line text-green-600 text-3xl flex-shrink-0"></i>
                  <div class="flex-1">
                    <p class="text-sm text-gray-600 mb-1">Thâm niên tích lũy</p>
                    <p class="text-xl font-bold text-green-700">{{ props.employee.cumulative_tenure_text }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                      Tổng {{ props.employee.employment_history?.length || 0 }} đợt làm việc
                    </p>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>
      </TabPanel>

      <!-- TAB LỊCH SỬ -->
      <TabPanel value="timeline">
        <div class="mb-4 flex items-center gap-2">
          <span class="font-medium">Lọc theo module:</span>
          <Select v-model="selectedActivityModule" :options="activityModuleOptions" optionLabel="label" optionValue="value"
                  placeholder="Tất cả" showClear class="w-64" @change="onModuleFilterChange" />
        </div>

        <div v-if="loadingActivities" class="text-center py-8">
          <i class="pi pi-spin pi-spinner text-4xl text-gray-400"></i>
          <p class="text-gray-600 mt-2">Đang tải...</p>
        </div>

        <div v-else-if="activities.length === 0" class="text-center py-8">
          <i class="pi pi-info-circle text-4xl text-gray-400 mb-3"></i>
          <p class="text-gray-600">Chưa có hoạt động nào</p>
        </div>

        <Timeline v-else :value="activities" align="left" class="customized-timeline">
          <template #marker="slotProps">
            <span class="flex w-8 h-8 items-center justify-center text-white rounded-full z-10"
                  :class="getActivityColor(slotProps.item.log_name)">
              <i :class="getActivityIcon(slotProps.item.description)" />
            </span>
          </template>
          <template #content="slotProps">
            <Card class="mt-3">
              <template #title>
                <div class="flex items-center justify-between">
                  <span class="text-base">{{ getActivityLabel(slotProps.item) }}</span>
                  <Badge :value="getModuleLabel(slotProps.item.log_name)" :severity="getModuleSeverity(slotProps.item.log_name)" />
                </div>
              </template>
              <template #subtitle>
                <div class="text-sm text-gray-600">
                  <i class="pi pi-user mr-1" />{{ slotProps.item.causer?.name || 'Hệ thống' }}
                  <i class="pi pi-clock ml-3 mr-1" />{{ formatDateTime(slotProps.item.created_at) }}
                </div>
              </template>
              <template #content>
                <div v-if="slotProps.item.properties" class="text-sm">
                  <pre class="bg-gray-50 p-3 rounded text-xs overflow-auto">{{ JSON.stringify(slotProps.item.properties, null, 2) }}</pre>
                </div>
              </template>
            </Card>
          </template>
        </Timeline>

        <div v-if="activityPagination && activityPagination.last_page > 1" class="mt-4 flex justify-center">
          <Paginator
            :rows="activityPagination.per_page"
            :totalRecords="activityPagination.total"
            :first="(activityPagination.current_page - 1) * activityPagination.per_page"
            @page="onActivityPageChange"
          />
        </div>
      </TabPanel>

        </Tabs>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import { EmployeeEducationService } from '@/services'
import { EmployeeRelativeService } from '@/services'
import { EmployeeExperienceService } from '@/services'
import { EmployeeSkillService } from '@/services'
import { EmployeeAssignmentService } from '@/services'
import { toYMD, formatDate } from '@/utils/dateHelper'
import ProfileChecklist from '@/Components/ProfileChecklist.vue'
import LeaveBalanceTab from '@/Pages/Employees/Components/LeaveBalanceTab.vue'
import ContractTab from '@/Pages/Employees/Components/ContractTab.vue'

const page = usePage()

// PrimeVue imports
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Checkbox from 'primevue/checkbox'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanel from 'primevue/tabpanel'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Badge from 'primevue/badge'
import Rating from 'primevue/rating'
import Timeline from 'primevue/timeline'
import Card from 'primevue/card'
import Paginator from 'primevue/paginator'

const props = defineProps({
  employee: { type: Object, required: true },
  education_levels: { type: Array, required: true },
  schools: { type: Array, required: true },
  departments: { type: Array, required: true },
  positions: { type: Array, required: true },
  skill_categories: { type: Array, default: () => [] },
  educations: { type: Array, required: true },
  relatives: { type: Array, default: () => [] },
  experiences: { type: Array, default: () => [] },
  skills: { type: Array, default: () => [] },           // master danh mục
  employee_skills: { type: Array, default: () => [] },  // kỹ năng của NV
  assignments: { type: Array, default: () => [] },      // phân công của NV
  contracts: { type: Array, default: () => [] },        // hợp đồng của NV
})

// ====== Tab EDUCATION ======
const eduDt = ref()
const eduRows = computed(() => props.educations || [])
const selectedEdu = ref([])
const eduFilters = ref({ global: { value: null, matchMode: 'contains' } })

const eduDialog = ref(false)
const eduDeleteDialog = ref(false)
const eduDeleteManyDialog = ref(false)
const currentEdu = ref(null)
const savingEdu = ref(false)
const deletingEdu = ref(false)
const studyFormOptions = [
  { value: 'FULLTIME', label: 'Chính quy' },
  { value: 'PARTTIME', label: 'Vừa học vừa làm' },
  { value: 'ONLINE', label: 'Trực tuyến' },
]

const eduForm = ref({
  id: null,
  education_level_id: null,
  school_id: null,
  major: '',
  start_year: null,
  end_year: null,
  study_form: null,
  certificate_no: '',
  graduation_date: null,
  grade: '',
  note: '',
})

function openEduNew(){ resetEduForm(); eduDialog.value = true }
function openEduEdit(row){
  eduForm.value = {
    id: row.id,
    education_level_id: row.education_level_id,
    school_id: row.school_id,
    major: row.major,
    start_year: row.start_year,
    end_year: row.end_year,
    study_form: row.study_form,
    certificate_no: row.certificate_no,
    graduation_date: row.graduation_date,
    grade: row.grade,
    note: row.note,
  }
  eduDialog.value = true
}
function closeEduDialog(){ eduDialog.value = false }
function resetEduForm(){
  eduForm.value = {
    id: null,
    education_level_id: null,
    school_id: null,
    major: '',
    start_year: null,
    end_year: null,
    study_form: null,
    certificate_no: '',
    graduation_date: null,
    grade: '',
    note: '',
  }
}

function exportEduCSV(){ eduDt.value?.exportCSV() }

function saveEducation(){
  savingEdu.value = true
  const payload = {
    ...eduForm.value,
    graduation_date: toYMD(eduForm.value.graduation_date)
  }
  const opts = {
    onFinish: () => savingEdu.value = false,
    onSuccess: () => { eduDialog.value = false }
  }
  if (!eduForm.value.id) {
    EmployeeEducationService.store(props.employee.id, payload, opts)
  } else {
    EmployeeEducationService.update(props.employee.id, eduForm.value.id, payload, opts)
  }
}

function confirmEduDelete(row){ currentEdu.value = row; eduDeleteDialog.value = true }
function removeEducation(){
  deletingEdu.value = true
  EmployeeEducationService.destroy(props.employee.id, currentEdu.value.id, {
    onFinish: () => { deletingEdu.value = false; eduDeleteDialog.value = false }
  })
}
function confirmEduDeleteSelected(){ eduDeleteManyDialog.value = true }
function removeManyEducation(){
  const ids = selectedEdu.value.map(x=>x.id)
  deletingEdu.value = true
  EmployeeEducationService.bulkDelete(props.employee.id, ids, {
    onFinish: () => { deletingEdu.value = false; eduDeleteManyDialog.value = false; selectedEdu.value = [] }
  })
}

/* ===== Relatives state & methods ===== */
const relDt = ref()
const relRows = computed(()=> props.relatives || [])
const relSelected = ref([])
const relFilters = ref({ global: { value: null, matchMode: 'contains' } })
const relDialog = ref(false)
const relDeleteDialog = ref(false)
const relDeleteManyDialog = ref(false)
const currentRel = ref(null)
const savingRel = ref(false)
const deletingRel = ref(false)

const relationOptions = [
  { value:'FATHER', label:'Cha' },{ value:'MOTHER', label:'Mẹ' },
  { value:'SPOUSE', label:'Vợ/Chồng' },{ value:'CHILD', label:'Con' },
  { value:'SIBLING', label:'Anh/Chị/Em' },{ value:'OTHER', label:'Khác' },
]
const relLabel = (v)=> (relationOptions.find(x=>x.value===v)?.label || v)

const relForm = ref({
  id:null, full_name:'', relation:null, dob:null, phone:'', occupation:'', address:'',
  is_emergency_contact:false, note:''
})

function openRelNew(){ relForm.value = { id:null, full_name:'', relation:null, dob:null, phone:'', occupation:'', address:'', is_emergency_contact:false, note:'' }; relDialog.value=true }
function openRelEdit(r){
  relForm.value = {
    ...r,
    dob: r.dob ? new Date(r.dob) : null
  }
  relDialog.value=true
}
function saveRelative(){
  savingRel.value = true
  const payload = { ...relForm.value, dob: toYMD(relForm.value.dob) }
  const opts = { onFinish:()=> savingRel.value=false, onSuccess:()=>{ relDialog.value=false } }
  if (!relForm.value.id) EmployeeRelativeService.store(props.employee.id, payload, opts)
  else EmployeeRelativeService.update(props.employee.id, relForm.value.id, payload, opts)
}
function confirmRelDelete(r){ currentRel.value=r; relDeleteDialog.value=true }
function removeRelative(){
  deletingRel.value=true
  EmployeeRelativeService.destroy(props.employee.id, currentRel.value.id, { onFinish:()=>{ deletingRel.value=false; relDeleteDialog.value=false } })
}
function confirmRelDeleteSelected(){ relDeleteManyDialog.value=true }
function removeManyRelative(){
  const ids = relSelected.value.map(x=>x.id)
  deletingRel.value=true
  EmployeeRelativeService.bulkDelete(props.employee.id, ids, { onFinish:()=>{ deletingRel.value=false; relDeleteManyDialog.value=false; relSelected.value=[] } })
}

/* ===== Experiences state & methods ===== */
const expDt = ref()
const expRows = computed(()=> props.experiences || [])
const expSelected = ref([])
const expFilters = ref({ global: { value: null, matchMode: 'contains' } })
const expDialog = ref(false)
const expDeleteDialog = ref(false)
const expDeleteManyDialog = ref(false)
const currentExp = ref(null)
const savingExp = ref(false)
const deletingExp = ref(false)

const expForm = ref({
  id:null, company_name:'', position_title:'', start_date:null, end_date:null,
  is_current:false, responsibilities:'', achievements:''
})

function openExpNew(){ expForm.value={ id:null, company_name:'', position_title:'', start_date:null, end_date:null, is_current:false, responsibilities:'', achievements:'' }; expDialog.value=true }
function openExpEdit(r){ expForm.value={ ...r }; expDialog.value=true }
function saveExperience(){
  savingExp.value=true
  const payload = { ...expForm.value, start_date: toYMD(expForm.value.start_date), end_date: toYMD(expForm.value.end_date) }
  const opts = { onFinish:()=> savingExp.value=false, onSuccess:()=>{ expDialog.value=false } }
  if (!expForm.value.id) EmployeeExperienceService.store(props.employee.id, payload, opts)
  else EmployeeExperienceService.update(props.employee.id, expForm.value.id, payload, opts)
}
function confirmExpDelete(r){ currentExp.value=r; expDeleteDialog.value=true }
function removeExperience(){
  deletingExp.value=true
  EmployeeExperienceService.destroy(props.employee.id, currentExp.value.id, { onFinish:()=>{ deletingExp.value=false; expDeleteDialog.value=false } })
}
function confirmExpDeleteSelected(){ expDeleteManyDialog.value=true }
function removeManyExperience(){
  const ids = expSelected.value.map(x=>x.id)
  deletingExp.value=true
  EmployeeExperienceService.bulkDelete(props.employee.id, ids, { onFinish:()=>{ deletingExp.value=false; expDeleteManyDialog.value=false; expSelected.value=[] } })
}

/* ===== Skills state & methods ===== */
const skillDt = ref()
const skillRows = computed(()=> props.employee_skills || [])
const skillSelected = ref([])
const skillFilters = ref({ global: { value: null, matchMode: 'contains' } })
const skillDialog = ref(false)
const skillDeleteDialog = ref(false)
const skillDeleteManyDialog = ref(false)
const currentSkill = ref(null)
const savingSkill = ref(false)
const deletingSkill = ref(false)
const selectedSkillCategory = ref(null)
const skillFormCategoryFilter = ref(null)

const skillForm = ref({ id:null, skill_id:null, level:0, years:0, note:'' })

// Skill category filter options
const skillCategoryOptions = computed(() => {
  return props.skill_categories.map(cat => ({
    label: cat.name,
    value: cat.id
  }))
})

// Filtered skills for form dropdown
const filteredSkillsForForm = computed(() => {
  if (!skillFormCategoryFilter.value) {
    return props.skills
  }
  return props.skills.filter(s => s.category_id === skillFormCategoryFilter.value)
})

// Group skills by category
// Enhanced skill rows với category info để grouping
const enhancedSkillRows = computed(() => {
  return skillRows.value.map(empSkill => {
    const skill = props.skills.find(s => s.id === empSkill.skill_id)
    const category = skill?.category
    const categoryName = category?.name || 'Chưa phân loại'
    const categoryOrder = category?.order_index || 999

    return {
      ...empSkill,
      category_name: categoryName,
      category_order: categoryOrder,
      category_id: category?.id || null
    }
  })
})

// Filtered skills by selected category
const filteredSkillRows = computed(() => {
  let rows = enhancedSkillRows.value

  if (selectedSkillCategory.value) {
    rows = rows.filter(s => s.category_id === selectedSkillCategory.value)
  }

  // Sort by category_order first, then by skill name
  return rows.sort((a, b) => {
    if (a.category_order !== b.category_order) {
      return a.category_order - b.category_order
    }
    return (a.skill_name || '').localeCompare(b.skill_name || '')
  })
})

// Helper để đếm số skill trong mỗi category
function getCategorySkillCount(categoryName) {
  return filteredSkillRows.value.filter(s => s.category_name === categoryName).length
}
function openSkillNew(){
  skillForm.value={ id:null, skill_id:null, level:0, years:0, note:'' }
  skillFormCategoryFilter.value = null
  // Clear previous errors
  if (page.props.errors) {
    delete page.props.errors.skill_id
  }
  skillDialog.value=true
}
function openSkillEdit(r){
  skillForm.value={ id:r.id, skill_id:r.skill_id, level:r.level ?? 0, years:r.years ?? 0, note:r.note ?? '' }
  // Pre-select category based on skill
  const skill = props.skills.find(s => s.id === r.skill_id)
  skillFormCategoryFilter.value = skill?.category_id || null
  // Clear previous errors
  if (page.props.errors) {
    delete page.props.errors.skill_id
  }
  skillDialog.value=true
}
function saveSkill(){
  savingSkill.value=true
  const payload = { skill_id: skillForm.value.skill_id, level: skillForm.value.level ?? 0, years: skillForm.value.years ?? 0, note: skillForm.value.note }
  const opts = {
    onFinish:()=> savingSkill.value=false,
    onSuccess:()=>{
      skillDialog.value=false
    }
  }
  if (!skillForm.value.id) EmployeeSkillService.store(props.employee.id, payload, opts)
  else EmployeeSkillService.update(props.employee.id, skillForm.value.id, payload, opts)
}
function confirmSkillDelete(r){ currentSkill.value=r; skillDeleteDialog.value=true }
function removeSkill(){
  deletingSkill.value=true
  EmployeeSkillService.destroy(props.employee.id, currentSkill.value.id, { onFinish:()=>{ deletingSkill.value=false; skillDeleteDialog.value=false } })
}
function confirmSkillDeleteSelected(){ skillDeleteManyDialog.value=true }
function removeManySkill(){
  const ids = skillSelected.value.map(x=>x.id)
  deletingSkill.value=true
  EmployeeSkillService.bulkDelete(props.employee.id, ids, { onFinish:()=>{ deletingSkill.value=false; skillDeleteManyDialog.value=false; skillSelected.value=[] } })
}

/* ===== Assignments state & methods ===== */
const assignmentDt = ref()
const assignmentRows = computed(()=> props.assignments || [])
const selectedAssignments = ref([])
const assignmentFilters = ref({ global: { value: null, matchMode: 'contains' } })
const assignmentDialog = ref(false)
const assignmentDeleteDialog = ref(false)
const assignmentDeleteManyDialog = ref(false)
const currentAssignment = ref(null)
const savingAssignment = ref(false)
const deletingAssignment = ref(false)
const assignmentSubmitted = ref(false)

const roleTypeOptions = [
  { label: 'Trưởng phòng', value: 'HEAD' },
  { label: 'Phó phòng', value: 'DEPUTY' },
  { label: 'Nhân viên', value: 'MEMBER' }
]

const statusOptions = [
  { label: 'Hoạt động', value: 'ACTIVE' },
  { label: 'Không hoạt động', value: 'INACTIVE' }
]

const assignmentForm = ref({
  id: null,
  department_id: null,
  position_id: null,
  is_primary: false,
  role_type: 'MEMBER',
  start_date: null,
  end_date: null,
  status: 'ACTIVE'
})

// Filter positions theo department đã chọn
const filteredPositions = computed(() => {
  if (!assignmentForm.value.department_id) {
    return props.positions || []
  }
  return (props.positions || []).filter(p => p.department_id === assignmentForm.value.department_id)
})

// Watch department change để reset position nếu không thuộc department mới
watch(() => assignmentForm.value.department_id, (newDeptId, oldDeptId) => {
  if (newDeptId !== oldDeptId && assignmentForm.value.position_id) {
    // Kiểm tra xem position hiện tại có thuộc department mới không
    const positionStillValid = filteredPositions.value.some(p => p.id === assignmentForm.value.position_id)
    if (!positionStillValid) {
      assignmentForm.value.position_id = null
    }
  }
})

// Kiểm tra xem có primary assignment ACTIVE không
const hasPrimaryActive = computed(() => {
  return assignmentRows.value.some(a => a.is_primary && a.status === 'ACTIVE')
})

// Hiển thị warning khi check primary
const showPrimaryWarning = computed(() => {
  // Nếu đang edit và đã là primary -> không warning
  if (assignmentForm.value.id) {
    const current = assignmentRows.value.find(a => a.id === assignmentForm.value.id)
    if (current?.is_primary) return false
  }
  // Nếu check primary + status ACTIVE + đã có primary active -> warning
  return assignmentForm.value.is_primary &&
         assignmentForm.value.status === 'ACTIVE' &&
         hasPrimaryActive.value
})

function onPrimaryChange() {
  // Trigger reactivity để update showPrimaryWarning
}

function getRoleLabel(roleType) {
  const found = roleTypeOptions.find(x => x.value === roleType)
  return found ? found.label : roleType
}

// Highlight primary assignment row
function assignmentRowClass(data) {
  return data.is_primary && data.status === 'ACTIVE' ? 'bg-green-50' : ''
}

function exportAssignmentCSV(){ assignmentDt.value?.exportCSV() }

function openAssignmentNew(){
  assignmentSubmitted.value = false
  assignmentForm.value = {
    id: null,
    department_id: null,
    position_id: null,
    is_primary: false,
    role_type: 'MEMBER',
    start_date: null,
    end_date: null,
    status: 'ACTIVE'
  }
  assignmentDialog.value = true
}

function openAssignmentEdit(r){
  assignmentSubmitted.value = false
  assignmentForm.value = {
    id: r.id,
    department_id: r.department_id,
    position_id: r.position_id,
    is_primary: !!r.is_primary,
    role_type: r.role_type,
    start_date: r.start_date,
    end_date: r.end_date,
    status: r.status
  }
  assignmentDialog.value = true
}

function saveAssignment(){
  assignmentSubmitted.value = true
  if (!assignmentForm.value.department_id || !assignmentForm.value.role_type || !assignmentForm.value.status) return

  savingAssignment.value = true
  const payload = {
    ...assignmentForm.value,
    employee_id: props.employee.id,
    start_date: toYMD(assignmentForm.value.start_date),
    end_date: toYMD(assignmentForm.value.end_date)
  }

  const opts = {
    onFinish: () => savingAssignment.value = false,
    onSuccess: () => { assignmentDialog.value = false }
  }
  if (!assignmentForm.value.id) {
    EmployeeAssignmentService.storeForEmployee(props.employee.id, payload, opts)
  } else {
    EmployeeAssignmentService.updateForEmployee(props.employee.id, assignmentForm.value.id, payload, opts)
  }
}

function confirmAssignmentDelete(r){ currentAssignment.value = r; assignmentDeleteDialog.value = true }
function removeAssignment(){
  deletingAssignment.value = true
  EmployeeAssignmentService.destroyForEmployee(props.employee.id, currentAssignment.value.id, {
    onFinish: () => { deletingAssignment.value = false; assignmentDeleteDialog.value = false }
  })
}

function confirmAssignmentDeleteSelected(){ assignmentDeleteManyDialog.value = true }
function removeManyAssignment(){
  const ids = selectedAssignments.value.map(x=>x.id)
  deletingAssignment.value = true
  EmployeeAssignmentService.bulkDeleteForEmployee(props.employee.id, ids, {
    onFinish: () => { deletingAssignment.value = false; assignmentDeleteManyDialog.value = false; selectedAssignments.value = [] }
  })
}

/* ===== Activity Timeline state & methods ===== */
const activities = ref([])
const loadingActivities = ref(false)
const selectedActivityModule = ref(null)
const activityPagination = ref(null)

const activityModuleOptions = [
  { label: 'Tất cả', value: null },
  { label: 'Phân công', value: 'employee-assignment' },
  { label: 'Học vấn', value: 'employee-education' },
  { label: 'Người thân', value: 'employee-relative' },
  { label: 'Kinh nghiệm', value: 'employee-experience' },
  { label: 'Kỹ năng', value: 'employee-skill' },
]

async function loadActivities(page = 1) {
  loadingActivities.value = true
  try {
    const params = new URLSearchParams({ page: page.toString() })
    if (selectedActivityModule.value) {
      params.append('module', selectedActivityModule.value)
    }

    const response = await fetch(`/employees/${props.employee.id}/activities?${params}`)
    const data = await response.json()

    activities.value = data.data || []
    activityPagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total
    }
  } catch (error) {
    console.error('Failed to load activities:', error)
  } finally {
    loadingActivities.value = false
  }
}

function onModuleFilterChange() {
  // Reset to page 1 when filter changes
  loadActivities(1)
}

function onActivityPageChange(event) {
  loadActivities(event.page + 1)
}

function getActivityColor(logName) {
  if (logName.includes('assignment')) return 'bg-blue-500'
  if (logName.includes('education')) return 'bg-purple-500'
  if (logName.includes('relative')) return 'bg-green-500'
  if (logName.includes('experience')) return 'bg-orange-500'
  if (logName.includes('skill')) return 'bg-pink-500'
  return 'bg-gray-500'
}

function getActivityIcon(description) {
  if (description.includes('created') || description.includes('Tạo')) return 'pi pi-plus'
  if (description.includes('updated') || description.includes('Cập nhật')) return 'pi pi-pencil'
  if (description.includes('deleted') || description.includes('Xóa')) return 'pi pi-trash'
  return 'pi pi-info-circle'
}

function getActivityLabel(activity) {
  // Ưu tiên lấy label tiếng Việt từ properties.label (từ BE enum)
  if (activity.properties?.label) {
    return activity.properties.label
  }
  // Fallback: hiển thị description nếu không có label
  return activity.description || 'Hoạt động'
}

function getModuleLabel(logName) {
  if (logName.includes('assignment')) return 'Phân công'
  if (logName.includes('education')) return 'Học vấn'
  if (logName.includes('relative')) return 'Người thân'
  if (logName.includes('experience')) return 'Kinh nghiệm'
  if (logName.includes('skill')) return 'Kỹ năng'
  return logName
}

function getModuleSeverity(logName) {
  if (logName.includes('assignment')) return 'info'
  if (logName.includes('education')) return 'secondary'
  if (logName.includes('relative')) return 'success'
  if (logName.includes('experience')) return 'warn'
  if (logName.includes('skill')) return 'danger'
  return 'secondary'
}

function formatDateTime(datetime) {
  if (!datetime) return '-'
  const date = new Date(datetime)
  return date.toLocaleString('vi-VN')
}

// Get end reason label
function getEndReasonLabel(reason) {
  const labels = {
    'CONTRACT_END': 'Hết hạn hợp đồng',
    'RESIGN': 'Nghỉ việc',
    'TERMINATION': 'Sa thải',
    'LAYOFF': 'Cắt giảm nhân sự',
    'RETIREMENT': 'Nghỉ hưu',
    'MATERNITY_LEAVE': 'Nghỉ sinh',
    'REHIRE': 'Tái tuyển dụng',
    'OTHER': 'Lý do khác'
  }
  return labels[reason] || reason
}

// Load activities on mount
onMounted(() => {
  loadActivities()
})

// Reload activities when page props update (after CRUD operations)
watch(() => [props.educations, props.relatives, props.experiences, props.employee_skills, props.assignments], () => {
  // Reload activities when any data changes (indicates a CRUD operation happened)
  loadActivities(activityPagination.value?.current_page || 1)
}, { deep: true })
</script>
