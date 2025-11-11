<?php
// test_mail_debug.php - Test email với debug chi tiết
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Lấy cấu hình email
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT * FROM CauHinhEmail LIMIT 1");
$config = $stmt->fetch();

if (!$config) {
    die('<div style="color: red; padding: 20px; border: 2px solid red;">❌ Chưa có cấu hình email trong database!</div>');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .debug-output { background: #000; color: #0f0; padding: 20px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 500px; overflow-y: auto; }
        .config-box { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🔍 Test Email với Debug Mode</h1>
        
        <!-- Hiển thị cấu hình -->
        <div class="config-box">
            <h3>📋 Cấu hình hiện tại:</h3>
            <table class="table table-bordered">
                <tr>
                    <td><strong>SMTP Host:</strong></td>
                    <td><?= htmlspecialchars($config['SmtpHost']) ?></td>
                </tr>
                <tr>
                    <td><strong>SMTP Port:</strong></td>
                    <td><?= htmlspecialchars($config['SmtpPort']) ?></td>
                </tr>
                <tr>
                    <td><strong>Username:</strong></td>
                    <td><?= htmlspecialchars($config['SmtpUsername']) ?></td>
                </tr>
                <tr>
                    <td><strong>Password:</strong></td>
                    <td><?= str_repeat('*', strlen($config['SmtpPassword'])) ?> (<?= strlen($config['SmtpPassword']) ?> ký tự)</td>
                </tr>
                <tr>
                    <td><strong>Email gửi:</strong></td>
                    <td><?= htmlspecialchars($config['EmailNguoiGui']) ?></td>
                </tr>
            </table>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $emailTo = $_POST['email_to'] ?? '';
            
            echo '<div class="config-box">';
            echo '<h3>🚀 Đang gửi email...</h3>';
            echo '<div class="debug-output">';
            
            $mail = new PHPMailer(true);
            
            try {
                // BẬT DEBUG LEVEL 3 (Chi tiết nhất)
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = 'html';
                
                // Cấu hình SMTP
                $mail->isSMTP();
                $mail->Host = $config['SmtpHost'];
                $mail->SMTPAuth = true;
                $mail->Username = $config['SmtpUsername'];
                $mail->Password = $config['SmtpPassword'];
                
                // Tự động phát hiện loại SMTP
                $host = strtolower($config['SmtpHost']);
                
                echo "<br><strong style='color: yellow;'>🔍 Phát hiện SMTP Host: {$config['SmtpHost']}</strong><br>";
                
                if (strpos($host, 'gmail') !== false) {
                    echo "<strong style='color: yellow;'>📧 Đang sử dụng cấu hình GMAIL</strong><br>";
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                } elseif (strpos($host, 'office365') !== false || strpos($host, 'outlook') !== false) {
                    echo "<strong style='color: yellow;'>📧 Đang sử dụng cấu hình OFFICE 365</strong><br>";
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                } else {
                    echo "<strong style='color: yellow;'>📧 Đang sử dụng cấu hình CUSTOM SMTP (.edu hoặc server riêng)</strong><br>";
                    $mail->Port = $config['SmtpPort'];
                    
                    if ($config['SmtpPort'] == 587 || $config['SmtpPort'] == 25) {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        echo "<strong style='color: yellow;'>🔒 Encryption: STARTTLS (Port {$config['SmtpPort']})</strong><br>";
                    } elseif ($config['SmtpPort'] == 465) {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        echo "<strong style='color: yellow;'>🔒 Encryption: SSL/TLS (Port 465)</strong><br>";
                    } else {
                        $mail->SMTPSecure = false;
                        $mail->SMTPAutoTLS = false;
                        echo "<strong style='color: yellow;'>⚠️ No Encryption (Port {$config['SmtpPort']})</strong><br>";
                    }
                    
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];
                    echo "<strong style='color: yellow;'>🔓 SSL Verification: DISABLED (cho self-signed cert)</strong><br><br>";
                }
                
                $mail->Timeout = 30;
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';
                
                $mail->setFrom($config['EmailNguoiGui'], $config['TenNguoiGui']);
                $mail->addAddress($emailTo);
                
                $mail->isHTML(true);
                $mail->Subject = 'Test Email từ APPNGHIPHEP - ' . date('d/m/Y H:i:s');
                $mail->Body = '<h2>✅ Test Email thành công!</h2>
                               <p>Email này được gửi từ hệ thống APPNGHIPHEP lúc ' . date('d/m/Y H:i:s') . '</p>
                               <p>Tiếng Việt: áàảãạăắằẳẵặâấầẩẫậéèẻẽẹêếềểễệ</p>';
                
                $mail->send();
                
                echo '</div>';
                echo '<div class="alert alert-success mt-3">';
                echo '<h4>✅ GỬI EMAIL THÀNH CÔNG!</h4>';
                echo '<p>Email đã được gửi tới: <strong>' . htmlspecialchars($emailTo) . '</strong></p>';
                echo '<p>Hãy kiểm tra hộp thư của bạn (có thể trong Spam)</p>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '</div>';
                echo '<div class="alert alert-danger mt-3">';
                echo '<h4>❌ GỬI EMAIL THẤT BẠI!</h4>';
                echo '<p><strong>Lỗi:</strong> ' . $mail->ErrorInfo . '</p>';
                echo '<hr>';
                echo '<h5>💡 Hướng dẫn khắc phục:</h5>';
                echo '<ul>';
                
                $errorMsg = strtolower($mail->ErrorInfo);
                
                if (strpos($errorMsg, 'could not authenticate') !== false) {
                    echo '<li><strong>Username/Password SAI</strong> - Kiểm tra lại SmtpUsername và SmtpPassword</li>';
                    echo '<li>Nếu dùng Gmail: Cần bật "Less secure app access" hoặc dùng App Password</li>';
                    echo '<li>Nếu dùng email .edu: Liên hệ IT để lấy thông tin SMTP chính xác</li>';
                } elseif (strpos($errorMsg, 'connection refused') !== false || strpos($errorMsg, 'could not connect') !== false) {
                    echo '<li><strong>Không kết nối được SMTP server</strong></li>';
                    echo '<li>Kiểm tra SmtpHost có đúng không</li>';
                    echo '<li>Kiểm tra SmtpPort (thường là 587, 465 hoặc 25)</li>';
                    echo '<li>Firewall có thể đang chặn - Tắt tạm để test</li>';
                } elseif (strpos($errorMsg, 'certificate') !== false || strpos($errorMsg, 'ssl') !== false) {
                    echo '<li><strong>Lỗi SSL Certificate</strong></li>';
                    echo '<li>Code đã tắt verify SSL, nhưng server có thể yêu cầu</li>';
                    echo '<li>Thử đổi Port hoặc SMTPSecure</li>';
                }
                
                echo '</ul>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        ?>

        <!-- Form gửi test -->
        <div class="config-box">
            <h3>📧 Gửi email test:</h3>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label"><strong>Email người nhận:</strong></label>
                    <input type="email" class="form-control" name="email_to" required placeholder="your-email@example.com">
                    <small class="text-muted">Nhập email của bạn để nhận email test</small>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> Gửi Email Test với Debug
                </button>
                <a href="test_email.php" class="btn btn-secondary btn-lg">Quay lại</a>
            </form>
        </div>

        <!-- Hướng dẫn cấu hình theo loại email -->
        <div class="config-box">
            <h3>🔧 CẤU HÌNH NHANH THEO LOẠI EMAIL:</h3>
            
            <div class="accordion" id="quickConfig">
                <!-- Gmail -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#configGmail">
                            📮 Gmail (Khuyên dùng cho test)
                        </button>
                    </h2>
                    <div id="configGmail" class="accordion-collapse collapse show">
                        <div class="accordion-body">
                            <p><strong>Cách 1: Dùng mật khẩu thông thường (Dễ nhất)</strong></p>
                            <ol>
                                <li>Truy cập: <a href="https://myaccount.google.com/lesssecureapps" target="_blank">https://myaccount.google.com/lesssecureapps</a></li>
                                <li>Bật "Allow less secure apps" = ON</li>
                                <li>Dùng mật khẩu Gmail bình thường</li>
                            </ol>
                            
                            <pre class="bg-light p-3"><code>UPDATE CauHinhEmail SET
    SmtpHost = 'smtp.gmail.com',
    SmtpPort = 587,
    SmtpUsername = 'your-email@gmail.com',
    SmtpPassword = 'your-gmail-password',
    EmailNguoiGui = 'your-email@gmail.com',
    TenNguoiGui = 'Hệ thống nghỉ phép';</code></pre>

                            <hr>
                            <p><strong>Cách 2: Dùng App Password (An toàn hơn)</strong></p>
                            <ol>
                                <li>Bật xác thực 2 bước cho Gmail</li>
                                <li>Vào: <a href="https://myaccount.google.com/apppasswords" target="_blank">https://myaccount.google.com/apppasswords</a></li>
                                <li>Tạo App Password → Copy mã 16 ký tự</li>
                                <li>Dùng mã 16 ký tự làm SmtpPassword (không dấu cách)</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Email .edu -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#configEdu">
                            🎓 Email trường (.edu)
                        </button>
                    </h2>
                    <div id="configEdu" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <p><strong>⚠️ Cần liên hệ phòng IT của trường để lấy:</strong></p>
                            <ul>
                                <li>SMTP Host (VD: smtp.yourschool.edu.vn)</li>
                                <li>SMTP Port (thường là 587, 25 hoặc 465)</li>
                                <li>Username (email trường của bạn)</li>
                                <li>Password (mật khẩu email trường)</li>
                            </ul>
                            
                            <pre class="bg-light p-3"><code>UPDATE CauHinhEmail SET
    SmtpHost = 'smtp.yourschool.edu.vn',  -- HỎI IT
    SmtpPort = 587,                       -- HỎI IT
    SmtpUsername = 'yourusername@yourschool.edu',
    SmtpPassword = 'your-school-email-password',
    EmailNguoiGui = 'noreply@yourschool.edu',
    TenNguoiGui = 'Hệ thống nghỉ phép';</code></pre>
                        </div>
                    </div>
                </div>

                <!-- Office 365 -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#configO365">
                            📨 Office 365 / Outlook
                        </button>
                    </h2>
                    <div id="configO365" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <pre class="bg-light p-3"><code>UPDATE CauHinhEmail SET
    SmtpHost = 'smtp.office365.com',
    SmtpPort = 587,
    SmtpUsername = 'your-email@yourschool.edu',
    SmtpPassword = 'your-password',
    EmailNguoiGui = 'your-email@yourschool.edu',
    TenNguoiGui = 'Hệ thống nghỉ phép';</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>