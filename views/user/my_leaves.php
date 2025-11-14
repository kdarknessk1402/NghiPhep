<?php
// views/user/my_leaves.php - Danh sách đơn nghỉ phép của tôi
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('USER');

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Xử lý xóa đơn
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $maDon = $_GET['id'];
    
    // Kiểm tra quyền xóa (chỉ xóa đơn đang chờ duyệt)
    $stmt = $pdo->prepare("SELECT * FROM DonNghiPhep WHERE MaDon = ? AND MaNguoiDung = ?");
    $stmt->execute([$maDon, $currentUser['id']]);
    $don = $stmt->fetch();
    
    if ($don && $don['TrangThai'] === 'WAITING') {
        $stmt = $pdo->prepare("DELETE FROM DonNghiPhep WHERE MaDon = ?");
        $stmt->execute([$maDon]);
        
        logActivity($currentUser['id'], 'DELETE_LEAVE', "Xóa đơn: $maDon");
        redirectWithMessage('my_leaves.php', 'success', 'Đã xóa đơn nghỉ phép');
    } else {
        redirectWithMessage('my_leaves.php', 'error', 'Không thể xóa đơn này');
    }
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;

// Lọc theo trạng thái
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$whereClause = "WHERE MaNguoiDung = ?";
$params = [$currentUser['id']];

if ($filter !== 'all') {
    $whereClause .= " AND TrangThai = ?";
    $params[] = strtoupper($filter);
}

// Đếm tổng số đơn
$stmt = $pdo->prepare("SELECT COUNT(*) FROM DonNghiPhep $whereClause");
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();

// Tính phân trang
$pagination = paginate($totalItems, $itemsPerPage, $page);

// Lấy danh sách đơn
$stmt = $pdo->prepare("
    SELECT * FROM DonNghiPhep 
    $whereClause
    ORDER BY NgayTao DESC
    LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$leaves = $stmt->fetchAll();

// Thống kê
$stmt = $pdo->prepare("SELECT COUNT(*) FROM DonNghiPhep WHERE MaNguoiDung = ?");
$stmt->execute([$currentUser['id']]);
$totalStat = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM DonNghiPhep WHERE MaNguoiDung = ? AND TrangThai = 'WAITING'");
$stmt->execute([$currentUser['id']]);
$waitingStat = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM DonNghiPhep WHERE MaNguoiDung = ? AND TrangThai = 'ACCEPT'");
$stmt->execute([$currentUser['id']]);
$acceptedStat = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM DonNghiPhep WHERE MaNguoiDung = ? AND TrangThai = 'DENY'");
$stmt->execute([$currentUser['id']]);
$deniedStat = $stmt->fetchColumn();

$stats = [
    'total' => $totalStat,
    'waiting' => $waitingStat,
    'accepted' => $acceptedStat,
    'denied' => $deniedStat
];

$pageTitle = "Đơn nghỉ phép của tôi";
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
                        <a class="nav-link active" href="my_leaves.php">
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
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-list"></i> Đơn nghỉ phép của tôi</h2>
                    <a href="create_leave.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tạo đơn mới
                    </a>
                </div>
                
                <!-- Thống kê -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <a href="?filter=all" class="text-decoration-none">
                            <div class="card stat-card <?= $filter === 'all' ? 'filter-active' : '' ?>">
                                <div class="text-center">
                                    <i class="fas fa-file-alt fa-2x mb-2 <?= $filter === 'all' ? 'text-white' : 'text-primary' ?>"></i>
                                    <h3 class="mb-0"><?= $stats['total'] ?></h3>
                                    <p class="mb-0">Tất cả đơn</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="?filter=waiting" class="text-decoration-none">
                            <div class="card stat-card <?= $filter === 'waiting' ? 'filter-active' : '' ?>">
                                <div class="text-center">
                                    <i class="fas fa-clock fa-2x mb-2 <?= $filter === 'waiting' ? 'text-white' : 'text-warning' ?>"></i>
                                    <h3 class="mb-0"><?= $stats['waiting'] ?></h3>
                                    <p class="mb-0">Chờ duyệt</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="?filter=accept" class="text-decoration-none">
                            <div class="card stat-card <?= $filter === 'accept' ? 'filter-active' : '' ?>">
                                <div class="text-center">
                                    <i class="fas fa-check-circle fa-2x mb-2 <?= $filter === 'accept' ? 'text-white' : 'text-success' ?>"></i>
                                    <h3 class="mb-0"><?= $stats['accepted'] ?></h3>
                                    <p class="mb-0">Đã duyệt</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="?filter=deny" class="text-decoration-none">
                            <div class="card stat-card <?= $filter === 'deny' ? 'filter-active' : '' ?>">
                                <div class="text-center">
                                    <i class="fas fa-times-circle fa-2x mb-2 <?= $filter === 'deny' ? 'text-white' : 'text-danger' ?>"></i>
                                    <h3 class="mb-0"><?= $stats['denied'] ?></h3>
                                    <p class="mb-0">Từ chối</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Danh sách đơn -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($leaves)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-4x mb-3"></i>
                                <h5>Chưa có đơn nghỉ phép nào</h5>
                                <p>Nhấn nút "Tạo đơn mới" để tạo đơn nghỉ phép</p>
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
                                            <th>Lý do</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày tạo</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leaves as $leave): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($leave['MaDon']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($leave['LoaiPhep']) ?></td>
                                            <td><?= formatDate($leave['NgayBatDauNghi']) ?></td>
                                            <td><?= formatDate($leave['NgayKetThucNghi']) ?></td>
                                            <td><strong><?= $leave['SoNgayNghi'] ?></strong> ngày</td>
                                            <td>
                                                <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                                      title="<?= htmlspecialchars($leave['LyDo']) ?>">
                                                    <?= htmlspecialchars($leave['LyDo']) ?>
                                                </span>
                                            </td>
                                            <td><?= getStatusBadge($leave['TrangThai']) ?></td>
                                            <td><?= formatDateTime($leave['NgayTao']) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#viewModal<?= $leave['MaDon'] ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($leave['TrangThai'] === 'WAITING'): ?>
                                                    <a href="?action=delete&id=<?= $leave['MaDon'] ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Bạn có chắc muốn xóa đơn này?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
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
                                                                <td><strong><?= $leave['SoNgayNghi'] ?></strong> ngày</td>
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
                                                            <tr>
                                                                <th>Cập nhật lần cuối:</th>
                                                                <td><?= formatDateTime($leave['NgayCapNhat']) ?></td>
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
                            
                            <!-- Phân trang -->
                            <?php if ($pagination['total_pages'] > 1): ?>
                                <div class="mt-3">
                                    <?= renderPagination($pagination, 'my_leaves.php' . ($filter !== 'all' ? "?filter=$filter" : '')) ?>
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