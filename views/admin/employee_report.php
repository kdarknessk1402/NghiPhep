<?php
// views/admin/employee_report.php - Báo cáo chấm công theo nhân viên
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireAnyRole(['ADMIN', 'MANAGER']);

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Lấy tháng/năm từ query string
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$selectedYear = date('Y', strtotime($selectedMonth . '-01'));
$selectedMonthNum = date('m', strtotime($selectedMonth . '-01'));
$khoaPhong = isset($_GET['khoa_phong']) ? $_GET['khoa_phong'] : 'all';

// Tính số ngày trong tháng
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonthNum, $selectedYear);
$firstDay = "$selectedYear-$selectedMonthNum-01";
$lastDay = "$selectedYear-$selectedMonthNum-$daysInMonth";

// Điều kiện lọc theo khoa/phòng cho Manager
$whereKhoaPhong = "";
$khoaPhongParam = null;

if (hasRole('MANAGER')) {
    $stmt = $pdo->prepare("SELECT KhoaPhong FROM NguoiDung WHERE MaNguoiDung = ?");
    $stmt->execute([$currentUser['id']]);
    $managerDept = $stmt->fetchColumn();
    $whereKhoaPhong = " AND n.KhoaPhong = ?";
    $khoaPhongParam = $managerDept;
} elseif ($khoaPhong !== 'all') {
    $whereKhoaPhong = " AND n.KhoaPhong = ?";
    $khoaPhongParam = $khoaPhong;
}

// Lấy danh sách nhân viên
$sql = "
    SELECT 
        n.MaNguoiDung,
        n.HoTen,
        n.Email,
        n.KhoaPhong,
        n.ViTri,
        n.GioiTinh,
        n.SoNgayPhepNam,
        n.SoNgayPhepDaDung,
        (n.SoNgayPhepNam - n.SoNgayPhepDaDung) as SoNgayPhepConLai,
        v.TenVaiTro
    FROM NguoiDung n
    JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
    WHERE v.TenVaiTro = 'USER'
    $whereKhoaPhong
    ORDER BY n.KhoaPhong, n.HoTen
";

$stmt = $pdo->prepare($sql);
$params = [];
if ($khoaPhongParam) {
    $params[] = $khoaPhongParam;
}
$stmt->execute($params);
$employees = $stmt->fetchAll();

