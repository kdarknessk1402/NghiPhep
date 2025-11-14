<?php
// views/login.php - Trang đăng nhập
require_once __DIR__ . '/../includes/session.php';

// Nếu đã đăng nhập, chuyển về trang chủ
if (isLoggedIn()) {
    header('Location: ' . getHomePage());
    exit;
}

$pageTitle = "Đăng nhập - Hệ thống nghỉ phép";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .login-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        
        .form-control {
            border-left: none;
        }
        
        .demo-accounts {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
        }
        
        .demo-accounts h6 {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .demo-account {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .demo-account:last-child {
            border-bottom: none;
        }
        
        .demo-account strong {
            color: #495057;
        }
        
        .demo-account span {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-calendar-check fa-3x mb-3"></i>
                <h2>HỆ THỐNG NGHỈ PHÉP</h2>
                <p>Quản lý đơn nghỉ phép trực tuyến</p>
            </div>
            
            <div class="login-body">
                <?php displayFlashMessage(); ?>
                
                <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-clock"></i> Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="../controllers/AuthController.php" id="loginForm">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user"></i> Tên đăng nhập
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   name="username" 
                                   id="username"
                                   placeholder="Nhập tên đăng nhập"
                                   required
                                   autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-lock"></i> Mật khẩu
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control" 
                                   name="password" 
                                   id="password"
                                   placeholder="Nhập mật khẩu"
                                   required>
                            <button class="btn btn-outline-secondary" 
                                    type="button" 
                                    id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100">
                        <i class="fas fa-sign-in-alt"></i> ĐĂNG NHẬP
                    </button>
                </form>
                
                <!-- Tài khoản demo -->
                <div class="demo-accounts">
                    <h6><i class="fas fa-info-circle"></i> Tài khoản demo (Mật khẩu: 123456)</h6>
                    <div class="demo-account">
                        <strong><i class="fas fa-crown text-danger"></i> Admin:</strong>
                        <span>admin</span>
                    </div>
                    <div class="demo-account">
                        <strong><i class="fas fa-user-tie text-primary"></i> Quản lý 1:</strong>
                        <span>manager</span>
                    </div>
                    <div class="demo-account">
                        <strong><i class="fas fa-user-tie text-primary"></i> Quản lý 2:</strong>
                        <span>manager2</span>
                    </div>
                    <div class="demo-account">
                        <strong><i class="fas fa-user text-info"></i> Nhân viên 1:</strong>
                        <span>user1</span>
                    </div>
                    <div class="demo-account">
                        <strong><i class="fas fa-user text-info"></i> Nhân viên 2:</strong>
                        <span>user2</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-white">
                © 2025 APPNGHIPHEP. Phát triển bởi Khoa Công nghệ Thông tin.
            </small>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle hiển thị mật khẩu
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Validate form
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (username === '' || password === '') {
                e.preventDefault();
                alert('Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu');
                return false;
            }
        });
        
        // Auto-fill demo account khi click
        document.querySelectorAll('.demo-account span').forEach(account => {
            account.style.cursor = 'pointer';
            account.style.textDecoration = 'underline';
            
            account.addEventListener('click', function() {
                document.getElementById('username').value = this.textContent;
                document.getElementById('password').value = '123456';
                document.getElementById('username').focus();
            });
        });
    </script>
</body>
</html>