<?php
// views/user/dashboard.php - Dashboard User (FIXED)
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('USER');

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Lấy thông tin user chi tiết (CÓ PHÉP TỒN) - FIXED NULL SAFETY
$stmt = $pdo->prepare("
    SELECT n.*, v.TenVaiTro,
           (n.SoNgayPhepNam - n.SoNgayPhepDaDung) as SoNgayPhepConLai,
           COALESCE(n.SoNgayPhepTonNamTruoc, 0) as SoNgayPhepTonNamTruoc,
           n.NamPhepTon,
           CASE 
               WHEN MONTH(CURDATE()) <= 3 AND n.NamPhepTon = YEAR(CURDATE()) - 1
               THEN COALESCE(n.SoNgayPhepTonNamTruoc, 0)
               ELSE 0
           END as PhepTonConDungDuoc,
           (n.SoNgayPhepNam - n.SoNgayPhepDaDung) + 
           CASE 
               WHEN MONTH(CURDATE()) <= 3 AND n.NamPhepTon = YEAR(CURDATE()) - 1
               THEN COALESCE(n.SoNgayPhepTonNamTruoc, 0)
               ELSE 0
           END as TongPhepCoTheDung
    FROM NguoiDung n
    JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
    WHERE n.MaNguoiDung = ?
");
$stmt->execute([$currentUser['id']]);
$userInfo = $stmt->fetch();

// Đảm bảo các giá trị không bị NULL
$userInfo['ViTri'] = $userInfo['ViTri'] ?? 'Chưa cập nhật';
$userInfo['KhoaPhong'] = $userInfo['KhoaPhong'] ?? 'Chưa cập nhật';
$userInfo['SoNgayPhepTonNamTruoc'] = $userInfo['SoNgayPhepTonNamTruoc'] ?? 0;

// Thống kê đơn
$stmt = $pdo->prepare("SELECT COUNT(*) FROM DonNghiPhep WHERE MaNguoiDung = ?");
$stmt->execute([$currentUser['id']]);
$totalLeaves = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM DonNghiPhep WHERE MaNguoiDung = ? AND TrangThai = 'WAITING'");
$stmt->execute([$currentUser['id']]);
$waitingLeaves = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM DonNghiPhep WHERE MaNguoiDung = ? AND TrangThai = 'ACCEPT'");
$stmt->execute([$currentUser['id']]);
$acceptedLeaves = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM DonNghiPhep WHERE MaNguoiDung = ? AND TrangThai = 'DENY'");
$stmt->execute([$currentUser['id']]);
$deniedLeaves = $stmt->fetchColumn();

// Lấy danh sách đơn gần đây (5 đơn)
$stmt = $pdo->prepare("
    SELECT * FROM DonNghiPhep 
    WHERE MaNguoiDung = ? 
    ORDER BY NgayTao DESC 
    LIMIT 5
");
$stmt->execute([$currentUser['id']]);
$recentLeaves = $stmt->fetchAll();

$pageTitle = "Dashboard - Nhân viên";
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
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
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
                        <a class="nav-link" href="nghi_bu.php">
                            <i class="fas fa-exchange-alt"></i> Nghỉ bù - Làm bù
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> Quản lý tài khoản
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <?php displayFlashMessage(); ?>
                
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2>
                                <i class="fas fa-hand-sparkles"></i> 
                                Xin chào, <?= htmlspecialchars($userInfo['HoTen']) ?>!
                            </h2>
                            <p class="mb-0">
                                <i class="fas fa-building"></i> <?= htmlspecialchars($userInfo['KhoaPhong']) ?> - 
                                <i class="fas fa-briefcase"></i> <?= htmlspecialchars($userInfo['ViTri']) ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="create_leave.php" class="btn btn-light btn-lg">
                                <i class="fas fa-plus-circle"></i> Tạo đơn nghỉ phép
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Thống kê phép năm -->
                <h4 class="mb-3">
                    <i class="fas fa-chart-pie"></i> Thống kê phép năm
                </h4>
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
                                    <h3 class="mb-0"><?= number_format($userInfo['SoNgayPhepDaDung'], 1) ?></h3>
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
                                    <h3 class="mb-0 text-success"><?= number_format($userInfo['SoNgayPhepConLai'], 1) ?></h3>
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
                <h4 class="mb-3">
                    <i class="fas fa-chart-bar"></i> Trạng thái đơn nghỉ phép
                </h4>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center p-4">
                            <div class="stat-icon bg-warning text-white mx-auto mb-3">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h5>Chờ duyệt</h5>
                            <h2 class="mb-0"><?= $waitingLeaves ?></h2>
                            <small class="text-muted">đơn</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center p-4">
                            <div class="stat-icon bg-success text-white mx-auto mb-3">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5>Đã duyệt</h5>
                            <h2 class="mb-0"><?= $acceptedLeaves ?></h2>
                            <small class="text-muted">đơn</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center p-4">
                            <div class="stat-icon bg-danger text-white mx-auto mb-3">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h5>Từ chối</h5>
                            <h2 class="mb-0"><?= $deniedLeaves ?></h2>
                            <small class="text-muted">đơn</small>
                        </div>
                    </div>
                </div>
                
                <!-- Danh sách đơn gần đây -->
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history"></i> Đơn nghỉ phép gần đây
                        </h5>
                        <a href="my_leaves.php" class="btn btn-sm btn-outline-primary">
                            Xem tất cả <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentLeaves)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <h5>Chưa có đơn nghỉ phép nào</h5>
                                <p>Nhấn nút bên dưới để tạo đơn nghỉ phép mới</p>
                                <a href="create_leave.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tạo đơn mới
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mã đơn</th>
                                            <th>Loại phép</th>
                                            <th>Từ ngày</th>
                                            <th>Đến ngày</th>
                                            <th>Số ngày</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày tạo</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentLeaves as $leave): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($leave['MaDon']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($leave['LoaiPhep']) ?></td>
                                            <td><?= formatDate($leave['NgayBatDauNghi']) ?></td>
                                            <td><?= formatDate($leave['NgayKetThucNghi']) ?></td>
                                            <td><strong><?= number_format($leave['SoNgayNghi'], 1) ?></strong> ngày</td>
                                            <td><?= getStatusBadge($leave['TrangThai']) ?></td>
                                            <td>
                                                <small><?= formatDateTime($leave['NgayTao']) ?></small>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#viewModal<?= $leave['MaDon'] ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal xem chi tiết -->
                                        <div class="modal fade" id="viewModal<?= $leave['MaDon'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-file-alt"></i> Chi tiết đơn nghỉ phép
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <table class="table">
                                                            <tr>
                                                                <th width="35%">Mã đơn:</th>
                                                                <td><?= htmlspecialchars($leave['MaDon']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th>Loại phép:</th>
                                                                <td><?= htmlspecialchars($leave['LoaiPhep']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th>Từ ngày:</th>
                                                                <td><?= formatDate($leave['NgayBatDauNghi']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th>Đến ngày:</th>
                                                                <td><?= formatDate($leave['NgayKetThucNghi']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th>Số ngày nghỉ:</th>
                                                                <td><strong><?= number_format($leave['SoNgayNghi'], 1) ?></strong> ngày</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Lý do:</th>
                                                                <td><?= nl2br(htmlspecialchars($leave['LyDo'])) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th>Trạng thái:</th>
                                                                <td><?= getStatusBadge($leave['TrangThai']) ?></td>
                                                            </tr>
                                                            <?php if (!empty($leave['GhiChuAdmin'])): ?>
                                                            <tr>
                                                                <th>Ghi chú:</th>
                                                                <td class="text-danger">
                                                                    <?= nl2br(htmlspecialchars($leave['GhiChuAdmin'])) ?>
                                                                </td>
                                                            </tr>
                                                            <?php endif; ?>
                                                            <tr>
                                                                <th>Ngày tạo:</th>
                                                                <td><?= formatDateTime($leave['NgayTao']) ?></td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            Đóng
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>