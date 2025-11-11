<?php
// controllers/AuthController.php - Xử lý đăng nhập/đăng xuất

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

class AuthController {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    // Xử lý đăng nhập
    public function login($username, $password) {
        try {
            // Validate input
            if (empty($username) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu'
                ];
            }
            
            // Truy vấn user với vai trò
            $stmt = $this->pdo->prepare("
                SELECT 
                    n.MaNguoiDung,
                    n.TenDangNhap,
                    n.MatKhau,
                    n.HoTen,
                    n.Email,
                    n.ViTri,
                    n.KhoaPhong,
                    n.SoNgayPhepNam,
                    n.SoNgayPhepDaDung,
                    v.TenVaiTro
                FROM NguoiDung n
                INNER JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
                WHERE n.TenDangNhap = ?
                LIMIT 1
            ");
            
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                logActivity($username, 'LOGIN_FAILED', 'User không tồn tại');
                return [
                    'success' => false,
                    'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'
                ];
            }
            
            // Kiểm tra mật khẩu
            if (!verifyPassword($password, $user['MatKhau'])) {
                logActivity($username, 'LOGIN_FAILED', 'Sai mật khẩu');
                return [
                    'success' => false,
                    'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'
                ];
            }
            
            // Đăng nhập thành công
            login($user);
            logActivity($user['MaNguoiDung'], 'LOGIN_SUCCESS', 'Đăng nhập thành công');
            
            return [
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'user' => [
                    'id' => $user['MaNguoiDung'],
                    'username' => $user['TenDangNhap'],
                    'fullname' => $user['HoTen'],
                    'role' => $user['TenVaiTro']
                ],
                'redirect' => getHomePage()
            ];
            
        } catch (PDOException $e) {
            logActivity($username, 'LOGIN_ERROR', $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ];
        }
    }
    
    // Xử lý đăng xuất
    public function logout() {
        if (isLoggedIn()) {
            $userId = $_SESSION['user_id'];
            logActivity($userId, 'LOGOUT', 'Đăng xuất');
            logout();
        }
        
        return [
            'success' => true,
            'message' => 'Đã đăng xuất',
            'redirect' => '/appnghiphep/views/login.php'
        ];
    }
    
    // Đổi mật khẩu
    public function changePassword($userId, $oldPassword, $newPassword, $confirmPassword) {
        try {
            // Validate
            if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
                return [
                    'success' => false,
                    'message' => 'Vui lòng điền đầy đủ thông tin'
                ];
            }
            
            if ($newPassword !== $confirmPassword) {
                return [
                    'success' => false,
                    'message' => 'Mật khẩu mới không khớp'
                ];
            }
            
            if (strlen($newPassword) < 6) {
                return [
                    'success' => false,
                    'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự'
                ];
            }
            
            // Lấy mật khẩu hiện tại
            $stmt = $this->pdo->prepare("SELECT MatKhau FROM NguoiDung WHERE MaNguoiDung = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Người dùng không tồn tại'
                ];
            }
            
            // Kiểm tra mật khẩu cũ
            if (!verifyPassword($oldPassword, $user['MatKhau'])) {
                return [
                    'success' => false,
                    'message' => 'Mật khẩu cũ không đúng'
                ];
            }
            
            // Cập nhật mật khẩu mới
            $hashedPassword = hashPassword($newPassword);
            $stmt = $this->pdo->prepare("UPDATE NguoiDung SET MatKhau = ? WHERE MaNguoiDung = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            logActivity($userId, 'CHANGE_PASSWORD', 'Đổi mật khẩu thành công');
            
            return [
                'success' => true,
                'message' => 'Đổi mật khẩu thành công'
            ];
            
        } catch (PDOException $e) {
            logActivity($userId, 'CHANGE_PASSWORD_ERROR', $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ];
        }
    }
    
    // Lấy thông tin user
    public function getUserInfo($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    n.MaNguoiDung,
                    n.TenDangNhap,
                    n.HoTen,
                    n.Email,
                    n.ViTri,
                    n.KhoaPhong,
                    n.NamBatDauLamViec,
                    n.SoNgayPhepNam,
                    n.SoNgayPhepDaDung,
                    v.TenVaiTro,
                    (n.SoNgayPhepNam - n.SoNgayPhepDaDung) as SoNgayPhepConLai
                FROM NguoiDung n
                INNER JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
                WHERE n.MaNguoiDung = ?
            ");
            
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return null;
            }
            
            return $user;
            
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // Kiểm tra quyền truy cập
    public function checkPermission($userId, $requiredRole) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT v.TenVaiTro
                FROM NguoiDung n
                INNER JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
                WHERE n.MaNguoiDung = ?
            ");
            
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return false;
            }
            
            if (is_array($requiredRole)) {
                return in_array($user['TenVaiTro'], $requiredRole);
            }
            
            return $user['TenVaiTro'] === $requiredRole;
            
        } catch (PDOException $e) {
            return false;
        }
    }
}

// Xử lý request POST từ form đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();
    
    // Đăng nhập
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $result = $authController->login($username, $password);
        
        if ($result['success']) {
            header('Location: ' . $result['redirect']);
            exit;
        } else {
            setFlashMessage('error', $result['message']);
            header('Location: /appnghiphep/views/login.php');
            exit;
        }
    }
    
    // Đăng xuất
    if (isset($_POST['action']) && $_POST['action'] === 'logout') {
        $result = $authController->logout();
        header('Location: ' . $result['redirect']);
        exit;
    }
    
    // Đổi mật khẩu
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        requireLogin();
        
        $userId = $_SESSION['user_id'];
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $result = $authController->changePassword($userId, $oldPassword, $newPassword, $confirmPassword);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
        } else {
            setFlashMessage('error', $result['message']);
        }
        
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

// Xử lý GET request (đăng xuất qua URL)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    $authController = new AuthController();
    $result = $authController->logout();
    header('Location: ' . $result['redirect']);
    exit;
}
?>