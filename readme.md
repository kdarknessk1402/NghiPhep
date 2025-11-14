# 📋 HỆ THỐNG QUẢN LÝ NGHỈ PHÉP - APPNGHIPHEP

## 🎯 GIỚI THIỆU

Hệ thống quản lý đơn nghỉ phép trực tuyến dành cho các tổ chức, trường học.

**Tính năng chính:**

- ✅ Đăng nhập với phân quyền 3 cấp (Admin, Manager, User)
- ✅ Tạo và quản lý đơn nghỉ phép
- ✅ Duyệt/Từ chối đơn nghỉ phép
- ✅ Gửi email thông báo tự động
- ✅ Quản lý người dùng
- ✅ Thống kê và báo cáo

---

## 📦 YÊU CẦU HỆ THỐNG

- **XAMPP** hoặc **WAMP** (PHP 7.4+, MySQL 8.0+)
- **Composer** (để cài đặt PHPMailer)
- **Web Browser** (Chrome, Firefox khuyến nghị)

---

## 🚀 HƯỚNG DẪN CÀI ĐẶT

### Bước 1: Clone/Download Project

```bash
# Clone hoặc tải về và giải nén vào thư mục:
C:\xampp\htdocs\appnghiphep\
```

### Bước 2: Tạo Database

1. Mở **phpMyAdmin**: http://localhost/phpmyadmin
2. Tạo database mới tên: `APPNGHIPHEP`
3. Chọn **Collation**: `utf8mb4_unicode_ci`
4. Click vào tab **SQL**
5. Copy toàn bộ nội dung file `database.sql` và paste vào
6. Click **Go** để chạy

### Bước 3: Cài đặt PHPMailer

Mở **Command Prompt** hoặc **Terminal**:

```bash
cd C:\xampp\htdocs\appnghiphep
composer require phpmailer/phpmailer
```

### Bước 4: Cấu hình Database

Mở file `config/database.php` và chỉnh sửa (nếu cần):

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // Mật khẩu MySQL của bạn
define('DB_NAME', 'APPNGHIPHEP');
```

### Bước 5: Cấu hình Email

Chạy SQL sau để cấu hình email (ví dụ Gmail):

```sql
UPDATE CauHinhEmail SET
    SmtpHost = 'smtp.gmail.com',
    SmtpPort = 587,
    SmtpUsername = 'thbao.thuduc@gmail.com',
    SmtpPassword = 'gzgiilqoihmefzve',
    EmailNguoiGui = 'thbao.thuduc@gmail.com',
    TenNguoiGui = 'Hệ thống nghỉ phép';
```

**Lưu ý Email Gmail:**

- Bật "Less secure apps": https://myaccount.google.com/lesssecureapps
- Hoặc tạo App Password: https://myaccount.google.com/apppasswords

### Bước 6: Reset mật khẩu tài khoản demo

```sql
-- Reset tất cả mật khẩu về 123456
UPDATE NguoiDung SET
    MatKhau = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE TenDangNhap IN ('admin', 'user1', 'manager');
