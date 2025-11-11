<?php
// views/user/create_leave.php - Tạo đơn nghỉ phép
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail_config.php';

requireLogin();
requireRole('USER');

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Lấy thông tin số ngày phép còn lại
$stmt = $pdo->prepare("
    SELECT SoNgayPhepNam, SoNgayPhepDaDung, 
           (SoNgayPhepNam - SoNgayPhepDaDung) as SoNgayPhepConLai
    FROM NguoiDung 
    WHERE MaNguoiDung = ?
");
$stmt->execute([$currentUser['id']]);
$phepInfo = $stmt->fetch();

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loaiPhep = sanitizeInput($_POST['loai_phep'] ?? '');
    $ngayBatDau = $_POST['ngay_bat_dau'] ?? '';
    $ngayKetThuc = $_POST['ngay_ket_thuc'] ?? '';
    $lyDo = sanitizeInput($_POST['ly_do'] ?? '');
    
    $errors = [];
    
    // Validate
    if (empty($loaiPhep)) $errors[] = "Vui lòng chọn loại phép";
    if (empty($ngayBatDau)) $errors[] = "Vui lòng chọn ngày bắt đầu";
    if (empty($ngayKetThuc)) $errors[] = "Vui lòng chọn ngày kết thúc";
    if (empty($lyDo)) $errors[] = "Vui lòng nhập lý do nghỉ";
    
    if (empty($errors)) {
        // Kiểm tra ngày hợp lệ
        if (strtotime($ngayKetThuc) < strtotime($ngayBatDau)) {
            $errors[] = "Ngày kết thúc phải sau ngày bắt đầu";
        }
        
        // Tính số ngày nghỉ
        $soNgayNghi = calculateDays($ngayBatDau, $ngayKetThuc, true);
        
        // Kiểm tra số ngày phép còn lại
        if ($soNgayNghi > $phepInfo['SoNgayPhepConLai']) {
            $errors[] = "Số ngày nghỉ ($soNgayNghi) vượt quá số ngày phép còn lại ({$phepInfo['SoNgayPhepConLai']})";
        }
    }
    
    if (empty($errors)) {
        try {
            // Tạo mã đơn
            $maDon = generateLeaveCode('DN');
            
            // Insert đơn nghỉ phép
            $stmt = $pdo->prepare("
                INSERT INTO DonNghiPhep 
                (MaDon, MaNguoiDung, LoaiPhep, NgayBatDauNghi, NgayKetThucNghi, SoNgayNghi, LyDo, TrangThai)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'WAITING')
            ");
            
            $stmt->execute([
                $maDon,
                $currentUser['id'],
                $loaiPhep,
                $ngayBatDau,
                $ngayKetThuc,
                $soNgayNghi,
                $lyDo
            ]);
            
            // Gửi email thông báo cho admin/manager
            $emailAdmin = $pdo->query("
                SELECT Email FROM NguoiDung n
                JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
                WHERE v.TenVaiTro IN ('ADMIN', 'MANAGER')
            ")->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($emailAdmin)) {
                sendLeaveRequestNotification($maDon, $emailAdmin, 'create');
            }
            
            // Log activity
            logActivity($currentUser['id'], 'CREATE_LEAVE', "Tạo đơn nghỉ phép: $maDon");
            
            redirectWithMessage('my_leaves.php', 'success', "Tạo đơn nghỉ phép thành công! Mã đơn: $maDon");
            
        } catch (PDOException $e) {
            $errors[] = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}

$pageTitle = "Tạo đơn nghỉ phép";
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
        .info-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
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
                        <a class="nav-link active" href="create_leave.php">
                            <i class="fas fa-plus-circle"></i> Tạo đơn mới
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> Thông tin cá nhân
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">
                    <i class="fas fa-plus-circle"></i> Tạo đơn nghỉ phép mới
                </h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <strong><i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Thông tin số ngày phép -->
                <div class="info-box">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h3><?= $phepInfo['SoNgayPhepNam'] ?></h3>
                            <p class="mb-0">Tổng số ngày phép năm</p>
                        </div>
                        <div class="col-md-4">
                            <h3><?= $phepInfo['SoNgayPhepDaDung'] ?></h3>
                            <p class="mb-0">Đã sử dụng</p>
                        </div>
                        <div class="col-md-4">
                            <h3><?= $phepInfo['SoNgayPhepConLai'] ?></h3>
                            <p class="mb-0">Còn lại</p>
                        </div>
                    </div>
                </div>
                
                <!-- Form tạo đơn -->
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="" id="leaveForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-tag"></i> Loại phép <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" name="loai_phep" required>
                                        <option value="">-- Chọn loại phép --</option>
                                        <option value="Phép năm">Phép năm</option>
                                        <option value="Phép ốm">Phép ốm</option>
                                        <option value="Phép việc riêng">Phép việc riêng</option>
                                        <option value="Phép không lương">Phép không lương</option>
                                        <option value="Phép thai sản">Phép thai sản</option>
                                        <option value="Phép hiếu">Phép hiếu</option>
                                        <option value="Phép hỷ">Phép hỷ</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-info-circle"></i> Số ngày phép còn lại
                                    </label>
                                    <input type="text" class="form-control bg-light" 
                                           value="<?= $phepInfo['SoNgayPhepConLai'] ?> ngày" readonly>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-calendar-alt"></i> Ngày bắt đầu <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" name="ngay_bat_dau" 
                                           id="ngayBatDau" required min="<?= date('Y-m-d') ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-calendar-check"></i> Ngày kết thúc <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" name="ngay_ket_thuc" 
                                           id="ngayKetThuc" required min="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-hourglass-half"></i> Số ngày nghỉ
                                </label>
                                <input type="text" class="form-control bg-light" id="soNgayNghi" 
                                       value="0 ngày" readonly>
                                <small class="text-muted">Tự động tính dựa trên ngày bắt đầu và kết thúc</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-comment-alt"></i> Lý do nghỉ <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" name="ly_do" rows="4" 
                                          placeholder="Nhập lý do nghỉ phép..." required></textarea>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Lưu ý:</strong> 
                                Sau khi tạo đơn, vui lòng đợi quản lý duyệt. 
                                Bạn sẽ nhận được email thông báo khi đơn được xử lý.
                            </div>
                            
                            <div class="text-end">
                                <a href="my_leaves.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Gửi đơn nghỉ phép
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tự động tính số ngày nghỉ
        function calculateLeaveDays() {
            const startDate = document.getElementById('ngayBatDau').value;
            const endDate = document.getElementById('ngayKetThuc').value;
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (end >= start) {
                    const diffTime = Math.abs(end - start);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                    document.getElementById('soNgayNghi').value = diffDays + ' ngày';
                } else {
                    document.getElementById('soNgayNghi').value = '0 ngày';
                    alert('Ngày kết thúc phải sau ngày bắt đầu!');
                }
            }
        }
        
        document.getElementById('ngayBatDau').addEventListener('change', calculateLeaveDays);
        document.getElementById('ngayKetThuc').addEventListener('change', calculateLeaveDays);
        
        // Validate form
        document.getElementById('leaveForm').addEventListener('submit', function(e) {
            const startDate = document.getElementById('ngayBatDau').value;
            const endDate = document.getElementById('ngayKetThuc').value;
            
            if (!startDate || !endDate) {
                e.preventDefault();
                alert('Vui lòng chọn ngày bắt đầu và kết thúc!');
                return false;
            }
            
            if (new Date(endDate) < new Date(startDate)) {
                e.preventDefault();
                alert('Ngày kết thúc phải sau ngày bắt đầu!');
                return false;
            }
        });
    </script>
</body>
</html>