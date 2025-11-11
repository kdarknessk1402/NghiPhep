<?php
// test_verify_password.php - Kiểm tra xác thực mật khẩu
require_once 'config/database.php';
require_once 'includes/functions.php';

$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';

$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Verify Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        
        .card {
            max-width: 800px;
            margin: 0 auto;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .test-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .hash-display {
            background: #000;
            color: #0f0;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">
                <i class="fas fa-vial"></i> Test Xác Thực Mật Khẩu
            </h3>
        </div>
        
        <div class="card-body">
            <?php if (empty($username) || empty($password)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Vui lòng điền username và password
                </div>
            <?php else: ?>
                <div class="test-info">
                    <strong>Username test:</strong> <?= htmlspecialchars($username) ?><br>
                    <strong>Password test:</strong> <?= htmlspecialchars($password) ?>
                </div>
                
                <?php
                // Lấy thông tin user từ database
                $stmt = $pdo->prepare("SELECT MaNguoiDung, TenDangNhap, HoTen, MatKhau FROM NguoiDung WHERE TenDangNhap = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    echo '<div class="alert alert-danger">';
                    echo '<i class="fas fa-times-circle"></i> ';
                    echo '<strong>User không tồn tại!</strong>';
                    echo '</div>';
                } else {
                    echo '<h5><i class="fas fa-database"></i> Thông tin từ Database:</h5>';
                    echo '<div class="test-info">';
                    echo '<strong>Mã user:</strong> ' . htmlspecialchars($user['MaNguoiDung']) . '<br>';
                    echo '<strong>Tên đăng nhập:</strong> ' . htmlspecialchars($user['TenDangNhap']) . '<br>';
                    echo '<strong>Họ tên:</strong> ' . htmlspecialchars($user['HoTen']) . '<br>';
                    echo '<strong>Password Hash trong DB:</strong><br>';
                    echo '<div class="hash-display">' . htmlspecialchars($user['MatKhau']) . '</div>';
                    echo '</div>';
                    
                    echo '<hr>';
                    
                    // Test verify password
                    echo '<h5><i class="fas fa-key"></i> Kiểm tra xác thực:</h5>';
                    
                    $isValid = verifyPassword($password, $user['MatKhau']);
                    
                    if ($isValid) {
                        echo '<div class="alert alert-success">';
                        echo '<i class="fas fa-check-circle fa-2x mb-2"></i><br>';
                        echo '<strong style="font-size: 20px;">✅ XÁC THỰC THÀNH CÔNG!</strong><br>';
                        echo 'Mật khẩu "<strong>' . htmlspecialchars($password) . '</strong>" khớp với hash trong database.';
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-danger">';
                        echo '<i class="fas fa-times-circle fa-2x mb-2"></i><br>';
                        echo '<strong style="font-size: 20px;">❌ XÁC THỰC THẤT BẠI!</strong><br>';
                        echo 'Mật khẩu "<strong>' . htmlspecialchars($password) . '</strong>" KHÔNG khớp với hash trong database.';
                        echo '</div>';
                        
                        echo '<div class="alert alert-warning">';
                        echo '<h6><i class="fas fa-tools"></i> Cách khắc phục:</h6>';
                        echo '<ol>';
                        echo '<li>Chạy file <a href="create_password.php" target="_blank">create_password.php</a> để cập nhật mật khẩu</li>';
                        echo '<li>Hoặc chạy SQL sau trong phpMyAdmin:</li>';
                        echo '</ol>';
                        
                        $newHash = hashPassword($password);
                        echo '<div class="hash-display">';
                        echo "UPDATE NguoiDung SET MatKhau = '$newHash' WHERE TenDangNhap = '$username';";
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    echo '<hr>';
                    
                    // Test tạo hash mới
                    echo '<h5><i class="fas fa-cog"></i> Tạo hash mới cho mật khẩu này:</h5>';
                    $newHash = hashPassword($password);
                    echo '<div class="test-info">';
                    echo '<strong>Mật khẩu:</strong> ' . htmlspecialchars($password) . '<br>';
                    echo '<strong>Hash mới (bcrypt):</strong><br>';
                    echo '<div class="hash-display">' . $newHash . '</div>';
                    echo '</div>';
                    
                    echo '<div class="alert alert-info">';
                    echo '<strong>💡 Lưu ý:</strong> Mỗi lần hash sẽ tạo ra chuỗi khác nhau (do salt ngẫu nhiên), ';
                    echo 'nhưng đều có thể verify được với mật khẩu gốc.';
                    echo '</div>';
                }
                ?>
            <?php endif; ?>
            
            <hr>
            
            <div class="text-center">
                <a href="create_password.php" class="btn btn-primary">
                    <i class="fas fa-key"></i> Cập nhật mật khẩu
                </a>
                <a href="views/login.php" class="btn btn-secondary">
                    <i class="fas fa-sign-in-alt"></i> Đến trang đăng nhập
                </a>
            </div>
        </div>
    </div>
</body>
</html>