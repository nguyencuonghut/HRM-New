# Hướng Dẫn Sử Dụng Module Quản Lý Bảo Hiểm

## Giới Thiệu

Module Quản Lý Bảo Hiểm giúp tự động hóa quy trình quản lý bảo hiểm xã hội (BHXH), bảo hiểm y tế (BHYT), và bảo hiểm thất nghiệp (BHTN) cho nhân viên.

**Tính năng chính**:
- ✅ Quản lý tham gia bảo hiểm theo hợp đồng
- ✅ Tạo báo cáo tăng/giảm/điều chỉnh hàng tháng
- ✅ Tính toán tự động mức đóng BHXH
- ✅ Xuất file Excel nộp cơ quan BHXH
- ✅ Quản lý tỷ lệ đóng 5 thành phần

---

## Vai Trò & Quyền Hạn

### Nhân Viên HR
**Quyền hạn**: Xem báo cáo bảo hiểm

**Có thể làm gì**:
- Xem danh sách báo cáo BHXH hàng tháng
- Xem chi tiết thay đổi tăng/giảm/điều chỉnh
- Xem mức đóng của từng nhân viên

**Không thể làm**:
- Tạo hoặc chỉnh sửa báo cáo
- Duyệt các thay đổi
- Hoàn tất báo cáo
- Xuất file Excel

### Quản Lý Lương (Payroll Admin)
**Quyền hạn**: Toàn quyền quản lý bảo hiểm

**Có thể làm gì**:
- Tất cả quyền của Nhân viên HR
- Tạo báo cáo BHXH hàng tháng
- Duyệt/từ chối các thay đổi
- Điều chỉnh tháng kê khai
- Hoàn tất báo cáo
- Xuất file Excel
- Cấu hình tỷ lệ đóng BHXH

---

## Phần 1: Tạo Hợp Đồng Có Tham Gia Bảo Hiểm

### 1.1 Truy Cập Form Hợp Đồng

1. Vào menu **Hợp đồng** → **Danh sách hợp đồng**
2. Click nút **Tạo hợp đồng mới**
3. Điền thông tin hợp đồng: Nhân viên, ngày bắt đầu, chức vụ, lương...

### 1.2 Cấu Hình Bảo Hiểm

Trong form hợp đồng, kéo xuống phần **Thông tin bảo hiểm**:

#### Bước 1: Nhập Lương Đóng BHXH
```
Lương đóng BHXH: [15,000,000] VND
```
**Lưu ý**: 
- Lương đóng BHXH thường bằng lương cơ bản
- Tối đa = 20 × Lương tối thiểu vùng
- Hệ thống sẽ kiểm tra và cảnh báo nếu vượt mức

#### Bước 2: Chọn Các Thành Phần Bảo Hiểm

Hệ thống có 5 thành phần bảo hiểm:

**1. BHXH Hưu Trí** ☑
- Tỷ lệ: 22% (NLĐ: 8%, NSDLĐ: 14%)
- Bắt buộc cho hợp đồng chính thức

**2. BHXH Ốm Đau** ☑
- Tỷ lệ: 3% (NLĐ: 0%, NSDLĐ: 3%)
- Bắt buộc cho hợp đồng chính thức

**3. BHXH TNLĐ-BNN** ☑
- Tỷ lệ: 1% (NLĐ: 0%, NSDLĐ: 1%)
- Bảo hiểm tai nạn lao động, bệnh nghề nghiệp

**4. BHTN (Bảo Hiểm Thất Nghiệp)** ☑
- Tỷ lệ: 2% (NLĐ: 1%, NSDLĐ: 1%)
- **Đặc biệt**: Có 2 cách tính
  - ⚪ Theo lương BHXH (mặc định)
  - ⚪ Theo mức cố định (nhập số tiền)

**Ví dụ BHTN cố định**:
```
☑ BHTN
  ⚪ Theo lương BHXH
  ⚫ Theo mức cố định: [5,000,000] VND
```

