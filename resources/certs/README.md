# Bộ chứng chỉ HTTPS của newoq

`cacert.pem` là bộ chứng chỉ CA công khai của Mozilla, tải nguyên bản từ <https://curl.se/ca/cacert.pem>. Thông tin nguồn và giấy phép MPL 2.0: <https://curl.se/docs/caextract.html>, <https://www.mozilla.org/MPL/2.0/>. Giữ nguyên phần chú thích nguồn trong file PEM.

`AppServiceProvider` truyền đường dẫn file này qua tùy chọn `verify` cho Laravel HTTP Client. Wikipedia và Gemini dùng chung cấu hình; xác minh chứng chỉ và tên miền HTTPS vẫn bật. Cấu hình này không thay đổi PHP, Composer hoặc các ứng dụng khác trên máy.

## Chuyển sang máy khác

1. Sao chép toàn bộ dự án, bao gồm `resources/certs/cacert.pem`.
2. Để `HTTP_CA_BUNDLE` trống hoặc không khai báo trong `.env`. Đường dẫn mặc định được tính theo thư mục dự án trên máy hiện tại, không cố định ổ C hay XAMPP.
3. Chạy `php artisan config:clear` để bỏ cấu hình đã cache từ máy cũ.
4. Khởi động lại Apache hoặc tiến trình `php artisan serve`, rồi thử tìm Wikipedia.

Nếu thiếu hoặc không đọc được file PEM, dự án báo lỗi cấu hình thay vì tắt xác minh HTTPS.

## Mạng nội bộ hoặc phần mềm kiểm tra HTTPS

Bộ CA công khai không bao gồm CA riêng của tổ chức. Nếu proxy/phần mềm bảo mật ký lại chứng chỉ HTTPS, lấy chứng chỉ CA từ quản trị viên hoặc nguồn chính thức của phần mềm đó. Tạo một file PEM riêng gồm bộ CA công khai và CA nội bộ đã xác minh, rồi cấu hình đường dẫn tuyệt đối, ví dụ:

```dotenv
HTTP_CA_BUNDLE="C:/certificates/company-ca-bundle.pem"
```

Chạy `php artisan config:clear` và khởi động lại server sau khi thay đổi. Đường dẫn tùy chỉnh phụ thuộc từng máy; bỏ cấu hình này khi chuyển sang máy không có file tương ứng. Không đặt `verify=false` hoặc dùng `withoutVerifying()`.

Cấu hình CA không bảo đảm khắc phục mọi lỗi cURL 60: đồng hồ hệ thống sai, chứng chỉ máy chủ hết hạn hoặc tên miền không khớp vẫn cần được xử lý đúng nguyên nhân.

## Cập nhật bộ CA

Định kỳ tải lại file từ nguồn chính thức; không chỉnh từng chứng chỉ trong bản gốc. Chạy trong thư mục gốc dự án bằng PowerShell:

```powershell
$caFile = Join-Path (Get-Location) 'resources/certs/cacert.pem'
$caDownload = "$caFile.download"
Invoke-WebRequest -Uri 'https://curl.se/ca/cacert.pem' -OutFile $caDownload
$caHashResponse = (Invoke-WebRequest -Uri 'https://curl.se/ca/cacert.pem.sha256').Content
$caHashText = if ($caHashResponse -is [byte[]]) {
    [System.Text.Encoding]::UTF8.GetString($caHashResponse)
} else {
    [string]$caHashResponse
}
$caExpectedHash = ($caHashText.Trim() -split '\s+')[0]
$caActualHash = (Get-FileHash -LiteralPath $caDownload -Algorithm SHA256).Hash
if ($caActualHash -ine $caExpectedHash) {
    throw 'CA bundle SHA-256 mismatch; chua thay file dang dung.'
}
Move-Item -LiteralPath $caDownload -Destination $caFile -Force
```

Xem thay đổi, chạy `php artisan test`, rồi phân phối file mới cùng dự án. Không tự tải chứng chỉ khi người dùng tìm kiếm hoặc gọi API.
