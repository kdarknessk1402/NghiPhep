<?php
// views/user/profile.php - Thông tin cá nhân và đổi mật khẩu
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Lấy thông tin chi tiết người dùng
$stmt = $pdo->prepare("
    SELECT n.*, v.TenVaiTro,
           (n.SoNgayPhepNam - n.SoNgayPhepDaDung) as SoNgayPhepConLai
    FROM NguoiDung n
    JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
    WHERE n.MaNguoiDung = ?
");
$stmt->execute([$currentUser['id']]);
$userInfo = $stmt->fetch();

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        $email = sanitizeInput($_POST['email'] ?? '');
        $viTri = sanitizeInput($_POST['vi_tri'] ?? '');
        $khoaPhong = sanitizeInput($_POST['khoa_phong'] ?? '');
        
        if (!empty($email) && isValidEmail($email)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE NguoiDung 
                    SET Email = ?, ViTri = ?, KhoaPhong = ?
                    WHERE MaNguoiDung = ?
                ");
                $stmt->execute([$email, $viTri, $khoaPhong, $currentUser['id']]);
                
                // Cập nhật session
                $_SESSION['email'] = $email;
                
                logActivity($currentUser['id'], 'UPDATE_PROFILE', 'Cập nhật thông tin cá nhân');
                redirectWithMessage('profile.php', 'success', 'Cập nhật thông tin thành công');
                
            } catch (PDOException $e) {
                setFlashMessage('error', 'Lỗi: ' . $e->getMessage());
            }
        } else {
            setFlashMessage('error', 'Email không hợp lệ');
        }
    }
    
    if ($action === 'change_password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if (empty($oldPassword)) $errors[] = "Vui lòng nhập mật khẩu cũ";
        if (empty($newPassword)) $errors[] = "Vui lòng nhập mật khẩu mới";
        if (strlen($newPassword) < 6) $errors[] = "Mật khẩu mới phải có ít nhất 6 ký tự";
        if ($newPassword !== $confirmPassword) $errors[] = "Mật khẩu mới không khớp";
        
        if (empty($errors)) {
            // Kiểm tra mật khẩu cũ
            $stmt = $pdo->prepare("SELECT MatKhau FROM NguoiDung WHERE MaNguoiDung = ?");
            $stmt->execute([$currentUser['id']]);
            $user = $stmt->fetch();
            
            if (verifyPassword($oldPassword, $user['MatKhau'])) {
                $hashedPassword = hashPassword($newPassword);
                
                $stmt = $pdo->prepare("UPDATE NguoiDung SET MatKhau = ? WHERE MaNguoiDung = ?");
                $stmt->execute([$hashedPassword, $currentUser['id']]);
                
                logActivity($currentUser['id'], 'CHANGE_PASSWORD', 'Đổi mật khẩu');
                redirectWithMessage('profile.php', 'success', 'Đổi mật khẩu thành công');
                
            } else {
                setFlashMessage('error', 'Mật khẩu cũ không đúng');
            }
        } else {
            setFlashMessage('error', implode('<br>', $errors));
        }
    }
}

