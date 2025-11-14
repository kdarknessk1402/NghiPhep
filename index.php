<?php
// ============================================
// FILE: index.php (Đặt ở thư mục gốc)
// ============================================
require_once 'includes/session.php';

// Redirect về trang phù hợp
if (isLoggedIn()) {
    // Đã đăng nhập → Chuyển về dashboard theo role
    header('Location: ' . getHomePage());
} else {
    // Chưa đăng nhập → Chuyển về trang login
    header('Location: views/login.php');
}
exit;


// ============================================
// FILE: views/user/dashboard.php (Dashboard cho USER)
// ============================================
?>
<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

// Kiểm tra đăng nhập
requireLogin();

// Lấy thông tin user
$authController = new AuthController();
$userInfo = $authController->getUserInfo($_SESSION['user_id']);
$currentUser = getCurrentUser();

// Lấy số liệu thống kê
$pdo = getDBConnection();

// Tổng số đơn
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM DonNghiPhep WHERE MaNguoiDung = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalLeaves = $stmt->fetchColumn();

// Đơn chờ duyệt
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM DonNghiPhep WHERE MaNguoiDung = ? AND TrangThai = 'WAITING'");
$stmt->execute([$_SESSION['user_id']]);
$waitingLeaves = $stmt->fetchColumn();

// Đơn đã duyệt
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM DonNghiPhep WHERE MaNguoiDung = ? AND TrangThai = 'ACCEPT'");
$stmt->execute([$_SESSION['user_id']]);
$acceptedLeaves = $stmt->fetchColumn();

// Đơn bị từ chối
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM DonNghiPhep WHERE MaNguoiDung = ? AND TrangThai = 'DENY'");
$stmt->execute([$_SESSION['user_id']]);
$deniedLeaves = $stmt->fetchColumn();

// Lấy danh sách đơn gần đây
$stmt = $pdo->prepare("
    SELECT * FROM DonNghiPhep 
    WHERE MaNguoiDung = ? 
    ORDER BY NgayTao DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recentLeaves = $stmt->fetchAll();

$pageTitle = "Dashboard - Nhân viên";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #fff;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }
        
        .sidebar .nav-link {
            color: #495057;
            padding: 12px 20px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #667eea;
            color: white;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card {
            padding: 20px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-calendar-check"></i> HỆ THỐNG NGHỈ PHÉP
            </a>
            
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="fas fa-user-circle"></i> 
                    <?= htmlspecialchars($currentUser['fullname']) ?>
                    <?= getRoleBadge($currentUser['role']) ?>
                </span>
                
                <a href="../../controllers/AuthController.php?action=logout" 
                   class="btn btn-outline-light btn-sm"
                   onclick="return confirm('Bạn có chắc muốn đăng xuất?')">
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
                        <a class="nav-link active" href="dashboard.php">
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
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> Thông tin cá nhân
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="change_password.php">
                            <i class="fas fa-key"></i> Đổi mật khẩu
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <?php displayFlashMessage(); ?>
                
                <h2 class="mb-4">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </h2>
                
                <!-- Thống kê phép năm -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Phép năm</h6>
                                    <h3 class="mb-0"><?= $userInfo['SoNgayPhepNam'] ?></h3>
                                    <small class="text-muted">ngày</small>
                                </div>
                                <div class="stat-icon bg-primary text-white">
                                    <i class="fas fa-calendar"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Đã dùng</h6>
                                    <h3 class="mb-0"><?= $userInfo['SoNgayPhepDaDung'] ?></h3>
                                    <small class="text-muted">ngày</small>
                                </div>
                                <div class="stat-icon bg-warning text-white">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Còn lại</h6>
                                    <h3 class="mb-0"><?= $userInfo['SoNgayPhepConLai'] ?></h3>
                                    <small class="text-muted">ngày</small>
                                </div>
                                <div class="stat-icon bg-success text-white">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Tổng đơn</h6>
                                    <h3 class="mb-0"><?= $totalLeaves ?></h3>
                                    <small class="text-muted">đơn</small>
                                </div>
                                <div class="stat-icon bg-info text-white">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Thống kê trạng thái đơn -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center p-3">
                            <h5><i class="fas fa-clock text-warning"></i> Chờ duyệt</h5>
                            <h2><?= $waitingLeaves ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center p-3">
                            <h5><i class="fas fa-check-circle text-success"></i> Đã duyệt</h5>
                            <h2><?= $acceptedLeaves ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center p-3">
                            <h5><i class="fas fa-times-circle text-danger"></i> Từ chối</h5>
                            <h2><?= $deniedLeaves ?></h2>
                        </div>
                    </div>
                </div>
                
                <!-- Danh sách đơn gần đây -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history"></i> Đơn nghỉ phép gần đây
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentLeaves)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Chưa có đơn nghỉ phép nào</p>
                                <a href="create_leave.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tạo đơn mới
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mã đơn</th>
                                            <th>Loại phép</th>
                                            <th>Từ ngày</th>
                                            <th>Đến ngày</th>
                                            <th>Số ngày</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày tạo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentLeaves as $leave): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($leave['MaDon']) ?></strong></td>
                                            <td><?= htmlspecialchars($leave['LoaiPhep']) ?></td>
                                            <td><?= formatDate($leave['NgayBatDauNghi']) ?></td>
                                            <td><?= formatDate($leave['NgayKetThucNghi']) ?></td>
                                            <td><?= $leave['SoNgayNghi'] ?> ngày</td>
                                            <td><?= getStatusBadge($leave['TrangThai']) ?></td>
                                            <td><?= formatDateTime($leave['NgayTao']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="my_leaves.php" class="btn btn-outline-primary">
                                    Xem tất cả đơn <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>