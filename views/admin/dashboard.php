<?php
// views/admin/dashboard.php - Dashboard Admin - Quản lý đơn nghỉ phép
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail_config.php';

requireLogin();
requireAnyRole(['ADMIN', 'MANAGER']);

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Xử lý duyệt/từ chối đơn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $maDon = $_POST['ma_don'] ?? '';
    $action = $_POST['action'];
    $ghiChu = sanitizeInput($_POST['ghi_chu'] ?? '');
    
    if (!empty($maDon)) {
        try {
            // Lấy thông tin đơn
            $stmt = $pdo->prepare("
                SELECT d.*, n.Email, n.SoNgayPhepDaDung 
                FROM DonNghiPhep d
                JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
                WHERE d.MaDon = ?
            ");
            $stmt->execute([$maDon]);
            $don = $stmt->fetch();
            
            if ($don) {
                if ($action === 'approve') {
                    // Cập nhật trạng thái đơn
                    $stmt = $pdo->prepare("
                        UPDATE DonNghiPhep 
                        SET TrangThai = 'ACCEPT', GhiChuAdmin = ?
                        WHERE MaDon = ?
                    ");
                    $stmt->execute([$ghiChu, $maDon]);
                    
                    // Cập nhật số ngày phép đã dùng
                    $stmt = $pdo->prepare("
                        UPDATE NguoiDung 
                        SET SoNgayPhepDaDung = SoNgayPhepDaDung + ?
                        WHERE MaNguoiDung = ?
                    ");
                    $stmt->execute([$don['SoNgayNghi'], $don['MaNguoiDung']]);
                    
                    // Gửi email thông báo
                    sendLeaveRequestNotification($maDon, $don['Email'], 'approve');
                    
                    logActivity($currentUser['id'], 'APPROVE_LEAVE', "Duyệt đơn: $maDon");
                    setFlashMessage('success', "Đã duyệt đơn $maDon");
                    
                } elseif ($action === 'reject') {
                    // Cập nhật trạng thái đơn
                    $stmt = $pdo->prepare("
                        UPDATE DonNghiPhep 
                        SET TrangThai = 'DENY', GhiChuAdmin = ?
                        WHERE MaDon = ?
                    ");
                    $stmt->execute([$ghiChu, $maDon]);
                    
                    // Gửi email thông báo
                    sendLeaveRequestNotification($maDon, $don['Email'], 'reject');
                    
                    logActivity($currentUser['id'], 'REJECT_LEAVE', "Từ chối đơn: $maDon");
                    setFlashMessage('warning', "Đã từ chối đơn $maDon");
                }
            }
            
            header('Location: dashboard.php');
            exit;
            
        } catch (PDOException $e) {
            setFlashMessage('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'waiting';

// Điều kiện lọc
$whereClause = "WHERE 1=1";
$params = [];

if ($filter !== 'all') {
    $whereClause .= " AND d.TrangThai = ?";
    $params[] = strtoupper($filter);
}

// Đếm tổng
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM DonNghiPhep d
    JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
    $whereClause
");
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();

$pagination = paginate($totalItems, $itemsPerPage, $page);

// Lấy danh sách đơn
$stmt = $pdo->prepare("
    SELECT 
        d.*,
        n.HoTen,
        n.Email,
        n.KhoaPhong,
        n.ViTri,
        v.TenVaiTro
    FROM DonNghiPhep d
    JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
    JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
    $whereClause
    ORDER BY 
        CASE d.TrangThai 
            WHEN 'WAITING' THEN 1 
            WHEN 'ACCEPT' THEN 2 
            WHEN 'DENY' THEN 3 
        END,
        d.NgayTao DESC
    LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$leaves = $stmt->fetchAll();

// Thống kê
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM DonNghiPhep")->fetchColumn(),
    'waiting' => $pdo->query("SELECT COUNT(*) FROM DonNghiPhep WHERE TrangThai = 'WAITING'")->fetchColumn(),
    'accepted' => $pdo->query("SELECT COUNT(*) FROM DonNghiPhep WHERE TrangThai = 'ACCEPT'")->fetchColumn(),
    'denied' => $pdo->query("SELECT COUNT(*) FROM DonNghiPhep WHERE TrangThai = 'DENY'")->fetchColumn(),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM NguoiDung")->fetchColumn()
];

$pageTitle = "Quản lý đơn nghỉ phép";
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
        .stat-card { padding: 15px; cursor: pointer; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-3px); }
        .filter-active { background-color: #667eea !important; color: white !important; }
        .waiting-row { background-color: #fff3cd; }
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
                    <?= getRoleBadge($currentUser['role']) ?>
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
                        <a class="nav-link active" href="dashboard.php">
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
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> Cấu hình hệ thống
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <?php displayFlashMessage(); ?>
                
                <h2 class="mb-4">
                    <i class="fas fa-clipboard-list"></i> Quản lý đơn nghỉ phép
                </h2>
                
                <!-- Thống kê -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <a href="?filter=all" class="text-decoration-none">
                            <div class="card stat-card text-center <?= $filter === 'all' ? 'filter-active' : '' ?>">
                                <i class="fas fa-file-alt fa-2x mb-2 <?= $filter === 'all' ? 'text-white' : 'text-primary' ?>"></i>
                                <h4 class="mb-0"><?= $stats['total'] ?></h4>
                                <small>Tất cả</small>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-2">
                        <a href="?filter=waiting" class="text-decoration-none">
                            <div class="card stat-card text-center <?= $filter === 'waiting' ? 'filter-active' : '' ?>">
                                <i class="fas fa-clock fa-2x mb-2 <?= $filter === 'waiting' ? 'text-white' : 'text-warning' ?>"></i>
                                <h4 class="mb-0"><?= $stats['waiting'] ?></h4>
                                <small>Chờ duyệt</small>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-2">
                        <a href="?filter=accept" class="text-decoration-none">
                            <div class="card stat-card text-center <?= $filter === 'accept' ? 'filter-active' : '' ?>">
                                <i class="fas fa-check-circle fa-2x mb-2 <?= $filter === 'accept' ? 'text-white' : 'text-success' ?>"></i>
                                <h4 class="mb-0"><?= $stats['accepted'] ?></h4>
                                <small>Đã duyệt</small>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-2">
                        <a href="?filter=deny" class="text-decoration-none">
                            <div class="card stat-card text-center <?= $filter === 'deny' ? 'filter-active' : '' ?>">
                                <i class="fas fa-times-circle fa-2x mb-2 <?= $filter === 'deny' ? 'text-white' : 'text-danger' ?>"></i>
                                <h4 class="mb-0"><?= $stats['denied'] ?></h4>
                                <small>Từ chối</small>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="card stat-card text-center">
                            <i class="fas fa-users fa-2x mb-2 text-info"></i>
                            <h4 class="mb-0"><?= $stats['total_users'] ?></h4>
                            <small>Người dùng</small>
                        </div>
                    </div>
                </div>
                
                <!-- Danh sách đơn -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> 
                            Danh sách đơn nghỉ phép
                            <?php if ($filter !== 'all'): ?>
                                - <?= ucfirst($filter) ?>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($leaves)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-4x mb-3"></i>
                                <h5>Không có đơn nào</h5>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mã đơn</th>
                                            <th>Nhân viên</th>
                                            <th>Khoa/Phòng</th>
                                            <th>Loại phép</th>
                                            <th>Từ - Đến</th>
                                            <th>Số ngày</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leaves as $leave): ?>
                                        <tr class="<?= $leave['TrangThai'] === 'WAITING' ? 'waiting-row' : '' ?>">
                                            <td>
                                                <strong><?= htmlspecialchars($leave['MaDon']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?= formatDateTime($leave['NgayTao']) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($leave['HoTen']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($leave['Email']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($leave['KhoaPhong']) ?></td>
                                            <td><?= htmlspecialchars($leave['LoaiPhep']) ?></td>
                                            <td>
                                                <?= formatDate($leave['NgayBatDauNghi']) ?>
                                                <br>→ <?= formatDate($leave['NgayKetThucNghi']) ?>
                                            </td>
                                            <td><strong><?= $leave['SoNgayNghi'] ?></strong> ngày</td>
                                            <td><?= getStatusBadge($leave['TrangThai']) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#viewModal<?= $leave['MaDon'] ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($leave['TrangThai'] === 'WAITING'): ?>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#approveModal<?= $leave['MaDon'] ?>">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#rejectModal<?= $leave['MaDon'] ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal xem chi tiết -->
                                        <div class="modal fade" id="viewModal<?= $leave['MaDon'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-file-alt"></i> Chi tiết đơn nghỉ phép
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6 class="text-primary">Thông tin nhân viên</h6>
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <th width="40%">Họ tên:</th>
                                                                        <td><?= htmlspecialchars($leave['HoTen']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Email:</th>
                                                                        <td><?= htmlspecialchars($leave['Email']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Vị trí:</th>
                                                                        <td><?= htmlspecialchars($leave['ViTri']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Khoa/Phòng:</th>
                                                                        <td><?= htmlspecialchars($leave['KhoaPhong']) ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            
                                                            <div class="col-md-6">
                                                                <h6 class="text-primary">Thông tin đơn nghỉ</h6>
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <th width="40%">Mã đơn:</th>
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
                                                                        <th>Số ngày:</th>
                                                                        <td><strong><?= $leave['SoNgayNghi'] ?></strong> ngày</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Trạng thái:</th>
                                                                        <td><?= getStatusBadge($leave['TrangThai']) ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mt-3">
                                                            <h6 class="text-primary">Lý do nghỉ:</h6>
                                                            <div class="alert alert-info">
                                                                <?= nl2br(htmlspecialchars($leave['LyDo'])) ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if (!empty($leave['GhiChuAdmin'])): ?>
                                                        <div class="mt-3">
                                                            <h6 class="text-danger">Ghi chú của quản lý:</h6>
                                                            <div class="alert alert-warning">
                                                                <?= nl2br(htmlspecialchars($leave['GhiChuAdmin'])) ?>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            Đóng
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Modal duyệt đơn -->
                                        <div class="modal fade" id="approveModal<?= $leave['MaDon'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <div class="modal-header bg-success text-white">
                                                            <h5 class="modal-title">
                                                                <i class="fas fa-check-circle"></i> Duyệt đơn nghỉ phép
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="ma_don" value="<?= $leave['MaDon'] ?>">
                                                            
                                                            <p>Bạn có chắc muốn <strong class="text-success">DUYỆT</strong> đơn này?</p>
                                                            
                                                            <div class="alert alert-info">
                                                                <strong>Nhân viên:</strong> <?= htmlspecialchars($leave['HoTen']) ?><br>
                                                                <strong>Loại phép:</strong> <?= htmlspecialchars($leave['LoaiPhep']) ?><br>
                                                                <strong>Số ngày nghỉ:</strong> <?= $leave['SoNgayNghi'] ?> ngày
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Ghi chú (không bắt buộc):</label>
                                                                <textarea class="form-control" name="ghi_chu" rows="3" 
                                                                          placeholder="Nhập ghi chú nếu có..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                Hủy
                                                            </button>
                                                            <button type="submit" class="btn btn-success">
                                                                <i class="fas fa-check"></i> Xác nhận duyệt
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Modal từ chối đơn -->
                                        <div class="modal fade" id="rejectModal<?= $leave['MaDon'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title">
                                                                <i class="fas fa-times-circle"></i> Từ chối đơn nghỉ phép
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="ma_don" value="<?= $leave['MaDon'] ?>">
                                                            
                                                            <p>Bạn có chắc muốn <strong class="text-danger">TỪ CHỐI</strong> đơn này?</p>
                                                            
                                                            <div class="alert alert-warning">
                                                                <strong>Nhân viên:</strong> <?= htmlspecialchars($leave['HoTen']) ?><br>
                                                                <strong>Loại phép:</strong> <?= htmlspecialchars($leave['LoaiPhep']) ?><br>
                                                                <strong>Số ngày nghỉ:</strong> <?= $leave['SoNgayNghi'] ?> ngày
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label text-danger">
                                                                    Lý do từ chối <span class="text-danger">*</span>:
                                                                </label>
                                                                <textarea class="form-control" name="ghi_chu" rows="3" 
                                                                          placeholder="Nhập lý do từ chối..." required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                Hủy
                                                            </button>
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-times"></i> Xác nhận từ chối
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Phân trang -->
                            <?php if ($pagination['total_pages'] > 1): ?>
                                <div class="mt-3">
                                    <?= renderPagination($pagination, 'dashboard.php' . ($filter !== 'all' ? "?filter=$filter" : '')) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>