**5. BHYT (Bảo Hiểm Y Tế)** ☑
- Tỷ lệ: 4.5% (NLĐ: 1.5%, NSDLĐ: 3%)
- Bắt buộc cho tất cả hợp đồng

#### Bước 3: Lưu Hợp Đồng

Click **Lưu** → Hệ thống tự động:
- Tạo bản ghi tham gia bảo hiểm
- Lưu các thành phần đã chọn với tỷ lệ hiện tại
- Tỷ lệ này sẽ KHÔNG thay đổi khi tỷ lệ mặc định thay đổi

### 1.3 Ví Dụ Cụ Thể

**Tình huống**: Tạo hợp đồng cho Nguyễn Văn A

```
Thông tin hợp đồng:
- Nhân viên: Nguyễn Văn A
- Ngày bắt đầu: 01/02/2026
- Lương cơ bản: 15,000,000 VND
- Lương đóng BHXH: 15,000,000 VND

Chọn bảo hiểm:
☑ BHXH Hưu Trí (22%)
☑ BHXH Ốm Đau (3%)
☑ BHXH TNLĐ-BNN (1%)
☑ BHTN - Theo lương BHXH (2%)
☑ BHYT (4.5%)

Tổng mức đóng/tháng:
= 15,000,000 × (22% + 3% + 1% + 2% + 4.5%)
= 15,000,000 × 32.5%
= 4,875,000 VND
```

---

## Phần 2: Tạo Báo Cáo Bảo Hiểm Hàng Tháng

### 2.1 Khi Nào Tạo Báo Cáo?

**Thời điểm**: Đầu tháng sau (ví dụ: đầu tháng 2 để báo cáo tháng 1)

**Điều kiện**: Có ít nhất 1 trong các thay đổi sau trong tháng:
- Nhân viên mới ký hợp đồng (TĂNG)
- Nhân viên nghỉ việc (GIẢM)
- Thay đổi lương BHXH (ĐIỀU CHỈNH)

### 2.2 Các Bước Tạo Báo Cáo

#### Bước 1: Truy Cập Trang Báo Cáo BHXH

1. Vào menu **Quản lý BHXH** → **Báo cáo BHXH**
2. Màn hình hiển thị danh sách báo cáo đã tạo

#### Bước 2: Tạo Báo Cáo Mới

1. Click nút **Tạo báo cáo mới**
2. Chọn **Tháng** và **Năm** (ví dụ: Tháng 01/2026)
3. Click **Tạo**

#### Bước 3: Chờ Hệ Thống Xử Lý

Hệ thống tự động:
- Quét tất cả hợp đồng trong tháng
- Phát hiện thay đổi (tăng/giảm/điều chỉnh)
- Tạo bản ghi cho mỗi thay đổi
- Gợi ý tháng kê khai

**Thời gian**: 5-30 giây tùy số lượng nhân viên

#### Bước 4: Xem Kết Quả

Sau khi tạo xong, màn hình hiển thị 3 tab:

**Tab 1: TĂNG LAO ĐỘNG** (nhân viên mới)
- Danh sách nhân viên ký hợp đồng mới
- Ngày bắt đầu tham gia BHXH
- Lương đóng BHXH
- Tháng kê khai gợi ý

**Tab 2: GIẢM** (nhân viên nghỉ)
- Danh sách nhân viên nghỉ việc
- Ngày kết thúc hợp đồng
- Tháng kê khai gợi ý

**Tab 3: ĐIỀU CHỈNH** (thay đổi lương)
- Danh sách nhân viên thay đổi lương BHXH
- Lương cũ và lương mới
- Tháng điều chỉnh

### 2.3 Ví Dụ Báo Cáo Tháng 01/2026

