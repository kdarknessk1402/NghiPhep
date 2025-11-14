<?php
// views/user/nghi_bu.php - Quản lý nghỉ bù/làm bù
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('USER');

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Lấy danh sách nghỉ bù
$stmt = $pdo->prepare("
    SELECT nb.*,
           nd.HoTen as TenNguoiDuyet
    FROM NghiBu nb
    LEFT JOIN NguoiDung nd ON nb.NguoiDuyet = nd.MaNguoiDung
    WHERE nb.MaNguoiDung = ?
    ORDER BY nb.NgayTao DESC
");
$stmt->execute([$currentUser['id']]);
$nghiBuList = $stmt->fetchAll();

// Thống kê
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as Total,
        SUM(CASE WHEN TrangThai = 'Cho_lam_bu' THEN 1 ELSE 0 END) as ChoLamBu,
        SUM(CASE WHEN TrangThai = 'Da_lam_bu' THEN 1 ELSE 0 END) as DaLamBu,
        SUM(CASE WHEN TrangThai = 'Qua_han' THEN 1 ELSE 0 END) as QuaHan,
        SUM(CASE WHEN TrangThai = 'Cho_lam_bu' THEN SoNgayNghi ELSE 0 END) as TongNgayChoLamBu
    FROM NghiBu
    WHERE MaNguoiDung = ?
");
$stmt->execute([$currentUser['id']]);
$stats = $stmt->fetch();

$pageTitle = "Nghỉ bù - Làm bù";
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
        .timeline-item { position: relative; padding-left: 40px; margin-bottom: 20px; }
        .timeline-item::before { content: ''; position: absolute; left: 10px; top: 0; width: 3px; height: 100%; background: #dee2e6; }
        .timeline-icon { position: absolute; left: 0; top: 0; width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .type-lam-truoc { background-color: #28a745; color: white; }
        .type-nghi-truoc { background-color: #ffc107; color: white; }
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
                        <a class="nav-link active" href="nghi_bu.php">
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
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-exchange-alt"></i> Nghỉ bù - Làm bù
                    </h2>
                    <a href="create_nghi_bu.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Đăng ký mới
                    </a>
                </div>
                
                <!-- Hướng dẫn -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Hướng dẫn nghỉ bù - làm bù:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>✅ Làm trước - Nghỉ sau:</strong>
                            <p class="mb-0">Làm thêm T7/CN → Được nghỉ ngày T2-T6 mà không trừ phép</p>
                        </div>
                        <div class="col-md-6">
                            <strong>⚠️ Nghỉ trước - Làm sau:</strong>
                            <p class="mb-0">Nghỉ ngày T2-T6 → Phải làm bù T7/CN sau đó</p>
                        </div>
                    </div>
                </div>
                
                <!-- Thống kê -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center p-3">
                            <h4 class="mb-1"><?= $stats['Total'] ?></h4>
                            <p class="text-muted mb-0"><i class="fas fa-list"></i> Tổng số lần</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center p-3 bg-warning bg-opacity-10">
                            <h4 class="mb-1 text-warning">
                                <?= $stats['ChoLamBu'] ?>
                                <?php if ($stats['TongNgayChoLamBu'] > 0): ?>
                                    <small>(<?= number_format($stats['TongNgayChoLamBu'], 1) ?>)</small>
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted mb-0"><i class="fas fa-clock"></i> Chờ làm bù</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center p-3 bg-success bg-opacity-10">
                            <h4 class="mb-1 text-success"><?= $stats['DaLamBu'] ?></h4>
                            <p class="text-muted mb-0"><i class="fas fa-check-circle"></i> Đã làm bù</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center p-3 bg-danger bg-opacity-10">
                            <h4 class="mb-1 text-danger"><?= $stats['QuaHan'] ?></h4>
                            <p class="text-muted mb-0"><i class="fas fa-times-circle"></i> Quá hạn</p>
                        </div>
                    </div>
                </div>
                
                <!-- Danh sách nghỉ bù -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($nghiBuList)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-4x mb-3"></i>
                                <h5>Chưa có đăng ký nghỉ bù nào</h5>
                                <p>Click nút "Đăng ký mới" để tạo đăng ký nghỉ bù/làm bù</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($nghiBuList as $item): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon <?= $item['LoaiNghiBu'] == 'Lam_truoc_nghi_sau' ? 'type-lam-truoc' : 'type-nghi-truoc' ?>">
                                    <i class="fas fa-<?= $item['LoaiNghiBu'] == 'Lam_truoc_nghi_sau' ? 'check' : 'clock' ?>"></i>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-9">
                                                <h5 class="mb-2">
                                                    <?php if ($item['LoaiNghiBu'] == 'Lam_truoc_nghi_sau'): ?>
                                                        <span class="badge bg-success">Làm trước - Nghỉ sau</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Nghỉ trước - Làm sau</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php
                                                    $statusBadge = [
                                                        'Cho_lam_bu' => '<span class="badge bg-warning">Chờ làm bù</span>',
                                                        'Da_lam_bu' => '<span class="badge bg-success">Đã làm bù</span>',
                                                        'Qua_han' => '<span class="badge bg-danger">Quá hạn</span>'
                                                    ];
                                                    echo $statusBadge[$item['TrangThai']];
                                                    ?>
                                                </h5>
                                                
                                                <div class="row mb-2">
                                                    <div class="col-md-6">
                                                        <strong><i class="fas fa-calendar-times text-danger"></i> Ngày nghỉ bù:</strong><br>
                                                        <?= formatDate($item['NgayNghiBu']) ?> 
                                                        (<?= date('l', strtotime($item['NgayNghiBu'])) ?>)
                                                        - <strong><?= $item['SoNgayNghi'] ?> ngày</strong>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong><i class="fas fa-calendar-check text-success"></i> Ngày làm bù:</strong><br>
                                                        <?php if ($item['NgayLamBu']): ?>
                                                            <?= formatDate($item['NgayLamBu']) ?>
                                                            (<?= date('l', strtotime($item['NgayLamBu'])) ?>)
                                                            - <strong><?= $item['SoNgayLam'] ?> ngày</strong>
                                                        <?php else: ?>
                                                            <span class="text-muted">Chưa xác định</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($item['LyDo']): ?>
                                                <p class="mb-2">
                                                    <strong><i class="fas fa-comment"></i> Lý do:</strong><br>
                                                    <?= nl2br(htmlspecialchars($item['LyDo'])) ?>
                                                </p>
                                                <?php endif; ?>
                                                
                                                <?php if ($item['GhiChu']): ?>
                                                <p class="mb-0 text-danger">
                                                    <strong><i class="fas fa-sticky-note"></i> Ghi chú:</strong>
                                                    <?= htmlspecialchars($item['GhiChu']) ?>
                                                </p>
                                                <?php endif; ?>
                                                
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> Đăng ký lúc: <?= formatDateTime($item['NgayTao']) ?>
                                                    <?php if ($item['TenNguoiDuyet']): ?>
                                                        | <i class="fas fa-user-check"></i> Người duyệt: <?= htmlspecialchars($item['TenNguoiDuyet']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            
                                            <div class="col-md-3 text-end">
                                                <?php if ($item['TrangThaiDuyet'] == 'WAITING'): ?>
                                                    <span class="badge bg-warning mb-2">Chờ duyệt</span>
                                                <?php elseif ($item['TrangThaiDuyet'] == 'ACCEPT'): ?>
                                                    <span class="badge bg-success mb-2">Đã duyệt</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger mb-2">Từ chối</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($item['LoaiNghiBu'] == 'Lam_truoc_nghi_sau' && $item['TrangThai'] == 'Cho_lam_bu'): ?>
                                                    <div class="alert alert-info p-2 mt-2">
                                                        <small>
                                                            <i class="fas fa-gift"></i>
                                                            Bạn có quyền nghỉ <?= $item['SoNgayNghi'] ?> ngày
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($item['LoaiNghiBu'] == 'Nghi_truoc_lam_sau' && $item['TrangThai'] == 'Cho_lam_bu'): ?>
                                                    <div class="alert alert-warning p-2 mt-2">
                                                        <small>
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                            Cần làm bù <?= $item['SoNgayNghi'] ?> ngày
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>