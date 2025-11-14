<?php
// views/manager/dashboard.php - Dashboard Manager (Xem đơn của khoa/phòng mình)
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail_config.php';

requireLogin();
requireRole('MANAGER');

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Lấy khoa/phòng của manager
$stmt = $pdo->prepare("SELECT KhoaPhong FROM NguoiDung WHERE MaNguoiDung = ?");
$stmt->execute([$currentUser['id']]);
$managerDept = $stmt->fetchColumn();

// Xử lý duyệt/từ chối đơn (giống admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $maDon = $_POST['ma_don'] ?? '';
    $action = $_POST['action'];
    $ghiChu = sanitizeInput($_POST['ghi_chu'] ?? '');
    
    if (!empty($maDon)) {
        try {
            $stmt = $pdo->prepare("
                SELECT d.*, n.Email, n.SoNgayPhepDaDung, n.KhoaPhong
                FROM DonNghiPhep d
                JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
                WHERE d.MaDon = ?
            ");
            $stmt->execute([$maDon]);
            $don = $stmt->fetch();
            
            // Kiểm tra xem đơn có thuộc khoa/phòng của manager không
            if ($don && $don['KhoaPhong'] === $managerDept) {
                if ($action === 'approve') {
                    $stmt = $pdo->prepare("
                        UPDATE DonNghiPhep 
                        SET TrangThai = 'ACCEPT', GhiChuAdmin = ?
                        WHERE MaDon = ?
                    ");
                    $stmt->execute([$ghiChu, $maDon]);
                    
                    $stmt = $pdo->prepare("
                        UPDATE NguoiDung 
                        SET SoNgayPhepDaDung = SoNgayPhepDaDung + ?
                        WHERE MaNguoiDung = ?
                    ");
                    $stmt->execute([$don['SoNgayNghi'], $don['MaNguoiDung']]);
                    
                    sendLeaveRequestNotification($maDon, $don['Email'], 'approve');
                    
                    logActivity($currentUser['id'], 'APPROVE_LEAVE', "Duyệt đơn: $maDon");
                    setFlashMessage('success', "Đã duyệt đơn $maDon");
                    
                } elseif ($action === 'reject') {
                    $stmt = $pdo->prepare("
                        UPDATE DonNghiPhep 
                        SET TrangThai = 'DENY', GhiChuAdmin = ?
                        WHERE MaDon = ?
                    ");
                    $stmt->execute([$ghiChu, $maDon]);
                    
                    sendLeaveRequestNotification($maDon, $don['Email'], 'reject');
                    
                    logActivity($currentUser['id'], 'REJECT_LEAVE', "Từ chối đơn: $maDon");
                    setFlashMessage('warning', "Đã từ chối đơn $maDon");
                }
            } else {
                setFlashMessage('error', 'Bạn không có quyền xử lý đơn này');
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

// Điều kiện lọc - CHỈ XEM ĐƠN CỦA KHOA/PHÒNG MÌNH
$whereClause = "WHERE n.KhoaPhong = ?";
$params = [$managerDept];

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

// Thống kê của khoa/phòng
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM DonNghiPhep d
    JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
    WHERE n.KhoaPhong = ?
");
$stmt->execute([$managerDept]);
$totalDept = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM DonNghiPhep d
    JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
    WHERE n.KhoaPhong = ? AND d.TrangThai = 'WAITING'
");
$stmt->execute([$managerDept]);
$waitingDept = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM DonNghiPhep d
    JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
    WHERE n.KhoaPhong = ? AND d.TrangThai = 'ACCEPT'
");
$stmt->execute([$managerDept]);
$acceptedDept = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM DonNghiPhep d
    JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
    WHERE n.KhoaPhong = ? AND d.TrangThai = 'DENY'
");
$stmt->execute([$managerDept]);
$deniedDept = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM NguoiDung WHERE KhoaPhong = ?");
$stmt->execute([$managerDept]);
$totalUsersDept = $stmt->fetchColumn();

$stats = [
    'total' => $totalDept,
    'waiting' => $waitingDept,
    'accepted' => $acceptedDept,
    'denied' => $deniedDept,
    'total_users' => $totalUsersDept
];

$pageTitle = "Quản lý đơn nghỉ phép - " . $managerDept;
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
        .dept-badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 5px 15px; border-radius: 20px; display: inline-block; }
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
                    <i class="fas fa-user-tie"></i> <?= htmlspecialchars($currentUser['fullname']) ?>
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
                        <a class="nav-link" href="../user/my_leaves.php">
                            <i class="fas fa-list"></i> Đơn của tôi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../user/create_leave.php">
                            <i class="fas fa-plus-circle"></i> Tạo đơn mới
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../user/profile.php">
                            <i class="fas fa-user"></i> Thông tin cá nhân
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <?php displayFlashMessage(); ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">
                            <i class="fas fa-clipboard-list"></i> Quản lý đơn nghỉ phép
                        </h2>
                        <span class="dept-badge">
                            <i class="fas fa-building"></i> <?= htmlspecialchars($managerDept) ?>
                        </span>
                    </div>
                </div>
                
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
                            <small>Nhân viên</small>
                        </div>
                    </div>
                </div>
                
                <!-- Danh sách đơn - GIỐNG ADMIN -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> 
                            Danh sách đơn nghỉ phép - <?= htmlspecialchars($managerDept) ?>
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
                                            <th>Vị trí</th>
                                            <th>Loại phép</th>
                                            <th>Từ - Đến</th>
                                            <th>Số ngày</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Tái sử dụng code HTML từ admin dashboard
                                        // Copy phần tbody từ admin_dashboard.php
                                        foreach ($leaves as $leave): 
                                        ?>
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
                                            <td><?= htmlspecialchars($leave['ViTri']) ?></td>
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
                                        
                                        <!-- Modals tương tự admin - Copy từ admin_dashboard.php -->
                                        <?php 
                                        // Copy 3 modals: viewModal, approveModal, rejectModal từ admin_dashboard.php
                                        include __DIR__ . '/../admin/_leave_modals.php';
                                        ?>
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