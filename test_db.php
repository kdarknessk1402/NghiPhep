<?php
require_once 'config/database.php';
$pdo = getDBConnection();
echo "✅ Kết nối database thành công!<br>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM NguoiDung");
$result = $stmt->fetch();
echo "📊 Số người dùng: " . $result['total'];
?>