// Lấy tất cả đơn nghỉ phép trong tháng
$stmt = $pdo->prepare("
    SELECT 
        d.*,
        n.MaNguoiDung
    FROM DonNghiPhep d
    JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
    WHERE d.TrangThai = 'ACCEPT'
    AND (
        (d.NgayBatDauNghi <= ? AND d.NgayKetThucNghi >= ?)
        OR (d.NgayBatDauNghi BETWEEN ? AND ?)
        OR (d.NgayKetThucNghi BETWEEN ? AND ?)
    )
    $whereKhoaPhong
");

$leaveParams = [$lastDay, $firstDay, $firstDay, $lastDay, $firstDay, $lastDay];
if ($khoaPhongParam) {
    $leaveParams[] = $khoaPhongParam;
}
$stmt->execute($leaveParams);
$leaves = $stmt->fetchAll();

// Tạo mảng đơn nghỉ theo user
$leavesByUser = [];
foreach ($leaves as $leave) {
    $userId = $leave['MaNguoiDung'];
    if (!isset($leavesByUser[$userId])) {
        $leavesByUser[$userId] = [];
    }
    $leavesByUser[$userId][] = $leave;
}

// Hàm kiểm tra ngày có nghỉ không
function isDateInLeave($date, $leaves) {
    foreach ($leaves as $leave) {
        $startDate = $leave['NgayBatDauNghi'];
        $endDate = $leave['NgayKetThucNghi'];
        
        if ($date >= $startDate && $date <= $endDate) {
            // Kiểm tra nửa ngày
            $info = [
                'isLeave' => true,
                'loaiPhep' => $leave['LoaiPhep'],
                'maDon' => $leave['MaDon']
            ];
            
            // Ngày đầu - check nửa ngày bắt đầu
            if ($date === $startDate && $leave['NghiNuaNgayBatDau'] !== 'Khong') {
                $info['halfDay'] = $leave['NghiNuaNgayBatDau']; // Sang hoặc Chieu
            }
            // Ngày cuối - check nửa ngày kết thúc
            elseif ($date === $endDate && $leave['NghiNuaNgayKetThuc'] !== 'Khong') {
                $info['halfDay'] = $leave['NghiNuaNgayKetThuc'];
            }
            
            return $info;
        }
    }
    return ['isLeave' => false];
}

// Danh sách khoa/phòng để lọc
$danhSachKhoaPhong = $pdo->query("
    SELECT DISTINCT KhoaPhong 
    FROM NguoiDung 
    WHERE KhoaPhong IS NOT NULL 
    ORDER BY KhoaPhong
")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = "Báo cáo chấm công theo nhân viên";
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
        body { background-color: #f8f9fa; font-size: 14px; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar { min-height: calc(100vh - 56px); background-color: #fff; box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05); }
        .sidebar .nav-link { color: #495057; padding: 12px 20px; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #667eea; color: white; }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); margin-bottom: 20px; }
        
        /* Bảng chấm công */
        .attendance-table { font-size: 12px; }
        .attendance-table th, .attendance-table td { 
            padding: 8px 4px; 
            text-align: center; 
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }
        .attendance-table th { 
            background-color: #667eea; 
            color: white; 
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .attendance-table tbody tr:hover { background-color: #f8f9fa; }
        
        /* Trạng thái ngày */
        .day-cell { 
            min-width: 35px; 
            cursor: pointer;
            position: relative;
        }
        .day-header { font-size: 11px; }
        .weekend { background-color: #ffe5e5; }
        .today { background-color: #fff3cd; font-weight: bold; }
        .on-leave { background-color: #ffcccc; color: #dc3545; font-weight: bold; }
        .half-day { background-color: #fff3cd; color: #856404; font-weight: bold; }
        .working { background-color: #d4edda; color: #155724; }
        
        /* Icon */
        .leave-icon { font-size: 10px; }
        
        /* Fixed columns */
        .sticky-col { 
            position: sticky; 
            left: 0; 
            background-color: white; 
            z-index: 5;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sticky-col-2 { 
            position: sticky; 
            left: 150px; 
            background-color: white; 
            z-index: 5;
        }
        
        /* Summary */
        .summary-cell { font-weight: bold; background-color: #f8f9fa; }
        
        /* Print styles */
        @media print {
            .no-print { display: none; }
            .sidebar { display: none; }
            .attendance-table { font-size: 10px; }
            .attendance-table th, .attendance-table td { padding: 4px 2px; }
            .card { box-shadow: none; }
        }
        
        /* Scrollable table */
        .table-container {
            overflow-x: auto;
            position: relative;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark no-print">
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
            <div class="col-md-2 sidebar p-0 no-print">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <?php if (hasRole('ADMIN')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">
                            <i class="fas fa-users"></i> Quản lý người dùng
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Báo cáo thống kê
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="employee_report.php">
                            <i class="fas fa-calendar-check"></i> Bảng chấm công
                        </a>
                    </li>
                    <?php if (hasRole('ADMIN')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> Cấu hình hệ thống
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-calendar-check"></i> Bảng chấm công nhân viên
                        <small class="text-muted">Tháng <?= $selectedMonthNum ?>/<?= $selectedYear ?></small>
                    </h2>
                    <div class="no-print">
                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fas fa-print"></i> In báo cáo
                        </button>
                        <button onclick="exportToExcel()" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Xuất Excel
                        </button>
                    </div>
                </div>
                
                <!-- Bộ lọc -->
                <div class="card no-print">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Chọn tháng/năm:</label>
                                    <input type="month" class="form-control" name="month" 
                                           value="<?= htmlspecialchars($selectedMonth) ?>" required>
                                </div>
                                
                                <?php if (hasRole('ADMIN')): ?>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Khoa/Phòng:</label>
                                    <select class="form-select" name="khoa_phong">
                                        <option value="all" <?= $khoaPhong === 'all' ? 'selected' : '' ?>>Tất cả</option>
                                        <?php foreach ($danhSachKhoaPhong as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept) ?>" 
                                                <?= $khoaPhong === $dept ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter"></i> Xem báo cáo
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Chú thích -->
                <div class="card no-print">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2">
                                <div class="working p-2 rounded">✓ Đi làm</div>
                            </div>
                            <div class="col-md-2">
                                <div class="on-leave p-2 rounded">X Nghỉ phép</div>
                            </div>
                            <div class="col-md-2">
                                <div class="half-day p-2 rounded">1/2 Nửa ngày</div>
                            </div>
                            <div class="col-md-2">
                                <div class="weekend p-2 rounded">CN Cuối tuần</div>
                            </div>
                            <div class="col-md-2">
                                <div class="today p-2 rounded">● Hôm nay</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bảng chấm công -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-container">
                            <table class="table table-bordered attendance-table mb-0" id="attendanceTable">
                                <thead>
                                    <tr>
                                        <th rowspan="2" class="sticky-col" style="width: 150px;">Họ tên</th>
                                        <th rowspan="2" style="width: 120px;">Khoa/Phòng</th>
                                        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                                            <?php
                                            $currentDate = "$selectedYear-$selectedMonthNum-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                                            $dayOfWeek = date('w', strtotime($currentDate));
                                            $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
                                            $isToday = ($currentDate == date('Y-m-d'));
                                            $cellClass = $isWeekend ? 'weekend' : '';
                                            $cellClass .= $isToday ? ' today' : '';
                                            ?>
                                            <th class="day-cell <?= $cellClass ?>">
                                                <div><?= $day ?></div>
                                                <div class="day-header">
                                                    <?php
                                                    $dayNames = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
                                                    echo $dayNames[$dayOfWeek];
                                                    ?>
                                                </div>
                                            </th>
                                        <?php endfor; ?>
                                        <th rowspan="2" class="summary-cell">Tổng<br>nghỉ</th>
                                        <th rowspan="2" class="summary-cell">Phép<br>năm</th>
                                        <th rowspan="2" class="summary-cell">Còn<br>lại</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                    <?php
                                    $userId = $employee['MaNguoiDung'];
                                    $userLeaves = isset($leavesByUser[$userId]) ? $leavesByUser[$userId] : [];
                                    $totalLeaveDays = 0;
                                    ?>
                                    <tr>
                                        <td class="sticky-col text-start">
                                            <strong><?= htmlspecialchars($employee['HoTen']) ?></strong>
                                            <?php if ($employee['GioiTinh'] == 'Nu'): ?>
                                                <i class="fas fa-venus text-danger"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($employee['KhoaPhong']) ?></td>
                                        
                                        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                                            <?php
                                            $currentDate = "$selectedYear-$selectedMonthNum-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                                            $dayOfWeek = date('w', strtotime($currentDate));
                                            $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
                                            $isToday = ($currentDate == date('Y-m-d'));
                                            
                                            $leaveInfo = isDateInLeave($currentDate, $userLeaves);
                                            
                                            $cellClass = '';
                                            $cellContent = '';
                                            $title = '';
                                            
                                            if ($isWeekend) {
                                                $cellClass = 'weekend';
                                                $cellContent = 'CN';
                                            } elseif ($leaveInfo['isLeave']) {
                                                if (isset($leaveInfo['halfDay'])) {
                                                    $cellClass = 'half-day';
                                                    $cellContent = $leaveInfo['halfDay'] == 'Sang' ? '½S' : '½C';
                                                    $totalLeaveDays += 0.5;
                                                    $title = $leaveInfo['loaiPhep'] . ' - ' . 
                                                            ($leaveInfo['halfDay'] == 'Sang' ? 'Buổi sáng' : 'Buổi chiều') . 
                                                            ' - ' . $leaveInfo['maDon'];
                                                } else {
                                                    $cellClass = 'on-leave';
                                                    $cellContent = 'X';
                                                    $totalLeaveDays += 1;
                                                    $title = $leaveInfo['loaiPhep'] . ' - ' . $leaveInfo['maDon'];
                                                }
                                            } else {
                                                $cellClass = 'working';
                                                $cellContent = '✓';
                                                $title = 'Đi làm';
                                            }
                                            
                                            if ($isToday) {
                                                $cellClass .= ' today';
                                            }
                                            ?>
                                            <td class="day-cell <?= $cellClass ?>" title="<?= $title ?>">
                                                <?= $cellContent ?>
                                            </td>
                                        <?php endfor; ?>
                                        
                                        <td class="summary-cell">
                                            <strong><?= number_format($totalLeaveDays, 1) ?></strong>
                                        </td>
                                        <td class="summary-cell">
                                            <?= $employee['SoNgayPhepNam'] ?>
                                        </td>
                                        <td class="summary-cell">
                                            <span class="badge bg-<?= $employee['SoNgayPhepConLai'] > 5 ? 'success' : 'warning' ?>">
                                                <?= number_format($employee['SoNgayPhepConLai'], 1) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Thống kê tổng quan -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Thống kê tổng quan tháng <?= $selectedMonthNum ?>/<?= $selectedYear ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h4><?= count($employees) ?></h4>
                                <p class="text-muted">Tổng nhân viên</p>
                            </div>
                            <div class="col-md-3">
                                <h4><?= count($leaves) ?></h4>
                                <p class="text-muted">Tổng đơn nghỉ</p>
                            </div>
                            <div class="col-md-3">
                                <h4><?= number_format(array_sum(array_column($leaves, 'SoNgayNghi')), 1) ?></h4>
                                <p class="text-muted">Tổng ngày nghỉ</p>
                            </div>
                            <div class="col-md-3">
                                <h4><?= $daysInMonth ?></h4>
                                <p class="text-muted">Ngày trong tháng</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        function exportToExcel() {
            const table = document.getElementById('attendanceTable');
            const wb = XLSX.utils.table_to_book(table, {sheet: "Bảng chấm công"});
            
            const fileName = 'BangChamCong_<?= $selectedMonth ?>.xlsx';
            XLSX.writeFile(wb, fileName);
        }
        
        // Tooltip cho các ô
        document.querySelectorAll('.day-cell').forEach(cell => {
            if (cell.title) {
                cell.style.cursor = 'help';
            }
        });
    </script>
</body>
</html>