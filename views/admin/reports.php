<?php
// views/admin/reports.php - Báo cáo và thống kê
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireAnyRole(['ADMIN', 'MANAGER']);

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Lọc theo thời gian
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01'); // Đầu tháng
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d'); // Hôm nay
$khoaPhong = isset($_GET['khoa_phong']) ? $_GET['khoa_phong'] : 'all';

// Điều kiện lọc theo khoa/phòng
$whereKhoaPhong = "";
$params = [$fromDate, $toDate];

if ($khoaPhong !== 'all') {
    $whereKhoaPhong = " AND n.KhoaPhong = ?";
    $params[] = $khoaPhong;
} elseif (hasRole('MANAGER')) {
    // Manager chỉ thấy báo cáo của khoa mình
    $stmt = $pdo->prepare("SELECT KhoaPhong FROM NguoiDung WHERE MaNguoiDung = ?");
    $stmt->execute([$currentUser['id']]);
    $managerDept = $stmt->fetchColumn();
    $whereKhoaPhong = " AND n.KhoaPhong = ?";
    $params[] = $managerDept;
}

// 1. THỐNG KÊ TỔNG QUAN
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as TongDon,
        SUM(CASE WHEN d.TrangThai = 'WAITING' THEN 1 ELSE 0 END) as ChoDuyet,
        SUM(CASE WHEN d.TrangThai = 'ACCEPT' THEN 1 ELSE 0 END) as DaDuyet,
        SUM(CASE WHEN d.TrangThai = 'DENY' THEN 1 ELSE 0 END) as TuChoi,
        SUM(CASE WHEN d.TrangThai = 'ACCEPT' THEN d.SoNgayNghi ELSE 0 END) as TongNgayNghi
    FROM DonNghiPhep d
    JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
    WHERE d.NgayTao BETWEEN ? AND ? 
    $whereKhoaPhong
");
$stmt->execute($params);
$tongQuan = $stmt->fetch();

