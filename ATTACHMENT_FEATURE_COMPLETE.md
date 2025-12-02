# Tính năng Đính kèm Tệp (Attachments) - Hoàn thành

## Tổng quan
Đã hoàn thành tính năng đính kèm tệp cho Contract và Appendix theo cách tích hợp vào form hiện có (simple approach).

## Các thành phần đã triển khai

### 1. Backend - Controllers
**ContractController.php**
- ✅ Xử lý upload file trong `store()` và `update()`
- ✅ Lưu file vào `storage/contracts/{id}/attachments/`
- ✅ Xử lý xóa file qua array `delete_attachments[]`
- ✅ Method `downloadAttachment()` để tải file
- ✅ Load relationship `attachments` trong `index()`

**ContractAppendixController.php**
- ✅ Xử lý upload file trong `store()` và `update()`
- ✅ Lưu file vào `storage/appendixes/{id}/attachments/`
- ✅ Xử lý xóa file qua array `delete_attachments[]`
- ✅ Method `downloadAttachment()` để tải file
- ✅ Load relationship `attachments` trong `index()`

### 2. Backend - Routes
**web.php**
- ✅ `GET /contracts/attachments/{attachment}/download` - Tải attachment của Contract
- ✅ `GET /contracts/appendixes/attachments/{attachment}/download` - Tải attachment của Appendix

### 3. Backend - Resources
**ContractResource.php**
- ✅ Thêm field `attachments` với mapping:
  - id, file_name, file_size, mime_type, created_at
  - download_url (route đầy đủ)
  
**ContractAppendixResource.php**
- ✅ Tương tự ContractResource

### 4. Frontend - Component
**AttachmentUploader.vue**
- ✅ Component tái sử dụng cho cả Contract và Appendix
- ✅ Hiển thị danh sách file đã có với link download
- ✅ Chọn file mới (multiple files, max 10MB/file)
- ✅ Đánh dấu file cũ để xóa qua checkbox
- ✅ Hiển thị icon theo loại file (PDF, Word, Excel, Image, etc.)
- ✅ Format kích thước file (KB, MB)
- ✅ Props: `existingAttachments`, `readonly`
- ✅ Emits: `update:newFiles`, `update:deleteIds`
- ✅ Exposed methods: `getNewFiles()`, `getDeleteIds()`, `reset()`

### 5. Frontend - Contract Integration
**ContractIndex.vue**
- ✅ Import và thêm AttachmentUploader vào dialog
- ✅ Thêm ref `attachmentUploader`
- ✅ Thêm vào form model:
  - `attachments: []` - File hiện có
  - `newAttachments: []` - File mới upload
  - `deleteAttachments: []` - ID file cần xóa
- ✅ Sửa `save()` method:
  - Chuyển sang FormData để upload file
  - Append tất cả form fields
  - Append `attachments[]` files
  - Append `delete_attachments[]` IDs
  - Sử dụng `router.post()` với `forceFormData: true`
- ✅ Sửa `edit()` method: Load attachments từ row data
- ✅ Sửa `hideDialog()`: Reset attachmentUploader

### 6. Frontend - Appendix Integration
**ContractAppendixTab.vue**
- ✅ Import và thêm AttachmentUploader vào dialog
- ✅ Thêm ref `attachmentUploader`
- ✅ Thêm vào form model: attachments, newAttachments, deleteAttachments
- ✅ Sửa `save()` method: Tương tự ContractIndex
- ✅ Sửa `edit()` method: Load attachments
- ✅ Sửa `reset()` method: Reset attachment fields
- ✅ Sửa `closeDialog()`: Reset attachmentUploader

## Cách hoạt động

### Upload file mới
1. User chọn file từ AttachmentUploader component
2. Files được lưu vào `form.newAttachments` array
3. Khi click "Lưu", files được append vào FormData
4. Backend nhận và lưu vào `storage/contracts/{id}/attachments/` hoặc `storage/appendixes/{id}/attachments/`
5. Tạo record trong database table `contract_attachments` hoặc `contract_appendix_attachments`

### Xóa file cũ
1. User tick checkbox ở file cần xóa
2. ID được thêm vào `form.deleteAttachments` array
3. Khi click "Lưu", IDs được gửi trong FormData
4. Backend xử lý:
   - Xóa file vật lý từ storage
   - Xóa record từ database

### Download file
1. User click link download
2. Request gửi đến route `/contracts/attachments/{id}/download`
3. Backend kiểm tra authorization
4. Trả về file với `response()->download()`

## Validation & Bảo mật

### Frontend
- Max file size: 10MB/file
- Multiple files allowed
- File type icons để nhận diện

### Backend
- Authorization check: User phải có quyền view Contract/Appendix
- File path validation
- Storage isolation: Mỗi Contract/Appendix có folder riêng

## Technical Details

### FormData Structure
```javascript
FormData {
  // Form fields
  employee_id: "123",
  contract_number: "HD001",
  start_date: "2024-01-01",
  // ... other fields
  
  // New files
  attachments[]: File,
  attachments[]: File,
  
  // IDs to delete
  delete_attachments[]: "456",
  delete_attachments[]: "789",
  
  // Method override for PUT request
  _method: "PUT"  // Only for update
}
```

### Storage Structure
```
storage/app/public/
├── contracts/
│   ├── 1/
│   │   └── attachments/
│   │       ├── 1234567890_document.pdf
│   │       └── 1234567891_contract.docx
│   └── 2/
│       └── attachments/
│           └── 1234567892_scan.jpg
└── appendixes/
    ├── 1/
    │   └── attachments/
    │       └── 1234567893_appendix.pdf
    └── 2/
        └── attachments/
            └── 1234567894_update.xlsx
```

### Database Schema
```sql
-- contract_attachments
id, contract_id, file_name, file_path, file_size, mime_type, uploaded_by, created_at

-- contract_appendix_attachments
id, appendix_id, file_name, file_path, file_size, mime_type, uploaded_by, created_at
```

## API Response Format

### Contract with attachments
```json
{
  "id": 1,
  "contract_number": "HD001",
  "attachments": [
    {
      "id": 1,
      "file_name": "document.pdf",
      "file_size": 1024000,
      "mime_type": "application/pdf",
      "created_at": "2024-01-01T00:00:00",
      "download_url": "http://localhost/contracts/attachments/1/download"
    }
  ]
}
```

## Testing Checklist

- [ ] Upload file mới khi tạo Contract
- [ ] Upload nhiều file cùng lúc
- [ ] Edit Contract và thêm file mới
- [ ] Edit Contract và xóa file cũ
- [ ] Edit Contract: thêm file mới + xóa file cũ cùng lúc
- [ ] Download file
- [ ] Kiểm tra authorization (user khác không thể download)
- [ ] Validate file size > 10MB
- [ ] Tương tự với Appendix (tất cả test cases trên)
- [ ] File được lưu đúng folder
- [ ] File bị xóa khi delete Contract/Appendix
- [ ] UI component: icons hiển thị đúng theo loại file
- [ ] UI component: readonly mode

## Notes
- Không cần tạo controller/route riêng cho attachment CRUD
- File upload được xử lý trong cùng transaction với model save
- Nếu save fail, file không được lưu (không có orphan files)
- Component AttachmentUploader có thể tái sử dụng cho các module khác
