<?php
// views/admin/create_leave_for_user.php - Admin tạo đơn nghỉ phép cho nhân viên
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('ADMIN');

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Lấy danh sách nhân viên (ưu tiên NỮ)
$stmt = $pdo->query("
    SELECT n.*, v.TenVaiTro,
           (n.SoNgayPhepNam - n.SoNgayPhepDaDung) as SoNgayPhepConLai
    FROM NguoiDung n
    JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
    WHERE v.TenVaiTro = 'USER'
    ORDER BY n.GioiTinh DESC, n.HoTen ASC
");
$users = $stmt->fetchAll();

$pageTitle = "Tạo đơn nghỉ phép cho nhân viên";
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
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
        .user-card { cursor: pointer; transition: all 0.3s; }
        .user-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15); }
        .female-badge { background-color: #ff69b4; color: white; }
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
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-user-plus"></i> Chọn nhân viên để tạo đơn nghỉ phép
            </h2>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Hướng dẫn:</strong> Click vào nhân viên để tạo đơn nghỉ phép cho họ. 
            Nhân viên nữ <i class="fas fa-venus female-badge px-2 py-1 rounded"></i> có thể được tạo đơn phép thai sản.
        </div>
        
        <div class="row">
            <?php foreach ($users as $user): ?>
            <div class="col-md-4 mb-4">
                <div class="card user-card" onclick="createLeaveForUser('<?= $user['MaNguoiDung'] ?>')">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="card-title">
                                    <i class="fas fa-user-circle"></i> 
                                    <?= htmlspecialchars($user['HoTen']) ?>
                                </h5>
                                <p class="card-text mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-id-badge"></i> <?= htmlspecialchars($user['MaNguoiDung']) ?>
                                    </small>
                                </p>
                                <p class="card-text mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['Email']) ?>
                                    </small>
                                </p>
                                <p class="card-text mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-building"></i> <?= htmlspecialchars($user['KhoaPhong']) ?>
                                    </small>
                                </p>
                            </div>
                            <div class="text-end">
                                <?php if ($user['GioiTinh'] == 'Nu'): ?>
                                    <span class="badge female-badge">
                                        <i class="fas fa-venus"></i> Nữ
                                    </span>
                                    <br>
                                    <small class="badge bg-info mt-1">Có phép thai sản</small>
                                <?php else: ?>
                                    <span class="badge bg-primary">
                                        <i class="fas fa-mars"></i> Nam
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <small class="text-muted">Phép năm</small>
                                <h6><?= $user['SoNgayPhepNam'] ?> ngày</h6>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Còn lại</small>
                                <h6 class="text-success"><?= $user['SoNgayPhepConLai'] ?> ngày</h6>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary w-100 mt-2">
                            <i class="fas fa-plus-circle"></i> Tạo đơn cho nhân viên này
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function createLeaveForUser(userId) {
            window.location.href = '../user/create_leave.php?for_user=' + userId;
        }
    </script>
</body>
</html>