```
Báo cáo BHXH - Tháng 01/2026

Tab TĂNG LAO ĐỘNG (5 nhân viên):
┌────────────────┬──────────────┬─────────────┬──────────────┐
│ Mã NV          │ Họ Tên       │ Ngày ký HĐ  │ Tháng KK     │
├────────────────┼──────────────┼─────────────┼──────────────┤
│ NV001          │ Nguyễn Văn A │ 05/01/2026  │ 2026-01      │
│ NV002          │ Trần Thị B   │ 10/01/2026  │ 2026-01      │
│ NV003          │ Lê Văn C     │ 25/01/2026  │ 2026-02 (*)  │
└────────────────┴──────────────┴─────────────┴──────────────┘

(*) Ký cuối tháng → gợi ý kê khai tháng sau

Tab GIẢM (2 nhân viên):
┌────────────────┬──────────────┬─────────────┬──────────────┐
│ Mã NV          │ Họ Tên       │ Ngày nghỉ   │ Tháng KK     │
├────────────────┼──────────────┼─────────────┼──────────────┤
│ NV100          │ Phạm Văn X   │ 15/01/2026  │ 2026-01      │
│ NV101          │ Hoàng Thị Y  │ 31/01/2026  │ 2026-01      │
└────────────────┴──────────────┴─────────────┴──────────────┘

Tab ĐIỀU CHỈNH (1 nhân viên):
┌────────────────┬──────────────┬─────────────┬─────────────┐
│ Mã NV          │ Họ Tên       │ Lương cũ    │ Lương mới   │
├────────────────┼──────────────┼─────────────┼─────────────┤
│ NV050          │ Võ Văn Z     │ 10,000,000  │ 12,000,000  │
└────────────────┴──────────────┴─────────────┴─────────────┘
```

---

## Phần 3: Điều Chỉnh Tháng Kê Khai

### 3.1 Tại Sao Cần Điều Chỉnh?

**Tháng kê khai gợi ý** của hệ thống dựa trên ngày thay đổi:
- Ký HĐ ngày 05/01 → gợi ý kê khai tháng 01
- Ký HĐ ngày 25/01 → gợi ý kê khai tháng 02 (cuối tháng)

**Nhưng thực tế có thể khác**:
- Nhân viên yêu cầu kê khai từ tháng khác
- Thỏa thuận đặc biệt với nhân viên
- Điều chỉnh theo quy định công ty

### 3.2 Cách Điều Chỉnh

#### Bước 1: Mở Báo Cáo ở Trạng Thái DRAFT

Chỉ có thể điều chỉnh khi báo cáo chưa hoàn tất.

#### Bước 2: Tìm Bản Ghi Cần Điều Chỉnh

Trong tab TĂNG/GIẢM/ĐIỀU CHỈNH, xem cột:
- **Tháng KK gợi ý**: Tháng hệ thống đề xuất (có tag màu xanh)
- **Tháng KK chính thức**: Dropdown có thể chỉnh sửa

#### Bước 3: Thay Đổi Tháng

1. Click vào dropdown **Tháng KK chính thức**
2. Chọn tháng khác (ví dụ: 2026-02 thay vì 2026-01)
3. Ô **Lý do thay đổi** xuất hiện (bắt buộc nhập)

#### Bước 4: Nhập Lý Do

**Bắt buộc** khi tháng chính thức ≠ tháng gợi ý

Ví dụ lý do:
```
"Nhân viên ký hợp đồng ngày 28/01 nhưng yêu cầu 
kê khai từ tháng 02 theo thỏa thuận"
```

#### Bước 5: Lưu Thay Đổi

Hệ thống tự động lưu sau khi nhập lý do.

**Hiển thị**:
- Icon cảnh báo màu vàng ⚠️ xuất hiện
- Tooltip hiển thị: "Đã thay đổi từ tháng gợi ý"

### 3.3 Ví Dụ Điều Chỉnh

```
Bản ghi: NV003 - Lê Văn C

Tháng KK gợi ý:     [2026-02] (tag xanh)
Tháng KK chính thức: [2026-01] (dropdown - đã chỉnh sửa)
Lý do thay đổi:      "Nhân viên yêu cầu kê khai từ tháng 01 
                      theo thỏa thuận với phòng nhân sự"

Status: ⚠️ Đã điều chỉnh
```

