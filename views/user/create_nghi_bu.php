<?php
// views/user/create_nghi_bu.php - Form đăng ký nghỉ bù/làm bù
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('USER');

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Lấy thông tin user
$stmt = $pdo->prepare("SELECT * FROM NguoiDung WHERE MaNguoiDung = ?");
$stmt->execute([$currentUser['id']]);
$userInfo = $stmt->fetch();

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loaiNghiBu = $_POST['loai_nghi_bu'] ?? '';
    $ngayNghiBu = $_POST['ngay_nghi_bu'] ?? '';
    $buoiNghi = $_POST['buoi_nghi'] ?? 'Ca_ngay';
    $ngayLamBu = $_POST['ngay_lam_bu'] ?? null;
    $buoiLam = $_POST['buoi_lam'] ?? 'Ca_ngay';
    $lyDo = sanitizeInput($_POST['ly_do'] ?? '');
    
    $errors = [];
    
    // Validate
    if (empty($loaiNghiBu)) $errors[] = "Vui lòng chọn loại nghỉ bù";
    if (empty($ngayNghiBu)) $errors[] = "Vui lòng chọn ngày nghỉ bù";
    
    if (empty($errors)) {
        // Kiểm tra ngày nghỉ bù phải là T2-T6
        $dayOfWeekNghi = date('N', strtotime($ngayNghiBu)); // 1=Monday, 7=Sunday
        if ($dayOfWeekNghi >= 6) {
            $errors[] = "Ngày nghỉ bù phải là thứ 2 đến thứ 6";
        }
        
        // Kiểm tra ngày làm bù phải là T7/CN
        if (!empty($ngayLamBu)) {
            $dayOfWeekLam = date('N', strtotime($ngayLamBu));
            if ($dayOfWeekLam < 6) {
                $errors[] = "Ngày làm bù phải là thứ 7 hoặc Chủ nhật";
            }
        }
        
        // Tính số ngày nghỉ/làm
        $soNgayNghi = ($buoiNghi == 'Ca_ngay') ? 1 : 0.5;
        $soNgayLam = (!empty($ngayLamBu) && $buoiLam == 'Ca_ngay') ? 1 : 0.5;
    }
    
    if (empty($errors)) {
        try {
            // Insert vào bảng NghiBu
            $stmt = $pdo->prepare("
                INSERT INTO NghiBu 
                (MaNguoiDung, LoaiNghiBu, NgayNghiBu, BuoiNghi, SoNgayNghi, 
                 NgayLamBu, BuoiLam, SoNgayLam, LyDo, TrangThai, TrangThaiDuyet)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Cho_lam_bu', 'WAITING')
            ");
            
            $stmt->execute([
                $currentUser['id'],
                $loaiNghiBu,
                $ngayNghiBu,
                $buoiNghi,
                $soNgayNghi,
                $ngayLamBu ?: null,
                $ngayLamBu ? $buoiLam : null,
                $ngayLamBu ? $soNgayLam : 0,
                $lyDo
            ]);
            
            $maNghiBu = $pdo->lastInsertId();
            
            logActivity($currentUser['id'], 'CREATE_NGHI_BU', "Đăng ký nghỉ bù: $maNghiBu");
            redirectWithMessage('nghi_bu.php', 'success', 'Đăng ký nghỉ bù thành công!');
            
        } catch (PDOException $e) {
            $errors[] = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}

$pageTitle = "Đăng ký nghỉ bù - Làm bù";
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
        .option-card { cursor: pointer; transition: all 0.3s; border: 2px solid #dee2e6; }
        .option-card:hover { border-color: #667eea; transform: translateY(-5px); }
        .option-card.selected { border-color: #667eea; background-color: #f0f4ff; }
        .date-info { background-color: #fff3cd; padding: 10px; border-radius: 8px; margin-top: 10px; }
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
                <h2 class="mb-4">
                    <i class="fas fa-plus-circle"></i> Đăng ký nghỉ bù - Làm bù
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
                
                <!-- Hướng dẫn -->
                <div class="alert alert-info mb-4">
                    <h5><i class="fas fa-info-circle"></i> Hướng dẫn:</h5>
                    <ul class="mb-0">
                        <li><strong>Làm trước - Nghỉ sau:</strong> Làm thêm vào T7/CN → Được nghỉ ngày T2-T6 mà không trừ phép</li>
                        <li><strong>Nghỉ trước - Làm sau:</strong> Nghỉ ngày T2-T6 → Phải làm bù vào T7/CN sau đó</li>
                    </ul>
                </div>
                
                <!-- Form -->
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="" id="nghiBuForm">
                            <!-- BƯỚC 1: Chọn loại nghỉ bù -->
                            <h5 class="mb-3">
                                <i class="fas fa-step-forward"></i> Bước 1: Chọn loại nghỉ bù
                            </h5>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="option-card card p-4 text-center" onclick="selectLoaiNghiBu('Lam_truoc_nghi_sau')">
                                        <input type="radio" name="loai_nghi_bu" value="Lam_truoc_nghi_sau" 
                                               id="loai1" style="display: none;">
                                        <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                                        <h5>Làm trước - Nghỉ sau</h5>
                                        <p class="text-muted mb-0">
                                            Bạn đã/sẽ làm thêm vào T7/CN, và muốn nghỉ vào ngày T2-T6
                                        </p>
                                        <div class="mt-3">
                                            <span class="badge bg-success">Ưu tiên</span>
                                            <span class="badge bg-info">Không trừ phép</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="option-card card p-4 text-center" onclick="selectLoaiNghiBu('Nghi_truoc_lam_sau')">
                                        <input type="radio" name="loai_nghi_bu" value="Nghi_truoc_lam_sau" 
                                               id="loai2" style="display: none;">
                                        <i class="fas fa-calendar-times fa-3x text-warning mb-3"></i>
                                        <h5>Nghỉ trước - Làm sau</h5>
                                        <p class="text-muted mb-0">
                                            Bạn muốn nghỉ ngày T2-T6, và cam kết làm bù vào T7/CN
                                        </p>
                                        <div class="mt-3">
                                            <span class="badge bg-warning">Cần làm bù</span>
                                            <span class="badge bg-danger">Phải đúng hạn</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- BƯỚC 2: Thông tin chi tiết -->
                            <div id="detailsSection" style="display: none;">
                                <hr>
                                <h5 class="mb-3">
                                    <i class="fas fa-step-forward"></i> Bước 2: Điền thông tin chi tiết
                                </h5>
                                
                                <div class="row">
                                    <!-- Ngày nghỉ bù -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-calendar-times text-danger"></i> 
                                            Ngày nghỉ bù (T2 - T6) <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control" name="ngay_nghi_bu" 
                                               id="ngayNghiBu" required min="<?= date('Y-m-d') ?>">
                                        <div class="date-info" id="infoNghiBu" style="display: none;">
                                            <i class="fas fa-info-circle"></i> <span id="dayNameNghiBu"></span>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <label class="form-label fw-bold">Buổi nghỉ:</label><br>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="buoi_nghi" 
                                                       value="Ca_ngay" id="nghiCaNgay" checked>
                                                <label class="form-check-label" for="nghiCaNgay">
                                                    Cả ngày (1.0)
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="buoi_nghi" 
                                                       value="Buoi_sang" id="nghiSang">
                                                <label class="form-check-label" for="nghiSang">
                                                    Buổi sáng (0.5)
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="buoi_nghi" 
                                                       value="Buoi_chieu" id="nghiChieu">
                                                <label class="form-check-label" for="nghiChieu">
                                                    Buổi chiều (0.5)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Ngày làm bù -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-calendar-check text-success"></i> 
                                            Ngày làm bù (T7 / CN)
                                        </label>
                                        <input type="date" class="form-control" name="ngay_lam_bu" 
                                               id="ngayLamBu" min="<?= date('Y-m-d') ?>">
                                        <small class="text-muted">
                                            <span id="lamBuRequired">Tùy chọn</span>
                                        </small>
                                        <div class="date-info" id="infoLamBu" style="display: none;">
                                            <i class="fas fa-info-circle"></i> <span id="dayNameLamBu"></span>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <label class="form-label fw-bold">Buổi làm:</label><br>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="buoi_lam" 
                                                       value="Ca_ngay" id="lamCaNgay" checked>
                                                <label class="form-check-label" for="lamCaNgay">
                                                    Cả ngày (1.0)
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="buoi_lam" 
                                                       value="Buoi_sang" id="lamSang">
                                                <label class="form-check-label" for="lamSang">
                                                    Buổi sáng (0.5)
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="buoi_lam" 
                                                       value="Buoi_chieu" id="lamChieu">
                                                <label class="form-check-label" for="lamChieu">
                                                    Buổi chiều (0.5)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Lý do -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-comment-alt"></i> Lý do
                                    </label>
                                    <textarea class="form-control" name="ly_do" rows="3" 
                                              placeholder="Nhập lý do nghỉ bù/làm bù..."></textarea>
                                </div>
                                
                                <!-- Nút submit -->
                                <div class="text-end">
                                    <a href="nghi_bu.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Hủy
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Gửi đăng ký
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedLoai = null;
        
        // Chọn loại nghỉ bù
        function selectLoaiNghiBu(loai) {
            selectedLoai = loai;
            
            // Remove selected class từ tất cả
            document.querySelectorAll('.option-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class
            event.currentTarget.classList.add('selected');
            
            // Check radio
            if (loai === 'Lam_truoc_nghi_sau') {
                document.getElementById('loai1').checked = true;
            } else {
                document.getElementById('loai2').checked = true;
            }
            
            // Hiển thị form chi tiết
            document.getElementById('detailsSection').style.display = 'block';
            
            // Update required cho ngày làm bù
            const ngayLamBu = document.getElementById('ngayLamBu');
            const lamBuRequired = document.getElementById('lamBuRequired');
            
            if (loai === 'Lam_truoc_nghi_sau') {
                ngayLamBu.required = false;
                lamBuRequired.innerHTML = '<span class="text-success">Tùy chọn (có thể điền sau)</span>';
            } else {
                ngayLamBu.required = true;
                lamBuRequired.innerHTML = '<span class="text-danger">Bắt buộc</span>';
            }
        }
        
        // Validate và hiển thị tên thứ cho ngày nghỉ bù
        document.getElementById('ngayNghiBu').addEventListener('change', function() {
            const date = new Date(this.value);
            const dayOfWeek = date.getDay(); // 0=Sunday, 6=Saturday
            const dayNames = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
            
            const info = document.getElementById('infoNghiBu');
            const dayName = document.getElementById('dayNameNghiBu');
            
            info.style.display = 'block';
            dayName.textContent = dayNames[dayOfWeek];
            
            if (dayOfWeek === 0 || dayOfWeek === 6) {
                info.style.backgroundColor = '#f8d7da';
                dayName.innerHTML = `<strong style="color: red;">${dayNames[dayOfWeek]} - Không hợp lệ! (Phải chọn T2-T6)</strong>`;
                this.setCustomValidity('Ngày nghỉ bù phải là thứ 2 đến thứ 6');
            } else {
                info.style.backgroundColor = '#d1ecf1';
                dayName.innerHTML = `<strong style="color: green;">${dayNames[dayOfWeek]} - Hợp lệ ✓</strong>`;
                this.setCustomValidity('');
            }
        });
        
        // Validate và hiển thị tên thứ cho ngày làm bù
        document.getElementById('ngayLamBu').addEventListener('change', function() {
            if (!this.value) {
                document.getElementById('infoLamBu').style.display = 'none';
                return;
            }
            
            const date = new Date(this.value);
            const dayOfWeek = date.getDay();
            const dayNames = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
            
            const info = document.getElementById('infoLamBu');
            const dayName = document.getElementById('dayNameLamBu');
            
            info.style.display = 'block';
            dayName.textContent = dayNames[dayOfWeek];
            
            if (dayOfWeek === 0 || dayOfWeek === 6) {
                info.style.backgroundColor = '#d1ecf1';
                dayName.innerHTML = `<strong style="color: green;">${dayNames[dayOfWeek]} - Hợp lệ ✓</strong>`;
                this.setCustomValidity('');
            } else {
                info.style.backgroundColor = '#f8d7da';
                dayName.innerHTML = `<strong style="color: red;">${dayNames[dayOfWeek]} - Không hợp lệ! (Phải chọn T7/CN)</strong>`;
                this.setCustomValidity('Ngày làm bù phải là thứ 7 hoặc Chủ nhật');
            }
        });
        
        // Validate form khi submit
        document.getElementById('nghiBuForm').addEventListener('submit', function(e) {
            if (!selectedLoai) {
                e.preventDefault();
                alert('Vui lòng chọn loại nghỉ bù!');
                return false;
            }
        });
    </script>
</body>
</html>