<?php
// views/admin/settings.php - Cấu hình hệ thống
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('ADMIN');

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Lấy cấu hình email hiện tại
$stmt = $pdo->query("SELECT * FROM CauHinhEmail LIMIT 1");
$emailConfig = $stmt->fetch();

// Xử lý cập nhật cấu hình email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_email') {
        $smtpHost = sanitizeInput($_POST['smtp_host'] ?? '');
        $smtpPort = (int)($_POST['smtp_port'] ?? 587);
        $smtpUsername = sanitizeInput($_POST['smtp_username'] ?? '');
        $smtpPassword = $_POST['smtp_password'] ?? '';
        $emailNguoiGui = sanitizeInput($_POST['email_nguoi_gui'] ?? '');
        $tenNguoiGui = sanitizeInput($_POST['ten_nguoi_gui'] ?? '');
        
        try {
            // Cập nhật hoặc insert
            if ($emailConfig) {
                $stmt = $pdo->prepare("
                    UPDATE CauHinhEmail SET
                    SmtpHost = ?,
                    SmtpPort = ?,
                    SmtpUsername = ?,
                    " . (!empty($smtpPassword) ? "SmtpPassword = ?," : "") . "
                    EmailNguoiGui = ?,
                    TenNguoiGui = ?
                    WHERE MaCauHinh = ?
                ");
                
                $params = [$smtpHost, $smtpPort, $smtpUsername];
                if (!empty($smtpPassword)) {
                    $params[] = $smtpPassword;
                }
                $params[] = $emailNguoiGui;
                $params[] = $tenNguoiGui;
                $params[] = $emailConfig['MaCauHinh'];
                
                $stmt->execute($params);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO CauHinhEmail 
                    (SmtpHost, SmtpPort, SmtpUsername, SmtpPassword, EmailNguoiGui, TenNguoiGui, EmailNhan)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$smtpHost, $smtpPort, $smtpUsername, $smtpPassword, 
                               $emailNguoiGui, $tenNguoiGui, $emailNguoiGui]);
            }
            
            logActivity($currentUser['id'], 'UPDATE_EMAIL_CONFIG', 'Cập nhật cấu hình email');
            redirectWithMessage('settings.php', 'success', 'Cập nhật cấu hình email thành công');
            
        } catch (PDOException $e) {
            setFlashMessage('error', 'Lỗi: ' . $e->getMessage());
        }
    }
    
    if ($action === 'update_default_leave_days') {
        $defaultDays = (int)($_POST['default_days'] ?? 12);
        
        try {
            // Cập nhật số ngày phép mặc định cho user mới
            // (Lưu vào một bảng cấu hình hoặc biến hệ thống)
            // Ở đây ta sẽ cập nhật cho tất cả user có phép = 12
            $stmt = $pdo->prepare("
                UPDATE NguoiDung 
                SET SoNgayPhepNam = ? 
                WHERE SoNgayPhepNam = 12
            ");
            $stmt->execute([$defaultDays]);
            
            logActivity($currentUser['id'], 'UPDATE_DEFAULT_LEAVE', "Cập nhật số ngày phép mặc định: $defaultDays");
            redirectWithMessage('settings.php', 'success', 'Cập nhật số ngày phép mặc định thành công');
            
        } catch (PDOException $e) {
            setFlashMessage('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}

$pageTitle = "Cấu hình hệ thống";
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
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); margin-bottom: 20px; }
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
                    <i class="fas fa-user-shield"></i> <?= htmlspecialchars($currentUser['fullname']) ?>
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
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">
                            <i class="fas fa-users"></i> Quản lý người dùng
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Báo cáo thống kê
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="employee_report.php">
                            <i class="fas fa-calendar-check"></i> Bảng chấm công
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">
                            <i class="fas fa-cog"></i> Cấu hình hệ thống
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <?php displayFlashMessage(); ?>
                
                <h2 class="mb-4">
                    <i class="fas fa-cog"></i> Cấu hình hệ thống
                </h2>
                
                <!-- Cấu hình Email -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope"></i> Cấu hình Email SMTP
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_email">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        SMTP Host <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="smtp_host" 
                                           value="<?= htmlspecialchars($emailConfig['SmtpHost'] ?? '') ?>" 
                                           placeholder="smtp.gmail.com" required>
                                    <small class="text-muted">VD: smtp.gmail.com, smtp.office365.com</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        SMTP Port <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" name="smtp_port" 
                                           value="<?= htmlspecialchars($emailConfig['SmtpPort'] ?? '587') ?>" 
                                           placeholder="587" required>
                                    <small class="text-muted">Thường là 587 (STARTTLS) hoặc 465 (SSL)</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Username (Email) <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" name="smtp_username" 
                                       value="<?= htmlspecialchars($emailConfig['SmtpUsername'] ?? '') ?>" 
                                       placeholder="your-email@gmail.com" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Password
                                </label>
                                <input type="password" class="form-control" name="smtp_password" 
                                       placeholder="Để trống nếu không thay đổi">
                                <small class="text-muted">
                                    Chỉ nhập nếu muốn thay đổi mật khẩu. 
                                    Với Gmail, có thể cần App Password.
                                </small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        Email người gửi <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" name="email_nguoi_gui" 
                                           value="<?= htmlspecialchars($emailConfig['EmailNguoiGui'] ?? '') ?>" 
                                           placeholder="noreply@school.edu.vn" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        Tên người gửi <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="ten_nguoi_gui" 
                                           value="<?= htmlspecialchars($emailConfig['TenNguoiGui'] ?? '') ?>" 
                                           placeholder="Hệ thống nghỉ phép" required>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Lưu ý:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Với Gmail: Cần bật "Less secure apps" hoặc dùng App Password</li>
                                    <li>Với Email .edu: Liên hệ IT để lấy thông tin SMTP chính xác</li>
                                    <li>Hệ thống sẽ tự động phát hiện loại SMTP và cấu hình phù hợp</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Lưu cấu hình Email
                            </button>
                            
                            <a href="../../test_mail_debug.php" target="_blank" class="btn btn-info">
                                <i class="fas fa-vial"></i> Test gửi Email
                            </a>
                        </form>
                    </div>
                </div>
                
                <!-- Cấu hình phép năm -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt"></i> Cấu hình số ngày phép
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_default_leave_days">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Số ngày phép năm mặc định <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" name="default_days" 
                                       value="12" min="0" max="365" required>
                                <small class="text-muted">
                                    Số ngày phép sẽ được áp dụng cho nhân viên mới
                                </small>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Cảnh báo:</strong> 
                                Thao tác này sẽ cập nhật số ngày phép cho tất cả nhân viên đang có 12 ngày phép.
                            </div>
                            
                            <button type="submit" class="btn btn-success"
                                    onclick="return confirm('Bạn có chắc muốn cập nhật số ngày phép mặc định?')">
                                <i class="fas fa-save"></i> Cập nhật
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Thông tin hệ thống -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i> Thông tin hệ thống
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th width="30%">Phiên bản PHP:</th>
                                <td><?= phpversion() ?></td>
                            </tr>
                            <tr>
                                <th>Phiên bản MySQL:</th>
                                <td><?= $pdo->query('SELECT VERSION()')->fetchColumn() ?></td>
                            </tr>
                            <tr>
                                <th>Tổng số người dùng:</th>
                                <td><?= $pdo->query('SELECT COUNT(*) FROM NguoiDung')->fetchColumn() ?></td>
                            </tr>
                            <tr>
                                <th>Tổng số đơn nghỉ phép:</th>
                                <td><?= $pdo->query('SELECT COUNT(*) FROM DonNghiPhep')->fetchColumn() ?></td>
                            </tr>
                            <tr>
                                <th>Email đã gửi:</th>
                                <td><?= $pdo->query('SELECT COUNT(*) FROM LichSuEmail')->fetchColumn() ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Backup & Maintenance -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-tools"></i> Bảo trì hệ thống
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-database fa-3x text-primary mb-3"></i>
                                        <h6>Sao lưu Database</h6>
                                        <button class="btn btn-primary btn-sm" onclick="alert('Chức năng đang phát triển')">
                                            <i class="fas fa-download"></i> Backup
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                                        <h6>Xóa log cũ</h6>
                                        <button class="btn btn-danger btn-sm" onclick="alert('Chức năng đang phát triển')">
                                            <i class="fas fa-trash"></i> Clear Logs
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-history fa-3x text-success mb-3"></i>
                                        <h6>Xem lịch sử</h6>
                                        <button class="btn btn-success btn-sm" onclick="viewActivityLog()">
                                            <i class="fas fa-eye"></i> View Logs
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewActivityLog() {
            // Mở modal hoặc trang mới hiển thị activity log
            alert('Chức năng xem log đang phát triển. \nFile log: logs/activity.log');
        }
    </script>
</body>
</html>