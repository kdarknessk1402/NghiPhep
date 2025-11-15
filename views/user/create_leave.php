<?php
// views/user/create_leave.php - Tạo đơn nghỉ phép (CẢI TIẾN)
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail_config.php';

requireLogin();
requireAnyRole(['USER', 'ADMIN']); // Admin cũng có thể tạo đơn cho user

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Kiểm tra xem là Admin tạo cho user khác hay user tự tạo
$isAdminCreateForOther = false;
$targetUserId = $currentUser['id'];

if (hasRole('ADMIN') && isset($_GET['for_user'])) {
    $isAdminCreateForOther = true;
    $targetUserId = $_GET['for_user'];
}

// Lấy thông tin user đích (người sẽ nghỉ phép)
$stmt = $pdo->prepare("
    SELECT n.*, v.TenVaiTro,
           (n.SoNgayPhepNam - n.SoNgayPhepDaDung) as SoNgayPhepConLai
    FROM NguoiDung n
    JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
    WHERE n.MaNguoiDung = ?
");
$stmt->execute([$targetUserId]);
$userInfo = $stmt->fetch();

if (!$userInfo) {
    die('Không tìm thấy thông tin người dùng');
}

// Xử lý form submit
// THAY THẾ phần xử lý POST trong create_leave.php
// Tìm: if ($_SERVER['REQUEST_METHOD'] === 'POST') {

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loaiPhep = sanitizeInput($_POST['loai_phep'] ?? '');
    $ngayBatDau = $_POST['ngay_bat_dau'] ?? '';
    $ngayKetThuc = $_POST['ngay_ket_thuc'] ?? '';
    $nuaNgayBatDau = $_POST['nua_ngay_bat_dau'] ?? 'Khong';
    $nuaNgayKetThuc = $_POST['nua_ngay_ket_thuc'] ?? 'Khong';
    $lyDo = sanitizeInput($_POST['ly_do'] ?? '');
    
    // XÁC ĐỊNH loại đơn có tính vào phép năm không
    $loaiPhepKhongTinh = ['Phép thai sản', 'Phép hiếu', 'Phép hỷ', 'Phép không lương'];
    $tinhVaoPhepNam = in_array($loaiPhep, $loaiPhepKhongTinh) ? 0 : 1;
    
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
        
        // Tính số ngày nghỉ (CÓ TÍNH NỬA NGÀY)
        $soNgayNghi = calculateDaysWithHalfDay(
            $ngayBatDau, 
            $ngayKetThuc, 
            $nuaNgayBatDau, 
            $nuaNgayKetThuc
        );
        
        // QUAN TRỌNG: Chỉ kiểm tra phép còn lại nếu TinhVaoPhepNam = 1
        if ($tinhVaoPhepNam == 1 && $soNgayNghi > $userInfo['SoNgayPhepConLai']) {
            $errors[] = "Số ngày nghỉ ($soNgayNghi) vượt quá số ngày phép còn lại ({$userInfo['SoNgayPhepConLai']})";
        }
    }
    
    if (empty($errors)) {
        try {
            // Tạo mã đơn
            $maDon = generateLeaveCode('DN');
            
            // Insert đơn nghỉ phép (CÓ THÊM TinhVaoPhepNam)
            $stmt = $pdo->prepare("
                INSERT INTO DonNghiPhep 
                (MaDon, MaNguoiDung, NguoiTao, LoaiPhep, NgayBatDauNghi, NghiNuaNgayBatDau, 
                 NgayKetThucNghi, NghiNuaNgayKetThuc, SoNgayNghi, LyDo, TrangThai, TinhVaoPhepNam)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'WAITING', ?)
            ");
            
            $stmt->execute([
                $maDon,
                $targetUserId,
                $currentUser['id'],
                $loaiPhep,
                $ngayBatDau,
                $nuaNgayBatDau,
                $ngayKetThuc,
                $nuaNgayKetThuc,
                $soNgayNghi,
                $lyDo,
                $tinhVaoPhepNam  // <-- Tham số mới
            ]);
            
            // Gửi email thông báo
            $emailList = [];
            
            $stmt = $pdo->prepare("
                SELECT Email FROM NguoiDung n
                JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
                WHERE v.TenVaiTro = 'MANAGER' 
                AND n.KhoaPhong = ?
            ");
            $stmt->execute([$userInfo['KhoaPhong']]);
            $managerEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $adminEmails = $pdo->query("
                SELECT Email FROM NguoiDung n
                JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
                WHERE v.TenVaiTro = 'ADMIN'
            ")->fetchAll(PDO::FETCH_COLUMN);
            
            $emailList = array_unique(array_merge($managerEmails, $adminEmails));
            
            if (!empty($emailList)) {
                sendLeaveRequestNotification($maDon, $emailList, 'create');
            }
            
            logActivity($currentUser['id'], 'CREATE_LEAVE', "Tạo đơn nghỉ phép: $maDon" . 
                ($isAdminCreateForOther ? " cho user: $targetUserId" : ""));
            
            if ($isAdminCreateForOther) {
                redirectWithMessage('../admin/dashboard.php', 'success', "Tạo đơn nghỉ phép thành công! Mã đơn: $maDon");
            } else {
                redirectWithMessage('my_leaves.php', 'success', "Tạo đơn nghỉ phép thành công! Mã đơn: $maDon");
            }
            
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
        .half-day-option { display: none; }
        .maternity-info { display: none; background-color: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 15px; }
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
                        <a class="nav-link" href="<?= hasRole('ADMIN') ? '../admin/dashboard.php' : 'dashboard.php' ?>">
                            <i class="fas fa-home"></i> Trang chủ
                        </a>
                    </li>
                    <?php if (!hasRole('ADMIN')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="my_leaves.php">
                            <i class="fas fa-list"></i> Đơn của tôi
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="create_leave.php">
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
                <h2 class="mb-4">
                    <i class="fas fa-plus-circle"></i> Tạo đơn nghỉ phép mới
                    <?php if ($isAdminCreateForOther): ?>
                        <span class="badge bg-warning">Admin tạo cho: <?= htmlspecialchars($userInfo['HoTen']) ?></span>
                    <?php endif; ?>
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
                        <div class="col-md-3">
                            <h3><?= $userInfo['SoNgayPhepNam'] ?></h3>
                            <p class="mb-0">Tổng số ngày phép năm</p>
                        </div>
                        <div class="col-md-3">
                            <h3><?= $userInfo['SoNgayPhepDaDung'] ?></h3>
                            <p class="mb-0">Đã sử dụng</p>
                        </div>
                        <div class="col-md-3">
                            <h3><?= $userInfo['SoNgayPhepConLai'] ?></h3>
                            <p class="mb-0">Còn lại</p>
                        </div>
                        <div class="col-md-3">
                            <h3><i class="fas fa-<?= $userInfo['GioiTinh'] == 'Nu' ? 'venus' : 'mars' ?>"></i></h3>
                            <p class="mb-0"><?= $userInfo['GioiTinh'] == 'Nu' ? 'Nữ' : 'Nam' ?></p>
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
                                    <select class="form-select" name="loai_phep" id="loaiPhep" required>
                                        <option value="">-- Chọn loại phép --</option>
                                        
                                        <!-- Các loại phép TÍNH VÀO 12 ngày -->
                                        <optgroup label="Phép thường (Tính vào 12 ngày/năm)">
                                            <option value="Phép năm">📅 Phép năm</option>
                                            <option value="Phép ốm">🤒 Phép ốm</option>
                                            <option value="Phép việc riêng">💼 Phép việc riêng</option>
                                            <option value="Việc cá nhân">👤 Việc cá nhân</option>
                                            <option value="Việc gia đình">👨‍👩‍👧‍👦 Việc gia đình</option>
                                        </optgroup>
                                        
                                        <!-- Các loại phép KHÔNG TÍNH VÀO 12 ngày -->
                                        <optgroup label="Phép đặc biệt (Không tính vào 12 ngày)">
                                            <?php if ($userInfo['GioiTinh'] == 'Nu'): ?>
                                            <option value="Phép thai sản">🤰 Phép thai sản (6 tháng)</option>
                                            <?php endif; ?>
                                            <option value="Phép hiếu">🕊️ Phép hiếu</option>
                                            <option value="Phép hỷ">💒 Phép hỷ</option>
                                            <option value="Phép không lương">💵 Phép không lương</option>
                                        </optgroup>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-info-circle"></i> Số ngày phép còn lại
                                    </label>
                                    <input type="text" class="form-control bg-light" 
                                           value="<?= $userInfo['SoNgayPhepConLai'] ?> ngày" readonly hidden>
                                </div>
                            </div>
                            
                            <!-- Thông báo phép thai sản -->
                            <div class="maternity-info" id="maternityInfo">
                                <i class="fas fa-baby"></i> <strong>Lưu ý:</strong> 
                                Phép thai sản mặc định là 6 tháng (180 ngày). 
                                Ngày kết thúc sẽ tự động được tính từ ngày bắt đầu + 6 tháng.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-calendar-alt"></i> Ngày bắt đầu <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" name="ngay_bat_dau" 
                                           id="ngayBatDau" required min="<?= date('Y-m-d') ?>">
                                    
                                    <!-- Option nửa ngày bắt đầu -->
                                    <div class="half-day-option mt-2" id="halfDayStartOption">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="nua_ngay_bat_dau" 
                                                   id="fullDayStart" value="Khong" checked>
                                            <label class="form-check-label" for="fullDayStart">Cả ngày</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="nua_ngay_bat_dau" 
                                                   id="morningStart" value="Sang">
                                            <label class="form-check-label" for="morningStart">
                                                <i class="fas fa-sun text-warning"></i> Sáng (0.5 ngày)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="nua_ngay_bat_dau" 
                                                   id="afternoonStart" value="Chieu">
                                            <label class="form-check-label" for="afternoonStart">
                                                <i class="fas fa-moon text-primary"></i> Chiều (0.5 ngày)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-calendar-check"></i> Ngày kết thúc <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" name="ngay_ket_thuc" 
                                           id="ngayKetThuc" required min="<?= date('Y-m-d') ?>">
                                    
                                    <!-- Option nửa ngày kết thúc -->
                                    <div class="half-day-option mt-2" id="halfDayEndOption">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="nua_ngay_ket_thuc" 
                                                   id="fullDayEnd" value="Khong" checked>
                                            <label class="form-check-label" for="fullDayEnd">Cả ngày</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="nua_ngay_ket_thuc" 
                                                   id="morningEnd" value="Sang">
                                            <label class="form-check-label" for="morningEnd">
                                                <i class="fas fa-sun text-warning"></i> Sáng (0.5 ngày)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="nua_ngay_ket_thuc" 
                                                   id="afternoonEnd" value="Chieu">
                                            <label class="form-check-label" for="afternoonEnd">
                                                <i class="fas fa-moon text-primary"></i> Chiều (0.5 ngày)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-hourglass-half"></i> Số ngày nghỉ
                                </label>
                                <input type="text" class="form-control bg-light" id="soNgayNghi" 
                                       value="0 ngày" readonly>
                                <small class="text-muted">Tự động tính dựa trên ngày bắt đầu và kết thúc (có tính nửa ngày)</small>
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
                                <a href="<?= $isAdminCreateForOther ? '../admin/dashboard.php' : 'my_leaves.php' ?>" 
                                   class="btn btn-secondary">
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
        const loaiPhepSelect = document.getElementById('loaiPhep');
        const ngayBatDau = document.getElementById('ngayBatDau');
        const ngayKetThuc = document.getElementById('ngayKetThuc');
        const soNgayNghiInput = document.getElementById('soNgayNghi');
        const halfDayStartOption = document.getElementById('halfDayStartOption');
        const halfDayEndOption = document.getElementById('halfDayEndOption');
        const maternityInfo = document.getElementById('maternityInfo');
        
        // Xử lý khi chọn loại phép
        loaiPhepSelect.addEventListener('change', function() {
            const loaiPhep = this.value;
            const noteElement = document.getElementById('loaiPhepNote');
            
            // Danh sách loại phép KHÔNG tính vào 12 ngày
            const khongTinhPhep = ['Phép thai sản', 'Phép hiếu', 'Phép hỷ', 'Phép không lương'];
            
            if (khongTinhPhep.includes(loaiPhep)) {
                noteElement.innerHTML = '<i class="fas fa-info-circle text-success"></i> <strong>Loại phép này KHÔNG tính vào 12 ngày phép năm</strong>';
                noteElement.style.color = '#28a745';
            } else if (loaiPhep) {
                noteElement.innerHTML = '<i class="fas fa-info-circle text-warning"></i> Loại phép này sẽ tính vào 12 ngày phép năm';
                noteElement.style.color = '#856404';
            } else {
                noteElement.innerHTML = '';
            }
            
            // Code xử lý thai sản và nửa ngày (GIỮ NGUYÊN CODE CŨ)
            if (loaiPhep === 'Phép thai sản') {
                maternityInfo.style.display = 'block';
                halfDayStartOption.style.display = 'none';
                halfDayEndOption.style.display = 'none';
                
                if (ngayBatDau.value) {
                    const startDate = new Date(ngayBatDau.value);
                    startDate.setMonth(startDate.getMonth() + 6);
                    ngayKetThuc.value = startDate.toISOString().split('T')[0];
                    ngayKetThuc.readOnly = true;
                    calculateLeaveDays();
                }
            } else if (loaiPhep === 'Phép năm' || loaiPhep === 'Phép việc riêng' || loaiPhep === 'Việc cá nhân' || loaiPhep === 'Việc gia đình') {
                maternityInfo.style.display = 'none';
                halfDayStartOption.style.display = 'block';
                halfDayEndOption.style.display = 'block';
                ngayKetThuc.readOnly = false;
            } else {
                maternityInfo.style.display = 'none';
                halfDayStartOption.style.display = 'none';
                halfDayEndOption.style.display = 'none';
                ngayKetThuc.readOnly = false;
            }
            
            calculateLeaveDays();
        });
        
        // Xử lý khi chọn ngày bắt đầu (cho phép thai sản)
        ngayBatDau.addEventListener('change', function() {
            if (loaiPhepSelect.value === 'Phép thai sản' && this.value) {
                const startDate = new Date(this.value);
                startDate.setMonth(startDate.getMonth() + 6);
                ngayKetThuc.value = startDate.toISOString().split('T')[0];
            }
            calculateLeaveDays();
        });
        
        ngayKetThuc.addEventListener('change', calculateLeaveDays);
        
        // Xử lý khi chọn nửa ngày
        document.querySelectorAll('input[name="nua_ngay_bat_dau"], input[name="nua_ngay_ket_thuc"]').forEach(radio => {
            radio.addEventListener('change', calculateLeaveDays);
        });
        
        // Hàm tính số ngày nghỉ (CÓ TÍNH NỬA NGÀY)
        function calculateLeaveDays() {
            const startDate = ngayBatDau.value;
            const endDate = ngayKetThuc.value;
            
            if (!startDate || !endDate) {
                soNgayNghiInput.value = '0 ngày';
                return;
            }
            
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            if (end < start) {
                soNgayNghiInput.value = '0 ngày';
                alert('Ngày kết thúc phải sau ngày bắt đầu!');
                return;
            }
            
            // Tính số ngày cơ bản
            const diffTime = Math.abs(end - start);
            let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            // Trừ đi nếu có nửa ngày
            const nuaNgayBatDau = document.querySelector('input[name="nua_ngay_bat_dau"]:checked')?.value || 'Khong';
            const nuaNgayKetThuc = document.querySelector('input[name="nua_ngay_ket_thuc"]:checked')?.value || 'Khong';
            
            if (nuaNgayBatDau !== 'Khong') {
                diffDays -= 0.5;
            }
            if (nuaNgayKetThuc !== 'Khong') {
                diffDays -= 0.5;
            }
            
            soNgayNghiInput.value = diffDays + ' ngày';
        }
        
        // Validate form
        document.getElementById('leaveForm').addEventListener('submit', function(e) {
            const startDate = ngayBatDau.value;
            const endDate = ngayKetThuc.value;
            
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