---

## Phần 4: Hoàn Tất Báo Cáo

### 4.1 Khi Nào Hoàn Tất?

**Điều kiện**:
- ✅ Đã xem xét tất cả thay đổi
- ✅ Đã điều chỉnh tháng kê khai (nếu cần)
- ✅ Đã duyệt/từ chối các bản ghi (nếu có workflow)
- ✅ Dữ liệu chính xác, sẵn sàng nộp

### 4.2 Các Bước Hoàn Tất

#### Bước 1: Review Toàn Bộ Báo Cáo

Kiểm tra từng tab:
- Tab TĂNG: Số lượng đúng, thông tin chính xác
- Tab GIẢM: Ngày nghỉ chính xác
- Tab ĐIỀU CHỈNH: Lương mới đúng

#### Bước 2: Click Nút "Hoàn Tất Báo Cáo"

Nút nằm ở góc trên bên phải màn hình.

#### Bước 3: Xác Nhận

Popup xác nhận:
```
Xác nhận hoàn tất báo cáo?

Sau khi hoàn tất:
- Báo cáo không thể chỉnh sửa
- Tạo snapshot tính toán mức đóng BHXH
- Tab "Tổng hợp đóng BHXH" sẽ hiển thị

[Hủy]  [Xác nhận]
```

Click **Xác nhận**.

#### Bước 4: Chờ Hệ Thống Xử Lý

Hệ thống thực hiện:
- Tính toán mức đóng cho từng nhân viên
- Tính cho từng thành phần (5 thành phần)
- Tạo snapshot không thể thay đổi
- Cập nhật trạng thái báo cáo → FINALIZED

**Thời gian**: 5-15 giây (tùy số lượng nhân viên)

#### Bước 5: Xem Kết Quả

Tab thứ 4 **"TỔNG HỢP ĐÓNG BHXH"** được kích hoạt.

### 4.3 Lưu Ý Quan Trọng

⚠️ **Sau khi hoàn tất**:
- KHÔNG thể chỉnh sửa bất kỳ thông tin nào
- KHÔNG thể thêm/xóa bản ghi
- KHÔNG thể xóa báo cáo
- Chỉ có thể xem và xuất Excel

💡 **Nếu phát hiện sai sau khi hoàn tất**:
- Cần tạo báo cáo điều chỉnh ở tháng tiếp theo
- Hoặc liên hệ Admin để revert (mất dữ liệu snapshot)

---

## Phần 5: Xem Tổng Hợp Đóng BHXH

### 5.1 Truy Cập Tab Tổng Hợp

Sau khi báo cáo đã FINALIZED:
1. Mở báo cáo
2. Click tab **"TỔNG HỢP ĐÓNG BHXH"** (tab thứ 4)

### 5.2 Hiển Thị Dữ Liệu

Bảng hiển thị:

| Mã NV | Họ Tên | Lương BHXH | BHXH Hưu Trí | BHXH Ốm Đau | BHXH TNLĐ | BHTN | BHYT | Tổng |
|-------|--------|------------|--------------|-------------|-----------|------|------|------|
| NV001 | Nguyễn Văn A | 15,000,000 | 3,300,000 | 450,000 | 150,000 | 300,000 | 675,000 | 4,875,000 |
| NV002 | Trần Thị B | 12,000,000 | 2,640,000 | 360,000 | 120,000 | 240,000 | 540,000 | 3,900,000 |
| ... | | | | | | | | |
| **TỔNG** | | | **5,940,000** | **810,000** | **270,000** | **540,000** | **1,215,000** | **8,775,000** |

### 5.3 Giải Thích Các Cột

**Lương BHXH**: Lương cơ sở để tính BHXH

**BHXH Hưu Trí**: Lương BHXH × 22%

**BHXH Ốm Đau**: Lương BHXH × 3%

