<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../vendor/autoload.php';

function getMailConfig() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM CauHinhEmail LIMIT 1");
    return $stmt->fetch();
}

function sendEmail($to, $subject, $body, $isHTML = true, $debug = false) {
    $config = getMailConfig();
    
    if (!$config) {
        return ['success' => false, 'message' => 'Chưa cấu hình email'];
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // BẬT DEBUG (Nếu cần)
        if ($debug) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = 'html';
        }
        
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host = $config['SmtpHost'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['SmtpUsername'];
        $mail->Password = $config['SmtpPassword'];
        
        // ĐẶC BIỆT: Cấu hình linh hoạt cho nhiều loại email
        // Tự động phát hiện loại SMTP
        $host = strtolower($config['SmtpHost']);
        
        if (strpos($host, 'gmail') !== false) {
            // Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
        } elseif (strpos($host, 'office365') !== false || strpos($host, 'outlook') !== false) {
            // Office 365 / Outlook
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
        } else {
            // Email .edu hoặc SMTP server tùy chỉnh
            $mail->Port = $config['SmtpPort'];
            
            // Thử STARTTLS trước
            if ($config['SmtpPort'] == 587 || $config['SmtpPort'] == 25) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($config['SmtpPort'] == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }
            
            // Tắt verify SSL (cho email .edu hoặc self-signed cert)
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }
        
        // Timeout settings
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;
        
        // Cài đặt encoding UTF-8 cho tiếng Việt
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Người gửi
        $mail->setFrom($config['EmailNguoiGui'], $config['TenNguoiGui']);
        
        // Người nhận
        if (is_array($to)) {
            foreach ($to as $email) {
                $mail->addAddress($email);
            }
        } else {
            $mail->addAddress($to);
        }
        
        // Nội dung email
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        if (!$isHTML) {
            $mail->AltBody = $body;
        }
        
        // Gửi email
        $mail->send();
        
        return ['success' => true, 'message' => 'Email đã được gửi'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Lỗi gửi email: {$mail->ErrorInfo}"];
    }
}

// Hàm gửi email thông báo đơn nghỉ phép
function sendLeaveRequestNotification($maDon, $emailNhan, $action = 'create') {
    $pdo = getDBConnection();
    
    // Lấy thông tin đơn nghỉ phép
    $stmt = $pdo->prepare("
        SELECT d.*, n.HoTen, n.Email, n.KhoaPhong 
        FROM DonNghiPhep d
        JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
        WHERE d.MaDon = ?
    ");
    $stmt->execute([$maDon]);
    $don = $stmt->fetch();
    
    if (!$don) return false;
    
    // Tạo nội dung email
    switch ($action) {
        case 'create':
            $subject = "[APPNGHIPHEP] Đơn nghỉ phép mới - " . $maDon;
            $body = "
                <h2>Thông báo đơn nghỉ phép mới</h2>
                <p><strong>Mã đơn:</strong> {$don['MaDon']}</p>
                <p><strong>Nhân viên:</strong> {$don['HoTen']}</p>
                <p><strong>Khoa/Phòng:</strong> {$don['KhoaPhong']}</p>
                <p><strong>Loại phép:</strong> {$don['LoaiPhep']}</p>
                <p><strong>Từ ngày:</strong> {$don['NgayBatDauNghi']}</p>
                <p><strong>Đến ngày:</strong> {$don['NgayKetThucNghi']}</p>
                <p><strong>Số ngày nghỉ:</strong> {$don['SoNgayNghi']}</p>
                <p><strong>Lý do:</strong> {$don['LyDo']}</p>
                <p><strong>Trạng thái:</strong> Chờ duyệt</p>
                <hr>
                <p><em>Vui lòng đăng nhập vào hệ thống để xem chi tiết và phê duyệt.</em></p>
            ";
            break;
            
        case 'approve':
            $subject = "[APPNGHIPHEP] Đơn nghỉ phép đã được duyệt - " . $maDon;
            $body = "
                <h2>Đơn nghỉ phép của bạn đã được PHÊ DUYỆT</h2>
                <p><strong>Mã đơn:</strong> {$don['MaDon']}</p>
                <p><strong>Từ ngày:</strong> {$don['NgayBatDauNghi']}</p>
                <p><strong>Đến ngày:</strong> {$don['NgayKetThucNghi']}</p>
                <p><strong>Số ngày nghỉ:</strong> {$don['SoNgayNghi']}</p>
                <p><strong>Ghi chú:</strong> {$don['GhiChuAdmin']}</p>
                <hr>
                <p><em>Chúc bạn có kỳ nghỉ vui vẻ!</em></p>
            ";
            break;
            
        case 'reject':
            $subject = "[APPNGHIPHEP] Đơn nghỉ phép bị từ chối - " . $maDon;
            $body = "
                <h2>Đơn nghỉ phép của bạn đã bị TỪ CHỐI</h2>
                <p><strong>Mã đơn:</strong> {$don['MaDon']}</p>
                <p><strong>Từ ngày:</strong> {$don['NgayBatDauNghi']}</p>
                <p><strong>Đến ngày:</strong> {$don['NgayKetThucNghi']}</p>
                <p><strong>Lý do từ chối:</strong> {$don['GhiChuAdmin']}</p>
                <hr>
                <p><em>Vui lòng liên hệ quản lý để biết thêm chi tiết.</em></p>
            ";
            break;
    }
    
    $result = sendEmail($emailNhan, $subject, $body);
    
    // Lưu lịch sử gửi email
    $stmt = $pdo->prepare("
        INSERT INTO LichSuEmail (MaDon, EmailNhan, TieuDeEmail, TrangThai, ThongBaoLoi)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $maDon,
        $emailNhan,
        $subject,
        $result['success'] ? 'Thanh_cong' : 'That_bai',
        $result['success'] ? null : $result['message']
    ]);
    
    return $result;
}
?>