```

---

## 🎮 SỬ DỤNG HỆ THỐNG

### Truy cập hệ thống

```
URL: http://localhost/appnghiphep/
```

### Tài khoản mặc định

| Username  | Password | Vai trò | Quyền                      |
| --------- | -------- | ------- | -------------------------- |
| `admin`   | `123456` | Admin   | Toàn quyền hệ thống        |
| `manager` | `123456` | Manager | Quản lý đơn của khoa/phòng |
| `user1`   | `123456` | User    | Tạo và xem đơn của mình    |

---

## 📂 CẤU TRÚC THỦ MỤC

```
appnghiphep/
├── config/
│   ├── database.php           # Cấu hình kết nối DB
│   └── mail_config.php        # Cấu hình PHPMailer
├── includes/
│   ├── session.php            # Quản lý session
│   └── functions.php          # Hàm tiện ích
├── controllers/
│   └── AuthController.php     # Xử lý đăng nhập/đăng xuất
├── models/                     # (Có thể mở rộng)
├── views/
│   ├── login.php              # Trang đăng nhập
│   ├── user/
│   │   ├── dashboard.php      # Dashboard nhân viên
│   │   ├── my_leaves.php      # Đơn của tôi
│   │   ├── create_leave.php   # Tạo đơn mới
│   │   └── profile.php        # Thông tin cá nhân
│   ├── manager/
│   │   └── dashboard.php      # Dashboard quản lý
│   └── admin/
│       ├── dashboard.php      # Dashboard admin
│       └── manage_users.php   # Quản lý người dùng
├── assets/
│   ├── css/                   # (Có thể thêm CSS tùy chỉnh)
│   └── js/                    # (Có thể thêm JS tùy chỉnh)
├── vendor/                    # PHPMailer (tự động tạo)
├── logs/                      # Log hoạt động (tự động tạo)
├── index.php                  # Trang chủ (redirect)
├── create_password.php        # Tool tạo mật khẩu hash
├── test_email.php             # Test gửi email
└── test_mail_debug.php        # Test email với debug
```

---

## 🔧 KIỂM TRA VÀ DEBUG

### Test kết nối Database

Tạo file `test_db.php`:

```php
<?php
require_once 'config/database.php';
$pdo = getDBConnection();
echo "✅ Kết nối database thành công!<br>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM NguoiDung");
$result = $stmt->fetch();
echo "📊 Số người dùng: " . $result['total'];
?>
```

Truy cập: http://localhost/appnghiphep/test_db.php

### Test gửi email

Truy cập: http://localhost/appnghiphep/test_mail_debug.php

Nhập email của bạn và click "Gửi Email Test"

---

## 📋 QUY TRÌNH SỬ DỤNG

### Quy trình nghỉ phép chuẩn:

1. **USER** tạo đơn nghỉ phép

   - Chọn loại phép, ngày bắt đầu/kết thúc
   - Hệ thống tự động tính số ngày nghỉ
   - Kiểm tra số ngày phép còn lại

2. **Hệ thống** gửi email thông báo cho Admin/Manager

3. **MANAGER/ADMIN** xem và duyệt đơn

   - Xem chi tiết đơn
   - Duyệt hoặc từ chối với ghi chú

4. **Hệ thống** gửi email kết quả cho USER
   - Tự động trừ số ngày phép nếu được duyệt

---

## 🛠️ TÍNH NĂNG CHI TIẾT

### Dành cho USER (Nhân viên)

- ✅ Dashboard: Xem thống kê phép năm
- ✅ Tạo đơn nghỉ phép mới
- ✅ Xem danh sách đơn của mình
- ✅ Lọc đơn theo trạng thái
- ✅ Xóa đơn đang chờ duyệt
- ✅ Cập nhật thông tin cá nhân
- ✅ Đổi mật khẩu

### Dành cho MANAGER (Quản lý)

- ✅ Xem đơn của khoa/phòng mình quản lý
- ✅ Duyệt/Từ chối đơn với ghi chú
- ✅ Thống kê theo khoa/phòng
- ✅ Tất cả chức năng của USER

### Dành cho ADMIN

- ✅ Xem tất cả đơn trong hệ thống
- ✅ Duyệt/Từ chối bất kỳ đơn nào
- ✅ Quản lý người dùng (Thêm/Sửa/Xóa)
- ✅ Reset mật khẩu cho user
- ✅ Cấu hình hệ thống
- ✅ Xem log hoạt động

---

## 🔐 BẢO MẬT

- ✅ Mật khẩu được mã hóa bằng **bcrypt**
- ✅ Session management an toàn
- ✅ CSRF protection (có thể mở rộng)
- ✅ XSS protection với `htmlspecialchars()`
- ✅ SQL Injection protection với **PDO Prepared Statements**
- ✅ Log mọi hoạt động quan trọng

---

## 📧 CẤU HÌNH EMAIL

### Gmail

```sql
UPDATE CauHinhEmail SET
    SmtpHost = 'smtp.gmail.com',
    SmtpPort = 587,
    SmtpUsername = 'your-email@gmail.com',
    SmtpPassword = 'your-password';
```

### Email .edu (Trường học)

```sql
UPDATE CauHinhEmail SET
    SmtpHost = 'smtp.yourschool.edu.vn',  -- Liên hệ IT
```