**BHXH TNLĐ**: Lương BHXH × 1%

**BHTN**: 
- Lương BHXH × 2% (nếu theo lương)
- Hoặc mức cố định đã cấu hình
- Có chú thích "(Cố định)" nếu dùng mức fix

**BHYT**: Lương BHXH × 4.5%

**Tổng**: Tổng 5 thành phần

### 5.4 Đọc Dữ Liệu

**Dòng Footer (Tổng)**:
- Cộng tổng theo cột
- Dùng để đối chiếu với hồ sơ nộp BHXH

**Lưu ý**:
- Dữ liệu này là **snapshot** tại thời điểm hoàn tất
- KHÔNG thay đổi khi tỷ lệ BHXH thay đổi
- Dùng làm chứng từ lưu trữ

---

## Phần 6: Xuất File Excel

### 6.1 Khi Nào Xuất Excel?

**Mục đích**: Nộp cho cơ quan BHXH hoặc lưu trữ

**Điều kiện**: Báo cáo đã FINALIZED

### 6.2 Các Bước Xuất Excel

#### Bước 1: Mở Tab Tổng Hợp

Vào báo cáo đã hoàn tất → Tab **"TỔNG HỢP ĐÓNG BHXH"**

#### Bước 2: Click Nút "Xuất Excel"

Nút nằm ở góc trên bên phải của tab.

#### Bước 3: Chờ Tải File

- Hệ thống tạo file Excel
- Tự động tải về máy
- Tên file: `BaoCao_BHXH_YYYY_MM.xlsx`

**Thời gian**: 3-10 giây tùy kích thước

### 6.3 Nội Dung File Excel

File Excel bao gồm:

**Sheet 1: Tổng hợp đóng BHXH**
- Tiêu đề: "BÁO CÁO BHXH - THÁNG XX/YYYY"
- Thông tin công ty
- Bảng chi tiết (giống UI)
- Dòng tổng cộng
- Chữ ký (trống, để in và ký tay)

**Format**:
- Font: Arial 10
- Header: Bold, nền xanh nhạt
- Số liệu: Định dạng số có dấu phẩy
- Tổng: Bold, nền vàng nhạt

### 6.4 Kiểm Tra File Excel

Sau khi tải về, kiểm tra:
- ✅ File mở được không lỗi
- ✅ Số lượng nhân viên đúng
- ✅ Số liệu khớp với UI
- ✅ Tổng cộng tính đúng
- ✅ Format đẹp, in được

---

## Phần 7: Các Tình Huống Thường Gặp

### 7.1 Nhân Viên Ký HĐ Giữa Tháng

**Tình huống**: NV ký hợp đồng ngày 15/01

**Xử lý**:
- Hệ thống gợi ý kê khai tháng 01
- Nếu công ty quy định từ ngày 15 trở đi → kê khai tháng sau:
  - Điều chỉnh tháng KK → 2026-02
  - Nhập lý do: "Ký sau ngày 15, kê khai tháng sau theo quy định"

### 7.2 Nhân Viên Nghỉ Cuối Tháng

**Tình huống**: NV nghỉ việc ngày 31/01

**Xử lý**:
- Hệ thống gợi ý giảm tháng 01
- Giữ nguyên hoặc điều chỉnh tùy thỏa thuận
- BHXH vẫn đóng đủ tháng 01

### 7.3 Điều Chỉnh Lương BHXH

**Tình huống**: Tăng lương cho NV từ 10M → 12M vào ngày 15/01

**Xử lý**:
- Xuất hiện trong tab ĐIỀU CHỈNH
- Tháng điều chỉnh: 01/2026
- Tính toán: Dùng lương mới (12M) từ tháng này

### 7.4 BHTN Cố Định

**Tình huống**: Lương cơ bản 20M nhưng BHTN chỉ tính trên 5M

