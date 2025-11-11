<?php
// includes/session.php - Quản lý phiên đăng nhập

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đã đăng nhập chưa
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Kiểm tra vai trò
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    return $_SESSION['role'] === $role;
}

// Kiểm tra có ít nhất một trong các vai trò
function hasAnyRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    return in_array($_SESSION['role'], $roles);
}

// Lấy thông tin user hiện tại
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'fullname' => $_SESSION['fullname'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'],
        'department' => $_SESSION['department'] ?? ''
    ];
}

// Đăng nhập
function login($user) {
    $_SESSION['user_id'] = $user['MaNguoiDung'];
    $_SESSION['username'] = $user['TenDangNhap'];
    $_SESSION['fullname'] = $user['HoTen'];
    $_SESSION['email'] = $user['Email'];
    $_SESSION['role'] = $user['TenVaiTro'];
    $_SESSION['department'] = $user['KhoaPhong'] ?? '';
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID để tránh session fixation
    session_regenerate_id(true);
}

// Đăng xuất
function logout() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

// Yêu cầu đăng nhập (redirect nếu chưa đăng nhập)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /appnghiphep/views/login.php');
        exit;
    }
}

// Yêu cầu vai trò cụ thể
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        http_response_code(403);
        die('Bạn không có quyền truy cập trang này!');
    }
}

// Yêu cầu một trong các vai trò
function requireAnyRole($roles) {
    requireLogin();
    
    if (!hasAnyRole($roles)) {
        http_response_code(403);
        die('Bạn không có quyền truy cập trang này!');
    }
}

// Lấy trang chủ theo vai trò
function getHomePage() {
    if (!isLoggedIn()) {
        return '/appnghiphep/views/login.php';
    }
    
    switch ($_SESSION['role']) {
        case 'ADMIN':
            return '/appnghiphep/views/admin/dashboard.php';
        case 'MANAGER':
            return '/appnghiphep/views/manager/dashboard.php';
        case 'USER':
            return '/appnghiphep/views/user/dashboard.php';
        default:
            return '/appnghiphep/index.php';
    }
}

// Set flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

// Get và xóa flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

// Display flash message HTML
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $typeClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        
        $class = $typeClass[$flash['type']] ?? 'alert-info';
        
        echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// Kiểm tra timeout session (30 phút không hoạt động)
function checkSessionTimeout($timeout = 1800) { // 30 phút = 1800 giây
    if (isLoggedIn()) {
        $last_activity = $_SESSION['login_time'] ?? time();
        
        if (time() - $last_activity > $timeout) {
            logout();
            header('Location: /appnghiphep/views/login.php?timeout=1');
            exit;
        }
        
        $_SESSION['login_time'] = time();
    }
}
?>