// 2. THỐNG KÊ THEO LOẠI PHÉP
$stmt = $pdo->prepare("
    SELECT 
        d.LoaiPhep,
        COUNT(*) as SoDon,
        SUM(d.SoNgayNghi) as TongNgay
    FROM DonNghiPhep d
    JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
    WHERE d.NgayTao BETWEEN ? AND ? 
    $whereKhoaPhong
    GROUP BY d.LoaiPhep
    ORDER BY SoDon DESC
");
$stmt->execute($params);
$theoLoaiPhep = $stmt->fetchAll();

// 3. THỐNG KÊ THEO KHOA/PHÒNG
$stmt = $pdo->prepare("
    SELECT 
        n.KhoaPhong,
        COUNT(*) as SoDon,
        SUM(CASE WHEN d.TrangThai = 'WAITING' THEN 1 ELSE 0 END) as ChoDuyet,
        SUM(CASE WHEN d.TrangThai = 'ACCEPT' THEN 1 ELSE 0 END) as DaDuyet,
        SUM(CASE WHEN d.TrangThai = 'DENY' THEN 1 ELSE 0 END) as TuChoi,
        SUM(CASE WHEN d.TrangThai = 'ACCEPT' THEN d.SoNgayNghi ELSE 0 END) as TongNgayNghi
    FROM DonNghiPhep d
    JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
    WHERE d.NgayTao BETWEEN ? AND ? 
    " . (hasRole('MANAGER') ? " AND n.KhoaPhong = ?" : "") . "
    GROUP BY n.KhoaPhong
    ORDER BY SoDon DESC
");

$managerParams = [$fromDate, $toDate];
if (hasRole('MANAGER')) {
    $managerParams[] = $managerDept;
}
$stmt->execute($managerParams);
$theoKhoaPhong = $stmt->fetchAll();

// 4. TOP NHÂN VIÊN NGHỈ NHIỀU NHẤT
$stmt = $pdo->prepare("
    SELECT 
        n.HoTen,
        n.KhoaPhong,
        COUNT(*) as SoDon,
        SUM(d.SoNgayNghi) as TongNgayNghi,
        n.SoNgayPhepNam,
        n.SoNgayPhepDaDung,
        (n.SoNgayPhepNam - n.SoNgayPhepDaDung) as ConLai
    FROM DonNghiPhep d
    JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
    WHERE d.NgayTao BETWEEN ? AND ? 
    AND d.TrangThai = 'ACCEPT'
    $whereKhoaPhong
    GROUP BY n.MaNguoiDung
    ORDER BY TongNgayNghi DESC
    LIMIT 10
");
$stmt->execute($params);
$topNhanVien = $stmt->fetchAll();

// 5. THỐNG KÊ THEO THÁNG (12 tháng gần nhất)
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(d.NgayTao, '%Y-%m') as Thang,
        COUNT(*) as SoDon,
        SUM(CASE WHEN d.TrangThai = 'ACCEPT' THEN d.SoNgayNghi ELSE 0 END) as TongNgayNghi
    FROM DonNghiPhep d
    JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
    WHERE d.NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    $whereKhoaPhong
    GROUP BY Thang
    ORDER BY Thang ASC
");

$monthParams = [];
if ($whereKhoaPhong) {
    $monthParams[] = end($params); // Lấy tham số cuối (khoa/phòng)
}
$stmt->execute($monthParams);
$theoThang = $stmt->fetchAll();

// 6. DANH SÁCH KHOA/PHÒNG ĐỂ LỌC
$danhSachKhoaPhong = $pdo->query("
    SELECT DISTINCT KhoaPhong 
    FROM NguoiDung 
    WHERE KhoaPhong IS NOT NULL 
    ORDER BY KhoaPhong
")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = "Báo cáo thống kê";
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
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); margin-bottom: 20px; }
        .stat-card { text-align: center; padding: 20px; }
        .stat-number { font-size: 36px; font-weight: bold; }
        .chart-container { position: relative; height: 300px; }
        @media print {
            .no-print { display: none; }
            .sidebar { display: none; }
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
                        <a class="nav-link active" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Báo cáo thống kê
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="employee_report.php">
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
                        <i class="fas fa-chart-line"></i> Báo cáo thống kê
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
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Từ ngày:</label>
                                    <input type="date" class="form-control" name="from_date" 
                                           value="<?= htmlspecialchars($fromDate) ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Đến ngày:</label>
                                    <input type="date" class="form-control" name="to_date" 
                                           value="<?= htmlspecialchars($toDate) ?>">
                                </div>
                                
                                <?php if (hasRole('ADMIN')): ?>
                                <div class="col-md-3">
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
                                
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter"></i> Lọc
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Thống kê tổng quan -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <i class="fas fa-file-alt fa-3x mb-2"></i>
                            <div class="stat-number"><?= $tongQuan['TongDon'] ?></div>
                            <div>Tổng số đơn</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <i class="fas fa-clock fa-3x mb-2"></i>
                            <div class="stat-number"><?= $tongQuan['ChoDuyet'] ?></div>
                            <div>Chờ duyệt</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <i class="fas fa-check-circle fa-3x mb-2"></i>
                            <div class="stat-number"><?= $tongQuan['DaDuyet'] ?></div>
                            <div>Đã duyệt</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white">
                            <i class="fas fa-calendar-day fa-3x mb-2"></i>
                            <div class="stat-number"><?= number_format($tongQuan['TongNgayNghi'], 1) ?></div>
                            <div>Tổng ngày nghỉ</div>
                        </div>
                    </div>
                </div>
                
                <!-- Biểu đồ -->
                <div class="row">
                    <!-- Biểu đồ theo loại phép -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Thống kê theo loại phép</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="chartLoaiPhep"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Biểu đồ theo trạng thái -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-chart-doughnut"></i> Thống kê theo trạng thái</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="chartTrangThai"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Biểu đồ theo tháng -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Xu hướng 12 tháng gần nhất</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="chartTheoThang"></canvas>
                    </div>
                </div>
                
                <!-- Bảng theo khoa/phòng -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-building"></i> Thống kê theo Khoa/Phòng</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="tableKhoaPhong">
                                <thead class="table-light">
                                    <tr>
                                        <th>Khoa/Phòng</th>
                                        <th class="text-center">Tổng đơn</th>
                                        <th class="text-center">Chờ duyệt</th>
                                        <th class="text-center">Đã duyệt</th>
                                        <th class="text-center">Từ chối</th>
                                        <th class="text-center">Tổng ngày nghỉ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($theoKhoaPhong as $row): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['KhoaPhong']) ?></strong></td>
                                        <td class="text-center"><?= $row['SoDon'] ?></td>
                                        <td class="text-center"><span class="badge bg-warning"><?= $row['ChoDuyet'] ?></span></td>
                                        <td class="text-center"><span class="badge bg-success"><?= $row['DaDuyet'] ?></span></td>
                                        <td class="text-center"><span class="badge bg-danger"><?= $row['TuChoi'] ?></span></td>
                                        <td class="text-center"><strong><?= number_format($row['TongNgayNghi'], 1) ?></strong> ngày</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Top nhân viên -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 10 nhân viên nghỉ nhiều nhất</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="tableTopNhanVien">
                                <thead class="table-light">
                                    <tr>
                                        <th>STT</th>
                                        <th>Họ tên</th>
                                        <th>Khoa/Phòng</th>
                                        <th class="text-center">Số đơn</th>
                                        <th class="text-center">Tổng ngày nghỉ</th>
                                        <th class="text-center">Phép năm</th>
                                        <th class="text-center">Đã dùng</th>
                                        <th class="text-center">Còn lại</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $stt = 1; foreach ($topNhanVien as $row): ?>
                                    <tr>
                                        <td><?= $stt++ ?></td>
                                        <td><strong><?= htmlspecialchars($row['HoTen']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['KhoaPhong']) ?></td>
                                        <td class="text-center"><?= $row['SoDon'] ?></td>
                                        <td class="text-center"><strong><?= number_format($row['TongNgayNghi'], 1) ?></strong></td>
                                        <td class="text-center"><?= $row['SoNgayPhepNam'] ?></td>
                                        <td class="text-center"><?= number_format($row['SoNgayPhepDaDung'], 1) ?></td>
                                        <td class="text-center"><span class="badge bg-info"><?= number_format($row['ConLai'], 1) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        // Dữ liệu cho biểu đồ
        const dataLoaiPhep = <?= json_encode($theoLoaiPhep) ?>;
        const dataTrangThai = {
            choDuyet: <?= $tongQuan['ChoDuyet'] ?>,
            daDuyet: <?= $tongQuan['DaDuyet'] ?>,
            tuChoi: <?= $tongQuan['TuChoi'] ?>
        };
        const dataTheoThang = <?= json_encode($theoThang) ?>;
        
        // Biểu đồ theo loại phép
        new Chart(document.getElementById('chartLoaiPhep'), {
            type: 'bar',
            data: {
                labels: dataLoaiPhep.map(item => item.LoaiPhep),
                datasets: [{
                    label: 'Số đơn',
                    data: dataLoaiPhep.map(item => item.SoDon),
                    backgroundColor: 'rgba(102, 126, 234, 0.8)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                }
            }
        });
        
        // Biểu đồ theo trạng thái
        new Chart(document.getElementById('chartTrangThai'), {
            type: 'doughnut',
            data: {
                labels: ['Chờ duyệt', 'Đã duyệt', 'Từ chối'],
                datasets: [{
                    data: [dataTrangThai.choDuyet, dataTrangThai.daDuyet, dataTrangThai.tuChoi],
                    backgroundColor: ['#ffc107', '#28a745', '#dc3545']
                }]
            }
        });
        
        // Biểu đồ theo tháng
        new Chart(document.getElementById('chartTheoThang'), {
            type: 'line',
            data: {
                labels: dataTheoThang.map(item => item.Thang),
                datasets: [
                    {
                        label: 'Số đơn',
                        data: dataTheoThang.map(item => item.SoDon),
                        borderColor: 'rgba(102, 126, 234, 1)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Tổng ngày nghỉ',
                        data: dataTheoThang.map(item => item.TongNgayNghi),
                        borderColor: 'rgba(255, 193, 7, 1)',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.4
                    }
                ]
            }
        });
        
        // Xuất Excel
        function exportToExcel() {
            const wb = XLSX.utils.book_new();
            
            // Sheet 1: Tổng quan
            const wsTongQuan = XLSX.utils.json_to_sheet([
                { 'Chỉ tiêu': 'Tổng số đơn', 'Giá trị': <?= $tongQuan['TongDon'] ?> },
                { 'Chỉ tiêu': 'Chờ duyệt', 'Giá trị': <?= $tongQuan['ChoDuyet'] ?> },
                { 'Chỉ tiêu': 'Đã duyệt', 'Giá trị': <?= $tongQuan['DaDuyet'] ?> },
                { 'Chỉ tiêu': 'Từ chối', 'Giá trị': <?= $tongQuan['TuChoi'] ?> },
                { 'Chỉ tiêu': 'Tổng ngày nghỉ', 'Giá trị': <?= $tongQuan['TongNgayNghi'] ?> }
            ]);
            XLSX.utils.book_append_sheet(wb, wsTongQuan, 'Tổng quan');
            
            // Sheet 2: Theo khoa/phòng
            const wsKhoaPhong = XLSX.utils.table_to_sheet(document.getElementById('tableKhoaPhong'));
            XLSX.utils.book_append_sheet(wb, wsKhoaPhong, 'Theo Khoa-Phòng');
            
            // Sheet 3: Top nhân viên
            const wsTopNV = XLSX.utils.table_to_sheet(document.getElementById('tableTopNhanVien'));
            XLSX.utils.book_append_sheet(wb, wsTopNV, 'Top Nhân viên');
            
            // Xuất file
            XLSX.writeFile(wb, 'BaoCaoNghiPhep_' + new Date().toISOString().split('T')[0] + '.xlsx');
        }
    </script>
</body>
</html>