**Xử lý**:
- Khi tạo HĐ, chọn BHTN
- Chọn "Theo mức cố định"
- Nhập: 5,000,000
- Khi tính: BHTN = 5,000,000 × 2% = 100,000 (thay vì 20M × 2% = 400,000)

### 7.5 Nhân Viên Không Tham Gia BHTN

**Tình huống**: NV trên 60 tuổi không đóng BHTN

**Xử lý**:
- Khi tạo HĐ, BỎ CHỌN ô BHTN
- Chỉ chọn 4 thành phần còn lại
- Tổng mức đóng = 30.5% (thay vì 32.5%)

---

## Phần 8: Khắc Phục Sự Cố

### 8.1 Không Tạo Được Báo Cáo

**Triệu chứng**: Click "Tạo báo cáo" nhưng không có gì xảy ra

**Nguyên nhân**:
- Tháng đã có báo cáo
- Không có quyền tạo báo cáo
- Lỗi kết nối

**Giải pháp**:
1. Kiểm tra xem tháng đã có báo cáo chưa
2. Kiểm tra quyền hạn (cần role Payroll Admin)
3. Thử refresh trang (F5)
4. Xóa cache trình duyệt (Ctrl+Shift+Del)
5. Liên hệ IT nếu vẫn lỗi

### 8.2 Tháng Kê Khai Không Lưu

**Triệu chứng**: Thay đổi tháng nhưng quay lại vẫn là tháng cũ

**Nguyên nhân**:
- Chưa nhập lý do thay đổi
- Lý do quá ngắn (< 10 ký tự)

**Giải pháp**:
1. Kiểm tra ô "Lý do" có viền đỏ không
2. Nhập lý do đầy đủ, rõ ràng
3. Đợi thông báo "Đã lưu" xuất hiện

### 8.3 Không Xuất Được Excel

**Triệu chứng**: Click "Xuất Excel" nhưng không tải file

**Nguyên nhân**:
- Trình duyệt chặn download
- Báo cáo chưa hoàn tất

**Giải pháp**:
1. Kiểm tra báo cáo đã FINALIZED chưa
2. Cho phép download từ site này
3. Thử trình duyệt khác (Chrome, Firefox)
4. Kiểm tra dung lượng ổ đĩa

### 8.4 Số Liệu Không Khớp

**Triệu chứng**: Tổng trong Excel khác với UI

**Nguyên nhân**:
- Cache cũ
- Đang xem báo cáo khác tháng

**Giải pháp**:
1. Refresh trang (Ctrl+F5)
2. Kiểm tra đúng tháng chưa
3. So sánh từng dòng để tìm khác biệt
4. Liên hệ IT nếu vẫn sai

---

## Phần 9: Câu Hỏi Thường Gặp (FAQ)

### Q1: Tỷ lệ BHXH có thay đổi theo năm không?

**Trả lời**: Có, tỷ lệ do Nhà nước quy định và có thể thay đổi.

**Lưu ý**: Khi tỷ lệ thay đổi:
- Hợp đồng MỚI dùng tỷ lệ mới
- Hợp đồng CŨ giữ nguyên tỷ lệ lúc tạo
- Admin sẽ cập nhật tỷ lệ mới trong hệ thống

### Q2: Làm sao biết tỷ lệ hiện tại?

**Trả lời**: Vào **Quản lý BHXH → Cấu hình BHXH** (nếu có quyền)

Hoặc xem trong form tạo hợp đồng, hệ thống hiển thị tỷ lệ bên cạnh mỗi thành phần.

### Q3: Có thể sửa báo cáo đã hoàn tất không?

**Trả lời**: KHÔNG. Sau khi FINALIZED, báo cáo không thể sửa.

**Nếu cần sửa**:
- Tạo báo cáo điều chỉnh ở tháng tiếp theo
- Hoặc liên hệ IT để revert (mất dữ liệu)

### Q4: Báo cáo tháng 1 phải nộp khi nào?

**Trả lời**: Theo quy định BHXH Việt Nam, nộp trước ngày 10 tháng 2.

