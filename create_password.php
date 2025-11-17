<?php
// create_password.php - Tạo mật khẩu hash và cập nhật vào database
require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = "Tạo mật khẩu Hash";
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $username = $_POST['username'] ?? '';
    
    if (!empty($password) && !empty($username)) {
        $pdo = getDBConnection();
        
        // Tạo hash mật khẩu
        $hashedPassword = hashPassword($password);
        
        // Cập nhật vào database
        $stmt = $pdo->prepare("UPDATE NguoiDung SET MatKhau = ? WHERE TenDangNhap = ?");
        $success = $stmt->execute([$hashedPassword, $username]);
        
        if ($success && $stmt->rowCount() > 0) {
            $result = [
                'success' => true,
                'message' => "Đã cập nhật mật khẩu cho user: $username",
                'hash' => $hashedPassword
            ];
        } else {
            $result = [
                'success' => false,
                'message' => "Không tìm thấy user: $username"
            ];
        }
    } else {
        $result = [
            'success' => false,
            'message' => 'Vui lòng nhập đầy đủ thông tin'
        ];
    }
}

// Lấy danh sách user hiện có
$pdo = getDBConnection();
$users = $pdo->query("SELECT MaNguoiDung, TenDangNhap, HoTen FROM NguoiDung")->fetchAll();
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        
        .container-box {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        
        .hash-output {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
            border: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container-box">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">
                    <i class="fas fa-key"></i> Tạo và Cập nhật Mật khẩu
                </h3>
            </div>
            
            <div class="card-body">
                <?php if ($result): ?>
                    <?php if ($result['success']): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> 
                            <strong>Thành công!</strong> <?= $result['message'] ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Password Hash:</label>
                            <div class="hash-output">
                                <?= $result['hash'] ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> 
                            <strong>Lỗi!</strong> <?= $result['message'] ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <h5 class="mb-3"><i class="fas fa-list"></i> Danh sách User hiện có:</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Mã User</th>
                                <th>Tên đăng nhập</th>
                                <th>Họ tên</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['MaNguoiDung']) ?></td>
                                <td><strong><?= htmlspecialchars($user['TenDangNhap']) ?></strong></td>
                                <td><?= htmlspecialchars($user['HoTen']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <hr>
                
                <h5 class="mb-3"><i class="fas fa-edit"></i> Cập nhật mật khẩu:</h5>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user"></i> Tên đăng nhập
                        </label>
                        <select class="form-select" name="username" required>
                            <option value="">-- Chọn user --</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?= htmlspecialchars($user['TenDangNhap']) ?>">
                                <?= htmlspecialchars($user['TenDangNhap']) ?> 
                                (<?= htmlspecialchars($user['HoTen']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-lock"></i> Mật khẩu mới
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="password" 
                               placeholder="Nhập mật khẩu mới"
                               required>
                        <small class="text-muted">
                            Mật khẩu sẽ được tự động hash trước khi lưu vào database
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Cập nhật Mật khẩu
                    </button>
                    
                    <a href="views/login.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-left"></i> Về trang đăng nhập
                    </a>
                </form>
                
                <hr>
                
                <!-- Quick Action: Reset tất cả về 123456 -->
                <div class="alert alert-warning">
                    <h5><i class="fas fa-bolt"></i> Reset nhanh tất cả tài khoản về mật khẩu: <strong>123456</strong></h5>
                    <p class="mb-2">Chạy SQL sau trong phpMyAdmin:</p>
                    <div class="hash-output">
                        UPDATE NguoiDung SET 
                            MatKhau = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
                        WHERE TenDangNhap IN ('admin', 'user1', 'manager');
                    </div>
                </div>
                
                <!-- Test password verification -->
                <hr>
                <h5 class="mb-3"><i class="fas fa-check-circle"></i> Test xác thực mật khẩu:</h5>
                <form method="GET" action="test_verify_password.php" target="_blank">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username để test:</label>
                        <select class="form-select" name="username">
                            <?php foreach ($users as $user): ?>
                            <option value="<?= htmlspecialchars($user['TenDangNhap']) ?>">
                                <?= htmlspecialchars($user['TenDangNhap']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mật khẩu để test:</label>
                        <input type="text" class="form-control" name="password" value="123456">
                    </div>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-vial"></i> Test xác thực
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>