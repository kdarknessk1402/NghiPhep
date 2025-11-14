<?php
// controllers/NghiBuController.php - Xử lý nghỉ bù/làm bù

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/mail_config.php';

class NghiBuController {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Tạo đơn nghỉ bù mới
     */
    public function createNghiBu($data, $userId) {
        try {
            $loaiNghiBu = $data['loai_nghi_bu'];
            $ngayNghiBu = $data['ngay_nghi_bu'];
            $ngayLamBu = $data['ngay_lam_bu'] ?? null;
            $lyDo = $data['ly_do'] ?? '';
            
            // Validate ngày nghỉ bù (phải là T2-T6)
            $dayOfWeekNghi = date('N', strtotime($ngayNghiBu));
            if ($dayOfWeekNghi >= 6) {
                return [
                    'success' => false,
                    'message' => 'Ngày nghỉ bù phải là thứ 2 đến thứ 6'
                ];
            }
            
            // Validate ngày làm bù (phải là T7/CN)
            if ($ngayLamBu) {
                $dayOfWeekLam = date('N', strtotime($ngayLamBu));
                if ($dayOfWeekLam < 6) {
                    return [
                        'success' => false,
                        'message' => 'Ngày làm bù phải là thứ 7 hoặc Chủ nhật'
                    ];
                }
            }
            
            // Kiểm tra logic nghỉ bù
            if ($loaiNghiBu === 'Lam_truoc_nghi_sau') {
                // Làm trước nghỉ sau: Ngày làm bù phải TRƯỚC ngày nghỉ bù
                if ($ngayLamBu && strtotime($ngayLamBu) >= strtotime($ngayNghiBu)) {
                    return [
                        'success' => false,
                        'message' => 'Với "Làm trước - Nghỉ sau": Ngày làm bù phải TRƯỚC ngày nghỉ bù'
                    ];
                }
            } else {
                // Nghỉ trước làm sau: Ngày nghỉ bù phải TRƯỚC ngày làm bù
                if (!$ngayLamBu) {
                    return [
                        'success' => false,
                        'message' => 'Với "Nghỉ trước - Làm sau": Bạn phải chọn ngày làm bù'
                    ];
                }
                if (strtotime($ngayNghiBu) >= strtotime($ngayLamBu)) {
                    return [
                        'success' => false,
                        'message' => 'Với "Nghỉ trước - Làm sau": Ngày nghỉ bù phải TRƯỚC ngày làm bù'
                    ];
                }
            }
            
            // Tính số ngày
            $soNgayNghi = 1.0; // Mặc định 1 ngày
            $soNgayLam = $ngayLamBu ? 1.0 : 0.0;
            
            // Insert vào database
            $stmt = $this->pdo->prepare("
                INSERT INTO NghiBu 
                (MaNguoiDung, LoaiNghiBu, NgayNghiBu, SoNgayNghi, 
                 NgayLamBu, SoNgayLam, LyDo, TrangThai, TrangThaiDuyet)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Cho_lam_bu', 'WAITING')
            ");
            
            $stmt->execute([
                $userId,
                $loaiNghiBu,
                $ngayNghiBu,
                $soNgayNghi,
                $ngayLamBu,
                $soNgayLam,
                $lyDo
            ]);
            
            $maNghiBu = $this->pdo->lastInsertId();
            
            // Gửi email thông báo cho Manager/Admin
            $this->sendNghiBuNotification($maNghiBu, 'create');
            
            logActivity($userId, 'CREATE_NGHI_BU', "Đăng ký nghỉ bù: $maNghiBu - $loaiNghiBu");
            
            return [
                'success' => true,
                'message' => 'Đăng ký nghỉ bù thành công!',
                'ma_nghi_bu' => $maNghiBu
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Duyệt đơn nghỉ bù
     */
    public function approveNghiBu($maNghiBu, $nguoiDuyet, $ghiChu = '') {
        try {
            // Lấy thông tin đơn nghỉ bù
            $stmt = $this->pdo->prepare("
                SELECT nb.*, n.Email, n.HoTen
                FROM NghiBu nb
                JOIN NguoiDung n ON nb.MaNguoiDung = n.MaNguoiDung
                WHERE nb.MaNghiBu = ?
            ");
            $stmt->execute([$maNghiBu]);
            $nghiBu = $stmt->fetch();
            
            if (!$nghiBu) {
                return ['success' => false, 'message' => 'Không tìm thấy đơn nghỉ bù'];
            }
            
            // Cập nhật trạng thái
            $stmt = $this->pdo->prepare("
                UPDATE NghiBu 
                SET TrangThaiDuyet = 'ACCEPT',
                    NguoiDuyet = ?,
                    GhiChu = ?
                WHERE MaNghiBu = ?
            ");
            $stmt->execute([$nguoiDuyet, $ghiChu, $maNghiBu]);
            
            // Nếu là "Làm trước - Nghỉ sau" và đã làm bù → Tạo đơn nghỉ phép tự động
            if ($nghiBu['LoaiNghiBu'] === 'Lam_truoc_nghi_sau' && $nghiBu['NgayLamBu']) {
                $this->createAutoLeaveFromNghiBu($nghiBu);
            }
            
            // Gửi email thông báo
            $this->sendNghiBuNotification($maNghiBu, 'approve');
            
            logActivity($nguoiDuyet, 'APPROVE_NGHI_BU', "Duyệt nghỉ bù: $maNghiBu");
            
            return [
                'success' => true,
                'message' => 'Đã duyệt đơn nghỉ bù'
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
    
    /**
     * Từ chối đơn nghỉ bù
     */
    public function rejectNghiBu($maNghiBu, $nguoiDuyet, $ghiChu) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE NghiBu 
                SET TrangThaiDuyet = 'DENY',
                    NguoiDuyet = ?,
                    GhiChu = ?,
                    TrangThai = 'Qua_han'
                WHERE MaNghiBu = ?
            ");
            $stmt->execute([$nguoiDuyet, $ghiChu, $maNghiBu]);
            
            // Gửi email thông báo
            $this->sendNghiBuNotification($maNghiBu, 'reject');
            
            logActivity($nguoiDuyet, 'REJECT_NGHI_BU', "Từ chối nghỉ bù: $maNghiBu");
            
            return [
                'success' => true,
                'message' => 'Đã từ chối đơn nghỉ bù'
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
    
    /**
     * Tạo đơn nghỉ phép tự động từ nghỉ bù (Làm trước - Nghỉ sau)
     */
    private function createAutoLeaveFromNghiBu($nghiBu) {
        try {
            // Tạo mã đơn
            $maDon = generateLeaveCode('DN');
            
            // Insert đơn nghỉ phép (loại nghỉ bù)
            $stmt = $this->pdo->prepare("
                INSERT INTO DonNghiPhep 
                (MaDon, MaNguoiDung, NguoiTao, LoaiPhep, LoaiDon, MaNghiBu,
                 NgayBatDauNghi, NgayKetThucNghi, SoNgayNghi, 
                 LyDo, TrangThai)
                VALUES (?, ?, ?, 'Nghỉ bù', 'Nghi_bu', ?,
                        ?, ?, ?, 
                        'Nghỉ bù do đã làm việc vào ngày ' || ?, 'ACCEPT')
            ");
            
            $stmt->execute([
                $maDon,
                $nghiBu['MaNguoiDung'],
                $nghiBu['MaNguoiDung'],
                $nghiBu['MaNghiBu'],
                $nghiBu['NgayNghiBu'],
                $nghiBu['NgayNghiBu'],
                $nghiBu['SoNgayNghi'],
                date('d/m/Y', strtotime($nghiBu['NgayLamBu']))
            ]);
            
            // Cập nhật trạng thái nghỉ bù
            $stmt = $this->pdo->prepare("
                UPDATE NghiBu 
                SET TrangThai = 'Da_lam_bu'
                WHERE MaNghiBu = ?
            ");
            $stmt->execute([$nghiBu['MaNghiBu']]);
            
            logActivity($nghiBu['MaNguoiDung'], 'AUTO_CREATE_LEAVE_FROM_NGHIBU', 
                       "Tạo tự động đơn nghỉ phép từ nghỉ bù: $maDon");
            
        } catch (PDOException $e) {
            error_log("Lỗi tạo đơn nghỉ phép tự động: " . $e->getMessage());
        }
    }
    
    /**
     * Xác nhận đã làm bù (cho trường hợp Nghỉ trước - Làm sau)
     */
    public function confirmLamBu($maNghiBu, $nguoiDuyet) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE NghiBu 
                SET TrangThai = 'Da_lam_bu',
                    NguoiDuyet = ?
                WHERE MaNghiBu = ? AND TrangThai = 'Cho_lam_bu'
            ");
            $stmt->execute([$nguoiDuyet, $maNghiBu]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($nguoiDuyet, 'CONFIRM_LAM_BU', "Xác nhận đã làm bù: $maNghiBu");
                return ['success' => true, 'message' => 'Đã xác nhận làm bù'];
            }
            
            return ['success' => false, 'message' => 'Không thể xác nhận'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
    
    /**
     * Gửi email thông báo nghỉ bù
     */
    private function sendNghiBuNotification($maNghiBu, $action) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT nb.*, n.HoTen, n.Email, n.KhoaPhong
                FROM NghiBu nb
                JOIN NguoiDung n ON nb.MaNguoiDung = n.MaNguoiDung
                WHERE nb.MaNghiBu = ?
            ");
            $stmt->execute([$maNghiBu]);
            $nghiBu = $stmt->fetch();
            
            if (!$nghiBu) return;
            
            // Lấy email Manager/Admin
            $emailList = [];
            
            // Manager cùng khoa/phòng
            $stmt = $this->pdo->prepare("
                SELECT Email FROM NguoiDung n
                JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
                WHERE v.TenVaiTro = 'MANAGER' AND n.KhoaPhong = ?
            ");
            $stmt->execute([$nghiBu['KhoaPhong']]);
            $managerEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Admin
            $adminEmails = $this->pdo->query("
                SELECT Email FROM NguoiDung n
                JOIN VaiTro v ON n.MaVaiTro = v.MaVaiTro
                WHERE v.TenVaiTro = 'ADMIN'
            ")->fetchAll(PDO::FETCH_COLUMN);
            
            $emailList = array_unique(array_merge($managerEmails, $adminEmails));
            
            if (empty($emailList)) return;
            
            // Tạo nội dung email
            $loaiNghiBuText = $nghiBu['LoaiNghiBu'] === 'Lam_truoc_nghi_sau' 
                ? 'Làm trước - Nghỉ sau' 
                : 'Nghỉ trước - Làm sau';
            
            switch ($action) {
                case 'create':
                    $subject = "[NGHỈ BÙ] {$nghiBu['HoTen']} - $loaiNghiBuText";
                    $body = $this->buildEmailCreateNghiBu($nghiBu, $loaiNghiBuText);
                    break;
                case 'approve':
                    $subject = "[PHÊ DUYỆT] Đơn nghỉ bù đã được duyệt";
                    $body = $this->buildEmailApproveNghiBu($nghiBu);
                    break;
                case 'reject':
                    $subject = "[TỪ CHỐI] Đơn nghỉ bù bị từ chối";
                    $body = $this->buildEmailRejectNghiBu($nghiBu);
                    break;
            }
            
            // Gửi email
            $targetEmail = ($action === 'create') ? $emailList : $nghiBu['Email'];
            sendEmail($targetEmail, $subject, $body, true);
            
        } catch (Exception $e) {
            error_log("Lỗi gửi email nghỉ bù: " . $e->getMessage());
        }
    }
    
    private function buildEmailCreateNghiBu($nghiBu, $loaiNghiBuText) {
        $ngayNghiBu = formatDate($nghiBu['NgayNghiBu']);
        $ngayLamBu = $nghiBu['NgayLamBu'] ? formatDate($nghiBu['NgayLamBu']) : 'Chưa xác định';
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px;'>
                <h2>🔄 Đơn Nghỉ Bù Mới</h2>
            </div>
            <div style='padding: 20px; border: 1px solid #ddd;'>
                <h3>{$loaiNghiBuText}</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr style='background-color: #f8f9fa;'>
                        <td style='padding: 10px; border: 1px solid #ddd;'><strong>Nhân viên:</strong></td>
                        <td style='padding: 10px; border: 1px solid #ddd;'>{$nghiBu['HoTen']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border: 1px solid #ddd;'><strong>Khoa/Phòng:</strong></td>
                        <td style='padding: 10px; border: 1px solid #ddd;'>{$nghiBu['KhoaPhong']}</td>
                    </tr>
                    <tr style='background-color: #f8f9fa;'>
                        <td style='padding: 10px; border: 1px solid #ddd;'><strong>Ngày nghỉ bù:</strong></td>
                        <td style='padding: 10px; border: 1px solid #ddd;'>{$ngayNghiBu}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border: 1px solid #ddd;'><strong>Ngày làm bù:</strong></td>
                        <td style='padding: 10px; border: 1px solid #ddd;'>{$ngayLamBu}</td>
                    </tr>
                    <tr style='background-color: #f8f9fa;'>
                        <td style='padding: 10px; border: 1px solid #ddd;'><strong>Lý do:</strong></td>
                        <td style='padding: 10px; border: 1px solid #ddd;'>{$nghiBu['LyDo']}</td>
                    </tr>
                </table>
                <p style='margin-top: 20px;'>Vui lòng đăng nhập hệ thống để duyệt đơn này.</p>
            </div>
        </div>
        ";
    }
    
    private function buildEmailApproveNghiBu($nghiBu) {
        return "<div style='font-family: Arial, sans-serif;'>
            <h2 style='color: #28a745;'>✅ Đơn nghỉ bù đã được duyệt</h2>
            <p>Xin chào <strong>{$nghiBu['HoTen']}</strong>,</p>
            <p>Đơn nghỉ bù của bạn đã được phê duyệt.</p>
        </div>";
    }
    
    private function buildEmailRejectNghiBu($nghiBu) {
        return "<div style='font-family: Arial, sans-serif;'>
            <h2 style='color: #dc3545;'>❌ Đơn nghỉ bù bị từ chối</h2>
            <p>Xin chào <strong>{$nghiBu['HoTen']}</strong>,</p>
            <p>Đơn nghỉ bù của bạn đã bị từ chối.</p>
            <p><strong>Lý do:</strong> {$nghiBu['GhiChu']}</p>
        </div>";
    }
    
    /**
     * Lấy danh sách nghỉ bù theo user
     */
    public function getNghiBuByUser($userId, $filter = 'all') {
        try {
            $whereClause = "WHERE nb.MaNguoiDung = ?";
            $params = [$userId];
            
            if ($filter !== 'all') {
                $whereClause .= " AND nb.TrangThai = ?";
                $params[] = ucfirst($filter);
            }
            
            $stmt = $this->pdo->prepare("
                SELECT nb.*, 
                       nd.HoTen as TenNguoiDuyet
                FROM NghiBu nb
                LEFT JOIN NguoiDung nd ON nb.NguoiDuyet = nd.MaNguoiDung
                $whereClause
                ORDER BY nb.NgayTao DESC
            ");
            
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Lấy danh sách nghỉ bù để quản lý duyệt (Manager/Admin)
     */
    public function getNghiBuForApproval($khoaPhong = null, $filter = 'waiting') {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($khoaPhong) {
                $whereClause .= " AND n.KhoaPhong = ?";
                $params[] = $khoaPhong;
            }
            
            if ($filter !== 'all') {
                $whereClause .= " AND nb.TrangThaiDuyet = ?";
                $params[] = strtoupper($filter);
            }
            
            $stmt = $this->pdo->prepare("
                SELECT nb.*, 
                       n.HoTen, n.Email, n.KhoaPhong, n.ViTri,
                       nd.HoTen as TenNguoiDuyet
                FROM NghiBu nb
                JOIN NguoiDung n ON nb.MaNguoiDung = n.MaNguoiDung
                LEFT JOIN NguoiDung nd ON nb.NguoiDuyet = nd.MaNguoiDung
                $whereClause
                ORDER BY 
                    CASE nb.TrangThaiDuyet 
                        WHEN 'WAITING' THEN 1 
                        WHEN 'ACCEPT' THEN 2 
                        WHEN 'DENY' THEN 3 
                    END,
                    nb.NgayTao DESC
            ");
            
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            return [];
        }
    }
}

// Xử lý POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    
    $controller = new NghiBuController();
    $action = $_POST['action'] ?? '';
    
    // Tạo đơn nghỉ bù mới
    if ($action === 'create') {
        requireRole('USER');
        
        $result = $controller->createNghiBu($_POST, $_SESSION['user_id']);
        
        if ($result['success']) {
            redirectWithMessage('../user/nghi_bu.php', 'success', $result['message']);
        } else {
            setFlashMessage('error', $result['message']);
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
    
    // Duyệt đơn
    if ($action === 'approve') {
        requireAnyRole(['ADMIN', 'MANAGER']);
        
        $maNghiBu = $_POST['ma_nghi_bu'] ?? '';
        $ghiChu = sanitizeInput($_POST['ghi_chu'] ?? '');
        
        $result = $controller->approveNghiBu($maNghiBu, $_SESSION['user_id'], $ghiChu);
        
        if ($result['success']) {
            redirectWithMessage($_SERVER['HTTP_REFERER'], 'success', $result['message']);
        } else {
            redirectWithMessage($_SERVER['HTTP_REFERER'], 'error', $result['message']);
        }
    }
    
    // Từ chối đơn
    if ($action === 'reject') {
        requireAnyRole(['ADMIN', 'MANAGER']);
        
        $maNghiBu = $_POST['ma_nghi_bu'] ?? '';
        $ghiChu = sanitizeInput($_POST['ghi_chu'] ?? '');
        
        if (empty($ghiChu)) {
            redirectWithMessage($_SERVER['HTTP_REFERER'], 'error', 'Vui lòng nhập lý do từ chối');
        }
        
        $result = $controller->rejectNghiBu($maNghiBu, $_SESSION['user_id'], $ghiChu);
        
        if ($result['success']) {
            redirectWithMessage($_SERVER['HTTP_REFERER'], 'success', $result['message']);
        } else {
            redirectWithMessage($_SERVER['HTTP_REFERER'], 'error', $result['message']);
        }
    }
    
    // Xác nhận đã làm bù
    if ($action === 'confirm_lam_bu') {
        requireAnyRole(['ADMIN', 'MANAGER']);
        
        $maNghiBu = $_POST['ma_nghi_bu'] ?? '';
        $result = $controller->confirmLamBu($maNghiBu, $_SESSION['user_id']);
        
        if ($result['success']) {
            redirectWithMessage($_SERVER['HTTP_REFERER'], 'success', $result['message']);
        } else {
            redirectWithMessage($_SERVER['HTTP_REFERER'], 'error', $result['message']);
        }
    }
}
?>