$pageTitle = "Thông tin cá nhân";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar { min-height: calc(100vh - 56px); background-color: #fff; box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05); }
        .sidebar .nav-link { color: #495057; padding: 12px 20px; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #667eea; color: white; }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
        .profile-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; }
        .avatar { width: 100px; height: 100px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; color: #667eea; margin: 0 auto 15px; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-calendar-check"></i> HỆ THỐNG NGHỈ PHÉP
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($currentUser['fullname']) ?>
                </span>
                <a href="../../controllers/AuthController.php?action=logout" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Trang chủ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_leaves.php">
                            <i class="fas fa-list"></i> Đơn của tôi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_leave.php">
                            <i class="fas fa-plus-circle"></i> Tạo đơn mới
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user"></i> Thông tin cá nhân
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <?php displayFlashMessage(); ?>
                
                <h2 class="mb-4">
                    <i class="fas fa-user-circle"></i> Thông tin cá nhân
                </h2>
                
                <div class="row">
                    <!-- Profile Card -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="profile-header text-center">
                                <div class="avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h4><?= htmlspecialchars($userInfo['HoTen']) ?></h4>
                                <p class="mb-0"><?= getRoleBadge($userInfo['TenVaiTro']) ?></p>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><i class="fas fa-id-badge text-primary"></i></td>
                                        <td><strong>Mã NV:</strong></td>
                                        <td><?= htmlspecialchars($userInfo['MaNguoiDung']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-user text-info"></i></td>
                                        <td><strong>Username:</strong></td>
                                        <td><?= htmlspecialchars($userInfo['TenDangNhap']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-envelope text-danger"></i></td>
                                        <td><strong>Email:</strong></td>
                                        <td><?= htmlspecialchars($userInfo['Email']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-briefcase text-warning"></i></td>
                                        <td><strong>Vị trí:</strong></td>
                                        <td><?= htmlspecialchars($userInfo['ViTri']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-building text-success"></i></td>
                                        <td><strong>Khoa/Phòng:</strong></td>
                                        <td><?= htmlspecialchars($userInfo['KhoaPhong']) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Thống kê phép -->
                        <div class="card mt-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Thống kê phép năm</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Tổng phép năm:</span>
                                        <strong><?= $userInfo['SoNgayPhepNam'] ?> ngày</strong>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Đã sử dụng:</span>
                                        <strong><?= $userInfo['SoNgayPhepDaDung'] ?> ngày</strong>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" 
                                             style="width: <?= ($userInfo['SoNgayPhepDaDung'] / $userInfo['SoNgayPhepNam']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Còn lại:</span>
                                        <strong class="text-success"><?= $userInfo['SoNgayPhepConLai'] ?> ngày</strong>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" 
                                             style="width: <?= ($userInfo['SoNgayPhepConLai'] / $userInfo['SoNgayPhepNam']) * 100 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Update Profile Form -->
                    <div class="col-md-8">
                        <!-- Cập nhật thông tin -->
                        <div class="card mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-edit"></i> Cập nhật thông tin
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Mã nhân viên</label>
                                            <input type="text" class="form-control bg-light" 
                                                   value="<?= htmlspecialchars($userInfo['MaNguoiDung']) ?>" readonly>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Tên đăng nhập</label>
                                            <input type="text" class="form-control bg-light" 
                                                   value="<?= htmlspecialchars($userInfo['TenDangNhap']) ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Họ tên</label>
                                        <input type="text" class="form-control bg-light" 
                                               value="<?= htmlspecialchars($userInfo['HoTen']) ?>" readonly>
                                        <small class="text-muted">Liên hệ admin để thay đổi</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">
                                            Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?= htmlspecialchars($userInfo['Email']) ?>" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Vị trí</label>
                                            <input type="text" class="form-control" name="vi_tri" 
                                                   value="<?= htmlspecialchars($userInfo['ViTri']) ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Khoa/Phòng</label>
                                            <input type="text" class="form-control" name="khoa_phong" 
                                                   value="<?= htmlspecialchars($userInfo['KhoaPhong']) ?>">
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Cập nhật thông tin
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Đổi mật khẩu -->
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-key"></i> Đổi mật khẩu
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" id="changePasswordForm">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">
                                            Mật khẩu cũ <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="old_password" 
                                                   id="oldPassword" required>
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('oldPassword', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">
                                            Mật khẩu mới <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="new_password" 
                                                   id="newPassword" required minlength="6">
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('newPassword', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Tối thiểu 6 ký tự</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">
                                            Nhập lại mật khẩu mới <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="confirm_password" 
                                                   id="confirmPassword" required minlength="6">
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('confirmPassword', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Lưu ý:</strong> Sau khi đổi mật khẩu, bạn sẽ cần đăng nhập lại với mật khẩu mới.
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key"></i> Đổi mật khẩu
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Validate form đổi mật khẩu
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Mật khẩu mới không khớp!');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Mật khẩu mới phải có ít nhất 6 ký tự!');
                return false;
            }
        });
    </script>
</body>
</html>