Khuyến nghị: Hoàn tất báo cáo trước ngày 5 để có thời gian kiểm tra.

### Q5: Lương BHXH khác lương thực lĩnh?

**Trả lời**: Đúng.
- **Lương BHXH**: Lương cơ sở để tính BHXH (thường = lương cơ bản)
- **Lương thực lĩnh**: Lương nhận về sau khi trừ BHXH, thuế, các khoản khác

### Q6: Ai có quyền xem báo cáo BHXH?

**Trả lời**:
- **Nhân viên HR**: Xem tất cả báo cáo
- **Payroll Admin**: Xem + Tạo + Sửa + Hoàn tất
- **Nhân viên thường**: KHÔNG có quyền xem

### Q7: File Excel có thể chỉnh sửa không?

**Trả lời**: Có, sau khi tải về bạn có thể chỉnh sửa trước khi nộp (thêm logo, điều chỉnh format...).

Nhưng **KHÔNG NÊN** thay đổi số liệu để tránh sai sót.

### Q8: Hệ thống có gửi email nhắc nhở không?

**Trả lời**: Hiện tại chưa có. Sẽ bổ sung trong phiên bản sau.

Bạn cần tự nhớ deadline hoặc đặt lịch nhắc trong calendar.

### Q9: Có thể xuất PDF không?

**Trả lời**: Hiện tại chỉ xuất Excel.

**Workaround**: Mở file Excel → Print → Save as PDF.

### Q10: Làm gì khi quên mật khẩu?

**Trả lời**: Click "Quên mật khẩu" ở trang đăng nhập, hoặc liên hệ IT.

---

## Phần 10: Liên Hệ Hỗ Trợ

### Hỗ Trợ Kỹ Thuật
- **Email**: support@company.com
- **Hotline**: 1900-xxxx (8h-17h, T2-T6)
- **Slack**: #hrm-support

### Hỗ Trợ Nghiệp Vụ BHXH
- **Phòng Nhân Sự**: nhansu@company.com
- **Phòng Tài Chính**: taichinh@company.com

### Báo Lỗi/Góp Ý
- **Jira**: [Link] (cho IT)
- **Feedback Form**: [Link]

---

**Phiên bản**: 1.0  
**Ngày cập nhật**: 12/01/2026  
**Người soạn**: Dev Team

---

## Phụ Lục A: Bảng Tỷ Lệ BHXH Hiện Hành

| Thành Phần | Người Lao Động | Người Sử Dụng | Tổng |
|------------|----------------|---------------|------|
| BHXH Hưu Trí | 8% | 14% | 22% |
| BHXH Ốm Đau | 0% | 3% | 3% |
| BHXH TNLĐ-BNN | 0% | 1% | 1% |
| BHTN | 1% | 1% | 2% |
| BHYT | 1.5% | 3% | 4.5% |
| **TỔNG** | **10.5%** | **22%** | **32.5%** |

## Phụ Lục B: Sơ Đồ Quy Trình

```
[Tạo Hợp Đồng] → [Chọn BHXH] → [Lưu]
                                   ↓
                    [Hệ thống tạo Participation]
                                   ↓
[Đầu tháng] → [Tạo Báo Cáo BHXH] → [Hệ thống quét thay đổi]
                                   ↓
              [Review + Điều chỉnh tháng KK (nếu cần)]
                                   ↓
                           [Hoàn tất báo cáo]
                                   ↓
              [Hệ thống tính toán + Tạo snapshot]
                                   ↓
                    [Xem Tổng hợp + Xuất Excel]
                                   ↓
                         [Nộp cơ quan BHXH]
```

## Phụ Lục C: Shortcut Keys (Sắp có)

| Phím tắt | Chức năng |
|----------|-----------|
| Ctrl + N | Tạo báo cáo mới |
| Ctrl + S | Lưu thay đổi |
| Ctrl + E | Xuất Excel |
| Ctrl + F | Tìm kiếm |
| ESC | Đóng dialog |
