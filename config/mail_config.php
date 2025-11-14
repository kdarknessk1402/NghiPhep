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

// Hàm gửi email thông báo đơn nghỉ phép (CẢI TIẾN)
function sendLeaveRequestNotification($maDon, $emailNhan, $action = 'create') {
    $pdo = getDBConnection();
    
    // Lấy thông tin đơn nghỉ phép
    $stmt = $pdo->prepare("
        SELECT d.*, n.HoTen, n.Email, n.KhoaPhong, n.ViTri
        FROM DonNghiPhep d
        JOIN NguoiDung n ON d.MaNguoiDung = n.MaNguoiDung
        WHERE d.MaDon = ?
    ");
    $stmt->execute([$maDon]);
    $don = $stmt->fetch();
    
    if (!$don) return false;
    
    // Lấy cấu hình email
    $config = getMailConfig();
    if (!$config) return ['success' => false, 'message' => 'Chưa cấu hình email'];
    
    $mail = new PHPMailer(true);
    
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host = $config['SmtpHost'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['SmtpUsername'];
        $mail->Password = $config['SmtpPassword'];
        
        // Tự động phát hiện loại SMTP
        $host = strtolower($config['SmtpHost']);
        if (strpos($host, 'gmail') !== false) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
        } elseif (strpos($host, 'office365') !== false || strpos($host, 'outlook') !== false) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
        } else {
            $mail->Port = $config['SmtpPort'];
            if ($config['SmtpPort'] == 587 || $config['SmtpPort'] == 25) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($config['SmtpPort'] == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }
        
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // QUAN TRỌNG: Khi tạo đơn mới, dùng email của USER làm người gửi
        if ($action === 'create') {
            // Email GỬI là của USER (người tạo đơn)
            $mail->setFrom($don['Email'], $don['HoTen']);
            // Email REPLY-TO cũng là của USER
            $mail->addReplyTo($don['Email'], $don['HoTen']);
        } else {
            // Khi duyệt/từ chối, dùng email hệ thống
            $mail->setFrom($config['EmailNguoiGui'], $config['TenNguoiGui']);
        }
        
        // Người nhận
        if (is_array($emailNhan)) {
            foreach ($emailNhan as $email) {
                $mail->addAddress($email);
            }
        } else {
            $mail->addAddress($emailNhan);
        }
        
        // Tạo nội dung email theo action
        switch ($action) {
            case 'create':
                $subject = "[ĐƠN NGHỈ PHÉP] " . $don['HoTen'] . " - " . $maDon;
                $body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 10px;'>
                        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px 10px 0 0;'>
                            <h2 style='margin: 0;'>📋 Đơn Nghỉ Phép Mới</h2>
                        </div>
                        <div style='padding: 20px;'>
                            <p>Kính gửi Ban Quản lý,</p>
                            <p>Có đơn nghỉ phép mới cần duyệt:</p>
                            
                            <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                                <tr style='background-color: #f8f9fa;'>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Mã đơn:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>{$don['MaDon']}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Nhân viên:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>{$don['HoTen']}</td>
                                </tr>
                                <tr style='background-color: #f8f9fa;'>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Email:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>{$don['Email']}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Vị trí:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>{$don['ViTri']}</td>
                                </tr>
                                <tr style='background-color: #f8f9fa;'>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Khoa/Phòng:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>{$don['KhoaPhong']}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Loại phép:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>{$don['LoaiPhep']}</td>
                                </tr>
                                <tr style='background-color: #f8f9fa;'>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Từ ngày:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>" . date('d/m/Y', strtotime($don['NgayBatDauNghi'])) . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Đến ngày:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>" . date('d/m/Y', strtotime($don['NgayKetThucNghi'])) . "</td>
                                </tr>
                                <tr style='background-color: #f8f9fa;'>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Số ngày nghỉ:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong style='color: #667eea; font-size: 18px;'>{$don['SoNgayNghi']}</strong> ngày</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Lý do:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>" . nl2br(htmlspecialchars($don['LyDo'])) . "</td>
                                </tr>
                                <tr style='background-color: #fff3cd;'>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Trạng thái:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><span style='background-color: #ffc107; color: white; padding: 5px 10px; border-radius: 5px;'>⏳ Chờ duyệt</span></td>
                                </tr>
                            </table>
                            
                            <div style='background-color: #e7f3ff; padding: 15px; border-left: 4px solid #667eea; margin: 20px 0;'>
                                <p style='margin: 0;'><strong>📌 Lưu ý:</strong> Vui lòng đăng nhập vào hệ thống để xem chi tiết và phê duyệt đơn này.</p>
                            </div>
                            
                            <div style='text-align: center; margin-top: 20px;'>
                                <a href='http://localhost/appnghiphep/' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                                    🔗 Đăng nhập hệ thống
                                </a>
                            </div>
                        </div>
                        <div style='background-color: #f8f9fa; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; color: #6c757d;'>
                            <p style='margin: 0;'>Email này được gửi tự động từ Hệ thống Quản lý Nghỉ Phép</p>
                            <p style='margin: 5px 0 0 0;'>Vui lòng không trả lời email này. Mọi thắc mắc vui lòng liên hệ trực tiếp với <strong>{$don['HoTen']}</strong> qua email: {$don['Email']}</p>
                        </div>
                    </div>
                ";
                break;
                
            case 'approve':
                $subject = "[PHÊ DUYỆT] Đơn nghỉ phép " . $maDon . " đã được duyệt";
                $body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 10px;'>
                        <div style='background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; border-radius: 10px 10px 0 0;'>
                            <h2 style='margin: 0;'>✅ Đơn Nghỉ Phép Đã Được Duyệt</h2>
                        </div>
                        <div style='padding: 20px;'>
                            <p>Xin chào <strong>{$don['HoTen']}</strong>,</p>
                            <p>Đơn nghỉ phép của bạn đã được <strong style='color: #28a745;'>PHÊ DUYỆT</strong>.</p>
                            
                            <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                                <tr style='background-color: #f8f9fa;'>
                                    <td style='padding: 10px; border: 1px solid #ddd; width: 40%;'><strong>Mã đơn:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>{$don['MaDon']}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Từ ngày:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>" . date('d/m/Y', strtotime($don['NgayBatDauNghi'])) . "</td>
                                </tr>
                                <tr style='background-color: #f8f9fa;'>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Đến ngày:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>" . date('d/m/Y', strtotime($don['NgayKetThucNghi'])) . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Số ngày nghỉ:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong style='color: #28a745; font-size: 18px;'>{$don['SoNgayNghi']}</strong> ngày</td>
                                </tr>
                                " . (!empty($don['GhiChuAdmin']) ? "
                                <tr style='background-color: #d1ecf1;'>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Ghi chú:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>" . nl2br(htmlspecialchars($don['GhiChuAdmin'])) . "</td>
                                </tr>
                                " : "") . "
                            </table>
                            
                            <div style='background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>
                                <p style='margin: 0;'><strong>🎉 Chúc mừng!</strong> Chúc bạn có kỳ nghỉ phép vui vẻ và bổ ích!</p>
                            </div>
                        </div>
                        <div style='background-color: #f8f9fa; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; color: #6c757d;'>
                            <p style='margin: 0;'>Email này được gửi tự động từ Hệ thống Quản lý Nghỉ Phép</p>
                        </div>
                    </div>
                ";
                break;
                
            case 'reject':
                $subject = "[TỪ CHỐI] Đơn nghỉ phép " . $maDon . " bị từ chối";
                $body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 10px;'>
                        <div style='background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; border-radius: 10px 10px 0 0;'>
                            <h2 style='margin: 0;'>❌ Đơn Nghỉ Phép Bị Từ Chối</h2>
                        </div>
                        <div style='padding: 20px;'>
                            <p>Xin chào <strong>{$don['HoTen']}</strong>,</p>
                            <p>Rất tiếc, đơn nghỉ phép của bạn đã bị <strong style='color: #dc3545;'>TỪ CHỐI</strong>.</p>
                            
                            <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                                <tr style='background-color: #f8f9fa;'>
                                    <td style='padding: 10px; border: 1px solid #ddd; width: 40%;'><strong>Mã đơn:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>{$don['MaDon']}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Từ ngày:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>" . date('d/m/Y', strtotime($don['NgayBatDauNghi'])) . "</td>
                                </tr>
                                <tr style='background-color: #f8f9fa;'>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Đến ngày:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'>" . date('d/m/Y', strtotime($don['NgayKetThucNghi'])) . "</td>
                                </tr>
                                <tr style='background-color: #f8d7da;'>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Lý do từ chối:</strong></td>
                                    <td style='padding: 10px; border: 1px solid #ddd;'><strong style='color: #dc3545;'>" . nl2br(htmlspecialchars($don['GhiChuAdmin'] ?? 'Không có lý do cụ thể')) . "</strong></td>
                                </tr>
                            </table>
                            
                            <div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>
                                <p style='margin: 0;'><strong>💡 Hướng dẫn:</strong> Vui lòng liên hệ trực tiếp với quản lý để biết thêm chi tiết và có thể nộp đơn mới nếu cần.</p>
                            </div>
                        </div>
                        <div style='background-color: #f8f9fa; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; color: #6c757d;'>
                            <p style='margin: 0;'>Email này được gửi tự động từ Hệ thống Quản lý Nghỉ Phép</p>
                        </div>
                    </div>
                ";
                break;
        }
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        
        // Lưu lịch sử gửi email
        $emailList = is_array($emailNhan) ? implode(', ', $emailNhan) : $emailNhan;
        $stmt = $pdo->prepare("
            INSERT INTO LichSuEmail (MaDon, EmailNhan, TieuDeEmail, TrangThai, ThongBaoLoi)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $maDon,
            $emailList,
            $subject,
            'Thanh_cong',
            null
        ]);
        
        return ['success' => true, 'message' => 'Email đã được gửi'];
        
    } catch (Exception $e) {
        // Lưu lỗi vào database
        $emailList = is_array($emailNhan) ? implode(', ', $emailNhan) : $emailNhan;
        $stmt = $pdo->prepare("
            INSERT INTO LichSuEmail (MaDon, EmailNhan, TieuDeEmail, TrangThai, ThongBaoLoi)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $maDon,
            $emailList,
            $subject ?? 'Email Error',
            'That_bai',
            $mail->ErrorInfo
        ]);
        
        return ['success' => false, 'message' => "Lỗi gửi email: {$mail->ErrorInfo}"];
    }
}
?>