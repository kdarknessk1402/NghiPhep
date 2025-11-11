<?php
// views/admin/manage_users.php - Quản lý người dùng
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('ADMIN');

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Xử lý thêm/sửa/xóa người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $maNguoiDung = sanitizeInput($_POST['ma_nguoi_dung'] ?? '');
        $tenDangNhap = sanitizeInput($_POST['ten_dang_nhap'] ?? '');
        $matKhau = $_POST['mat_khau'] ?? '';
        $hoTen = sanitizeInput($_POST['ho_ten'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $viTri = sanitizeInput($_POST['vi_tri'] ?? '');
        $khoaPhong = sanitizeInput($_POST['khoa_phong'] ?? '');
        $maVaiTro = $_POST['ma_vai_tro'] ?? 2;
        $soNgayPhepNam = $_POST['so_ngay_phep_nam'] ?? 12;
        
        $errors = [];
        
        if (empty($maNguoiDung)) $errors[] = "Mã người dùng không được trống";
        if (empty($tenDangNhap)) $errors[] = "Tên đăng nhập không được trống";
        if (empty($matKhau)) $errors[] = "Mật khẩu không được trống";
        if (empty($hoTen)) $errors[] = "Họ tên không được trống";
        if (empty($email) || !isValidEmail($email)) $errors[] = "Email không hợp lệ";
        
        if (empty($errors)) {
            try {
                $hashedPassword = hashPassword($matKhau);
                
                $stmt = $pdo->prepare("
                    INSERT INTO NguoiDung 
                    (MaNguoiDung, TenDangNhap, MatKhau, HoTen, Email, ViTri, KhoaPhong, MaVaiTro, SoNgayPhepNam, NamBatDauLamViec)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $maNguoiDung, $tenDangNhap, $hashedPassword, $hoTen, 
                    $email, $viTri, $khoaPhong, $maVaiTro, $soNgayPhepNam
                ]);
                
                logActivity($currentUser['id'], 'ADD_USER', "Thêm user: $maNguoiDung");
                redirectWithMessage('manage_users.php', 'success', 'Thêm người dùng thành công');
                
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    setFlashMessage('error', 'Mã người dùng hoặc tên đăng nhập đã tồn tại');
                } else {
                    setFlashMessage('error', 'Lỗi: ' . $e->getMessage());
                }
            }
        } else {
            setFlashMessage('error', implode('<br>', $errors));
        }
    }
    
    if ($action === 'edit') {
        $maNguoiDung = $_POST['ma_nguoi_dung'] ?? '';
        $hoTen = sanitizeInput($_POST['ho_ten'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $viTri = sanitizeInput($_POST['vi_tri'] ?? '');
        $khoaPhong = sanitizeInput($_POST['khoa_phong'] ?? '');
        $maVaiTro = $_POST['ma_vai_tro'] ?? '';
        $soNgayPhepNam = $_POST['so_ngay_phep_nam'] ?? '';
        $matKhauMoi = $_POST['mat_khau_moi'] ?? '';
        
        try {
            if (!empty($matKhauMoi)) {
                $hashedPassword = hashPassword($matKhauMoi);
                $stmt = $pdo->prepare("
                    UPDATE NguoiDung 
                    SET HoTen = ?, Email = ?, ViTri = ?, KhoaPhong = ?, 
                        MaVaiTro = ?, SoNgayPhepNam = ?, MatKhau = ?
                    WHERE MaNguoiDung = ?
                ");
                $stmt->execute([$hoTen, $email, $viTri, $khoaPhong, $maVaiTro, $soNgayPhepNam, $hashedPassword, $maNguoiDung]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE NguoiDung 
                    SET HoTen = ?, Email = ?, ViTri = ?, KhoaPhong = ?, 
                        MaVaiTro = ?, SoNgayPhepNam = ?
                    WHERE MaNguoiDung = ?
                ");
                $stmt->execute([$hoTen, $email, $viTri, $khoaPhong, $maVaiTro, $soNgayPhepNam, $maNguoiDung]);
            }
            
            logActivity($currentUser['id'], 'EDIT_USER', "Sửa user: $maNguoiDung");
            redirectWithMessage('manage_users.php', 'success', 'Cập nhật thông tin thành công');
            
        } catch (PDOException $e) {
            setFlashMessage('error', 'Lỗi: ' . $e->getMessage());
        }
    }
    
    if ($action === 'delete') {
        $maNguoiDung = $_POST['ma_nguoi_dung'] ?? '';
        
        if ($maNguoiDung !== $currentUser['id']) {
            try {
                $stmt = $pdo->prepare("DELETE FROM NguoiDung WHERE MaNguoiDung = ?");
                $stmt->execute([$maNguoiDung]);
                
                logActivity($currentUser['id'], 'DELETE_USER', "Xóa user: $maNguoiDung");
                redirectWithMessage('manage_users.php', 'success', 'Xóa người dùng thành công');
                
            } catch (PDOException $e) {
                setFlashMessage('error', 'Không thể xóa người dùng này');
            }
        } else {
            setFlashMessage('error', 'Không thể xóa chính mình');
        }
    }
}

// Lấy danh sách vai trò
$vaiTroList = $pdo->query("SELECT * FROM VaiTro ORDER BY MaVaiTro")->fetchAll();

// Phân trang và tìm kiếm
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;

$whereClause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (n.MaNguoiDung LIKE ? OR n.TenDangNhap LIKE ? OR n.HoTen LIKE ? OR n.Email LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Đếm tổng
$stmt = $pdo->prepare("SELECT COUNT(*) FROM NguoiDung n $whereClause");
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();

$pagination = paginate($totalItems, $itemsPerPage, $page);

// Lấy danh sách người dùng
$stmt = $pdo->prepare("
    SELECT n.*, v.TenVaiTro,
           (n.SoNgayPhepNam - n.SoNgayPhepDaDung) as SoNgayPhepConLai
    FROM NguoiDung n
    JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
    $whereClause
    ORDER BY n.NgayTao DESC
    LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = "Quản lý người dùng";
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
                        <a class="nav-link active" href="manage_users.php">
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
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users"></i> Quản lý người dùng</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus"></i> Thêm người dùng
                    </button>
                </div>
                
                <!-- Tìm kiếm -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-10">
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Tìm kiếm theo mã, tên đăng nhập, họ tên, email..." 
                                           value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> Tìm kiếm
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Danh sách người dùng -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã NV</th>
                                        <th>Tên đăng nhập</th>
                                        <th>Họ tên</th>
                                        <th>Email</th>
                                        <th>Vai trò</th>
                                        <th>Khoa/Phòng</th>
                                        <th>Phép năm</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($user['MaNguoiDung']) ?></strong></td>
                                        <td><?= htmlspecialchars($user['TenDangNhap']) ?></td>
                                        <td><?= htmlspecialchars($user['HoTen']) ?></td>
                                        <td><?= htmlspecialchars($user['Email']) ?></td>
                                        <td><?= getRoleBadge($user['TenVaiTro']) ?></td>
                                        <td><?= htmlspecialchars($user['KhoaPhong']) ?></td>
                                        <td>
                                            <span class="badge bg-success"><?= $user['SoNgayPhepConLai'] ?></span>
                                            / <?= $user['SoNgayPhepNam'] ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($user['MaNguoiDung'] !== $currentUser['id']): ?>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="deleteUser('<?= $user['MaNguoiDung'] ?>', '<?= htmlspecialchars($user['HoTen']) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Phân trang -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                            <div class="mt-3">
                                <?= renderPagination($pagination, 'manage_users.php' . (!empty($search) ? "?search=$search" : '')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal thêm người dùng -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus"></i> Thêm người dùng mới
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mã người dùng <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ma_nguoi_dung" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ten_dang_nhap" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="mat_khau" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vai trò <span class="text-danger">*</span></label>
                                <select class="form-select" name="ma_vai_tro" required>
                                    <?php foreach ($vaiTroList as $vaiTro): ?>
                                    <option value="<?= $vaiTro['MaVaiTro'] ?>" <?= $vaiTro['MaVaiTro'] == 2 ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($vaiTro['TenVaiTro']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ho_ten" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vị trí</label>
                                <input type="text" class="form-control" name="vi_tri">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Khoa/Phòng</label>
                                <input type="text" class="form-control" name="khoa_phong">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Số ngày phép năm</label>
                            <input type="number" class="form-control" name="so_ngay_phep_nam" value="12" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal sửa người dùng -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-edit"></i> Sửa thông tin người dùng
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="ma_nguoi_dung" id="edit_ma_nguoi_dung">
                        
                        <div class="mb-3">
                            <label class="form-label">Họ tên</label>
                            <input type="text" class="form-control" name="ho_ten" id="edit_ho_ten" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vị trí</label>
                                <input type="text" class="form-control" name="vi_tri" id="edit_vi_tri">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Khoa/Phòng</label>
                                <input type="text" class="form-control" name="khoa_phong" id="edit_khoa_phong">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vai trò</label>
                                <select class="form-select" name="ma_vai_tro" id="edit_ma_vai_tro">
                                    <?php foreach ($vaiTroList as $vaiTro): ?>
                                    <option value="<?= $vaiTro['MaVaiTro'] ?>">
                                        <?= htmlspecialchars($vaiTro['TenVaiTro']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số ngày phép năm</label>
                                <input type="number" class="form-control" name="so_ngay_phep_nam" id="edit_so_ngay_phep_nam" min="0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới (để trống nếu không đổi)</label>
                            <input type="password" class="form-control" name="mat_khau_moi">
                            <small class="text-muted">Chỉ nhập nếu muốn thay đổi mật khẩu</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save"></i> Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Form xóa người dùng (hidden) -->
    <form method="POST" action="" id="deleteUserForm" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="ma_nguoi_dung" id="delete_ma_nguoi_dung">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_ma_nguoi_dung').value = user.MaNguoiDung;
            document.getElementById('edit_ho_ten').value = user.HoTen;
            document.getElementById('edit_email').value = user.Email;
            document.getElementById('edit_vi_tri').value = user.ViTri || '';
            document.getElementById('edit_khoa_phong').value = user.KhoaPhong || '';
            document.getElementById('edit_ma_vai_tro').value = user.MaVaiTro;
            document.getElementById('edit_so_ngay_phep_nam').value = user.SoNgayPhepNam;
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        
        function deleteUser(maNguoiDung, hoTen) {
            if (confirm(`Bạn có chắc muốn xóa người dùng: ${hoTen}?\n\nLưu ý: Tất cả đơn nghỉ phép của người dùng này cũng sẽ bị xóa!`)) {
                document.getElementById('delete_ma_nguoi_dung').value = maNguoiDung;
                document.getElementById('deleteUserForm').submit();
            }
        }
    </script>
</body>
</html>